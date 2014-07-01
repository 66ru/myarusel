<?php

use m8rge\CurlHelper;
use m8rge\CurlException;

class UpdateCarouselsCommand extends ConsoleCommand
{
    const ITEMS_LIMIT = 300;

    public function actionIndex($id = null, $forceImages = false)
    {
        define('SINGLE_REFRESH', $id !== null);

        /** @var $fs FileSystem */
        $fs = Yii::app()->fs;

        if ($id === null) {
            $carousels = Carousel::model()->onSite()->orderById()->with('client')->findAll();
        } else {
            $carousels = array(Carousel::model()->with('client')->findByPk($id));
        }

        /** @var $carousel Carousel */
        foreach ($carousels as $carousel) {
            if (!($carousel instanceof Carousel)) {
                throw new CException('Can\'t find carousel');
            }
            $this->log("processing carousel id=" . $carousel->id);

            if (!empty($carousel->client->logoUid)) {
                $fs->resizeImage($carousel->client->logoUid, $carousel->logoSize[0], $carousel->logoSize[1]);
            }
            try {
                static $clientFiles = [];
                if (empty($clientFiles[ $carousel->clientId ])) {
                    $feedFile = $carousel->client->getFeedFile(true);
                    $clientFiles[ $carousel->clientId ] = $feedFile;
                } else {
                    $feedFile = $clientFiles[ $carousel->clientId ];
                }
            } catch (CurlException $e) {
                $this->captureException($e);
                continue;
            }
            try {
                $items = YMLHelper::getItems($feedFile, $carousel->categories, $carousel->viewType, self::ITEMS_LIMIT);
            } catch (CException $e) {
                $this->captureException($e);
                continue;
            }
            $allItemsCount = count($items);
            foreach ($items as $i => &$itemAttributes) {
                $tempFile = tempnam(Yii::app()->runtimePath, 'myarusel-image-');
                try {
                    if (!empty($itemAttributes['picture'])) {
                        $publishedMask = $fs->getCarouselFilePath($carousel->id, md5($itemAttributes['picture'])) . '.*';
                        $existingImage = glob($publishedMask);
                        $imageExists = !empty($existingImage);
                        if ($imageExists) {
                            $imageUid = reset($existingImage);
                            $imageUid = pathinfo($imageUid, PATHINFO_BASENAME);
                            $itemAttributes['imageUid'] = $imageUid;
                        }

                        if (!$imageExists || $forceImages) {
                            CurlHelper::downloadToFile($itemAttributes['picture'], $tempFile);
                            if (ImageHelper::checkImageCorrect($tempFile)) {
                                $itemAttributes['imageUid'] = $fs->publishFileForCarousel(
                                    $tempFile,
                                    $itemAttributes['picture'],
                                    $carousel->id
                                );
                                $fs->resizeCarouselImage(
                                    $carousel->id,
                                    $itemAttributes['imageUid'],
                                    $carousel->thumbSize[0],
                                    $carousel->thumbSize[1],
                                    $forceImages
                                );
                            }
                        }
                        $itemAttributes['carouselId'] = $carousel->id;
                        unset($itemAttributes['picture']);
                    } else {
                        unset($items[$i]);
                    }
                } catch (CurlException $e) {
                    unset($items[$i]);
                } catch (Imagecow\ImageException $e) {
                    unset($items[$i]);
                } finally {
                    @unlink($tempFile);
                }
            }
            unset($itemAttributes); // remove link

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

            if (SINGLE_REFRESH) {
                if ($allItemsCount > self::ITEMS_LIMIT) {
                    echo "Подлежат обработке " . $allItemsCount . " записей. Из них случайно отобрано " . self::ITEMS_LIMIT .
                        ". Успешно обработано " . count($items) . ".";
                } else {
                    echo "Подлежат обработке " . $allItemsCount . " записей. Успешно обработано " . count($items) . ".";
                }
            }
            $this->log("end processing carousel id=" . $carousel->id);
            $this->log(memory_get_usage() . ' | ' . memory_get_peak_usage());
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

        if (YII_DEBUG) {
            echo $e;
        }
        if (SINGLE_REFRESH) {
            if (!YII_DEBUG) {
                echo $e->getMessage();
            }
            Yii::app()->end(1);
        }
    }
}
