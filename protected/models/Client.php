<?php

use m8rge\CurlHelper;
use YiiUnistorage\Models\Files\ImageFile;
use Unistorage\Models\Files\RegularFile;

/**
 * This is the model class for table "Client".
 *
 * The followings are the available columns in table 'Client':
 * @property string $id
 * @property string $name
 * @property string $feedUrl
 * @property string $url
 * @property string $logoUri
 * @property string $caption
 * @property int ownerId
 * @property int failures
 *
 * @property Carousel[] carousels
 * @property Carousel[] carouselsOnSite
 * @property User $owner
 */
class Client extends CActiveRecord
{
    /** @var CUploadedFile */
    public $_logo;

    /** @var bool */
    public $_removeLogoFlag;

    /**
     * if error count of getting client feed file more than this, client excludes from update
     */
    const FAILURES_BOUND = 20;

    /**
     * @static
     * @param string $className
     * @return Client
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return array(
            array('name, feedUrl', 'required'),
            array('name', 'unique'),
            array('url', 'url'),
            array('feedUrl', 'url'),
            array('feedUrl', 'ext.validators.ymlValidator.ValidYml'),
            array('name, feedUrl, caption', 'length', 'max' => 255),
            array('logoUri', 'length', 'max' => 100),
            array('_logo', 'file', 'types' => 'jpg, gif, png', 'allowEmpty' => true),
            array('_removeLogoFlag', 'safe'),
            array('ownerId', 'in', 'allowEmpty' => false, 'range' => EHtml::listData(User::model())),
            array('id, name, feedUrl, caption, ownerId', 'safe', 'on' => 'search'),
        );
    }

    public function relations()
    {
        return array(
            'carousels' => array(self::HAS_MANY, 'Carousel', 'clientId'),
            'carouselsOnSite' => array(self::HAS_MANY, 'Carousel', 'clientId', 'scopes' => 'onSite'),
            'owner' => array(self::BELONGS_TO, 'User', 'ownerId'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'name' => 'Название',
            'url' => 'Ссылка на магазин',
            'feedUrl' => 'URL фида для Яндекс.Маркета',
            '_logo' => 'Логотип',
            '_removeLogoFlag' => 'Удалить логотип',
            'logoUrl' => 'Логотип',
            'logoUri' => 'Логотип',
            'caption' => 'Подпись',
            'ownerId' => 'Владелец',
            'failures' => 'Ошибки',
        );
    }

    public function getLogoUrl()
    {
        if (!empty($this->logoUri)) {
            $us = Yii::app()->unistorage;
            /** @var ImageFile $file */
            $file = $us->getFile($this->logoUri);
            if ($file instanceof \YiiUnistorage\Models\Files\RegularFile) {
                return $file->url;
            }
        }
        return '';
    }

    /**
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getResizedLogoUrl($width, $height)
    {
        if (!empty($this->logoUri)) {
            $us = Yii::app()->unistorage;
            /** @var ImageFile $file */
            $file = $us->getFile($this->logoUri);
            if ($file instanceof ImageFile) {
                $resizedImage = $file->resize(ImageFile::MODE_KEEP_RATIO, $width, $height);
                if ($resizedImage instanceof RegularFile) {
                    return $resizedImage->url;
                }
            }
        }
        return '';
    }

    public function getCategories()
    {
        return TreeHelper::getTreeForDropDownBox(YMLHelper::getCategories($this->getFeedFile()), true);
    }

    /**
     * @param string $newFeedFile
     */
    public function updateFeedFile($newFeedFile)
    {
        $feedCacheKey = $this->getFeedCacheKey();
        $feedFile = Yii::app()->cache->get($feedCacheKey);
        if (file_exists($feedFile)) {
            unlink(($feedFile));
        }
        Yii::app()->cache->set($feedCacheKey, $newFeedFile);
        $this->saveAttributes(['failures' => 0]);
    }

    public function getFeedFile($forceDownload = false)
    {
        $feedCacheKey = $this->getFeedCacheKey();
        $feedFile = Yii::app()->cache->get($feedCacheKey);
        if ($feedFile === false || !file_exists($feedFile) || $forceDownload) {
            $feedFile = tempnam(Yii::app()->getRuntimePath(), 'yml');
            CurlHelper::downloadToFile($this->feedUrl, $feedFile, [
                    CURLOPT_CONNECTTIMEOUT => 20
                ]);
            $this->updateFeedFile($feedFile);
        }

        return $feedFile;
    }

    /**
     * @return string
     */
    public function getFeedCacheKey()
    {
        $feedCacheKey = 'feedFile' . $this->feedUrl;
        return $feedCacheKey;
    }

    protected function afterDelete()
    {
        try {
            $feedFile = $this->getFeedFile();
            if (file_exists($feedFile)) {
                unlink($feedFile);
            }
        } catch (Exception $e) {
        }

        /** @var $carousel Carousel */
        foreach ($this->carousels as $carousel) {
            $carousel->delete();
        }
    }

    public function search()
    {
        $criteria = new CDbCriteria;

        $criteria->compare('name', $this->name, true);
        $criteria->compare('url', $this->url, true);
        $criteria->compare('feedUrl', $this->feedUrl, true);
        $criteria->compare('caption', $this->caption, true);
        $criteria->compare('ownerId', $this->ownerId);

        return new CActiveDataProvider(
            'Client', array(
                'criteria' => $criteria,
                'pagination' => array(
                    'pageSize' => 30,
                ),
            )
        );
    }

    protected function afterSave()
    {
        parent::afterSave();
        /** @var $carousel Carousel */
        foreach ($this->carousels as $carousel) {
            $carousel->invalidate();
        }
    }

    public function defaultScope()
    {
        $t = $this->getTableAlias(false, false);
        return array(
            'order' => $t . '.name',
        );
    }

    public function mine()
    {
        $this->getDbCriteria()->mergeWith(
            array(
                'condition' => 'ownerId = :ownerId',
                'params' => array(
                    ':ownerId' => Yii::app()->user->getId(),
                ),
            )
        );

        return $this;
    }

    public function onlyValid()
    {
        $this->getDbCriteria()->mergeWith(
            array(
                'condition' => 'failures < :failuresBound',
                'params' => array(
                    ':failuresBound' => self::FAILURES_BOUND,
                ),
            )
        );

        return $this;
    }
}