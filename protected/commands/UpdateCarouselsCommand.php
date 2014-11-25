<?php
ini_set('memory_limit', '78M');
use m8rge\AlternativeMail;
use m8rge\CurlHelper;
use m8rge\CurlException;

class UpdateCarouselsCommand extends ConsoleCommand
{
    const ITEMS_LIMIT = 300;

    /** @var bool */
    public $forceImages = false;

    public function actionIndex()
    {
        /** @var Client[] $clients */
        $clients = Client::model()->onlyValid()->with('carouselsOnSite')->findAll();

        $urlToFiles = [];
        /** @var array $urlToClient */
        $urlToClient = [];
        foreach ($clients as $client) {
            if (!empty($client->carouselsOnSite) && empty($urlToFiles[$client->feedUrl])) {
                $feedFile = tempnam(Yii::app()->getRuntimePath(), 'yml');
                $urlToFiles[$client->feedUrl] = $feedFile;
            }
            $urlToClient[$client->feedUrl][] = $client;
        }

        /** @var Client[] $failedClients */
        $failedClients = [];

        CurlHelper::batchDownload(
            $urlToFiles,
            function ($url, $file, $e) use ($urlToClient, &$failedClients) {
                if ($e) {
                    unlink($file);
                    $this->captureException($e);
                    foreach ($urlToClient[$url] as $client) {
                        /** @var Client $client */
                        $client->failures++;
                        $client->saveAttributes(['failures' => $client->failures]);
                        if ($client->failures == Client::FAILURES_BOUND) {
                            $failedClients[] = $client;
                        }
                    }
                    return;
                }
                foreach ($urlToClient[$url] as $client) {
                    /** @var Client $client */
                    $client->updateFeedFile($file);

                    // iterate through client carousels
                    foreach ($client->carouselsOnSite as $carousel) {
                        $this->processCarousel($carousel);
                    }
                }
            },
            [
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 60*30, // timeout with callback execution time
            ],
            2
        );

        $emailData = [];
        foreach ($failedClients as $failedClient) {
            $emailData[$failedClient->owner->email][] = $failedClient->name;
        }
        $body = "Обновление следующих клиентов было отключено из за недоступности фида:";

        foreach ($emailData as $email => $clientNames) {
            $mail = new AlternativeMail();
            $mail->setFrom('myarusel-no-reply@66.ru')
                ->addTo($email)
                ->setSubject('Отключены клиенты')
                ->setTextBody($body . "\n\n" . implode("\n", $clientNames))
                ->setHtmlBody("<p>$body</p><ul>" . implode('<li>', $clientNames) . '</ul>')
                ->send();
        }
    }

    public function actionSingle($id)
    {
        /** @var Carousel $carousel */
        $carousel = Carousel::model()->with('client')->findByPk($id);
        $this->processCarousel($carousel);
    }

