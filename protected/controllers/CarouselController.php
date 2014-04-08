<?php

class CarouselController extends Controller
{
	public function filters()
	{
		if (!empty($_GET['id'])) {
			/** @var $carousel Carousel */
			$carousel = Carousel::model()->findByPk($_GET['id']);

			if (!empty($carousel) && !YII_DEBUG)
				return array(
					array(
						'COutputCache',
						'duration'=>300,
						'varyByParam'=>array('id'),
						'dependency' => new CGlobalStateCacheDependency($carousel->getInvalidateKey()),
					),
				);
		}

		return array();
	}

	public function actionShow($id)
    {
		/** @var $carousel Carousel */
		$carousel = Carousel::model()->with(array(
			'client',
			'items' => array('scopes'=>'onSite')
		))->findByPk($id);

		if (empty($carousel) || empty($carousel->items)) {
            header("HTTP/1.0 404 Not Found");
            echo " ";
            Yii::app()->end(0);
        }

		$items = $carousel->items;
		shuffle($items);
		$this->render("//carousels/".$carousel->template, array(
			'client' => $carousel->client,
			'items' => $items,
			'onPage' => $carousel->onPage,
			'urlPrefix' => $carousel->urlPrefix,
			'urlPostfix' => $carousel->urlPostfix,
			'logoSize' => $carousel->logoSize,
			'thumbSize' => $carousel->thumbSize,
		));
	}
}
