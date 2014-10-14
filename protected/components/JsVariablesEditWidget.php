<?php

class JsVariablesEditWidget extends CInputWidget
{
    /**
     * @var Template
     */
    public $model;

    /**
     * internal
     * @var TbActiveForm
     */
    public $form;

    /**
     * internal
     * @var mixed
     */
    public $rowOptions;

    public function run()
    {
        echo '<div class="control-group">';
        echo $this->form->labelEx(
            $this->model,
            $this->attribute,
            array(
                'class' => 'control-label'
            )
        );

        $model = $this->model;
        $attribute = $this->attribute;
        if (is_array($model->$attribute)) {
            foreach ($model->$attribute as $entry) {
                echo $this->getRowTemplate($entry);
            }
        }

        echo '<div class="controls js-button-' . $this->id . '">';
        $this->widget(
            'bootstrap.widgets.TbButton',
            array(
                'label' => 'Добавить переменную',
                'icon' => 'plus',
            )
        );
        echo "</div>";
        $this->register();

        echo "</div>";
    }

    protected function register()
    {
        /** @var $clientScript CClientScript */
        $clientScript = Yii::app()->clientScript;

        $clientScript->registerScript(
            __CLASS__ . $this->id,
            '$("div.js-button-' . $this->id . ' a").bind("click", function() {
                var $row = $(' . json_encode($this->getRowTemplate()) . ').insertBefore($(this).closest("div.controls"));
                $row.show();
		    });

		    $("button.remove-button-' . $this->id . '").live("click", function() {
		       $(this).parent().remove();
		    });
            '
        );
        $clientScript->registerCss(
            __CLASS__ . $this->id,
            ".controls-line-{$this->id} {
                margin-bottom: 1em;
            }

            .remove-button-{$this->id} {
                margin-left: 20px;
            }"
        );
    }

    /**
     * @param null $entry
     * @return string
     */
    protected function getRowTemplate($entry = null)
    {
        $rowTemplate = '<div class="controls controls-row controls-line-' . $this->id . '">';
        $fieldName = CHtml::resolveName($this->model, $this->attribute);
        $rowTemplate .= CHtml::textField(
            $fieldName . '[name][]',
            !empty($entry['name']) ? $entry['name'] : '',
            array(
                'placeholder' => 'Имя переменной',
                'class' => 'span2',
            )
        );
        $rowTemplate .= CHtml::textField(
            $fieldName . '[label][]',
            !empty($entry['label']) ? $entry['label'] : '',
            array(
                'id' => false,
                'placeholder' => 'Название',
                'class' => 'span3',
            )
        );

        $rowTemplate .= $this->widget(
            'bootstrap.widgets.TbButton',
            array(
                'icon' => 'remove',
                'label' => 'удалить',
                'buttonType' => 'button',
                'htmlOptions' => array(
                    'class' => 'remove-button-' . $this->id,
                ),
            ),
            true
        );
        $rowTemplate .= "</div>";

        return $rowTemplate;
    }
}