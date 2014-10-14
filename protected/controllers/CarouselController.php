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
                'template',
                'client',
                'onSiteItems',
            ]
        )->findByPk($id);

        if (empty($carousel) || empty($carousel->onSiteItems)) {
            header("HTTP/1.0 404 Not Found");
            echo " ";
            Yii::app()->end(0);
        }

        $items = [];
        $onSiteItems = $carousel->onSiteItems;
        shuffle($onSiteItems);
        foreach ($onSiteItems as $item) {
            $items[] = [
                'title' => $item->title,
                'url' => TwigFunctions::createMyarouselLink($item->url, $carousel->urlPrefix, $carousel->urlPostfix),
                'image' => $item->getResizedImageUrl($carousel->template->itemWidth, $carousel->template->itemHeight),
                'price' => $item->price,
            ];
        }

        $data = [
            'client' => [
                'name' => $carousel->client->name,
                'url' => TwigFunctions::createMyarouselLink($carousel->client->url, $carousel->urlPrefix, $carousel->urlPostfix),
                'image' => $carousel->client->getResizedLogoUrl($carousel->template->logoWidth, $carousel->template->logoHeight),
                'caption' => $carousel->client->caption,
            ],
            'items' => $items,
        ];

        Yii::app()->clientScript->registerScript(
            'templateData',
            'window.templateData = ' . json_encode($data) . ';',
            CClientScript::POS_BEGIN
        );
        Yii::app()->clientScript->registerScript(
            'templateVariables',
            'window.templateVars = ' . json_encode($carousel->variables) . ';',
            CClientScript::POS_BEGIN
        );

        $this->renderText($carousel->template->html);
    }
}
