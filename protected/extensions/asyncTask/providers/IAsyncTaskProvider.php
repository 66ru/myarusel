<?php

interface IAsyncTaskProvider
{
    const STATUS_ERROR = -1;
    const STATUS_NOT_READY = 0;
    const STATUS_DONE = 1;

    /**
     * @param int $timeout seconds
     * @return string new id
     */
    public function addTask($timeout);

    /**
     * @param string $taskId
     */
    public function removeTask($taskId);

    /**
     * @param string $taskId
     * @param int $status
     * @param mixed $result
     */
    public function updateTask($taskId, $status, $result = null);

    /**
     * @param string $taskId
     * @return mixed|null [$status, $result]
     */
    public function getTask($taskId);

    /**
     * @return null
     */
    public function removeExpiredTasks();
}