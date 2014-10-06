<?php
// fix for fcgi
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
$parentPid = posix_getppid();
if (file_exists("/proc/$parentPid/cmdline") && strpos(file_get_contents("/proc/$parentPid/cmdline"), 'yiic')) {
    ini_set('display_errors', '0'); // don't display errors while under cron
    define('YII_DEBUG', false);
} else {
    ini_set('display_errors', '1');
    define('YII_DEBUG', true);
}

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii/framework/yii.php');

if (extension_loaded('xhprof')) {
    xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY);

    register_shutdown_function(function() {
            $profiler_namespace = 'myarusel';  // namespace for your application
            $xhprof_data = xhprof_disable();
            $xhprof_runs = new XHProfRuns_Default();
            echo 'xhprof_run id: ' . $xhprof_runs->save_run($xhprof_data, $profiler_namespace);
        });
}

$config = require(__DIR__ . '/config/console.php');
$app = Yii::createConsoleApplication($config);
$app->commandRunner->addCommands(__DIR__ . '/commands');
foreach ($config['modules'] as $module) {
    $app->commandRunner->addCommands(__DIR__ . '/modules/' . $module . '/commands');
}

$env = @getenv('YII_CONSOLE_COMMANDS');
if (!empty($env)) {
    $app->commandRunner->addCommands($env);
}

$app->run();