<?php

require_once(__DIR__ . '/../vendor/autoload.php');

//if (extension_loaded('xhprof')) {
//    if (!empty($_GET['run']) && !empty($_GET['source'])) {
//        require __DIR__ . '/../vendor/facebook/xhprof/xhprof_html/index.php';
//        exit;
//    }
//    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
//
//    register_shutdown_function(function() {
//        $profiler_namespace = 'webmail-multi';  // namespace for your application
//        $xhprof_data = xhprof_disable();
//        $xhprof_runs = new XHProfRuns_Default();
//        $run_id = $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
//
//        // url to the XHProf UI libraries (change the host name and path)
//        $profiler_url = sprintf('/index.php?run=%s&amp;source=%s', $run_id, $profiler_namespace);
//        echo '<a style="position:relative" href="'. $profiler_url .'" target="_blank">Profiler output</a>';
//    });
//}

$params = require(dirname(__FILE__) . '/../protected/config/params.php');
defined('YII_DEBUG') or define('YII_DEBUG', $params['yiiDebug']);
if (YII_DEBUG) ini_set('display_errors', '1');
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL', 3);

$yii = __DIR__ . '/../vendor/yiisoft/yii/framework/' . (YII_DEBUG ? 'yii.php' : 'yiilite.php');
$config = dirname(__FILE__) . '/../protected/config/main.php';

require_once($yii);
Yii::createWebApplication($config)->run();
