<?php

class m140708_112715_custom_css extends CDbMigration
{
	public function up()
	{
		$this->addColumn('carousel','customCss',"varchar(255) not null DEFAULT ''");
	}

	public function down()
	{
		$this->dropColumn('carousel','customCss');
	}

}