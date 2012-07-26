<?php

/**
 * This is the model class for table "Client".
 *
 * The followings are the available columns in table 'Client':
 * @property string $id
 * @property string $name
 * @property string $feedUrl
 * @property string $logoUid
 * @property string $caption
 * @property string $color
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
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	public function rules()
	{
		return array(
			array('name', 'required'),
			array('name', 'unique'),
			array('feedUrl', 'url'),
			array('name, feedUrl, caption, logoUid', 'length', 'max'=>255),
			array('_logo', 'file', 'types'=>'jpg, gif, png', 'allowEmpty' => true),
			array('_removeLogoFlag', 'safe'),

			array('color', 'numerical', 'integerOnly'=>true, 'on'=>'save'),
			array('color', 'length', 'max'=>6, 'on'=>'edit'),

			array('id, name, feedUrl, logoUid, caption, color', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
		);
	}

	public function attributeLabels()
	{
		return array(
			'name' => 'Название',
			'feedUrl' => 'URL фида для Яндекс.Маркета',
			'_logo' => 'Логотип',
			'_removeLogoFlag' => 'Удалить логотип',
			'logoUrl' => 'Логотип',
			'caption' => 'Подпись',
			'color' => 'Цвет',
		);
	}

	public function getLogoUrl() {
		if (!empty($this->logoUid)) {
			/** @var $fs FileSystem */
			$fs = Yii::app()->fs;
			return $fs->getFileUrl($this->logoUid);
		} else {
			return '';
		}
	}

	public function getResizedLogoUrl($width, $height, $master = NULL) {
		if (!empty($this->logoUid)) {
			/** @var $fs FileSystem */
			$fs = Yii::app()->fs;
			return $fs->getResizedImageUrl($this->logoUid, array($width, $height, $master));
		} else {
			return '';
		}
	}

	protected function afterDelete()
	{
		/** @var $fs FileSystem */
		$fs = Yii::app()->fs;
		$fs->removeFile($this->logoUid);
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('name',$this->name,true);
		$criteria->compare('feedUrl',$this->feedUrl,true);
		$criteria->compare('caption',$this->caption,true);

		return new CActiveDataProvider('Client', array(
			'criteria'=>$criteria,
		));
	}
}