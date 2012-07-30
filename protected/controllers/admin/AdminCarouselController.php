<?php

Yii::import('application.controllers.admin.*');

class AdminCarouselController extends AdminController
{
	public $modelName = 'Carousel';
	public $modelHumanTitle = array('карусельку', 'карусельки', 'каруселек');

	/**
	 * @param Carousel $model
	 * @return array
	 */
	public function getEditFormElements($model) {
		$categoriesList = array('');
		$categoriesDisabled = true;
		if (!empty($model->clientId)) {
			$categoriesList = $model->client->getCategories();
			$categoriesDisabled = false;
		}

		return array(
			'name' => array(
				'type' => 'textField',
			),
			'clientId' => array(
				'type' => 'dropDownList',
				'data' => CHtml::listData(Client::model()->findAll(array('select'=>'id,name')), 'id', 'name'),
				'htmlOptions' => array(
					'empty' => 'Не выбран',
				),
			),
			'categories' => array(
				'type' => 'dropDownList',
				'data' => $categoriesList,
				'htmlOptions' => array(
					'multiple' => true,
					'disabled' => $categoriesDisabled,
					'size' => 20,
					'encode' => false,
				),
			),
			array(
				'class' => 'ext.DependInputScriptWidget',
				'options' => array(
					'masterElementId' => ExtendedHtml::resolveId($model, "clientId"),
					'dependedElementId' => ExtendedHtml::resolveId($model, "categories"),
					'getDataUrl' => CHtml::normalizeUrl(array('/admin/adminCarousel/ajaxGetClientCategories', 'id' => '')),
				),
			)
		);
	}

	public function actionAjaxGetClientCategories() {
		if (!Yii::app()->request->isAjaxRequest)
			throw new CHttpException(403);

		/** @var $client Client */
		$client = Client::model()->findByPk($_GET['id']);
		if ($client instanceof Client) {
			$htmlOptions = array(
				'encode' => false,
			);
			echo CHtml::listOptions(null, $client->getCategories(), $htmlOptions);
		}
	}

	public function getTableColumns()
	{
		$attributes = array(
			array(
				'name' => 'name',
				'type' => 'raw',
				'value' => 'CHtml::link($data->name, $data->getUrl())',
			),
			array(
				'name' => 'clientId',
				'value' => '$data->client->name',
				'filter' => CHtml::listData(Client::model()->findAll(array('select'=>'id,name')), 'id', 'name'),
				'sortable' => false,
			),
			$this->getButtonsColumn(),
		);

		return $attributes;
	}
}
