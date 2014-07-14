<?php

interface IAsyncDriver 
{
    /**
     * @param callable $callback must return [int $status, mixed $result].
     * Where $status is one of IAsyncTaskProvider::STATUS_*
     * @return array callable return value: [int $status, mixed $result]
     */
    public function startInBackground($callback);
}