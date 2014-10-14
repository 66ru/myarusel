<?php

/**
 * @property int id
 * @property string name
 * @property int clientId
 * @property int onPage
 * @property string categories
 * @property string urlPrefix
 * @property string urlPostfix
 * @property int viewType
 * @property int templateId
 * @property int ownerId
 * @property int status
 * @property array variables
 *
 * @property Client $client
 * @property Template template
 * @property Item[] $items
 * @property Item[] $onSiteItems
 * @property User $owner
 * @property array $thumbSize
 * @property array $logoSize
 *
 * @method Carousel orderDefault()
 * @method Carousel orderById()
 * @method Carousel onSite()
 */
class Carousel extends CActiveRecord
{
    const INVALIDATE_KEY = 'invalidateCarousel';

    const VIEW_ALL_IN_CATEGORIES = 0; // all items in selected categories
    const VIEW_CHEAP_IN_CATEGORIES = 1; // cheap items one from each category
    const VIEW_GROUP_BY_CATEGORIES = 2; // random image from category, custom label
    const VIEW_ALL = 3; // all items

    const STATUS_DISABLED = 0;
    const STATUS_ACTIVE = 1;

    /**
     * @static
     * @param string $className
     * @return Carousel
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function init()
    {
        parent::init();

        $this->attachEventHandler('onAfterSave', array('EHtml', 'invalidateCache'));
        $this->attachEventHandler('onAfterDelete', array($this, 'cleanUp'));
        $this->attachEventHandler('onAfterSave', array($this, 'invalidate'));
    }

    public function getStatusList()
    {
        return [
            self::STATUS_DISABLED => 'отключен',
            self::STATUS_ACTIVE => 'активен',
        ];
    }

    public function getStatusText($status = null)
    {
        $statusList = $this->getStatusList();
        if (!is_null($status) && array_key_exists($status, $statusList)) {
            return $statusList[$status];
        } elseif (array_key_exists($this->status, $statusList)) {
            return $statusList[$this->status];
        } else {
            return null;
        }
    }

    public static function getViewTypes()
    {
        return array(
            self::VIEW_ALL_IN_CATEGORIES => 'Показывать все товары в выбранных категориях',
            self::VIEW_ALL => 'Показывать все товары',
            self::VIEW_CHEAP_IN_CATEGORIES => 'Показывать только дешевые товары, по одному из каждой рубрики',
            self::VIEW_GROUP_BY_CATEGORIES => 'Группировать товары по рубрикам',
        );
    }

    public function behaviors()
    {
        return [
            'SerializedFieldsBehavior' => [
                'class' => 'application.components.SerializedFieldsBehavior',
                'serializedFields' => ['categories', 'variables'],
            ],
        ];
    }

    public function rules()
    {
        return array(
            array('name', 'unique'),
            array('name, clientId, status', 'required'),
            array('urlPrefix', 'url'),
            array('urlPostfix', 'length', 'max' => 255),
            array('viewType', 'in', 'range' => array_keys(self::getViewTypes())),
            array('templateId', 'in', 'range' => EHtml::listData(Template::model())),
            array('status', 'in', 'range' => array_keys($this->getStatusList())),
            array('clientId', 'in', 'range' => EHtml::listData(Client::model())),
            array('ownerId', 'in', 'allowEmpty' => false, 'range' => EHtml::listData(User::model())),
            array('categories, variables', 'safe'),
            array('name, clientId, ownerId', 'safe', 'on' => 'search'),
        );
    }

    public function relations()
    {
        return array(
            'client' => array(self::BELONGS_TO, 'Client', 'clientId'),
            'items' => array(self::HAS_MANY, 'Item', 'carouselId'),
            'onSiteItems' => array(self::HAS_MANY, 'Item', 'carouselId', 'scopes' => 'onSite'),
            'owner' => array(self::BELONGS_TO, 'User', 'ownerId'),
            'template' => array(self::BELONGS_TO, 'Template', 'templateId'),
        );
    }

    public function attributeLabels()
    {
        return array(
            'name' => 'Имя',
            'clientId' => 'Клиент',
            'ownerId' => 'Владелец',
            'variables' => 'Js переменные',
            'categories' => 'Категории',
            'urlPrefix' => 'Префикс ссылки',
            'urlPostfix' => 'Постфикс ссылки',
            'viewType' => 'Формат отображения',
            'templateId' => 'Шаблон',
            'status' => 'Статус',
        );
    }

    public function scopes()
    {
        $t = $this->getTableAlias(false, false);
        return [
            'orderDefault' => [
                'order' => $t . '.name',
            ],
            'orderById' => [
                'order' => $t . '.id',
            ],
            'onSite' => [
                'condition' => $t . '.status = :status',
                'params' => [
                    ':status' => self::STATUS_ACTIVE,
                ]
            ]
        ];
    }

    public function search()
    {
        $criteria = new CDbCriteria;

        $criteria->compare('name', $this->name, true);
        $criteria->compare('clientId', $this->clientId);
        $criteria->compare('ownerId', $this->ownerId);
        $criteria->compare('status', $this->status);

        return new CActiveDataProvider(
            $this, array(
                'criteria' => $criteria,
                'pagination' => array(
                    'pageSize' => 50,
                ),
            )
        );
    }

    public function getInvalidateKey()
    {
        return self::INVALIDATE_KEY . $this->id;
    }

    public function invalidate()
    {
        Yii::app()->setGlobalState($this->getInvalidateKey(), time());
    }

    public function getUrl()
    {
        return Yii::app()->getBaseUrl(true) . CHtml::normalizeUrl(array('/carousel/show', 'id' => $this->id));
    }

    public function cleanUp()
    {
        parent::afterDelete();
        /** @var $item Item */
        foreach ($this->items as $item) {
            $item->delete();
        }

        Yii::app()->setGlobalState($this->getInvalidateKey(), null);
    }
}
