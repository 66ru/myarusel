<?php

Yii::setPathOfAlias('lib', realpath(dirname(__FILE__).'/../../lib'));

$params = require('params.php');
return array(
	'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
	'name'=>'Myarusel',
	'language' => 'ru',

	'preload'=>array('log'),

	'import'=>array(
		'application.models.*',
		'application.models.forms.*',
		'application.components.*',
		'application.helpers.*',
		'application.helpers.CurlHelper.CurlHelper',
	),

//	'modules'=>array(
//	),

	'components'=>array(
		'user'=>array(
			// enable cookie-based authentication
			'allowAutoLogin'=>true,
			'loginUrl'=>array('site/login'),
		),
		'urlManager'=>array(
			'urlFormat'=>'path',
			'urlSuffix' => '/',
			'showScriptName' => false,
			'rules'=>array(
				'/' => '/admin/adminCarousel',
//				'admin/' => 'admin/admin',
				'admin/<controller:\w+>/' => 'admin/admin<controller>',
				'admin/<controller:\w+>/<action:\w+>/' => 'admin/admin<controller>/<action>',
				'carousel/<id:\d+>' => 'carousel/show',
//				'<action:\w+>/<id:\d+>' => 'site/<action>',
				'<action:\w+>' => 'site/<action>',
//				'<controller:\w+>/<id:\d+>'=>'<controller>/view',
//				'<controller:\w+>/<action:\w+>/<id:\d+>'=>'<controller>/<action>',
//				'<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
			),
		),
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
		'viewRenderer'=>array(
			'class'=>'ext.ETwigViewRenderer',
			'twigPathAlias' => 'lib.twig.lib.Twig',
			'options' => array(
				'autoescape' => true,
			),
			'functions' => array(
				'widget' => array(
					0 => 'TwigFunctions::widget',
					1 => array('is_safe' => array('html')),
				),
			),
		),
		'bootstrap'=>array(
			'class'=>'lib.bootstrap.components.Bootstrap', // assuming you extracted bootstrap under extensions
			'responsiveCss' => true,
		),
		'errorHandler'=>array(
			'errorAction'=>'site/error',
		),
		'image' => array(
			'class' => 'ext.image.CImageComponent',
			'driver' => $params['imageDriver'],
		),
		'cache' => array(
			'class' => 'CFileCache',
		),
		'format' => array(
			'booleanFormat' => array('Нет', 'Да'),
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
				array(
					'class'=>'CFileLogRoute',
					'levels'=>'error, warning',
				),
				// uncomment the following to show log messages on web pages
				/*array(
					'class'=>'CWebLogRoute',
				),*/
			),
		),
	),

	'params'=> array_merge($params, array(
		'md5Salt' => 'ThisIsMymd5Salt(*&^%$#',
	)),
);