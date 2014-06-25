<?php

use m8rge\CurlHelper;
use m8rge\CurlException;

class UpdateCarouselsCommand extends ConsoleCommand
{
    const ITEMS_LIMIT = 300;

    public function actionIndex($id = null)
    {
        /** @var $fs FileSystem */
        $fs = Yii::app()->fs;

        if ($id === null) {
            $carousels = Carousel::model()->onSite()->findAll();
        } else {
            $carousels = array(Carousel::model()->findByPk($id));
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
                $feedFile = $carousel->client->getFeedFile(true);
            } catch (CurlException $e) {
                $this->captureException($e, !empty($id));
                continue;
            }
            try {
                $items = YMLHelper::getItems($feedFile, $carousel->categories, $carousel->viewType);
            } catch (CException $e) {
                $this->captureException($e, !empty($id));
                continue;
            }
            $allItemsCount = count($items);
            shuffle($items);
            $items = array_slice($items, 0, self::ITEMS_LIMIT);
            foreach ($items as $i => &$itemAttributes) {
                $tempFile = tempnam(Yii::app()->runtimePath, 'myarusel-image-');
                $itemAttributes['url'] = trim($itemAttributes['url']);
                try {
                    if (!empty($itemAttributes['picture'])) {
                        $itemAttributes['picture'] = trim($itemAttributes['picture']);
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
                                $carousel->thumbSize[1]
                            );
                        }
                        unlink($tempFile);
                        $itemAttributes['carouselId'] = $carousel->id;
                        unset($itemAttributes['picture']);
                    } else {
                        unset($items[$i]);
                    }
                } catch (CurlException $e) {
                    @unlink($tempFile);
                    unset($items[$i]);
                } catch (CException $e) {
                    @unlink($tempFile);
                    if ($e->getMessage() == 'image type not allowed') {
                        unset($items[$i]);
                    } else {
                        throw $e;
                    }
                }
            }
            unset($itemAttributes); // remove link

            /** @var $item Item */
            foreach ($carousel->items as $item) {
                $item->delete();
            }

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

            if ($id !== null) {
                if ($allItemsCount > self::ITEMS_LIMIT) {
                    echo "Подлежат обработке " . $allItemsCount . " записей. Из них случайно отобрано " . self::ITEMS_LIMIT .
                        ". Успешно обработано " . count($items) . ".";
                } else {
                    echo "Подлежат обработке " . $allItemsCount . " записей. Успешно обработано " . count($items) . ".";
                }
            }
        }
    }

    public function captureException($e, $fatal)
    {
        if (Yii::app()->params['useSentry']) {
            /** @var RSentryComponent $raven */
            $raven = Yii::app()->getComponent('RSentryException');
            $raven->getClient()->captureException($e);
        }

        if (YII_DEBUG) {
            echo $e;
        }
        if ($fatal) {
            Yii::app()->end(1);
        }
    }
}
