<?php

class m141009_095721_templateId extends CDbMigration
{
	public function up()
	{
		$this->addColumn('Carousel', 'templateId', 'int unsigned NOT NULL');
	}

	public function down()
	{
		$this->dropColumn('Carousel', 'templateId');
	}
}