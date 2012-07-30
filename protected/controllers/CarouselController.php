<?php

class CarouselController extends Controller
{
	public function filters()
	{
		return array(
			array(
				'COutputCache',
				'duration'=>3600,
				'varyByParam'=>array('id'),
				'dependency' => new CGlobalStateCacheDependency('invalidateCarousel'.$_GET['id']),
			),
		);
	}

	public function actionShow($id) {
		/** @var $carousel Carousel */
		$carousel = Carousel::model()->with(array(
			'client',
			'items' => array('scopes'=>'onSite')
		))->findByPk($id);

		if (empty($carousel))
			throw new CHttpException(404);

		$this->render('//carousels/default', array(
			'client' => $carousel->client,
			'items' => $carousel->items,
		));
	}
}
