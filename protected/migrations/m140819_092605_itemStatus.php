<?php

class m140819_092605_itemStatus extends CDbMigration
{
	public function up()
	{
        $this->addColumn('Item', 'status', 'int not null');
	}

	public function down()
	{
        $this->dropColumn('Item', 'status');
	}
}