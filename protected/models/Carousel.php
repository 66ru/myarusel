<?php

/**
 * @property int id
 * @property string name
 * @property int clientId
 * @property int onPage
 * @property string categories
 * @property string urlPrefix
 * @property int viewType
 * @property string template
 * @property int ownerId
 *
 * @property Client $client
 * @property array $items
 * @property User $owner
 * @property array $thumbSize
 * @property array $logoSize
 */
class Carousel extends CActiveRecord
{
	const INVALIDATE_KEY = 'invalidateCarousel';

	const VIEW_ALL = 0;
	const VIEW_ONLY_CHEAP = 1;
	const VIEW_USE_GROUPS = 2;

	const TEMPLATE_VERTICAL = 'vertical';
	const TEMPLATE_HORIZONTAL = 'horizontal';

	const THUMB_SIZE_ATTR = 'thumbSize';
	const LOGO_SIZE_ATTR = 'logoSize';

	public static function getViewTypes(){
		return array(
			self::VIEW_ALL => 'Показывать все товары',
			self::VIEW_ONLY_CHEAP => 'Показывать только дешевые товары, по одному из каждой рубрики',
			self::VIEW_USE_GROUPS => 'Группировать товары по рубрикам',
		);
	}

	public static function getTemplates(){
		return array(
			self::TEMPLATE_HORIZONTAL => 'Горизонтальный',
			self::TEMPLATE_VERTICAL => 'Вертикальный',
		);
	}

	public static function getTemplateAttributes(){
		return array(
			self::TEMPLATE_HORIZONTAL => array(
				self::THUMB_SIZE_ATTR => array(90, 90),
				self::LOGO_SIZE_ATTR => array(120 ,120),
			),
			self::TEMPLATE_VERTICAL => array(
				self::THUMB_SIZE_ATTR => array(86, 86),
				self::LOGO_SIZE_ATTR => array(220, 150),
			),
		);
	}

	public function getThumbSize() {
		$templateAttributes = self::getTemplateAttributes();
		return $templateAttributes[$this->template][self::THUMB_SIZE_ATTR];
	}

	public function getLogoSize() {
		$templateAttributes = self::getTemplateAttributes();
		return $templateAttributes[$this->template][self::LOGO_SIZE_ATTR];
	}

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
			array('onPage', 'numerical', 'integerOnly'=>true, 'min'=>0),
			array('urlPrefix', 'url'),
			array('viewType', 'in', 'range'=>array_keys(self::getViewTypes())),
			array('template', 'in', 'range'=>array_keys(self::getTemplates())),
			array('clientId', 'in', 'range'=>EHtml::listData(Client::model())),
			array('ownerId', 'in', 'allowEmpty' => false, 'range'=>EHtml::listData(User::model())),
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
			'urlPrefix' => 'Префикс ссылки',
			'viewType' => 'Формат отображения',
			'template' => 'Шаблон',
			'onPage' => 'Позиций в блоке',
		);
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('name', $this->name, true);
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
