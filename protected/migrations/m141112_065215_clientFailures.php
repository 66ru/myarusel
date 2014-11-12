<?php

class m141112_065215_clientFailures extends CDbMigration
{
	public function up()
	{
		$this->addColumn('Client', 'failures', 'int unsigned not null');
	}

	public function down()
	{
		$this->dropColumn('Client', 'failures');
	}
}