<?php

Yii::setPathOfAlias('lib', realpath(dirname(__FILE__).'/../../lib'));
Yii::setPathOfAlias('vendor', realpath(__DIR__ . '/../../vendor'));
Yii::setPathOfAlias('m8rge', realpath(dirname(__FILE__).'/../helpers/CurlHelper'));

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
        'except' => 'exception.*',
        'dsn' => $params['sentryDSN'],
    );
    $components['RSentryException'] = array(
        'dsn' => $params['sentryDSN'],
        'class' => 'vendor.m8rge.yii-sentry-log.RSentryComponent',
    );
}
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'Myarusel',

	'preload'=>array('log'),

	'import'=>array(
		'application.models.*',
		'application.models.forms.*',
		'application.components.*',
		'application.helpers.*',
	),

	'components'=>array_merge(
        array(
            'db'=>array(
                'connectionString' => 'mysql:host='.$params['dbHost'].';dbname='.$params['dbName'],
                'emulatePrepare' => true,
                'username' => $params['dbLogin'],
                'password' => $params['dbPassword'],
                'charset' => 'utf8',
            ),
            'authManager'=>array(
                'class'=>'CDbAuthManager',
                'connectionID'=>'db',
            ),
            'fs' => array(
                'class' => 'FileSystem',
                'nestedFolders' => 1,
            ),
            'image' => array(
                'class' => 'ext.image.CImageComponent',
                'driver' => $params['imageDriver'],
            ),
            'cache' => array(
                'class' => 'CFileCache',
            ),
            'log'=>array(
                'class'=>'CLogRouter',
                'routes'=>$logRoutes,
            ),
        ),
        $components
	),
	'commandMap'=>array(
		'migrate'=>array(
			'class'=>'system.cli.commands.MigrateCommand',
			'migrationTable'=>'migrations',
		),
    ),
	'params'=> array_merge($params, array(
		'md5Salt' => 'ThisIsMymd5Salt(*&^%$#',
	)),
);