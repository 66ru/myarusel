<?php

class CreateAuthItemsCommand extends CConsoleCommand
{
	public function actionIndex($name, $password) {
		/** @var $auth CAuthManager */
		$auth=Yii::app()->authManager;

		$existingRoles = $auth->getRoles();
		if (!array_key_exists('admin', $existingRoles)) {
			$auth->createRole('admin');
		}

		$newAdmin = User::model()->findByAttributes(array('name'=>$name));
		$newAdmin->scenario = 'save';
		if (empty($newAdmin))
			$newAdmin = new User();
		$newAdmin->name = $name;
		$newAdmin->password = md5($password.Yii::app()->params['md5Salt']);
		if (!$newAdmin->save())
			throw new CException(print_r($newAdmin->getErrors(), true));

		$userRoles = $auth->getRoles($newAdmin->id);
		if (!array_key_exists('admin', $userRoles))
			$auth->assign('admin', $newAdmin->id);
	}
}
