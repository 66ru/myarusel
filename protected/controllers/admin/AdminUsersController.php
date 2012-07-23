<?php

Yii::import('application.controllers.admin.*');

class AdminUsersController extends AdminController
{
	public $menu = array(
		array('label' => 'Добавить', 'url' => array('admin/adminUsers/add'))
	);

	public $defaultAction = 'list';

}
