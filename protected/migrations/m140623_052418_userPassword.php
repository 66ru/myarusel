<?php

class m140623_052418_userPassword extends CDbMigration
{
	public function up()
	{
        $this->renameColumn('User', 'password', 'hashedPassword');
	}

	public function down()
	{
        $this->renameColumn('User', 'hashedPassword', 'password');
	}
}