<?php

class StartAsyncTaskAction extends CAction
{
    public $asyncTaskComponentName = 'asyncTask';

    /**
     * @var int seconds
     */
    public $timeout = 60;

    /**
     * @var callable
     */
    public $task;

    public function run()
    {
        /** @var AsyncTaskComponent $component */
        $component = Yii::app()->getComponent($this->asyncTaskComponentName);

        $taskId = $component->getProvider()->addTask($this->timeout);

        header('Content-Type: application/json');
        echo json_encode(['taskId' => $taskId]);

        list($status, $result) = $component->getDriver()->startInBackground($this->task);

        $component->getProvider()->updateTask($taskId, $status, $result);
    }
}