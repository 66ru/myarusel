<?php

/**
 * @property int id
 * @property string name
 * @property int clientId
 * @property string categories
 *
 * @property Client $client
 * @property array $items
 */
class Carousel extends CActiveRecord
{
	/**
	 * @static
	 * @param string $className
	 * @return Carousel
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	public function behaviors()
	{
		return array(
			'SerializedFieldsBehavior' => array(
				'class' => 'application.components.SerializedFieldsBehavior',
				'serializedFields' => array('categories'),
			),
		);
	}

	public function init()
	{
		$this->scenario = 'save';
	}

	public function rules()
	{
		return array(
			array('name', 'unique'),
			array('name', 'required'),
			array('clientId', 'in', 'allowEmpty' => false, 'range'=>CHtml::listData(Client::model()->findAll(array('select'=>'id')), 'id', 'id')),
			array('categories', 'safe'),

			array('name, clientId', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
			'client' => array(self::BELONGS_TO, 'Client', 'clientId'),
			'items' => array(self::HAS_MANY, 'Item', 'carouselId'),
		);
	}

	public function attributeLabels()
	{
		return array(
			'name' => 'Имя',
			'clientId' => 'Клиент',
			'categories' => 'Категории',
		);
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('name', $this->name, true);
		$criteria->compare('clientId', $this->clientId);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	public function getUrl(){
		return Yii::app()->getBaseUrl(true).CHtml::normalizeUrl(array('/carousel/show', 'id' => $this->id));
	}

	protected function afterDelete()
	{
		parent::afterDelete();
		Item::model()->deleteAllByAttributes(array('carouselId' => $this->id));
	}
}
