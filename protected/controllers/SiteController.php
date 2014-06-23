<?php

Yii::app()->getComponent('bootstrap');

class SiteController extends Controller
{
    public $assetsUrl;

    public function filters()
    {
        return array(
            'postOnly +logout',
        );
    }

    public function init()
    {
        parent::init();

        /** @var $app CWebApplication */
        $app = Yii::app();
        $this->assetsUrl = $app->assetManager->publish(__DIR__ . '/../extensions/mAdmin/assets');
    }

    public function actionIndex()
    {
        $this->redirect(array('/system'));
    }

    public function actionLogin()
    {
        if (!Yii::app()->user->isGuest) {
            $this->redirect(Yii::app()->homeUrl);
        }
        $model = new LoginForm;

        // if it is ajax validation request
        if (isset($_POST['ajax']) && $_POST['ajax'] === 'login-form') {
            echo CActiveForm::validate($model);
            Yii::app()->end();
        }

        // collect user input data
        if (isset($_POST['LoginForm'])) {
            $model->attributes = $_POST['LoginForm'];
            // validate user input and redirect to the previous page if valid
            if ($model->validate() && $model->login()) {
                $this->redirect(Yii::app()->user->getReturnUrl(Yii::app()->homeUrl));
            }
        }
        // display the login form
        $this->render('login', array('model' => $model));
    }

    public function actionLogout()
    {
        Yii::app()->user->logout();
    }

    public function actionError()
    {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest) {
                echo $error['message'];
            } else {
                $this->render('error', $error);
            }
        }
    }
}