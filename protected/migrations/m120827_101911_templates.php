<?php

class m120827_101911_templates extends CDbMigration
{
	public function up()
	{
		$this->addColumn('Carousel', 'template', 'varchar(255) NOT NULL');
		$this->execute("UPDATE Carousel SET template='vertical' WHERE isVertical=1");
		$this->execute("UPDATE Carousel SET template='horizontal' WHERE isVertical=0");
		$this->dropColumn('Carousel', 'isVertical');
	}

	public function down()
	{
		$this->addColumn('Carousel', 'isVertical', 'int(1) unsigned NOT NULL');
		$this->execute("UPDATE Carousel SET isVertical=1 WHERE template='vertical'");
		$this->dropColumn('Carousel', 'template');
	}
}