<?php

class m140819_092605_itemStatus extends CDbMigration
{
	public function up()
	{
        $this->addColumn('Item', 'status', 'int not null');
		$this->execute("UPDATE `Item` set `status` = 1");
	}

	public function down()
	{
        $this->dropColumn('Item', 'status');
	}
}