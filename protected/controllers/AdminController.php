<?php

class AdminController extends CController
{
	public function filters()
	{
		return array(
			'accessControl'
		);
	}

	public function accessRules()
	{
		return array(
			array('allow',
				'roles'=>array('admin')
			),
			array('deny',
				'users'=>array('*')
			),
		);
	}

	public function actionIndex() {
		echo '5';
	}
}
