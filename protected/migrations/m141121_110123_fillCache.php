<?php

class m141121_110123_fillCache extends CDbMigration
{
	public function up()
	{
		$this->execute("
CREATE TABLE `ImageCache` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL DEFAULT '',
  `uri` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

		$carousels = EHtml::listData(Carousel::model(), 'id', 'clientId');

		$c = new CDbCriteria();
		$c->group = 'imageHash';
		$itemFilter = Item::model();
		$itemFilter->dbCriteria->mergeWith($c);
		ARHelper::streamProcessInPlainSql($itemFilter, function($fields) use($carousels) {
				$cache = new ImageCache();
				$cache->setAttributes([
						'hash' => $fields['imageHash'],
						'uri' => $fields['imageUri'],
					]);
				$cache->save(false);
			});
		$this->dropColumn('Item', 'imageHash');
	}

	public function down()
	{
		$this->addColumn('Item', 'imageHash', 'varchar(32) NOT NULL');
		$this->dropTable('ImageCache');
	}
}