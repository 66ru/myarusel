<?php

/**
 * @property int id
 * @property string name
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

	public function init()
	{
		$this->scenario = 'save';
	}

	public function rules()
	{
		return array(
			array('email', 'email'),
			array('name, email', 'unique'),
			array('name, password', 'required'),
			array('password', 'length', 'is'=>32, 'allowEmpty'=>false, 'on'=>'save'),
			array('password', 'length', 'max'=>31, 'allowEmpty'=>false, 'on'=>'edit'),

			array('name, email', 'safe', 'on'=>'search'),
		);
	}

	public function attributeLabels()
	{
		return array(
			'name' => 'Имя',
			'email' => 'E-mail',
			'password' => 'Пароль',
		);
	}

	public function search()
	{
		$criteria=new CDbCriteria;

		$criteria->compare('name', $this->name, true);
		$criteria->compare('email', $this->email, true);

		return new CActiveDataProvider($this, array(
			'criteria' => $criteria,
		));
	}
}
