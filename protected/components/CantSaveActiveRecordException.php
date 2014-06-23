<?php

class CantSaveActiveRecordException extends CException
{
    /**
     * @param CActiveRecord $model
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($model, $code = 0, Exception $previous = null)
    {
        parent::__construct('Can\'t save '.get_class($model).': ' . print_r($model->errors, true) .'Attributes: ' . print_r($model->attributes, true), $code, $previous);
    }
}