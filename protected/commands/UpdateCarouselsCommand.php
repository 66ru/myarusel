<?php

use m8rge\CurlHelper;
use m8rge\CurlException;

class UpdateCarouselsCommand extends CConsoleCommand
{
    const ITEMS_LIMIT = 300;

	public function actionIndex($id = null) {
		/** @var $fs FileSystem */
		$fs = Yii::app()->fs;

		if ($id === null)
			$carousels = Carousel::model()->findAll();
		else
			$carousels = array(Carousel::model()->findByPk($id));

		/** @var $carousel Carousel */
		foreach($carousels as $carousel) {
			if (!($carousel instanceof Carousel))
				throw new CException('Can\'t find carousel');

			if (!empty($carousel->client->logoUid))
				$fs->resizeImage($carousel->client->logoUid, array($carousel->logoSize, $carousel->logoSize));
			$feedFile = $carousel->client->getFeedFile(true);
			$items = YMLHelper::getItems($feedFile, $carousel->categories, $carousel->viewType);
            $allItemsCount = count($items);
			shuffle($items);
			$items = array_slice($items, 0, self::ITEMS_LIMIT);
			foreach ($items as $id => &$itemAttributes) {
				$tempFile = tempnam(sys_get_temp_dir(), 'myarusel-image');
				try {
					if (!empty($itemAttributes['picture'])) {
                        $itemAttributes['picture'] = trim($itemAttributes['picture']);
                        $filename = pathinfo( parse_url($itemAttributes['picture'], PHP_URL_PATH), PATHINFO_BASENAME );
						CurlHelper::downloadToFile($itemAttributes['picture'], $tempFile);
						if (ImageHelper::checkImageCorrect($tempFile)) {
							$itemAttributes['imageUid'] = $fs->publishFile($tempFile, $filename);
							$fs->resizeImage($itemAttributes['imageUid'], array($carousel->thumbSize, $carousel->thumbSize));
						}
						unlink($tempFile);
						$itemAttributes['carouselId'] = $carousel->id;
						unset($itemAttributes['picture']);
					} else {
						unset($items[$id]);
					}
				} catch (CurlException $e) {
					unset($items[$id]);
				}
			}
			unset($itemAttributes); // remove link

			/** @var $item Item */
			foreach($carousel->items as $item) {
				$item->delete();
			}

			foreach ($items as $itemAttributes) {
				$item = new Item();
				$item->setAttributes($itemAttributes);
				if (!$item->save())
					throw new CException("Can't save Item:\n".print_r($item->getErrors(), true).print_r($item->getAttributes(), true));
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
}
