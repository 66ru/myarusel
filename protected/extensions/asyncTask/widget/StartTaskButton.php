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

    public function run()
    {
        $encodedWaitingLabel = json_encode($this->waitingLabel);
        $encodedLabel = json_encode($this->label);
        $this->widget('bootstrap.widgets.TbButton', CMap::mergeArray([
                'buttonType' => 'ajaxButton',
                'label' => $this->label,
                'url' => CHtml::normalizeUrl($this->startTaskRoute),
                'ajaxOptions' => [
                    'context' => new CJavaScriptExpression('this'),
                    'complete' => 'function() {
                        $(this).html('.$encodedWaitingLabel.').attr("disabled", "disabled");
                    }',
                    'success' => 'function(data) {
                        var $button = $(this);
                        var taskId = data.taskId;
                        var checkStatus = function() {
                            $.getJSON("'.CHtml::normalizeUrl($this->taskStatusRoute).'", { taskId: taskId }, function(task) {
                                if (task.status == 1) {
                                    $button.text('.$encodedLabel.').removeAttr("disabled");
                                    ('.$this->doneAction.')($button, task.result);
                                } else if (task.status == 0) {
                                    setTimeout(checkStatus, '.$this->checkStatusTimeout.');
                                }
                            })
                        };

                        setTimeout(checkStatus, '.$this->checkStatusTimeout.');
                    }',
                ],
            ], $this->buttonProperties));
    }
}