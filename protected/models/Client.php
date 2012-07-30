<?php

/**
 * This is the model class for table "Client".
 *
 * The followings are the available columns in table 'Client':
 * @property string $id
 * @property string $name
 * @property string $feedUrl
 * @property string $url
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
			array('name, feedUrl', 'required'),
			array('name', 'unique'),
			array('url', 'url'),
			array('feedUrl', 'url'),
			array('feedUrl', 'ext.xmlValidator.ValidXml'),
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
			'url' => 'Ссылка на магазин',
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

	public function getCategories() {
		return TreeHelper::getTreeForDropDownBox( YMLHelper::getCategories($this->getFeedFile()), true );
	}

	public function getFeedFile($forceDownload = false) {
		$feedFile = Yii::app()->cache->get('feedFile'.$this->feedUrl);
		if ($feedFile === false || !file_exists($feedFile) || $forceDownload) {
			if (file_exists($feedFile))
				unlink(($feedFile));
			$feedFile = tempnam(Yii::app()->getRuntimePath(), 'yml');
			CurlHelper::downloadToFile($this->feedUrl, $feedFile);
			Yii::app()->cache->set('feedFile'.$this->feedUrl, $feedFile, 3600*24);
		}

		return $feedFile;
	}

	protected function afterDelete()
	{
		/** @var $fs FileSystem */
		$fs = Yii::app()->fs;
		if (!empty($this->logoUid))
			$fs->removeFile($this->logoUid);

		Carousel::model()->deleteAllByAttributes(array('clientId'=>$this->id));
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('name',$this->name,true);
		$criteria->compare('url',$this->feedUrl,true);
		$criteria->compare('feedUrl',$this->feedUrl,true);
		$criteria->compare('caption',$this->caption,true);

		return new CActiveDataProvider('Client', array(
			'criteria'=>$criteria,
		));
	}
}