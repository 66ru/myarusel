<?php

/**
 * Class ImageCache
 *
 * @property int id
 * @property string hash
 * @property string uri
 */
class ImageCache extends CActiveRecord
{
    /**
     * @param string $className
     * @return ImageCache
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return [
            ['hash, uri', 'required'],
            ['hash', 'length', 'max' => 32],
            ['uri', 'length', 'max' => 100],
        ];
    }

    /**
     * @param string $hash
     * @return ImageCache|null
     */
    public function findByHash($hash)
    {
        return $this->findByAttributes(['hash' => $hash]);
    }
}