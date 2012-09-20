<?php

Yii::import('application.controllers.admin.*');
Yii::app()->getComponent('image');

class AdminClientsController extends AdminController
{
	public $modelName = 'Client';
	public $modelHumanTitle = array('клиента', 'клиента', 'клиентов');

	/**
	 * @param Client $model
	 * @return array
	 */
	public function getEditFormElements($model)
	{
		$formElements = array(
			'name' => array(
				'type' => 'textField',
			),
			'url' => array(
				'type' => 'textField'
			),
			'feedUrl' => array(
				'type' => 'textField'
			),
			array(
				'class' => 'ext.ImageFileRowWidget',
				'name' => 'logoUrl',
				'options' => array(
					'uploadedFileFieldName' => '_logo',
					'removeImageFieldName' => '_removeLogoFlag',
					'thumbnailImageUrl' => $model->getResizedLogoUrl(array(120, 120)),
					'hint' => 'Максимальный размер: 120×120px',
				),
			),
			'caption' => array(
				'type' => 'textField'
			),
			'color' => array(
				'type' => 'textField'
			),
		);
		if (Yii::app()->user->checkAccess('admin'))
			$formElements['ownerId'] = array(
				'type' => 'dropDownList',
				'data' => CHtml::listData(User::model()->findAll(array('select'=>'id,name')), 'id', 'name'),
			);

		return $formElements;
	}

	public function getTableColumns()
	{
		$attributes = array(
			array(
				'class' => 'ext.BootImageColumn',
				'name' => 'logoUrl',
				'thumbnailUrl' => '$data->getResizedLogoUrl(array(120, 120))',
			),
			'name',
			array(
				'name' => 'url',
				'type' => 'raw',
				'value' => 'CHtml::link($data->url, $data->url, array("target"=>"_blank"))',
			),
			array(
				'name' => 'feedUrl',
				'type' => 'raw',
				'value' => 'CHtml::link($data->feedUrl, $data->feedUrl, array("target"=>"_blank"))',
			),
		);
		if (Yii::app()->user->checkAccess('admin'))
			$attributes[] = array(
				'name' => 'ownerId',
				'value' => '$data->owner->name',
				'filter' => CHtml::listData(User::model()->findAll(array('select'=>'id,name')), 'id', 'name'),
				'sortable' => false,
			);
		$attributes = array_merge($attributes, array(
			array_merge($this->getButtonsColumn(), array(
				'deleteConfirmation' => "Вы точно желаете удалить данного клиента?\n\nВсе его карусельки также будут удалены!",
			)),
		));

		return $attributes;
	}

	/**
	 * @param Client $model
	 */
	public function beforeSave($model)
	{
		/** @var $fs FileSystem */
		$fs = Yii::app()->fs;
		if ($model->_removeLogoFlag) {
			$fs->removeFile($model->logoUid);
			$model->logoUid = null;
		}
		$model->_logo = CUploadedFile::getInstance($model, '_logo');
		if ($model->validate() && !empty($model->_logo)) {
			if (!empty($model->logoUid))
				$fs->removeFile($model->logoUid);
			$model->logoUid = $fs->publishFile($model->_logo->tempName, $model->_logo->name);
			$fs->resizeImage($model->logoUid, array(120,120));
		}

		if ($model->scenario == 'insert')
			$model->ownerId = Yii::app()->user->getId();

		parent::beforeSave($model);
	}

	/**
	 * @param Client $model
	 * @throws CHttpException
	 */
	public function beforeEdit($model)
	{
		parent::beforeEdit($model);

		$admin = Yii::app()->user->checkAccess('admin');

		if (!$admin && !$model->isNewRecord && $model->ownerId != Yii::app()->user->getId())
			throw new CHttpException(403);
	}

	/**
	 * @param Client $model
	 * @param array $attributes
	 */
	public function beforeList($model, &$attributes)
	{
		parent::beforeList($model, $attributes);

		$admin = Yii::app()->user->checkAccess('admin');
		if (!$admin)
			$model->ownerId = Yii::app()->user->getId();
	}
}
