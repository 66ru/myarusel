<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
	private $_id;

	public function authenticate()
	{
        /** @var User $record */
		$record = User::model()->findByAttributes(array('name' => $this->username));
		if ($record === null)
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		else if ($record->hashedPassword !== md5($this->password . Yii::app()->params['md5Salt']))
			$this->errorCode = self::ERROR_PASSWORD_INVALID;
		else {
			$this->_id = $record->id;
			$this->errorCode = self::ERROR_NONE;
		}
		return !$this->errorCode;
	}

	public function getId()
	{
		return $this->_id;
	}
}