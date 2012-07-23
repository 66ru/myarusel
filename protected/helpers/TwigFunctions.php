<?php

class TwigFunctions
{
	/**
	 * @param string $className
	 * @param array $properties
	 * @return string
	 */
	public function widget($className, $properties) {
		$c = Yii::app()->getController();
		return $c->widget($className, $properties, true);
	}
}
