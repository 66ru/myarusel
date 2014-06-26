<?php

/**
 * @property int id
 * @property string title
 * @property string price
 * @property string url
 * @property string imageUid
 * @property int carouselId
 */
class Item extends CActiveRecord
{
    /**
     * @static
     * @param string $className
     * @return Item
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return array(
            array('title', 'length', 'max' => 255, 'allowEmpty' => false),
            array('url', 'url', 'allowEmpty' => false),
            array('imageUid, price', 'length', 'max' => 100, 'allowEmpty' => false),
            array('carouselId', 'in', 'allowEmpty' => false, 'range' => EHtml::cachedListData(Carousel::model())),
        );
    }

    public function scopes()
    {
        $t = $this->getTableAlias(false, false);

        return array(
            'onSite' => array(
                'condition' => $t . '.imageUid != "" AND ' . $t . '.url != ""',
            ),
        );
    }

    public function getImageUrl()
    {
        if (!empty($this->imageUid)) {
            /** @var $fs FileSystem */
            $fs = Yii::app()->fs;
            if (file_exists($fs->getCarouselFilePath($this->carouselId, $this->imageUid))) {
                return $fs->getCarouselFileUrl($this->carouselId, $this->imageUid);
            } else {
                return $fs->getFileUrl($this->imageUid);
            }
        } else {
            return '';
        }
    }

    /**
     * @param array $sizes array(width, height)
     * @return string
     */
    public function getResizedImageUrl($sizes)
    {
        $width = $sizes[0];
        $height = $sizes[1];
        if (!empty($this->imageUid)) {
            /** @var $fs FileSystem */
            $fs = Yii::app()->fs;
            if (file_exists(
                $fs->getResizedCarouselImagePath(
                    $this->carouselId,
                    $this->imageUid,
                    $width,
                    $height
                )
            )) {
                return $fs->getResizedCarouselImageUrl(
                    $this->carouselId,
                    $this->imageUid,
                    $width,
                    $height
                );
            } else {
                return $fs->getResizedImageUrl($this->imageUid, $width, $height);
            }
        } else {
            return '';
        }
    }
}
