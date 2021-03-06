<?php

Yii::setPathOfAlias('lib', realpath(__DIR__ . '/../../lib'));
Yii::setPathOfAlias('vendor', realpath(__DIR__ . '/../../vendor'));

$params = require('params.php');
$components = array();
$logRoutes = array(
    array(
        'class' => 'CFileLogRoute',
        'levels' => 'error,warning',
    ),
    array(
        'class' => 'CFileLogRoute',
        'levels' => 'info',
        'logFile' => 'info.log',
    )
);
if ($params['useSentry']) {
    $logRoutes[] = array(
        'class'=>'vendor.m8rge.yii-sentry-log.RSentryLog',
        'levels'=>'error, warning',
        'except' => 'exception.*,system.db.*',
        'dsn' => $params['sentryDSN'],
    );
    $components['RSentryException'] = array(
        'dsn' => $params['sentryDSN'],
        'class' => 'ESentryComponent',
    );
}
return array(
    'basePath' => dirname(__FILE__) . DIRECTORY_SEPARATOR . '..',
    'name' => $params['appName'],
    'language' => 'ru',
    'timeZone' => 'Asia/Yekaterinburg',
    'preload' => array('log', 'RSentryException'),
    'import' => array(
        'application.models.*',
        'application.models.forms.*',
        'application.components.*',
        'application.helpers.*',
        'ext.mAdmin.*',
    ),
    'modules' => require(__DIR__.'/modules.php'),
    'components' => array_merge(
        array(
            'user' => array(
                // enable cookie-based authentication
                'allowAutoLogin' => true,
                'loginUrl' => array('site/login'),
            ),
            'request' => array(
                'class' => 'application.components.SecuredCsrfHttpRequest',
                'enableCsrfValidation' => true,
                'noCsrfValidationRoutes'=>array(),
            ),
            'urlManager' => array(
                'urlFormat' => 'path',
                'urlSuffix' => '/',
                'showScriptName' => false,
                'useStrictParsing' => true,
                'rules' => array(
                    '/' => 'system/admin', // or system/admin
                    'admin/' => 'system',
                    'admin/<module:\w+>/<controller:\w+>/' => '<module>/admin<controller>',
                    'admin/<module:\w+>/<controller:\w+>/<action:\w+>/' => '<module>/admin<controller>/<action>',
                    'carousel/<id:\d+>' => 'carousel/show',
    //				'<action:\w+>/<id:\d+>' => 'site/<action>',
                    'validate' => 'validate/index',
                    '<action:\w+>' => 'site/<action>',
                    'admin/<action:\w+>/' => 'systemø/admin/<action>',
                ),
            ),
            'assetManager' => array(
                'linkAssets' => true,
            ),
            'session' => array(
                'class' => 'CDbHttpSession',
                'connectionID' => 'db',
            ),
            'db' => array(
                'connectionString' => 'mysql:host=' . $params['dbHost'] . ';dbname=' . $params['dbName'],
                'emulatePrepare' => true,
                'username' => $params['dbLogin'],
                'password' => $params['dbPassword'],
                'charset' => 'utf8',
            ),
            'authManager' => array(
                'class' => 'CDbAuthManager',
                'connectionID' => 'db',
            ),
            'fs' => array(
                'class' => 'FileSystem',
                'nestedFolders' => 1,
            ),
            'viewRenderer' => array(
                'class' => 'vendor.yiiext.twig-renderer.ETwigViewRenderer',
                'twigPathAlias' => 'vendor.twig.twig.lib.Twig',
                'options' => array(
                    'autoescape' => true,
                ),
                'functions' => array(
                    'widget' => array(
                        0 => 'TwigFunctions::widget',
                        1 => array('is_safe' => array('html')),
                    ),
                    'const' => 'TwigFunctions::constGet',
                    'static' => 'TwigFunctions::staticCall',
                    'url' => 'TwigFunctions::url',
                    'absUrl' => 'TwigFunctions::absUrl',
                    'plural' => 'TwigFunctions::plural',
                    'new' => 'TwigFunctions::newObject',
                    'createMyarouselLink' => 'TwigFunctions::createMyarouselLink',
                ),
                'filters' => array(
                    'unset' => 'TwigFunctions::_unset',
                ),
            ),
            'bootstrap' => array(
                'class' => 'vendor.clevertech.yii-booster.src.components.Bootstrap',
                'responsiveCss' => true,
                'jqueryCss' => false,
                'minify' => !YII_DEBUG,
            ),
            'errorHandler' => array(
                'errorAction' => 'site/error',
            ),
            'cache' => array(
                'class' => 'CMemCache',
                'useMemcached' => true,
            ),
            'format' => array(
                'booleanFormat' => array('Нет', 'Да'),
            ),
            'log' => array(
                'class' => 'CLogRouter',
                'routes' => $logRoutes,
            ),
            'unistorage' => array(
                'class' => 'vendor.66ru.unistorage-yii-client.YiiUnistorage.YiiUnistorage',
                'host' => $params['unistorageHost'],
                'token' => $params['unistorageToken'],
            ),
            'asyncTask' => [
                'class' => 'ext.asyncTask.AsyncTaskComponent',
            ],
        ),
        $components
    ),

	'params'=> array_merge($params, array(
		'md5Salt' => 'ThisIsMymd5Salt(*&^%$#',
	)),
);