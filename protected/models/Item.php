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

//	public function init()
//	{
//		$this->scenario = 'save';
//	}

	public function rules()
	{
		return array(
			array('title', 'length', 'max'=>255, 'allowEmpty' => false),
			array('url', 'url', 'allowEmpty' => false),
			array('imageUid, price', 'length', 'max'=>100, 'allowEmpty' => false),
			array('carouselId', 'in', 'allowEmpty' => false, 'range'=>CHtml::listData(Carousel::model()->findAll(array('select'=>'id')), 'id', 'id')),

//			array('name, clientId', 'safe', 'on'=>'search'),
		);
	}

	protected function afterDelete()
	{
		/** @var $fs FileSystem */
		$fs = Yii::app()->fs;
		if (!empty($this->imageUid))
			$fs->removeFile($this->imageUid);
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
			return $fs->getFileUrl($this->imageUid);
		} else {
			return '';
		}

	}
//	public function relations()
//	{
//		return array(
//			'client' => array(self::BELONGS_TO, 'Client', 'clientId'),
//		);
//	}

//	public function attributeLabels()
//	{
//		return array(
//			'name' => 'Имя',
//			'clientId' => 'Клиент',
//			'categories' => 'Категории',
//		);
//	}

//	public function search()
//	{
//		$criteria=new CDbCriteria;
//
//		$criteria->compare('name', $this->name, true);
//		$criteria->compare('clientId', $this->clientId);
//
//		return new CActiveDataProvider($this, array(
//			'criteria' => $criteria,
//		));
//	}
}
