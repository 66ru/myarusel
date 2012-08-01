<?php

/**
 * @property int id
 * @property string name
 * @property int clientId
 * @property string categories
 * @property bool onlyCheap
 * @property int ownerId
 *
 * @property Client $client
 * @property array $items
 * @property User $owner
 */
class Carousel extends CActiveRecord
{
	const INVALIDATE_KEY = 'invalidateCarousel';

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

	public function rules()
	{
		return array(
			array('name', 'unique'),
			array('name, clientId', 'required'),
			array('onlyCheap', 'boolean'),
			array('clientId', 'in', 'range'=>CHtml::listData(Client::model()->findAll(array('select'=>'id')), 'id', 'id')),
			array('ownerId', 'in', 'range'=>CHtml::listData(User::model()->findAll(array('select'=>'id')), 'id', 'id')),
			array('categories', 'safe'),

			array('name, clientId, ownerId', 'safe', 'on'=>'search'),
		);
	}

	public function relations()
	{
		return array(
			'client' => array(self::BELONGS_TO, 'Client', 'clientId'),
			'items' => array(self::HAS_MANY, 'Item', 'carouselId'),
			'owner' => array(self::BELONGS_TO, 'User', 'ownerId'),
		);
	}

	public function attributeLabels()
	{
		return array(
			'name' => 'Имя',
			'clientId' => 'Клиент',
			'ownerId' => 'Владелец',
			'categories' => 'Категории',
			'onlyCheap' => 'Показывать только дешевые товары, по одному из каждой рубрики',
		);
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('name', $this->name, true);
		$criteria->compare('onlyCheap', $this->onlyCheap);
		$criteria->compare('clientId', $this->clientId);
		$criteria->compare('ownerId', $this->ownerId);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}

	public function getInvalidateKey(){
		return self::INVALIDATE_KEY.$this->id;
	}

	public function invalidate(){
		Yii::app()->setGlobalState($this->getInvalidateKey(), time());
	}

	public function getUrl(){
		return Yii::app()->getBaseUrl(true).CHtml::normalizeUrl(array('/carousel/show', 'id' => $this->id));
	}

	protected function afterDelete()
	{
		parent::afterDelete();
		/** @var $item Item */
		foreach($this->items as $item) {
			$item->delete();
		}

		Yii::app()->setGlobalState($this->getInvalidateKey(), null);
	}

	protected function afterSave()
	{
		parent::afterSave();

		$this->invalidate();
	}

}
