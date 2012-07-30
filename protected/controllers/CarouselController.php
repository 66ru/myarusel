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
						'duration'=>3600,
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

		$this->render('//carousels/default', array(
			'client' => $carousel->client,
			'items' => $carousel->items,
		));
	}
}
