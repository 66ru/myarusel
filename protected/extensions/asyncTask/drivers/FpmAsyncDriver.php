<?php

class FpmAsyncDriver implements IAsyncDriver
{
    /**
     * @param callable $callback must return [int $status, mixed $result].
     * Where $status is one of IAsyncTaskProvider::STATUS_*
     * @return array callable return value: [int $status, mixed $result]
     */
    public function startInBackground($callback)
    {
        fastcgi_finish_request();
        /*
        //header("Connection: close");
        //ignore_user_abort(true);
        //ob_start();
        echo 'Text the user will see';
        //$size = ob_get_length();
        //header("Content-Length: $size");
        //ob_end_flush();
        //flush();
        fastcgi_finish_request();

        file_put_contents(__DIR__.'/1.txt', '1');
        */
        return call_user_func($callback);
    }
}