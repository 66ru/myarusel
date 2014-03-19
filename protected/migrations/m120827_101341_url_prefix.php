<?php

class m120827_101341_url_prefix extends CDbMigration
{
	public function up()
	{
		$this->addColumn('Carousel','urlPrefix','varchar(255) not null');
	}

	public function down()
	{
		$this->dropColumn('Carousel','urlPrefix');
	}

}