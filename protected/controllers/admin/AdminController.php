<?php

class AdminController extends Controller
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
		$this->render('application.views.admin.index');
	}

	public function actionList() {
		$this->render('application.views.admin.list');
	}

	public function actionAdd() {
		$this->render('application.views.admin.add');
	}
}
