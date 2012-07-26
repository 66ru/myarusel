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
					'thumbnailImageUrl' => $model->getResizedLogoUrl(120, 120),
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
				'thumbnailUrl' => '$data->getResizedLogoUrl(120, 120)',
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
			if (!empty($model->logoUid))
				$fs->removeFile($model->logoUid);
			$model->logoUid = $fs->publishFile($model->_logo->tempName, $model->_logo->name);
			$fs->resizeImage($model->logoUid, array(120,120));
		}

		parent::beforeSave($model);
	}
}
