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
			array('title', 'length', 'max'=>255, 'allowEmpty' => false),
			array('url', 'url', 'allowEmpty' => false),
			array('imageUid, price', 'length', 'max'=>100, 'allowEmpty' => false),
			array('carouselId', 'in', 'allowEmpty' => false, 'range'=>EHtml::listData(Carousel::model())),
		);
	}

	public function scopes()
	{
		return array(
			'onSite' => array(
				'condition' => $this->getTableAlias().'.imageUid != "" AND '.$this->getTableAlias().'.url != ""',
			),
		);
	}

	public function getImageUrl(){
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
	 * @param null $master Image::NONE, Image::AUTO, Image::WIDTH, Image::HEIGHT
	 * @return string
	 */
	public function getResizedImageUrl($sizes, $master = null) {
		$width = $sizes[0];
		$height = $sizes[1];
		if (!empty($this->imageUid)) {
			/** @var $fs FileSystem */
			$fs = Yii::app()->fs;
            if (file_exists($fs->getResizedCarouselImagePath($this->imageUid, $this->carouselId, array($width, $height, $master)))) {
                return $fs->getResizedCarouselImageUrl(
                    $this->imageUid,
                    $this->carouselId,
                    array($width, $height, $master)
                );
            } else {
                return $fs->getResizedImageUrl($this->imageUid, array($width, $height, $master));
            }
		} else {
			return '';
		}
	}
}
