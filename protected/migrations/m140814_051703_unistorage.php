<?php

class m140814_051703_unistorage extends CDbMigration
{
	public function up()
	{
        $this->createTable('unistoragecache', array(
                'cacheKey' => 'varchar(32) NOT NULL DEFAULT \'\'',
                'ttl' => 'int(11) NOT NULL',
                'object' => 'mediumtext NOT NULL',
                'PRIMARY KEY (`cacheKey`)',
                'KEY `ttl` (`ttl`)',
            ), "ENGINE=InnoDB CHARSET=utf8");
	}

	public function down()
	{
        $this->dropTable('unistoragecache');
	}
}