    /**
     * @param Carousel $carousel
     * @throws CantSaveActiveRecordException
     * @return bool|string status string or false
     */
    public function processCarousel($carousel)
    {
        $this->log("processing carousel id=" . $carousel->id);
        $us = Yii::app()->unistorage;

        // resizing client logo
        if (!empty($carousel->client->logoUri)) {
            /** @var YiiUnistorage\Models\Files\ImageFile $logo */
            $logo = $us->getFile($carousel->client->logoUri);
            $logo->resize(YiiUnistorage\Models\Files\ImageFile::MODE_KEEP_RATIO, $carousel->template->logoWidth, $carousel->template->logoHeight);
        }

        // fetching feed items
        try {
            $ymlItems = YMLHelper::getItems($carousel->client->getFeedFile(), $carousel->categories, $carousel->viewType, self::ITEMS_LIMIT, $allItemsCount);
        } catch (CException $e) {
            $this->captureException($e);
            return $e->getMessage();
        } catch (CurlException $e) {
            $this->captureException($e);
            return $e->getMessage();
        }

        // get image urls
        $urls = [];
        foreach ($ymlItems as $ymlItem) {
            if (!empty($ymlItem['picture'])) {
                $urls[] = $ymlItem['picture'];
            }
        }
        $urls = array_unique($urls);

        // fill images hash
        $imagesHash = $this->getImagesHash($urls);
        foreach ($ymlItems as $i => $ymlItem) {
            $imageUrl = $ymlItem['picture'];
            if (array_key_exists($imageUrl, $imagesHash)) {
                if ($imagesHash[$imageUrl] === false) {
                    unset($ymlItems[$i]);
                } else {
                    $ymlItems[$i]['imageHash'] = $imagesHash[$imageUrl];
                }
            }
        }

        // gather changed images and obsolete item ids or update item info
        $urlToFiles = [];
        $foundYmlIds = [];
        $itemIdsToHide = [];
        $ymlIdToId = EHtml::listData(Item::model()->byCarousel($carousel->id), 'ymlId', 'id');
        foreach ($ymlIdToId as $ymlId => $id) {
            if (empty($ymlItems[$ymlId])) { // current $item was deleted
                $itemIdsToHide[] = $id;
                continue;
            }
            $ymlItems[$ymlId]['itemId'] = $id;
            $imageHash = $ymlItems[$ymlId]['imageHash'];
            if ($cache = ImageCache::model()->findByHash($imageHash)) { // we have already downloaded this file
                $this->log('get uri from cache for ' . $cache->uri);
                $ymlItems[$ymlId]['imageUri'] = $cache->uri;
            } else {
                $urlToFiles[$ymlItems[$ymlId]['picture']] = tempnam(Yii::app()->runtimePath, 'img-');
            }
            $foundYmlIds[$ymlId] = true;
        }

        // gather new image urls
        $newYmlItems = array_diff_key($ymlItems, $foundYmlIds);
        foreach ($newYmlItems as $ymlId => $ymlItem) {
            if ($cache = ImageCache::model()->findByHash($ymlItem['imageHash'])) {
                $this->log('get uri from cache for ' . $cache->uri);
                $ymlItems[$ymlId]['imageUri'] = $cache->uri;
            } else {
                $urlToFiles[$ymlItem['picture']] = tempnam(Yii::app()->runtimePath, 'img-');
            }
        }

        // converting image urls to unistorage resource uris
        $imagesUri = $this->uploadToUs($urlToFiles, $carousel);
        foreach ($ymlItems as $ymlId => $ymlItem) {
            $imageUrl = $ymlItem['picture'];
            if (array_key_exists($imageUrl, $imagesUri)) {
                if ($imagesUri[$imageUrl] === false) {
                    unset($ymlItems[$ymlId]);
                } else {
                    $ymlItems[$ymlId]['imageUri'] = $imagesUri[$imageUrl];
                    $cache = new ImageCache();
                    $cache->setAttributes([
                            'hash' => $ymlItems[$ymlId]['imageHash'],
                            'uri' => $imagesUri[$imageUrl],
                            'url' => $imageUrl,
                        ]);
                    $cache->save(false);
                }
            }
        }

        foreach ($urlToFiles as $file) {
            @unlink($file);
        }

        // updating items
        $emptyItem = new Item();
        foreach ($ymlItems as $ymlId => $itemAttributes) {
            $item = clone $emptyItem;
            if (!empty($itemAttributes['itemId'])) {
                $item->id = $itemAttributes['itemId'];
                $item->isNewRecord = false;
            }
            $item->setAttributes([
                        'title' => $itemAttributes['title'],
                        'price' => $itemAttributes['price'],
                        'url' => $itemAttributes['url'],
                        'ymlId' => $ymlId,
                        'status' => Item::STATUS_VISIBLE,
                        'carouselId' => $carousel->id,
                    ]);
            if (!empty($itemAttributes['imageHash'])) {
                $item->imageUri = $itemAttributes['imageUri'];
            }

            if (!$item->save()) {
                throw new CantSaveActiveRecordException($item);
            }
            $item->getResizedImageUrl($carousel->template->itemWidth, $carousel->template->itemHeight);
        }
        $carousel->invalidate();

        // remove obsolete
        $c = new CDbCriteria();
        $c->addInCondition('id', $itemIdsToHide);
        Item::model()->updateAll(['status' => Item::STATUS_HIDDEN], $c);

        if ($allItemsCount > self::ITEMS_LIMIT) {
            return "Подлежат обработке " . $allItemsCount . " записей. Из них случайно отобрано " . self::ITEMS_LIMIT .
                ". Успешно обработано " . count($ymlItems) . ".";
        } else {
            return "Отобрано для обработки " . $allItemsCount . " записей. Успешно обработано " . count($ymlItems) . ".";
        }
    }

    /**
     * @param Exception $e
     */
    public function captureException($e)
    {
        if (Yii::app()->params['useSentry']) {
            /** @var RSentryComponent $raven */
            $raven = Yii::app()->getComponent('RSentryException');
            $raven->getClient()->captureException($e);
        }

        if (YII_DEBUG && Yii::app() instanceof CConsoleApplication) {
            echo $e;
        }
    }

    /**
     * @param $urls
     * @return mixed [url => string|false, ...]
     * @throws CurlException
     */
    public function getImagesHash($urls)
    {
        $imagesHash = [];

        CurlHelper::batchGet(
            $urls,
            function ($url, $result, $e) use (&$imagesHash) {
                if ($e) {
                    /** @var Exception $e */
                    $this->log($e->getMessage());
                    $imagesHash[$url] = false;
                    return;
                }
                $imageData = $url;
                // todo: work with 'Expires', 'Cache-Control' headers
                foreach (['ETag', 'Last-Modified', 'Content-Length'] as $headerName) {
                    $headerNameQuoted = preg_quote($headerName, '/');
                    if (preg_match('/^' . $headerNameQuoted . ':\s.+$/m', $result, $matches)) {
                        $imageData .= $matches[0];
                    }
                }

                $imagesHash[$url] = md5($imageData);
            },
            [
                CURLOPT_NOBODY => true,
                CURLOPT_HEADER => true,
                CURLOPT_TIMEOUT => 10, // timeout with callback execution time
            ]
        );

        return $imagesHash;
    }

    /**
     * @param $urlToFiles
     * @throws CurlException
     * @return mixed
     */
    public function uploadToUs($urlToFiles)
    {
        $us = Yii::app()->unistorage;

        $imagesUri = [];
        CurlHelper::batchDownload(
            $urlToFiles,
            function ($url, $file, $e) use ($us, &$imagesUri) {
                if ($e) {
                    /** @var Exception $e */
                    $this->log($e->getMessage());
                    $imagesUri[$url] = false;
                    return;
                }

                $this->log('upload to unistorage from ' . $url);
                $file = $us->uploadFile($file);
                $imagesUri[$url] = $file->resourceUri;
            },
            [
                CURLOPT_TIMEOUT => 60 * 5, // timeout with callback execution time
            ]
        );
        return $imagesUri;
    }
}
