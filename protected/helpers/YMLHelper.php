<?php

class YMLHelper
{
	private static function loadXmlFile($ymlFile){
		$xml = @simplexml_load_file($ymlFile);
		if ($xml === false)
			throw new CException('can\'t load xml file: '.pathinfo($ymlFile, PATHINFO_FILENAME));

		return $xml;
	}

	public static function getCategories($ymlFile) {
		$categories = array();
		$xml = self::loadXmlFile($ymlFile);

		/** @var $category SimpleXMLElement */
		foreach ($xml->shop->categories->category as $category) {
			$attributes = $category->attributes();
			$newCategory = array(
				'id' => (string)$attributes['id'],
				'name' => (string)$category,
			);
			if (!empty($attributes['parentId']))
				$newCategory['parentId'] = (string)$attributes['parentId'];
			$categories[] = $newCategory;
		}
		unset($xml);

		return $categories;
	}

	private static function internalGetChildIds($tree, $categoryId, $fillResult = false){
		$result = array();

		foreach ($tree as $id => $childrens) {
			if ($fillResult)
				$result[] = $id;

			$result+= self::internalGetChildIds($childrens, $categoryId, $fillResult || $id == $categoryId);
		}

		return $result;
	}

	/**
	 * @static
	 * @param string $ymlFile
	 * @param array $categoryIds
	 * @return array array with Items attributes
	 */
	public static function getItems($ymlFile, $categoryIds) {
		$currencies = array(
			'RUR' => '{price} руб.',
			'USD' => '${price}',
			'UAH' => '{price} грн.',
			'KZT' => '{price} тңг',
		);

		$categories = self::getCategories($ymlFile);
		$tree = TreeHelper::makeTree(null, CHtml::listData($categories, 'id', 'parentId'));

		$xml = self::loadXmlFile($ymlFile);

		$fullCategoryIds = array();
		foreach ($categoryIds as $categoryId) {
			$fullCategoryIds[] = $categoryId;
			$fullCategoryIds = array_merge($fullCategoryIds, self::internalGetChildIds($tree[null], $categoryId));
		}

		$itemsArray = array();
		/** @var $offer SimpleXMLElement */
		foreach ($xml->shop->offers->offer as $offer) {
			$attributes = $offer->attributes();
			if (in_array((string)$offer->categoryId, $fullCategoryIds)) {
				$price = number_format((float)$offer->price, 2, ',', ' ');
				$price = str_replace('{price}', $price, $currencies[(string)$offer->currencyId]);

				if (!empty($attributes['type']) && $attributes['type']=='vendor.model') {
					$itemsArray[] = array(
						'url' => !empty($offer->url) ? (string)$offer->url : '',
						'price' => $price,
						'picture' => !empty($offer->picture) ? (string)$offer->picture : '',
						'title' => ($offer->typePrefix ? $offer->typePrefix.' ' : '').$offer->vendor.' '.$offer->model,
					);
				} elseif (empty($attributes['type'])) {
					$itemsArray[] = array(
						'url' => !empty($offer->url) ? (string)$offer->url : '',
						'price' => $price,
						'picture' => !empty($offer->picture) ? (string)$offer->picture : '',
						'title' => (string)$offer->name,
					);
				}
			}
		}
		unset($xml);

		return $itemsArray;
	}
}
