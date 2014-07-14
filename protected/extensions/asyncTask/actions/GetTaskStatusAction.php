<?php

class GetTaskStatusAction extends CAction
{
    public $asyncTaskComponentName = 'asyncTask';

    public function run()
    {
        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException(400);
        }

        /** @var AsyncTaskComponent $component */
        $component = Yii::app()->getComponent($this->asyncTaskComponentName);
        if (!$component) {
            throw new CException("Component $this->asyncTaskComponentName doesn't exists");
        }

        header('Content-Type: application/json');
        $task = $component->provider->getTask($_GET['taskId']);
        if ($task[0] == IAsyncTaskProvider::STATUS_DONE) {
            $component->provider->removeTask($_GET['taskId']);
        }
        echo json_encode(['status' => $task[0], 'result' => $task[1]]);
        Yii::app()->end();
    }
} 