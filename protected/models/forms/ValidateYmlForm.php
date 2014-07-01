<?php

class ValidateYmlForm extends CFormModel
{
    public $feedUrl;

    public function rules()
    {
        return [
            ['feedUrl', 'required', 'message' => 'Необходимо заполнить данное поле.'],
            ['feedUrl', 'url', 'message' => 'Введенное значение не является правильным URL.'],
            ['feedUrl', 'ext.validators.ymlValidator.ValidYml'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'feedUrl' => 'URL фида для Яндекс.Маркета',
        ];
    }

    public function formConfig()
    {
        return [
            'elements' => [
                'feedUrl' => [
                    'type' => 'text',
                    'class' => 'span4',
                    'placeholder' => false,
                ]
            ],
            'buttons' => [
                'submit' => [
                    'label' => 'Проверить',
                    'buttonType' => 'submit',
                    'type' => 'primary',
                ]
            ],
        ];
    }
}