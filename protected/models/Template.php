<?php

/**
 * Class Template
 *
 * @property int id
 * @property string name
 * @property string html
 * @property array variables
 * @property int logoWidth
 * @property int logoHeight
 * @property int itemWidth
 * @property int itemHeight
 */
class Template extends CActiveRecord
{
    /**
     * @param string $className
     * @return Template
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function behaviors()
    {
        return [
            'SerializedFieldsBehavior' => [
                'class' => 'application.components.SerializedFieldsBehavior',
                'serializedFields' => ['variables'],
            ],
        ];
    }

    public function rules()
    {
        return [
            ['name, logoWidth, logoHeight, itemWidth, itemHeight', 'required'],
            ['name', 'length', 'max' => 255],
            ['html, variables', 'safe'],
            ['logoWidth, logoHeight, itemWidth, itemHeight', 'numerical', 'integerOnly' => true, 'min' => 1],
        ];
    }

    public function attributeLabels()
    {
        return array(
            'name' => 'Название',
            'html' => 'Код',
            'variables' => 'Js переменные',
            'logoWidth' => 'Ширина логотипа',
            'logoHeight' => 'Высота логотипа',
            'itemWidth' => 'Ширина изображения товара',
            'itemHeight' => 'Высота изображения товара',
        );
    }

    public function search()
    {
        $criteria = new CDbCriteria;

        $criteria->compare('name', $this->name, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }
}