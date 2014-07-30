<?php

class StartTaskButton extends CWidget
{
    /**
     * @var string
     */
    public $label = 'Запустить';

    /**
     * @var string
     */
    public $waitingLabel = '<i class=\'icon-time\'></i> выполняется';

    /**
     * @var string
     */
    public $doneAction = 'function($button, result) { console.log(result); }';

    /**
     * @var int milliseconds
     */
    public $checkStatusTimeout = 1000;

    /**
     * @var array
     */
    public $startTaskRoute = ['/site/startTask'];

    /**
     * @var array
     */
    public $taskStatusRoute = ['/site/taskStatus'];

    /**
     * @var array
     */
    public $buttonProperties = [];

    /**
     * @var array
     */
    public $htmlOptions = [];

    public function run()
    {
        $encodedWaitingLabel = json_encode($this->waitingLabel);
        $encodedLabel = json_encode($this->label);
        $encodedStatusRoute = json_encode(CHtml::normalizeUrl($this->taskStatusRoute));
        $buttonClass = 'start-task-button';

        if (isset($this->htmlOptions['class'])) {
            $this->htmlOptions['class'] .= ' ' . $buttonClass;
        } else {
            $this->htmlOptions['class'] = $buttonClass;
        }
        $this->widget('bootstrap.widgets.TbButton', CMap::mergeArray([
                'buttonType' => 'url',
                'label' => $this->label,
                'htmlOptions' => $this->htmlOptions,
                'url' => CHtml::normalizeUrl($this->startTaskRoute),
            ], $this->buttonProperties));

        Yii::app()->clientScript->registerScript(__CLASS__, "
            jQuery('body').on('click', '.$buttonClass', function() {
                jQuery.ajax({
                    context: this,
                    url: $(this).attr('href'),
                    cache: false,
                    complete: function() {
                        $(this).html($encodedWaitingLabel).attr('disabled', 'disabled');
                    },
                    success: function (data) {
                        var \$button = $(this);
                        var taskId = data.taskId;
                        var checkStatus = function() {
                            $.getJSON($encodedStatusRoute, { taskId: taskId }, function(task) {
                                if (task.status == 1) {
                                    \$button.text($encodedLabel).removeAttr('disabled');
                                    ($this->doneAction)(\$button, task.result);
                                } else if (task.status == 0) {
                                    setTimeout(checkStatus, $this->checkStatusTimeout);
                                }
                            })
                            .fail(function() {
                                setTimeout(checkStatus, $this->checkStatusTimeout);
                            });
                        };

                        setTimeout(checkStatus, $this->checkStatusTimeout);
                    },
                })
                return false;
            });
        ");
    }
}