<?php

class CarouselController extends Controller
{
    public function filters()
    {
        if (!empty($_GET['id'])) {
            /** @var $carousel Carousel */
            $carousel = Carousel::model()->findByPk($_GET['id']);

            if (!empty($carousel) && !YII_DEBUG) {
                return array(
                    array(
                        'COutputCache',
                        'duration' => 300,
                        'varyByParam' => array('id'),
                        'dependency' => new CGlobalStateCacheDependency($carousel->getInvalidateKey()),
                    ),
                );
            }
        }

        return array();
    }

    public function actionShow($id)
    {
        /** @var $carousel Carousel */
        $carousel = Carousel::model()->with(
            [
                'client',
                'onSiteItems',
            ]
        )->findByPk($id);

        if (empty($carousel) || empty($carousel->onSiteItems)) {
            header("HTTP/1.0 404 Not Found");
            echo " ";
            Yii::app()->end(0);
        }

        $onSiteItems = $carousel->onSiteItems;
        shuffle($onSiteItems);
        $this->render(
            "//carousels/" . $carousel->template,
            array(
                'client' => $carousel->client,
                'items' => $onSiteItems,
                'onPage' => $carousel->onPage,
                'urlPrefix' => $carousel->urlPrefix,
                'urlPostfix' => $carousel->urlPostfix,
                'customCss' => $carousel->customCss,
                'logoSize' => $carousel->logoSize,
                'thumbSize' => $carousel->thumbSize,
            )
        );
    }
}
