<?php

class TemplateVariablesEditWidget extends CInputWidget
{
    /**
     * internal
     * @var TbActiveForm
     */
    public $form;

    public $templateIdAttribute;

    public function run()
    {
        echo '<div id="'.$this->id.'"></div>';

        $name = CHtml::activeName($this->model, $this->attribute);
        $nameEncoded = json_encode($name);

        $templateVariables = [];
        $templates = Template::model()->findAll();
        /** @var Template[] $templates */
        foreach ($templates as $template) {
            $templateVariables[$template->id] = $template->variables;
        }
        $templateVariablesEncoded = json_encode($templateVariables);

        $variables = $this->model->variables;
        $variablesEncoded = json_encode($variables);

        $templateIdAnchor = CHtml::activeId($this->model, $this->templateIdAttribute);
        Yii::app()->clientScript->registerScript(__CLASS__, "
            var rowTemplate = '<div class=\"control-group\"><label class=\"control-label\"></label>\
            <div class=\"controls\"><input class=\"span4\" name=\"\" type=\"text\" maxlength=\"255\" value=\"\"></div></div>';

            var rootName = $nameEncoded;
            var templatesVariables = $templateVariablesEncoded;
            var variables = $variablesEncoded;

            $('#$templateIdAnchor').on('click', function() {
                $('#{$this->id}').html('');
                var variableVariables = templatesVariables[$(this).val()];
                if (variableVariables) {
                    for(var i in variableVariables) {
                        \$row = $(rowTemplate);
                        \$row.find('label').text(variableVariables[i].label);
                        \$row.find('input').attr('name', rootName + '['+ variableVariables[i].name +']');
                        if (variables[variableVariables[i].name]) {
                            \$row.find('input').val( variables[variableVariables[i].name] );
                        }
                        \$row.appendTo('#{$this->id}');
                    }
                }
            }).click();
        ");
    }
}