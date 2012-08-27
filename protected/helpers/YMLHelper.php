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
			$categories[$newCategory['id']] = $newCategory;
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
	 * @param int $viewType
	 * @return array array with Items attributes
	 */
	public static function getItems($ymlFile, $categoryIds, $viewType) {
		$currencies = array(
			'RUR' => '{price} р.',
			'USD' => '${price}',
			'UAH' => '{price} грн.',
			'KZT' => '{price} тңг',
		);

		$categories = self::getCategories($ymlFile);
		foreach ($categories as &$category)
			$category['count'] = 0;
		unset($category);
		$tree = TreeHelper::makeTree(null, CHtml::listData($categories, 'id', 'parentId'));

		$xml = self::loadXmlFile($ymlFile);

		$fullCategoryIds = array();
		if (!is_array($categoryIds))
			$categoryIds = array();
		foreach ($categoryIds as $categoryId) {
			$fullCategoryIds[] = $categoryId;
			$fullCategoryIds = array_merge($fullCategoryIds, self::internalGetChildIds($tree[null], $categoryId));
		}

		$itemsArray = array();
		/** @var $offer SimpleXMLElement */
		foreach ($xml->shop->offers->offer as $offer) {
			$attributes = $offer->attributes();
			if (in_array((string)$offer->categoryId, $fullCategoryIds)) {
				$price = number_format((float)$offer->price, 0, ',', ' ');
				$price = str_replace('{price}', $price, $currencies[(string)$offer->currencyId]);

				$newItem = array(
					'url' => !empty($offer->url) ? (string)$offer->url : '',
					'price' => $price,
					'priceNumeric' => (string)$offer->price,
					'picture' => !empty($offer->picture) ? (string)$offer->picture : '',
					'categoryId' => (string)$offer->categoryId,
				);
				if (!empty($attributes['type']) && $attributes['type']=='vendor.model') {
					$newItem['title'] = ($offer->typePrefix ? $offer->typePrefix.' ' : '').$offer->vendor.' '.$offer->model;
				} elseif (empty($attributes['type'])) {
					$newItem['title'] = (string)$offer->name;
				}
				$categories[$newItem['categoryId']]['count']++;
				$itemsArray[] = $newItem;
			}
		}
		unset($xml);

		if ($viewType == Carousel::VIEW_ONLY_CHEAP || $viewType == Carousel::VIEW_USE_GROUPS) {
			uasort($itemsArray, function ($a, $b){
				if ($a['categoryId'] == $b['categoryId'] &&
						$a['priceNumeric'] == $b['priceNumeric'])
					return 0;
				if ($a['categoryId'] < $b['categoryId'])
					return -1;
				if ($a['categoryId'] > $b['categoryId'])
					return 1;
				return $a['priceNumeric'] < $b['priceNumeric'] ? -1 : 1;
			});

			$lastCategoryId = null;
			foreach ($itemsArray as $id=>$item) {
				if ($itemsArray[$id]['categoryId'] == $lastCategoryId) {
					$lastCategoryId = $itemsArray[$id]['categoryId'];
					unset($itemsArray[$id]);
				} else {
					$lastCategoryId = $itemsArray[$id]['categoryId'];
				}
			}
		}
		if ($viewType == Carousel::VIEW_USE_GROUPS) {
			foreach ($itemsArray as &$item) {
				$item['title'] = $categories[$item['categoryId']]['name'];
				$item['price'] = "от {$item['price']} ({$categories[$item['categoryId']]['count']})";
			}
		}

		foreach ($itemsArray as &$item) {
			unset($item['priceNumeric']);
			unset($item['categoryId']);
		}
		unset($item);

		return $itemsArray;
	}
}
