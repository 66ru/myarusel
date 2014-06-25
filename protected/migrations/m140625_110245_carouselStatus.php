<?php

class m140625_110245_carouselStatus extends CDbMigration
{
	public function up()
	{
        $this->addColumn('Carousel', 'status', 'int not null');
        $this->execute('UPDATE Carousel SET status = 1');
	}

	public function down()
	{
        $this->dropColumn('Carousel', 'status');
	}
}