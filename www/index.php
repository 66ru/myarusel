<?php

$params=require(dirname(__FILE__).'/../protected/config/params.php');
defined('YII_DEBUG') or define('YII_DEBUG', $params['yiiDebug']);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

$yii=dirname(__FILE__).'/../lib/yii/framework/yii.php';
$config=dirname(__FILE__).'/../protected/config/main.php';

require_once($yii);
Yii::createWebApplication($config)->run();
