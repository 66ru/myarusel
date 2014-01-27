<?php

class TwigFunctions
{
	/**
	 * @param string $className
	 * @param array $properties
	 * @return string
	 */
	public static function widget($className, $properties = array()) {
		$c = Yii::app()->getController();
		return $c->widget($className, $properties, true);
	}

	/**
	 * @param string $prefix
	 * @param string $url
	 * @param string $postfix
	 * @return string
	 */
	public static function createMyarouselLink($url, $prefix = false, $postfix = false) {
		$res = $prefix ? $prefix . str_replace('http://', 'http:/', $url) : $url;
		if ($postfix) {
			$postfix = strstr($res, '?') ? preg_replace('*^\?*', '&', $postfix) : preg_replace('*^\&*', '?', $postfix);
			$res .= $postfix;
		}
		return $res;
	}

}
