<?php

class m141127_064504_ymlId32 extends CDbMigration
{
	public function up()
	{
		$this->alterColumn('Item', 'ymlId', 'varchar(32) not null');
	}

	public function down()
	{
		$this->alterColumn('Item', 'ymlId', 'varchar(20) not null');
	}
}