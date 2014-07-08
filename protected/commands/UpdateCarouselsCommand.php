<?php

use m8rge\CurlHelper;
use m8rge\CurlException;

class UpdateCarouselsCommand extends ConsoleCommand
{
    const ITEMS_LIMIT = 300;

    /** @var Client[] $urlToClient */
    public $urlToClient = [];

    /** @var bool */
    public $forceImages = false;

    public function actionIndex()
    {
        /** @var Client[] $clients */
        $clients = Client::model()->with('carouselsOnSite')->findAll();

        $urlToFiles = [];
        foreach ($clients as $client) {
            if (!empty($client->carouselsOnSite)) {
                $feedFile = tempnam(Yii::app()->getRuntimePath(), 'yml');
                $urlToFiles[$client->feedUrl] = $feedFile;
                $this->urlToClient[$client->feedUrl] = $client;
            }
        }

        CurlHelper::batchDownload(
            $urlToFiles,
            function ($url, $file, $e) {
                if ($e) {
                    unlink($file);
                    $this->captureException($e);
                    return;
                }
                $client = $this->urlToClient[$url];
                $client->updateFeedFile($file);

                // iterate through client carousels
                foreach ($client->carouselsOnSite as $carousel) {
                    $this->processCarousel($carousel);
                }
            },
            [
                CURLOPT_CONNECTTIMEOUT => 15,
            ]
        );
    }

    public function actionSingle($id)
    {
        /** @var Carousel $carousel */
        $carousel = Carousel::model()->with('client')->findByPk($id);
        $this->processCarousel($carousel);
    }

    /**
     * @param Carousel $carousel
     * @throws CDbException
     * @throws CException
     * @throws m8rge\CurlException
     * @return bool|string status string or false
     */
    public function processCarousel($carousel)
    {
        $this->log("processing carousel id=" . $carousel->id);
        /** @var $fs FileSystem */
        $fs = Yii::app()->fs;
        if (!empty($carousel->client->logoUid)) {
            $fs->resizeImage($carousel->client->logoUid, $carousel->logoSize[0], $carousel->logoSize[1]);
        }

        try {
            $items = YMLHelper::getItems($carousel->client->getFeedFile(), $carousel->categories, $carousel->viewType, self::ITEMS_LIMIT, $allItemsCount);
        } catch (CException $e) {
            $this->captureException($e);
            return $e->getMessage();
        } catch (CurlException $e) {
            $this->captureException($e);
            return $e->getMessage();
        }
        $urlToFiles = [];
        $urlToItemId = [];
        foreach ($items as $i => &$itemAttributes) {
            if (empty($itemAttributes['picture'])) {
                unset($items[$i]);
                continue;
            }
            $itemAttributes['carouselId'] = $carousel->id;

            $publishedMask = $fs->getCarouselFilePath($carousel->id, md5($itemAttributes['picture'])) . '.*';
            $existingImage = glob($publishedMask);
            $imageExists = !empty($existingImage);
            if ($imageExists) {
                $imageUid = reset($existingImage);
                $imageUid = pathinfo($imageUid, PATHINFO_BASENAME);
                $itemAttributes['imageUid'] = $imageUid;
            }
            if (!$imageExists || $this->forceImages) {
                $tempFile = tempnam(Yii::app()->runtimePath, 'myarusel-image-');
                $urlToFiles[$itemAttributes['picture']] = $tempFile;
                $urlToItemId[$itemAttributes['picture']] = $i;
            }
        }
        unset($itemAttributes);

        CurlHelper::batchDownload(
            $urlToFiles,
            function ($url, $file, $e) use (&$items, $urlToItemId, $carousel, $fs) {
                $itemId = $urlToItemId[$url];
                if ($e) {
                    /** @var Exception $e */
                    $this->log($e->getMessage());
                    unlink($file);
                    unset($items[$itemId]);
                    return;
                }
                $this->log('downloaded ' . $url);
                $itemAttributes = & $items[$itemId];

                try {
                    $itemAttributes['imageUid'] = $fs->publishFileForCarousel(
                        $file,
                        $url,
                        $carousel->id
                    );
                    $fs->resizeCarouselImage(
                        $carousel->id,
                        $itemAttributes['imageUid'],
                        $carousel->thumbSize[0],
                        $carousel->thumbSize[1],
                        $this->forceImages
                    );
                    unset($itemAttributes['picture']);
                } catch (Imagecow\ImageException $e) {
                    unset($items[$itemId]);
                } finally {
                    @unlink($file);
                }
            }
        );

        $c = new CDbCriteria([
                'condition' => 'carouselId = :carouselId',
                'params' => [
                    ':carouselId' => $carousel->id,
                ]
            ]);
        Yii::app()->db->commandBuilder->createDeleteCommand(Item::model()->tableName(), $c)->execute();

        foreach ($items as $itemAttributes) {
            $item = new Item();
            $item->setAttributes($itemAttributes);
            if (!$item->save()) {
                throw new CException(
                    "Can't save Item:\n" . print_r($item->getErrors(), true) . print_r($item->getAttributes(), true)
                );
            }
        }
        $carousel->invalidate();

        $this->log("end processing carousel id=" . $carousel->id);
        $this->log("memory current = " . round(memory_get_usage()/1024) . 'K, peak = ' . round(memory_get_peak_usage()/1024) . 'K');

        if ($allItemsCount > self::ITEMS_LIMIT) {
            return "Подлежат обработке " . $allItemsCount . " записей. Из них случайно отобрано " . self::ITEMS_LIMIT .
                ". Успешно обработано " . count($items) . ".";
        } else {
            return "Отобрано обработке " . $allItemsCount . " записей. Успешно обработано " . count($items) . ".";
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
}
