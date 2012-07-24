<?php

class AdminController extends Controller
{
	public $menu = array(
		array('label' => 'Добавить', 'url' => array('add'))
	);

	public $modelName;
	/**
	 * @var array Склонение должно соответствовать словам соответственно: (добавить .., редактирование .., список ..)
	 */
	public $modelHumanTitle = array('модель','модели','моделей');

	public $defaultAction = 'list';
	public $listTitle = 'Список';
	public $addTitle = 'Добавить';
	public $editTitle = 'Редактирование';

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

	public function actionAdd() {
		$this->actionEdit(true);
	}

	public function actionEdit($createNew = null) {
		if ($createNew) {
			$model = new $this->modelName();
		} else {
			$model = $this->loadModel();
		}
		$model->scenario = 'edit';

		if(isset($_POST[$this->modelName]))
		{
			$model->attributes=$_POST[$this->modelName];
			$model->scenario = 'save';
			$this->beforeSave($model);
			if($model->save())
				$this->redirect(array($this->getId()));
		}

		$this->beforeEdit($model);
		$this->render('application.views.admin.'.($createNew ? 'add' : 'edit'), array(
			'model' => $model,
			'editFormElements' => $this->getEditFormElements(),
		));
	}

	public function loadModel() {
		$model = null;
		if (isset($_GET['id']))
			$model = CActiveRecord::model($this->modelName)->findbyPk($_GET['id']);
		if ($model === null)
			throw new CHttpException(404);
		return $model;
	}

	public function actionList() {
		/** @var $model CActiveRecord */
		$model=new $this->modelName('search');
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET[$this->modelName]))
			$model->attributes=$_GET[$this->modelName];

		$this->render('application.views.admin.list', array(
			'model' => $model,
			'columns' => $this->getTableColumns(),
		));
	}

	public function actionDelete()
	{
		if(Yii::app()->request->isPostRequest)
		{
			// we only allow deletion via POST request
			$this->loadModel()->delete();

			// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
			if(!isset($_GET['ajax']))
				$this->redirect(array($this->getId()));
		}
		else
			throw new CHttpException(400,'Invalid request. Please do not repeat this request again.');
	}

	public function getTableColumns() {
		$model = CActiveRecord::model($this->modelName);
		$attributes = $model->getAttributes();
		unset($attributes[ $model->metaData->tableSchema->primaryKey ]);
		$attributes = array_keys($attributes);

		$attributes[] = array(
			'class' => 'bootstrap.widgets.BootButtonColumn',
			'template' => '{update}&nbsp;&nbsp;&nbsp;{delete}',
			'updateButtonUrl' => 'Yii::app()->controller->createUrl("edit",array("id"=>$data->primaryKey))'
		);

		return $attributes;
	}

	public function getEditFormElements() {
		$elements['buttons'] = array(
			'send'=> array(
				'type' => 'submit',
				'label'=> 'Сохранить',
			),
		);
		if (!isset($elements['enctype']))
			$elements['enctype'] = 'multipart/form-data';

		return $elements;
	}

	public function beforeSave($model) {}
	public function beforeEdit($model) {}
}
