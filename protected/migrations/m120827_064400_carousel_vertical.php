<?php

class m120827_064400_carousel_vertical extends CDbMigration
{
	public function up()
	{
		$this->addColumn('carousel','isVertical','int(1) not null UNSIGNED');
	}

	public function down()
	{
		$this->dropColumn('carousel','isVertical');
	}

}