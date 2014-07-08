<?php

class ConsoleCommand extends CConsoleCommand
{
    public function log($text)
    {
        if (YII_DEBUG && Yii::app() instanceof CConsoleApplication) {
            echo date('r') . ": " . $text . "\n";
        }
    }
}