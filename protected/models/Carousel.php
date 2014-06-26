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
 * @property string template
 * @property int ownerId
 * @property int status
 *
 * @property Client $client
 * @property array $items
 * @property User $owner
 * @property array $thumbSize
 * @property array $logoSize
 *
 * @method Carousel orderDefault()
 * @method Carousel onSite()
 */
class Carousel extends CActiveRecord
{
    const INVALIDATE_KEY = 'invalidateCarousel';

    const VIEW_ALL_IN_CATEGORIES = 0;
    const VIEW_ONLY_CHEAP = 1;
    const VIEW_USE_GROUPS = 2;
    const VIEW_ALL = 3;

    const TEMPLATE_VERTICAL = 'vertical';
    const TEMPLATE_HORIZONTAL = 'horizontal';
    const TEMPLATE_NARROWHORIZONTAL = 'narrowhorizontal';
    const TEMPLATE_ORANGEHORIZONTAL = 'orangehorizontal';

    const THUMB_SIZE_ATTR = 'thumbSize';
    const LOGO_SIZE_ATTR = 'logoSize';

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
        $this->attachEventHandler('onAfterDelete', array('EHtml', 'invalidateCache'));
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
            self::VIEW_ONLY_CHEAP => 'Показывать только дешевые товары, по одному из каждой рубрики',
            self::VIEW_USE_GROUPS => 'Группировать товары по рубрикам',
        );
    }

    public static function getTemplates()
    {
        return array(
            self::TEMPLATE_HORIZONTAL => 'Горизонтальный',
            self::TEMPLATE_ORANGEHORIZONTAL => 'Горизонтальный с оранжевой рамкой',
            self::TEMPLATE_VERTICAL => 'Вертикальный',
            self::TEMPLATE_NARROWHORIZONTAL => 'Горизонтальный узкий',
        );
    }

    public static function getTemplateAttributes()
    {
        return array(
            self::TEMPLATE_HORIZONTAL => array(
                self::THUMB_SIZE_ATTR => array(90, 90),
                self::LOGO_SIZE_ATTR => array(120, 120),
            ),
            self::TEMPLATE_ORANGEHORIZONTAL => array(
                self::THUMB_SIZE_ATTR => array(90, 90),
                self::LOGO_SIZE_ATTR => array(120, 120),
            ),
            self::TEMPLATE_VERTICAL => array(
                self::THUMB_SIZE_ATTR => array(86, 86),
                self::LOGO_SIZE_ATTR => array(220, 150),
            ),
            self::TEMPLATE_NARROWHORIZONTAL => array(
                self::THUMB_SIZE_ATTR => array(70, 70),
                self::LOGO_SIZE_ATTR => array(125, 125),
            ),
        );
    }

    public function getThumbSize()
    {
        $templateAttributes = self::getTemplateAttributes();
        return $templateAttributes[$this->template][self::THUMB_SIZE_ATTR];
    }

    public function getLogoSize()
    {
        $templateAttributes = self::getTemplateAttributes();
        return $templateAttributes[$this->template][self::LOGO_SIZE_ATTR];
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
            array('name, clientId, status', 'required'),
            array('onPage', 'numerical', 'integerOnly' => true, 'min' => 0),
            array('urlPrefix', 'url'),
            array('urlPostfix', 'length', 'max' => 255),
            array('viewType', 'in', 'range' => array_keys(self::getViewTypes())),
            array('template', 'in', 'range' => array_keys(self::getTemplates())),
            array('status', 'in', 'range' => array_keys($this->getStatusList())),
            array('clientId', 'in', 'range' => EHtml::listData(Client::model())),
            array('ownerId', 'in', 'allowEmpty' => false, 'range' => EHtml::listData(User::model())),
            array('categories', 'safe'),
            array('name, clientId, ownerId', 'safe', 'on' => 'search'),
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
            'urlPostfix' => 'Постфикс ссылки',
            'viewType' => 'Формат отображения',
            'template' => 'Шаблон',
            'status' => 'Статус',
            'onPage' => 'Позиций в блоке',
        );
    }

    public function scopes()
    {
        $t = $this->getTableAlias(false, false);
        return [
            'orderDefault' => [
                'order' => $t . '.name',
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
        /** @var FileSystem $fs */
        $fs = Yii::app()->getComponent('fs');
        $carouselPath = $fs->storagePath . '/' . $fs->getIntermediateCarouselPath($this->id);
        foreach (glob($carouselPath . '/*') as $file) {
            unlink($file);
        }

        Yii::app()->setGlobalState($this->getInvalidateKey(), null);
    }
}
