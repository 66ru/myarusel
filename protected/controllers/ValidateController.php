<?php

Yii::app()->getComponent('bootstrap');
Yii::import('bootstrap.widgets.*');

class ValidateController extends Controller
{
    public $assetsUrl;

    public function init()
    {
        parent::init();

        /** @var $app CWebApplication */
        $app = Yii::app();
        $this->assetsUrl = $app->assetManager->publish(__DIR__ . '/../extensions/mAdmin/assets');
    }

    public function actionIndex()
    {
        $form = new ValidateYmlForm();

        $className = get_class($form);
        if (!empty($_POST[$className])) {
            $form->setAttributes($_POST[$className]);
            if ($form->validate()) {
                Yii::app()->user->setFlash('success', '<strong>Файл не содержит ошибок!</strong>');
            }
        }
        $this->render('validate', [
                'adminLayout' => '/views/layouts/admin.twig',
                'title' => 'Проверка YML файла',
                'form' => $form,
            ]);
    }
}