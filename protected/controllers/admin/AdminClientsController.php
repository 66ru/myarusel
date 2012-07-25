<?php

Yii::import('application.controllers.admin.*');

class AdminClientsController extends AdminController
{
	public $modelName = 'Client';
	public $modelHumanTitle = array('клиента', 'клиента', 'клиентов');

	/**
	 * @param Client $model
	 * @return array
	 */
	public function getEditFormElements($model) {
		return array(
			'name' => array(
				'type' => 'textField',
			),
			'feedUrl' => array(
				'type' => 'textField'
			),
			'logoUrl' => array(
				'class' => 'ext.ImageFileRowWidget',
				'options' => array(
					'uploadedFileFieldName' => '_logo',
					'removeImageFieldName' => '_removeLogoFlag',
				),
			),
			'caption' => array(
				'type' => 'textField'
			),
			'color' => array(
				'type' => 'textField'
			),
		);
	}

	public function getTableColumns()
	{
		$attributes = array(
			array(
				'class' => 'ext.BootImageColumn',
				'name' => 'logoUrl',
			),
			'name',
			'feedUrl',
			$this->getButtonsColumn(),
		);

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
			$model->logoUid = $fs->publishFile($model->_logo->tempName, $model->_logo->name);
		}

		parent::beforeSave($model);
	}
}
