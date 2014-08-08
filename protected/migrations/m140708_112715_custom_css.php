<?php

class m140121_220615_url_postfix extends CDbMigration
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