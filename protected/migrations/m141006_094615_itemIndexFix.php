<?php

class m141006_094615_itemIndexFix extends CDbMigration
{
	public function up()
	{
		$this->dropIndex('carouselId', 'Item');
		$this->createIndex('carouselId', 'Item', 'carouselId,status');
	}

	public function down()
	{
		$this->dropIndex('carouselId', 'Item');
		$this->createIndex('carouselId', 'Item', 'carouselId');
	}
}