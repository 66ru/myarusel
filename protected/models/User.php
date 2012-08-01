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

	public function behaviors()
	{
		return array(
			'manyToMany' => array(
				'class' => 'lib.ar-relation-behavior.EActiveRecordRelationBehavior',
			),
		);
	}

	public function rules()
	{
		return array(
			array('name', 'required'),
			array('email', 'email'),
			array('name, email', 'unique'),
			array('password', 'required', 'on'=>'insert'),
			array('password', 'length', 'max'=>31, 'on'=>'insert,update'),
			array('password', 'length', 'is'=>32, 'allowEmpty'=>false, 'on'=>'save'),

			array('name, email', 'safe', 'on'=>'search'),
		);
	}

	public function attributeLabels()
	{
		return array(
			'name' => 'Имя',
			'email' => 'E-mail',
			'password' => 'Пароль',
			'authItems' => 'Права',
		);
	}

	public function relations()
	{
		return array(
			'authItems' => array(self::MANY_MANY, 'AuthItem', 'AuthAssignment(userid, itemname)'),
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
