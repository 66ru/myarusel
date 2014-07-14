<?php

class AsyncTaskGlobalStateProvider implements IAsyncTaskProvider
{
    const TASK_LIST_KEY = 'longTaskList';

    /**
     * @param string $taskId
     * @return string
     */
    public function getGlobalStateKey($taskId)
    {
        return 'longTask-' . $taskId;
    }

    /**
     * @param int $timeout seconds
     * @return string new id
     */
    public function addTask($timeout)
    {
        $taskId = strtr(microtime(), [' ' => '', '.'=>'']);
        $list = Yii::app()->getGlobalState(self::TASK_LIST_KEY, []);
        $list[$taskId] = time() + $timeout;
        Yii::app()->setGlobalState(self::TASK_LIST_KEY, $list);

        $this->removeExpiredTasks();
        return $taskId;
    }

    /**
     * @param string $taskId
     */
    public function removeTask($taskId)
    {
        $list = Yii::app()->getGlobalState(self::TASK_LIST_KEY, []);
        unset($list[$taskId]);
        Yii::app()->setGlobalState(self::TASK_LIST_KEY, $list);
        Yii::app()->clearGlobalState($this->getGlobalStateKey($taskId));
    }

    /**
     * @param string $taskId
     * @param int $status
     * @param mixed $result
     */
    public function updateTask($taskId, $status, $result = null)
    {
        Yii::app()->setGlobalState($this->getGlobalStateKey($taskId), [$status, $result]);
    }

    /**
     * @param string $taskId
     * @return mixed|null [$status, $result]
     */
    public function getTask($taskId)
    {
        return Yii::app()->getGlobalState($this->getGlobalStateKey($taskId), [self::STATUS_NOT_READY, null]);
    }

    /**
     * @return null
     */
    public function removeExpiredTasks()
    {
        $list = Yii::app()->getGlobalState(self::TASK_LIST_KEY, []);
        foreach ($list as $taskId => $expire) {
            if ($expire < time()) {
                unset($list[$taskId]);
                Yii::app()->clearGlobalState($this->getGlobalStateKey($taskId));
            }
        }
        Yii::app()->setGlobalState(self::TASK_LIST_KEY, $list);
    }
}