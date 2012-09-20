<?php

class CarouselController extends Controller
{
	public function filters()
	{
		if (!empty($_GET['id'])) {
			/** @var $carousel Carousel */
			$carousel = Carousel::model()->findByPk($_GET['id']);

			if (!empty($carousel))
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

	public function actionShow($id) {
		/** @var $carousel Carousel */
		$carousel = Carousel::model()->with(array(
			'client',
			'items' => array('scopes'=>'onSite')
		))->findByPk($id);

		if (empty($carousel) || empty($carousel->items))
			throw new CHttpException(404);

		$items = $carousel->items;
		shuffle($items);
		$this->render("//carousels/".$carousel->template, array(
			'client' => $carousel->client,
			'items' => $items,
			'onPage' => $carousel->onPage,
			'urlPrefix' => $carousel->urlPrefix,
		));
	}
}
