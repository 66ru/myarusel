<?php

Yii::import('ext.validators.ymlValidator.*');

class YMLHelper
{
    public static $currencies = array(
        'RUR' => '{price} р.',
        'RUB' => '{price} р.',
        'USD' => '${price}',
        'UAH' => '{price} грн.',
        'KZT' => '{price} тңг',
    );

    /**
     * @param string $ymlFile
     * @return XMLReader2
     * @throws CException
     */
    private static function loadXmlFile($ymlFile)
    {
        static $loadedFiles = array();
        if (empty($loadedFiles[$ymlFile])) {
            $validator = new ValidYml();
            $res = $validator->validateFile($ymlFile);
            if ($res !== true) {
                throw new CException($res);
            }
        }
        $r = new XMLReader2();
        if (!@$r->open($ymlFile)) {
            throw new CException('can\'t open xml file: ' . pathinfo($ymlFile, PATHINFO_FILENAME));
        }
        $loadedFiles[$ymlFile] = true;

        return $r;
    }

    /**
     * @param string $ymlFile
     * @return array
     * @throws CException
     */
    public static function getCategories($ymlFile)
    {
        $categories = array();

        $r = self::loadXmlFile($ymlFile);
        $r->readUntil('shop')->readUntil('categories')->readAll(
            'category',
            function () use ($r, &$categories) {
                $parentId = $r->getAttribute('parentId');
                $newCategory = array(
                    'id' => $r->getAttribute('id'),
                    'name' => $r->readString(),
                );
                if (!empty($parentId)) {
                    $newCategory['parentId'] = $parentId;
                }
                $categories[$newCategory['id']] = $newCategory;
            }
        );
        $r->close();

        return $categories;
    }

    /**
     * @param $categoryIds
     * @param $categories
     * @return array
     */
    protected static function getFetchingCategories($categoryIds, $categories)
    {
        $fetchCategoryIds = [];
        $categoriesTree = TreeHelper::makeTree(null, CHtml::listData($categories, 'id', 'parentId'));
        foreach ($categoryIds as $categoryId) {
            if (!in_array($categoryId, $fetchCategoryIds)) {
                $fetchCategoryIds[] = $categoryId;
                $fetchCategoryIds = array_merge(
                    $fetchCategoryIds,
                    self::internalGetChildIds($categoriesTree[null], $categoryId)
                );
            }
        }
        return $fetchCategoryIds;
    }

    private static function internalGetChildIds($tree, $categoryId, $fillResult = false)
    {
        $result = [];

        foreach ($tree as $id => $childrens) {
            if ($fillResult) {
                $result[] = $id;
            }

            $result = array_merge(
                $result,
                self::internalGetChildIds($childrens, $categoryId, $fillResult || $id == $categoryId)
            );
        }

        return $result;
    }

