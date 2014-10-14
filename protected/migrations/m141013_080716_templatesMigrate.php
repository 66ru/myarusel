<?php

class m141013_080716_templatesMigrate extends CDbMigration
{
	public function migrateTemplate($oldName, $newName, $logoSize, $thumbSize)
	{
		$this->insert('Template', [
				'name' => $newName,
				'html' => file_get_contents(__DIR__ . '/assets/'.$oldName.'.html'),
				'variables' => json_encode([
						[
							'name' => 'color',
							'label' => 'Цвет',
						],
						[
							'name' => 'onPage',
							'label' => 'Товаров в блоке',
						],
					]),
				'logoWidth' => $logoSize[0],
				'logoHeight' => $logoSize[1],
				'itemWidth' => $thumbSize[0],
				'itemHeight' => $thumbSize[1],
			]);

		$this->execute("UPDATE Carousel SET templateId = :templateId WHERE template = :textTemplate", [
				':templateId' => $this->getDbConnection()->lastInsertID,
				':textTemplate' => $oldName,
			]);
	}

	public function migrateTemplateVariables()
	{
		$data = $this->getDbConnection()->createCommand("
			SELECT C.id, C.`onPage`, CL.`color`
			FROM Carousel C
			JOIN `Client` CL ON C.`clientId` = CL.id")->queryAll();

		foreach ($data as $row) {
			$id = $row['id'];
			unset($row['id']);
			$this->update('Carousel', [
					'variables' => json_encode($row),
				], 'id = :id', [
					':id' => $id,
				]);
		}
	}

	public function up()
	{
		$this->addColumn('Carousel', 'variables', 'text NOT NULL');

		$this->migrateTemplate('horizontal', 'Горизонтальный', [120, 120], [90, 90]);
		$this->migrateTemplate('orangehorizontal', 'Горизонтальный с оранжевой рамкой', [120, 120], [90, 90]);
		$this->migrateTemplate('narrowhorizontal', 'Горизонтальный узкий', [125, 125], [70, 70]);
		$this->migrateTemplate('verticalbig', 'Вертикальный с одной большой картинкой', [450, 100], [220, 330]);
		$this->migrateTemplate('vertical', 'Вертикальный', [220, 150], [86, 86]);

		$this->migrateTemplateVariables();

		$this->dropColumn('Carousel', 'template');
		$this->dropColumn('Carousel', 'customCss');
		$this->dropColumn('Carousel', 'onPage');
		$this->dropColumn('Client', 'color');

		foreach (Carousel::model()->findAll() as $carousel) {
			/** @var Carousel $carousel */
			$carousel->invalidate();
		}
	}

	public function down()
	{
		echo "m141013_080716_templatesMigrate does not support migration down.\n";
		return false;
	}
}