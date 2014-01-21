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
            try {
			    $categoriesList = $model->client->getCategories();
                $categoriesDisabled = false;
            } catch (CException $e) {
                $model->addError('clientId', $e->getMessage());
            } catch (\m8rge\CurlException $e) {
                $model->addError('clientId', 'Произошла ошибка при получении yml файла клиента: ' . $e->getMessage());
            }
		}

		$clients = Client::model();
		if (!Yii::app()->user->checkAccess('admin'))
			$clients = $clients->mine();
		$clients = EHtml::listData($clients, 'id', 'name');

		$formElements =  array(
			'name' => array(
				'type' => 'textField',
			),
			'clientId' => array(
				'type' => 'dropDownList',
				'data' => $clients,
				'htmlOptions' => array(
					'empty' => 'Не выбран',
				),
			),
			'viewType' => array(
				'type' => 'dropDownList',
				'data' => Carousel::getViewTypes(),
			),
			'template' => array(
				'type' => 'dropDownList',
				'data' => Carousel::getTemplates(),
			),
			'onPage' => array(
				'type' => 'textField',
				'htmlOptions' => array(
					'hint' => 'Количество одновременно показываемых позиций на экране',
				),
			),
			'urlPrefix' => array(
				'type' => 'textField',
				'htmlOptions' => array(
					'hint' => 'Например: http://domain.com/go/',
				),
			),
			'urlPostfix' => array(
				'type' => 'textField',
				'htmlOptions' => array(
					'hint' => '?utm=fromDomain',
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
					'masterElementId' => EHtml::resolveId($model, "clientId"),
					'dependedElementId' => EHtml::resolveId($model, "categories"),
					'getDataUrl' => CHtml::normalizeUrl(array('/admin/adminCarousel/ajaxGetClientCategories', 'id' => '')),
				),
			)
		);
		if (Yii::app()->user->checkAccess('admin'))
			$formElements['ownerId'] = array(
				'type' => 'dropDownList',
				'data' => CHtml::listData(User::model()->findAll(array('select'=>'id,name')), 'id', 'name'),
			);
		return $formElements;
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
				'filter' => EHtml::listData(Client::model(), 'id', 'name'),
				'sortable' => false,
			),
		);

		if (Yii::app()->user->checkAccess('admin'))
			$attributes[] = array(
				'name' => 'ownerId',
				'value' => '$data->owner->name',
				'filter' => EHtml::listData(User::model(), 'id', 'name'),
				'sortable' => false,
			);

		$attributes = array_merge($attributes, array(
			array(
				'type' => 'raw',
				'value' => 'CHtml::link("Обновить кеш", array("/admin/adminCarousel/ajaxRefreshCache", "id"=>$data->id), array("class"=>"updateCache"))',
				'htmlOptions' => array(
					'style' => 'width:100px',
				)
			),
			$this->getButtonsColumn(),
		));

		/** @var $cs CClientScript */
		$cs = Yii::app()->clientScript;
		$cs->registerScript($this->getId(), "
$('.updateCache').live('click', function() {
	$.get($(this).attr('href'), function (data) {
        alert(data.output);
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

	/**
	 * @param Carousel $model
	 */
	public function beforeSave($model)
	{
		parent::beforeSave($model);

		if ($model->scenario == 'insert')
			$model->ownerId = Yii::app()->user->getId();
	}

	/**
	 * @param Carousel $model
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
	 * @param Carousel $model
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