    /**
     * @static
     * @param string $ymlFile
     * @param array $categoryIds
     * @param int $viewType
     * @param int $limit
     * @throws CException
     * @return array array with Items attributes
     */
    public static function getItems($ymlFile, $categoryIds, $viewType, $limit)
    {
        if (!is_array($categoryIds)) {
            $categoryIds = [];
        }
        if (empty($categoryIds) && $viewType != Carousel::VIEW_ALL) {
            return [];
        }

        $categories = self::getCategories($ymlFile);
        $fetchCategoryIds = self::getFetchingCategories($categoryIds, $categories);

        // Получаем id и цену товаров с картинкой и ссылкой
        $fetchingItemsInCategories = [];
        $r = self::loadXmlFile($ymlFile);
        $r->readUntil('shop')->readUntil('offers')->readAll(
            'offer',
            function () use ($r, $viewType, $fetchCategoryIds, &$fetchingItemsInCategories) {
                $offer = self::gatherOfferProperties($r);
                if (empty($offer['id']) || empty($offer['picture']) || empty($offer['url'])) {
                    return;
                }

                if ($viewType == Carousel::VIEW_ALL || in_array($offer['categoryId'], $fetchCategoryIds)) {
                    $itemPrice = (float)$offer['price'];
                    if ($viewType == Carousel::VIEW_ALL || $viewType == Carousel::VIEW_ALL_IN_CATEGORIES) {
                        $fetchingItemsInCategories[ null ][ $offer['id'] ] = true;
                    } else {
                        // price only needed in this case
                        $fetchingItemsInCategories[ $offer['categoryId'] ][ $offer['id'] ] = $itemPrice;
                    }
                }
            }
        );
        $r->close();

        if (empty($fetchingItemsInCategories)) {
            return [];
        }

        $itemsWithCategoryImage = [];
        $fetchingItems = [];
        // обрабатываем список товаров
        if ($viewType == Carousel::VIEW_ALL || $viewType == Carousel::VIEW_ALL_IN_CATEGORIES) {
            $fetchingItems = array_keys($fetchingItemsInCategories[null]);
            shuffle($fetchingItems);
            $fetchingItems = array_slice($fetchingItems, 0, $limit);
        } else if ($viewType == Carousel::VIEW_CHEAP_IN_CATEGORIES || $viewType == Carousel::VIEW_GROUP_BY_CATEGORIES) {
            // выборка самого дешевого товара,
            foreach ($fetchingItemsInCategories as $categoryId => $category) {
                if ($viewType == Carousel::VIEW_GROUP_BY_CATEGORIES) {
                    // а также выборка случайного изображения для категории
                    $imageItemSequence = mt_rand(0, count($category)-1);
                }
                $lowestPrice = PHP_INT_MAX;
                $resultItemId = null;
                $i = 0;
                foreach ($category as $itemId => $item) {
                    /** @noinspection PhpUndefinedVariableInspection */
                    if ($viewType == Carousel::VIEW_GROUP_BY_CATEGORIES && $imageItemSequence == $i) {
                        $itemsWithCategoryImage[$itemId] = $categoryId;
                    }
                    if ($item < $lowestPrice) {
                        $lowestPrice = $item;
                        $resultItemId = $itemId;
                    }
                    $i++;
                }
                $fetchingItems[] = $resultItemId;
            }
            shuffle($fetchingItems);
            $fetchingItems = array_slice($fetchingItems, 0, $limit);
        }

        $categoryImages = [];
        $itemsArray = [];
        // загружаем информацию для выбранных товаров
        $r = self::loadXmlFile($ymlFile);
        $r->readUntil('shop')->readUntil('offers')->readAll(
            'offer',
            function () use ($r, $viewType, $categories, $itemsWithCategoryImage, $fetchingItems, &$itemsArray, &$categoryImages) {
                $offerType = $r->getAttribute('type');
                $offer = self::gatherOfferProperties($r);
                if (!empty($offer['id']) && array_key_exists($offer['id'], $itemsWithCategoryImage)) {
                    $categoryImages[$itemsWithCategoryImage[$offer['id']]] = $offer['picture'];
                }
                if (empty($offer['id']) || !in_array($offer['id'], $fetchingItems)) {
                    return;
                }

                $price = number_format((float)$offer['price'], 0, ',', ' ');
                $price = str_replace('{price}', $price, self::$currencies[$offer['currencyId']]);
                if ($viewType == Carousel::VIEW_GROUP_BY_CATEGORIES) {
                    $price = "от $price";
                }

                if ($viewType == Carousel::VIEW_GROUP_BY_CATEGORIES) {
                    $title = $categories[ $offer['categoryId'] ]['name'];
                } else {
                    if ($offerType == 'vendor.model') {
                        $title = (!empty($offer['typePrefix']) ? $offer['typePrefix'] . ' ' : '') . $offer['vendor'] . ' ' . $offer['model'];
                    } else {
                        $title = $offer['name'];
                    }
                }

                $itemsArray[ $offer['id'] ] = [
                    'title' => $title,
                    'price' => $price,
                    'url' => trim($offer['url']),
                    'picture' => trim($offer['picture']),
                    'categoryId' => $offer['categoryId'],
                ];
            }
        );

        if ($viewType == Carousel::VIEW_GROUP_BY_CATEGORIES) {
            foreach ($itemsArray as &$item) {
                $item['picture'] = $categoryImages[$item['categoryId']];
                unset($item['categoryId']);
            }
            unset($item);
        } else {
            foreach ($itemsArray as &$item) {
                unset($item['categoryId']);
            }
            unset($item);
        }

        return $itemsArray;
    }

    /**
     * @param XMLReader $r
     * @return array
     */
    private static function gatherOfferProperties($r)
    {
        $offer = [
            'id' => $r->getAttribute('id'),
        ];
        while (!($r->nodeType == XMLReader::END_ELEMENT && $r->name == 'offer')) {
            $r->read();
            if ($r->nodeType == XMLReader::ELEMENT) {
                $offer[$r->name] = trim($r->readString());
            }
        }
        if (empty($offer['id']) && !empty($offer['picture'])) {
            $offer['id'] = md5($offer['picture']);
        }

        return $offer;
    }
}
