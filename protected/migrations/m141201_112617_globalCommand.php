<?php

class m141201_112617_globalCommand extends CDbMigration
{
	public function up()
	{
		$this->execute("
CREATE TABLE `CronLock` (
`taskName` varchar(255) NOT NULL,
`hostname` varchar(255) NOT NULL,
`lastActivity` datetime NOT NULL,
`pid` int(11) unsigned NOT NULL,
PRIMARY KEY (`taskName`),
KEY `hostname` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
	}

	public function down()
	{
		$this->dropTable('CronLock');
	}
}