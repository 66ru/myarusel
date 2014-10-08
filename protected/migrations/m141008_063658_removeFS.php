<?php

class m141008_063658_removeFS extends CDbMigration
{
	public function up()
	{
		$this->dropColumn('Item', 'imageUid');
		$this->dropColumn('Client', 'logoUid');
	}

	public function down()
	{
		$this->addColumn('Item', 'imageUid', 'varchar(100) NOT NULL DEFAULT \'\'');
		$this->addColumn('Client', 'logoUid', 'varchar(255) NOT NULL DEFAULT \'\'');
	}
}