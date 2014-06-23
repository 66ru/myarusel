<?php

/**
 * @property int id
 * @property string name
 * @property string email
 * @property string hashedPassword
 *
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
				'class' => 'vendor.yiiext.activerecord-relation-behavior.EActiveRecordRelationBehavior',
			),
		);
	}

    public function relations()
    {
        return array(
            'authItems' => array(self::MANY_MANY, 'AuthItem', 'AuthAssignment(userid, itemname)'),
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

    public function rules()
	{
		return array(
			array('name', 'required'),
			array('email', 'email'),
			array('name, email', 'unique'),
            array('password', 'PasswordIsSetValidator'),

			array('name, email', 'safe', 'on'=>'search'),
		);
	}

    public function PasswordIsSetValidator($attribute, $params)
    {
        if (empty($this->hashedPassword))
            $this->addError($attribute, 'Необходимо ввести пароль');
    }

    public function getPassword()
    {
        return null;
    }

    public function setPassword($value)
    {
        $this->hashedPassword = md5($value . Yii::app()->params['md5Salt']);
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
