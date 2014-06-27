<?php

Yii::import('ext.validators.ymlValidator.*');

class YMLHelper
{
	private static function loadXmlFile($ymlFile)
    {
        static $loadedFiles = array();
        if (empty($loadedFiles[$ymlFile])) {
            $validator = new ValidYml();
            $res = $validator->validateFile($ymlFile);
            if ($res !== true) {
                throw new CException($res);
            }
            $xml = @simplexml_load_file($ymlFile);
            if ($xml === false)
                throw new CException('can\'t load xml file: '.pathinfo($ymlFile, PATHINFO_FILENAME));
            $loadedFiles[$ymlFile] = $xml;
        }

		return $loadedFiles[$ymlFile];
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
			'RUB' => '{price} р.',
			'USD' => '${price}',
			'UAH' => '{price} грн.',
			'KZT' => '{price} тңг',
		);

		$categories = self::getCategories($ymlFile);
		foreach ($categories as &$category)
			$category['count'] = 0;
		unset($category);
		$categoriesTree = TreeHelper::makeTree(null, CHtml::listData($categories, 'id', 'parentId'));

		$xml = self::loadXmlFile($ymlFile);

		$fullCategoryIds = array();
		if (!is_array($categoryIds))
			$categoryIds = array();
		foreach ($categoryIds as $categoryId) {
			$fullCategoryIds[] = $categoryId;
			$fullCategoryIds = array_merge($fullCategoryIds, self::internalGetChildIds($categoriesTree[null], $categoryId));
		}

		$itemsArray = array();
		/** @var $offer SimpleXMLElement */
		foreach ($xml->shop->offers->offer as $offer) {
			$attributes = $offer->attributes();
			if ($viewType == Carousel::VIEW_ALL || in_array((string)$offer->categoryId, $fullCategoryIds)) {
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

		if ($viewType == Carousel::VIEW_ONLY_CHEAP || $viewType == Carousel::VIEW_USE_GROUPS)
		{
			// группировка позиций по категориям
			$categoriesWithItems = array();
			foreach ($itemsArray as $item) {
				$categoriesWithItems[ $item['categoryId'] ][] = $item;
			}

			// получение случайных картинок для групп товаров
			if ($viewType == Carousel::VIEW_USE_GROUPS)
				$categoryImages = self::getGroupsImages($categoriesWithItems);

			// сортировка позиций по цене ASC
			foreach ($categoriesWithItems as &$category)
				$category = self::sortByPriceAsc($category);
			unset($category);

			// удаление всех позиций в категории кроме первой
			foreach ($categoriesWithItems as &$category)
				$category = reset($category);
			unset($category);

			// если у нас группировка по группам, тогда меняем название и картинку на случайную
			if ($viewType == Carousel::VIEW_USE_GROUPS) {
				foreach ($categoriesWithItems as $id => &$item) {
					$item['title'] = $categories[$id]['name'];
					$item['price'] = "от {$item['price']}";
					$item['picture'] = $categoryImages[$id];
				}
				unset($item);
			}

			// возврат товаров в plain массив
			$itemsArray = array();
			foreach ($categoriesWithItems as $item) {
				$itemsArray[] = $item;
			}
		}

		foreach ($itemsArray as &$item) {
			unset($item['priceNumeric']);
			unset($item['categoryId']);
		}
		unset($item);

		return $itemsArray;
	}

	/**
	 * @param $imagesHelper
	 * @return array
	 */
	private static function getGroupsImages($imagesHelper)
	{
		// clear items without pictures
		foreach ($imagesHelper as &$category) {
			foreach ($category as $id => $item) {
				if (empty($item['picture']))
					unset($category[$id]);
			}
		}
		unset($category);

		// randomize;
		foreach ($imagesHelper as &$category) {
			shuffle($category);
			$category = reset($category);
			$category = $category['picture'];
		}

		return $imagesHelper;
	}

	/**
	 * @param array $itemsArray
	 * @return mixed
	 */
	private static function sortByPriceAsc($itemsArray)
	{
		uasort($itemsArray, function ($a, $b) {
			if ($a['priceNumeric'] == $b['priceNumeric'])
				return 0;

			return $a['priceNumeric'] < $b['priceNumeric'] ? -1 : 1;
		});

		return $itemsArray;
	}
}
