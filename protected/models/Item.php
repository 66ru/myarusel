<?php
use YiiUnistorage\Models\Files\ImageFile;
use Unistorage\Models\Files\RegularFile;

/**
 * @property int id
 * @property string title
 * @property string price
 * @property string url
 * @property string imageUri
 * @property string ymlId
 * @property int status
 * @property int carouselId
 */
class Item extends CActiveRecord
{
    const STATUS_HIDDEN = 0;
    const STATUS_VISIBLE = 1;

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
            array('id', 'numerical', 'integerOnly' => true, 'allowEmpty' => false, 'on' => 'update'),
            array('title', 'length', 'max' => 255, 'allowEmpty' => false),
            array('url', 'url', 'allowEmpty' => false),
            array('price, imageUri', 'length', 'max' => 100, 'allowEmpty' => false),
            array('ymlId', 'length', 'max' => 32, 'allowEmpty' => false), // 32 due missing id => md5(name) {see: YMLHelper::gatherOfferProperties}
            array('status', 'in', 'range' => [self::STATUS_HIDDEN, self::STATUS_VISIBLE], 'allowEmpty' => false),
            array('carouselId', 'in', 'allowEmpty' => false, 'range' => EHtml::cachedListData(Carousel::model())),
        );
    }

    public function scopes()
    {
        $t = $this->getTableAlias(false, false);

        return [
            'onSite' => [
                'condition' => "$t.imageUri != '' AND $t.url != '' AND $t.status = :status",
                'params' => [
                    ':status' => Item::STATUS_VISIBLE,
                ]
            ],
        ];
    }

    /**
     * @param int $carouselId
     * @return $this
     */
    public function byCarousel($carouselId)
    {
        $t = $this->tableAlias;
        $this->dbCriteria->mergeWith(array(
                'condition' => $t.'.carouselId = :carouselId',
                'params' => [
                    ':carouselId' => $carouselId,
                ]
            ));

        return $this;
    }

    public function getImageUrl()
    {
        if (!empty($this->imageUri)) {
            $us = Yii::app()->unistorage;
            /** @var ImageFile $file */
            $file = $us->getFile($this->imageUri);
            if ($file instanceof RegularFile) {
                return $file->url;
            }
        }

        return '';
    }

    /**
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getResizedImageUrl($width, $height)
    {
        if (!empty($this->imageUri)) {
            $us = Yii::app()->unistorage;
            /** @var ImageFile $file */
            $file = $us->getFile($this->imageUri);
            if ($file instanceof ImageFile) {
                $resizedFile = $file->resize(ImageFile::MODE_KEEP_RATIO, $width, $height);
                if ($resizedFile instanceof ImageFile || $resizedFile instanceof \Unistorage\Models\Files\TemporaryFile) {
                    return $resizedFile->url;
                }
            }
        }

        return '';
    }
}
