<?php

class m120827_060806_carousel_settings extends CDbMigration
{
	public function up()
	{
		$this->addColumn('carousel','onPage',"int(11) not null DEFAULT '3'");
	}

	public function down()
	{
		$this->dropColumn('carousel','onPage');
	}
}