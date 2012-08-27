<?php

class m120827_061344_carouselGroups extends CDbMigration
{
	public function up()
	{
		$this->addColumn('Carousel', 'viewType', 'int(11) unsigned NOT NULL');
		$this->execute("UPDATE Carousel SET viewType=1 WHERE onlyCheap=1");
		$this->dropColumn('Carousel', 'onlyCheap');
	}

	public function down()
	{
		$this->addColumn('Carousel', 'onlyCheap', 'int(1) NOT NULL');
		$this->execute("UPDATE Carousel SET onlyCheap=1 WHERE viewType=1");
		$this->dropColumn('Carousel', 'viewType');
	}
}