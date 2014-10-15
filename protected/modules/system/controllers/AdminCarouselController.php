<?php

class AdminCarouselController extends CommonAdminController
{
    public $modelName = 'Carousel';
    public $modelHumanTitle = array('карусельку', 'карусельки', 'каруселек');

    public function accessRules()
    {
        $allowedActions = array_merge($this->getAllowedActions(), array('index', 'list', 'ajaxGetClientCategories', 'ajaxRefreshCache', 'ajaxTaskStatus'));
        return array(
            array(
                'allow',
                'actions' => $allowedActions,
                'roles' => array('admin')
            ),
            array(
                'deny',
                'users' => array('*')
            ),
        );
    }

    public function actions()
    {
        return [
            'ajaxTaskStatus' => 'ext.asyncTask.actions.GetTaskStatusAction',
            'ajaxRefreshCache' => [
                'class' => 'ext.asyncTask.actions.StartAsyncTaskAction',
                'timeout' => 60*2,
                'task' => function() {
                    /** @var Carousel $carousel */
                    $carousel = Carousel::model()->with('client')->findByPk($_GET['id']);

                    Yii::import('application.commands.*');
                    $command = new UpdateCarouselsCommand('UpdateCarouselsCommand', null);
                    $hashToUri = EHtml::listData(Item::model()->byCarousel($carousel->id), 'imageHash', 'imageUri');
                    $status = $command->processCarousel($carousel, $hashToUri);

                    return [IAsyncTaskProvider::STATUS_DONE, $status];
                }
            ],
        ];
    }

    /**
     * @param Carousel $model
     * @return array
     */
    public function getEditFormElements($model)
    {
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
        if (!Yii::app()->user->checkAccess('admin')) {
            $clients = $clients->mine();
        }
        $clients = EHtml::listData($clients, 'id', 'name');

        $formElements = array(
            'name' => array(
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
            'status' => array(
                'type' => 'dropDownList',
                'data' => $model->getStatusList(),
                'htmlOptions' => array(
                    'empty' => 'Не выбран',
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
            'clientId' => array(
                'type' => 'dropDownList',
                'data' => $clients,
                'htmlOptions' => array(
                    'empty' => 'Не выбран',
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
            'viewType' => array(
                'type' => 'dropDownList',
                'data' => Carousel::getViewTypes(),
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
            'templateId' => array(
                'type' => 'dropDownList',
                'data' => EHtml::listData(Template::model(), 'id', 'name'),
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
            'urlPrefix' => array(
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
                'rowOptions' => array(
                    'hint' => 'Например: http://domain.com/go/',
                ),
            ),
            'urlPostfix' => array(
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
                'rowOptions' => array(
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
                    'class' => 'span4',
                    'encode' => false,
                ),
            ),
            array(
                'class' => 'ext.DependedAjaxInputWidget',
                'masterAttributeName' => "clientId",
                'dependedAttributeName' => "categories",
                'getDataUrl' => CHtml::normalizeUrl(array('/system/adminCarousel/ajaxGetClientCategories', 'id' => '')),
            )
        );
        if (Yii::app()->user->checkAccess('admin')) {
            $formElements['ownerId'] = array(
                'type' => 'dropDownList',
                'data' => CHtml::listData(User::model()->findAll(array('select' => 'id,name')), 'id', 'name'),
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            );
        }
        $formElements = array_merge($formElements, [
                '<div class="variables">',
                '<legend class="controls">Настройки шаблона</legend>',
                'variables' => [
                    'class' => 'system.components.TemplateVariablesEditWidget',
                    'templateIdAttribute' => 'templateId',
                ],
                '</div>',
            ]);
        return $formElements;
    }

    public function actionAjaxGetClientCategories()
    {
        if (!Yii::app()->request->isAjaxRequest) {
            throw new CHttpException(403);
        }

        /** @var $client Client */
        $client = Client::model()->findByPk($_GET['id']);
        if ($client instanceof Client) {
            $htmlOptions = array(
                'encode' => false,
            );
            echo CHtml::listOptions(null, $client->getCategories(), $htmlOptions);
        }
    }

    /**
     * @param Carousel $model
     * @return array
     * @throws CException
     */
    public function getTableColumns($model)
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

        if (Yii::app()->user->checkAccess('admin')) {
            $attributes[] = array(
                'name' => 'ownerId',
                'value' => '$data->owner->name',
                'filter' => EHtml::listData(User::model(), 'id', 'name'),
                'sortable' => false,
            );
        }

        $attributes = array_merge(
            $attributes,
            array(
                array(
                    'name' => 'status',
                    'value' => '$data->statusText',
                    'filter' => $model->getStatusList(),
                    'sortable' => false,
                ),
                array(
                    'type' => 'raw',
                    'value' => "Yii::app()->controller->widget('ext.asyncTask.widget.StartTaskButton', [
                            'label' => 'Обновить кеш',
                            'doneAction' => 'function(\$button, result) { alert(result); }',
                            'taskStatusRoute' => ['/system/adminCarousel/ajaxTaskStatus'],
                            'startTaskRoute' => ['/system/adminCarousel/ajaxRefreshCache', 'id'=>\$data->id],
                            'buttonProperties' => [
                                'size' => 'mini',
                                'type'=> 'link',
                            ],
                        ], true)",
                    'htmlOptions' => array(
                        'style' => 'width:100px',
                    ),
                ),
                $this->getButtonsColumn(),
            )
        );

        return $attributes;
    }

    /**
     * @param Carousel $model
     * @return bool
     */
    public function beforeSave($model)
    {
        if ($model->scenario == 'insert') {
            $model->ownerId = Yii::app()->user->getId();
        }

        return parent::beforeSave($model);
    }

    /**
     * @param Carousel $model
     * @throws CHttpException
     */
    public function beforeEdit($model)
    {
        parent::beforeEdit($model);

        $admin = Yii::app()->user->checkAccess('admin');

        if (!$admin && !$model->isNewRecord && $model->ownerId != Yii::app()->user->getId()) {
            throw new CHttpException(403);
        }
    }

    /**
     * @param Carousel $model
     * @param array $attributes
     */
    public function beforeList($model, &$attributes)
    {
        parent::beforeList($model, $attributes);

        $model->orderDefault();

        $admin = Yii::app()->user->checkAccess('admin');
        if (!$admin) {
            $model->ownerId = Yii::app()->user->getId();
        }
    }
}
