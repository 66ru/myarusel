<?php

class AdminTemplatesController extends CommonAdminController
{
    public $modelName = 'Template';
    public $modelHumanTitle = array('шаблон', 'шаблона', 'шаблонов');

    /**
     * @param Template $model
     * @return array
     */
    public function getEditFormElements($model)
    {
        return [
            'name' => [
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ],
            'html' => [
                'type' => 'textArea',
                'htmlOptions' => [
                    'placeholder' => false,
                    'style' => 'height: 500px',
                    'class' => 'span9',
                ],
            ],
            'logoWidth' => [
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                ),
            ],
            'logoHeight' => [
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                ),
            ],
            'itemWidth' => [
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                ),
            ],
            'itemHeight' => [
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                ),
            ],
            'variables' => [
                'class' => 'application.components.JsVariablesEditWidget',
            ],
        ];
    }

    /**
     * @param Template $model
     * @return array
     */
    public function getTableColumns($model)
    {
        return [
            [
                'name' => 'name',
            ],
            $this->getButtonsColumn(),
        ];
    }

    public function beforeSetAttributes($model, &$attributes)
    {
        ConstHelper::reformatHtmlFields($attributes, 'variables');

        return parent::beforeSetAttributes($model, $attributes);
    }
}