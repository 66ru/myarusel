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
				'value' => 'CHtml::link($data->name, $data->getUrl(), array("target"=>"_blank"))',
			),
			array(
				'name' => 'clientId',
				'value' => '$data->client->name',
				'filter' => CHtml::listData(Client::model()->findAll(array('select'=>'id,name')), 'id', 'name'),
				'sortable' => false,
			),
			array(
				'type' => 'raw',
				'value' => 'CHtml::link("Обновить кеш", array("/admin/adminCarousel/ajaxRefreshCache", "id"=>$data->id), array("class"=>"updateCache"))',
				'htmlOptions' => array(
					'style' => 'width:100px',
				)
			),
			$this->getButtonsColumn(),
		);

		/** @var $cs CClientScript */
		$cs = Yii::app()->clientScript;
		$cs->registerScript($this->getId(), "
$('.updateCache').click(function() {
	$.get($(this).attr('href'), function (data) {
		if (data.errorCode != 0)
			alert(data.output);
		else
			alert('Кеш успешно обновлен');
	}, 'json');

	return false;
})");

		return $attributes;
	}

	public function actionAjaxRefreshCache($id){
		if (!Yii::app()->request->isAjaxRequest)
			throw new CHttpException(403);

		$id = (int)$id;

		$returnVal = 0;
		$output = array();
		exec(Yii::app()->getBasePath().'/yiic updateCarousels --id='.$id, $output, $returnVal);

		echo json_encode(array(
			'errorCode'=>$returnVal,
			'output' => implode("\n", $output),
		));
	}
}
