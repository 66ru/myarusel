<?php

class m120827_060806_carousel_settings extends CDbMigration
{
	public function up()
	{
		$this->addColumn('Carousel','onPage',"int(11) not null DEFAULT '3'");
	}

	public function down()
	{
		$this->dropColumn('Carousel','onPage');
	}
}