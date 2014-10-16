<?php

Yii::app()->getComponent('image');

class AdminClientsController extends CommonAdminController
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
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
            'url' => array(
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
            'feedUrl' => array(
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
            'logoUrl' => array(
                'class' => 'ext.ImageFileRowWidget',
                'uploadedFileFieldName' => '_logo',
                'removeImageFieldName' => '_removeLogoFlag',
                'thumbnailImageUrl' => $model->getResizedLogoUrl(125,125),
            ),
            'caption' => array(
                'type' => 'textField',
                'htmlOptions' => array(
                    'placeholder' => false,
                    'class' => 'span4',
                ),
            ),
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

        return $formElements;
    }

    public function getTableColumns($model)
    {
        $attributes = array(
            array(
                'class' => 'ext.TbImageColumn',
                'name' => 'logoUrl',
                'thumbnailUrl' => '$data->getResizedLogoUrl(array(125, 125))',
            ),
            'name',
            array(
                'name' => 'url',
                'type' => 'raw',
                'value' => 'CHtml::link(StringHelper::cutString($data->url, 40), $data->url, array("target"=>"_blank", "title" => $data->url))',
            ),
            array(
                'name' => 'feedUrl',
                'type' => 'raw',
                'value' => 'CHtml::link(StringHelper::cutString($data->feedUrl, 60), $data->feedUrl, array("target"=>"_blank", "title" => $data->feedUrl))',
            ),
        );
        if (Yii::app()->user->checkAccess('admin')) {
            $attributes[] = array(
                'name' => 'ownerId',
                'value' => '$data->owner->name',
                'filter' => CHtml::listData(User::model()->findAll(array('select' => 'id,name')), 'id', 'name'),
                'sortable' => false,
            );
        }
        $attributes = array_merge(
            $attributes,
            array(
                array_merge(
                    $this->getButtonsColumn(),
                    array(
                        'deleteConfirmation' => "Вы точно желаете удалить данного клиента?\n\nВсе его карусельки также будут удалены!",
                    )
                ),
            )
        );

        return $attributes;
    }

    /**
     * @param Client $model
     * @return bool
     */
    public function beforeSave($model)
    {
        if ($model->_removeLogoFlag) {
            $model->logoUri = null;
        }
        $model->_logo = CUploadedFile::getInstance($model, '_logo');
        if ($model->validate() && !empty($model->_logo)) {
            $us = Yii::app()->unistorage;
            $file = $us->uploadFile($model->_logo->tempName, $model->_logo->name);
            $model->logoUri = $file->resourceUri;
        }

        if ($model->scenario == 'insert') {
            $model->ownerId = Yii::app()->user->getId();
        }

        return parent::beforeSave($model);
    }

    /**
     * @param Client $model
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
     * @param Client $model
     * @param array $attributes
     */
    public function beforeList($model, &$attributes)
    {
        parent::beforeList($model, $attributes);

        $admin = Yii::app()->user->checkAccess('admin');
        if (!$admin) {
            $model->ownerId = Yii::app()->user->getId();
        }
    }
}
