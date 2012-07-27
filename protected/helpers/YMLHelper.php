<?php

class YMLHelper
{
	public static function getCategories($ymlFile) {
		$categories = array();
		$xml = simplexml_load_file($ymlFile);
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

		return $categories;
	}

	public static function getItems($ymlFile, $categoryId) {
//		$xml = simplexml_load_file($ymlFile);
//		/** @var $category SimpleXMLElement */
//		foreach ($xml->shop->categories->category as $category) {
//			$attributes = $category->attributes();
//			$newCategory = array(
//				'id' => (string)$attributes['id'],
//				'name' => (string)$category,
//			);
//			if (!empty($attributes['parentId']) && )
//				$newCategory['parentId'] = (string)$attributes['parentId'];
//			$categories[] = $newCategory;
//		}
	}
}
