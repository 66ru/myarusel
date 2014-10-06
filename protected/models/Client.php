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
 * @property string $logoUid
 * @property string $logoUri
 * @property string $caption
 * @property string $color
 * @property int ownerId
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
            array('name, feedUrl, caption, logoUid', 'length', 'max' => 255),
            array('logoUri', 'length', 'max' => 100),
            array('_logo', 'file', 'types' => 'jpg, gif, png', 'allowEmpty' => true),
            array('_removeLogoFlag', 'safe'),
            array('ownerId', 'in', 'allowEmpty' => false, 'range' => EHtml::listData(User::model())),
            array('color', 'ext.hexValidator.FHexValidator'),
            array('color', 'length', 'max' => 6),
            array('id, name, feedUrl, logoUid, caption, color, ownerId', 'safe', 'on' => 'search'),
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
            'color' => 'Цвет',
        );
    }

    public function getLogoUrl()
    {
        if (!Yii::app()->params['useUnistorage'] && !empty($this->logoUid)) {
            $fs = Yii::app()->fs;
            return $fs->getFileUrl($this->logoUid);
        } elseif (!empty($this->logoUri)) {
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
     * @param array $sizes array(width, height)
     * @return string
     */
    public function getResizedLogoUrl($sizes)
    {
        $width = $sizes[0];
        $height = $sizes[1];
        if (!Yii::app()->params['useUnistorage'] && !empty($this->logoUid)) {
            $fs = Yii::app()->fs;
            return $fs->getResizedImageUrl($this->logoUid, $width, $height);
        } elseif (!empty($this->logoUri)) {
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

    public function getGradient()
    {
        $topColor = 0xf99f32;
        $bottomColor = 0xf47513;
        $topColorRgb = $bottomColorRgb = $currentColorRgb = array();
        foreach (array('r' => 8 * 2, 'g' => 8, 'b' => 0) as $component => $shift) {
            $topColorRgb[$component] = $topColor >> $shift & 0xFF;
            $bottomColorRgb[$component] = $bottomColor >> $shift & 0xFF;
            if (!empty($this->color)) {
                $currentColorRgb[$component] = hexdec($this->color) >> $shift & 0xFF;
            }
        }

        $outRange = 0;
        if (!empty($this->color)) {
            foreach (array('r', 'g', 'b') as $component) {
                $delta = ($topColorRgb[$component] - $bottomColorRgb[$component]) / 2;
                if ($currentColorRgb[$component] + $delta > 0xFF &&
                    $currentColorRgb[$component] + $delta - 0xFF > abs($outRange)
                ) {
                    $outRange = 0xFF - $currentColorRgb[$component] + $delta;
                }
                if ($currentColorRgb[$component] - $delta < 0 &&
                    abs($currentColorRgb[$component] - $delta) > abs($outRange)
                ) {
                    $outRange = -($currentColorRgb[$component] - $delta);
                }
            }
            foreach (array('r', 'g', 'b') as $component) {
                $delta = ($topColorRgb[$component] - $bottomColorRgb[$component]) / 2;
                $topColorRgb[$component] = round($currentColorRgb[$component] + $delta + $outRange);
                $bottomColorRgb[$component] = round($currentColorRgb[$component] - $delta + $outRange);
            }
        }
        $topColorRgb['hex'] = dechex($topColorRgb['r'] * 0x010000 + $topColorRgb['g'] * 0x000100 + $topColorRgb['b']);
        $bottomColorRgb['hex'] = dechex(
            $bottomColorRgb['r'] * 0x010000 + $bottomColorRgb['g'] * 0x000100 + $bottomColorRgb['b']
        );

        return array($topColorRgb, $bottomColorRgb);
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
        /** @var $fs FileSystem */
        $fs = Yii::app()->fs;
        if (!empty($this->logoUid)) {
            $fs->removeFile($this->logoUid);
        }

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
}