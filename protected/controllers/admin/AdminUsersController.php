<?php

Yii::import('application.controllers.admin.*');

class AdminUsersController extends AdminController
{
	public $modelName = 'User';
	public $modelHumanTitle = array('пользователя', 'пользователя', 'пользователей');

	public function getEditFormElements() {
		return array(
			'name' => array(
				'type' => 'textField',
				'htmlOptions' => array('hint'=>'In addition to freeform text, any HTML5 text-based input appears like so.'),
			),
			'email' => array(
				'type' => 'textField'
			),
			'password' => array(
				'type' => 'passwordField'
			),
		);
	}

	public function beforeSave($model)
	{
		$model->password = md5($model->password.Yii::app()->params['md5Salt']);;
		parent::beforeSave($model);
	}

	public function beforeEdit($model)
	{
		$model->password = '';
		parent::beforeEdit($model);
	}
}
