<?php

/**
 * @property int id
 * @property string email
 * @property string password
 */
class User extends CActiveRecord
{
	/**
	 * @static
	 * @param string $className
	 * @return User|CActiveRecord
	 */
	public static function model($className = __CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @param array $attributes
	 * @param string $condition
	 * @param array $params
	 * @return User|CActiveRecord
	 */
	public function findByAttributes($attributes, $condition = '', $params = array())
	{
		return parent::findByAttributes($attributes, $condition, $params);
	}

	public function rules()
	{
		return array(
			array('email', 'email', 'allowEmpty'=>false),
			array('email', 'unique'),
			array('password', 'length', 'is'=>32, 'allowEmpty'=>false),
		);
	}
}
