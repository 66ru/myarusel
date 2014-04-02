<?php

class m140121_220615_url_postfix extends CDbMigration
{
	public function up()
	{
		$this->addColumn('Carousel','urlPostfix','varchar(255) not null');
	}

	public function down()
	{
		$this->dropColumn('Carousel','urlPostfix');
	}

}