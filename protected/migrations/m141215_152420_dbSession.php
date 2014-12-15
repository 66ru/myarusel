<?php

class m141215_152420_dbSession extends CDbMigration
{
	public function up()
	{
		$this->execute("
CREATE TABLE YiiSession (
	id varCHAR(32) PRIMARY KEY,
	expire INTEGER,
	data BLOB
) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	}

	public function down()
	{
		$this->dropTable('YiiSession');
	}
}