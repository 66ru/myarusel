<?php

class UpdateCarouselsCommand extends CConsoleCommand
{
	public function run() {
		/** @var $fs FileSystem */
		$fs = Yii::app()->fs;

		$carousels = Carousel::model()->with('client')->findAll();

		/** @var $carousel Carousel */
		foreach($carousels as $carousel) {
			$feedFile = $carousel->client->getFeedFile(true);
			$items = YMLHelper::getItems($feedFile, $carousel->categories);
			foreach ($items as &$itemAttributes) {
				$tempFile = tempnam(sys_get_temp_dir(), 'myarusel-image');
				CurlHelper::downloadToFile($itemAttributes['picture'], $tempFile);
				if (ImageHelper::checkImageCorrect($tempFile))
					$itemAttributes['imageUid'] = $fs->publishFile($tempFile, $itemAttributes['picture']);
				$itemAttributes['carouselId'] = $carousel->id;
				unset($itemAttributes['picture']);
			}
			unset($itemAttributes); // remove link

			Item::model()->deleteAllByAttributes(array('carouselId' => $carousel->id));
			foreach ($items as $itemAttributes) {
				$item = new Item();
				$item->setAttributes($itemAttributes);
				if (!$item->save())
					throw new CException("Can't save Item:\n".print_r($item->getErrors(), true).print_r($item->getAttributes(), true));
			}
			Yii::app()->setGlobalState('invalidateCarousel'.$carousel->id, time());
		}
	}
}
