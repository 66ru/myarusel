<?php

/**
 * Class EHtml
 * version 1.5
 */
class EHtml
{
    /**
     * Works like CHtml::listData. Supports magic fields.
     * @param CActiveRecord $model
     * @param string $valueField defaults to primary key field
     * @param string $textField defaults to primary key field
     * @return array if ($valueField == $textField) <br> returns Array($valueField, ...) <br> else Array($valueField => $textField, ...)
     */
    public static function listData($model, $valueField = '', $textField = '')
    {
        $pk = $model->metaData->tableSchema->primaryKey;
        if ($valueField === '') {
            $valueField = $pk;
        }
        if ($textField === '') {
            $textField = $valueField;
        }

        $columnNames = $model->tableSchema->columnNames;
        $select = '*';
        if (in_array($valueField, $columnNames) && in_array($textField, $columnNames)) {
            $select = ($valueField == $textField) ? $valueField : $valueField . ',' . $textField;
        }
        $data = CHtml::listData(
            $model->findAll(array('select' => $select)),
            $valueField,
            $textField
        );
        if ($valueField == $textField) {
            $data = array_keys($data);
        }

        return $data;
    }

    /**
     * Works like CHtml::listData. Supports magic fields.
     * @param CActiveRecord $model
     * @param string $valueField defaults to primary key field
     * @param string $textField defaults to primary key field
     * @return array if ($valueField == $textField) <br> returns Array($valueField, ...) <br> else Array($valueField => $textField, ...)
     */
    public static function cachedListData($model, $valueField = '', $textField = '')
    {
        $cache_key = __CLASS__ . get_class($model) . '-list-'.$valueField . '-' . $textField;
        if (!$list = Yii::app()->cache->get($cache_key)) {
            $list = self::listData($model, $valueField, $textField);

            $event = new CEvent();
            $event->sender = $model;
            self::invalidateCache($event);
            $dependency = new CGlobalStateCacheDependency(self::getCacheStateName($model));

            Yii::app()->cache->set($cache_key, $list, 0, $dependency);
        }

        return $list;
    }

    public static function getCacheStateName($model)
    {
        return md5(__CLASS__ . get_class($model) . '-cacheState');
    }

    /**
     * @param CEvent $event
     */
    public static function invalidateCache($event)
    {
        Yii::app()->setGlobalState(self::getCacheStateName($event->sender), microtime());
    }
}
