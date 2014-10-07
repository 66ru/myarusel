<?php

class m140814_060003_unistorageMigrate extends CDbMigration
{
	public function up()
	{
        $itemColumns = Item::model()->metaData->columns;
        if (empty($itemColumns['imageUri'])) {
            $this->addColumn('Item', 'imageUri', 'varchar(100) not null');
        }
        if (empty($itemColumns['imageHash'])) {
            $this->addColumn('Item', 'imageHash', 'varchar(32) not null');
        }
        if (empty($itemColumns['ymlId'])) {
            $this->addColumn('Item', 'ymlId', 'varchar(20) not null');
        }
        $clientColumns = Client::model()->metaData->columns;
        if (empty($clientColumns['logoUri'])) {
            $this->addColumn('Client', 'logoUri', 'varchar(100) not null');
        }

        /** @var FileSystem $fs */
        $fs = Yii::app()->fs;
        /** @var YiiUnistorage $unistorage */
        $unistorage = Yii::app()->unistorage;

        ARHelper::processInBatch(Client::model(), function ($client) use ($fs, $unistorage) {
                /** @var Client $client */
                if (!empty($client->logoUid) && empty($client->logoUri)) {
                    $carouselFilePath = $fs->getFilePath($client->logoUid);
                    if (file_exists($carouselFilePath)) {
                        $file = $unistorage->uploadFile($carouselFilePath);
                        $client->logoUri = $file->resourceUri;
                        if (!$client->save()) {
                            throw new CantSaveActiveRecordException($client);
                        }
                    }
                }
            });
	}

	public function down()
	{
        $this->dropColumn('Item', 'imageUri');
        $this->dropColumn('Item', 'imageHash');
        $this->dropColumn('Item', 'ymlId');
        $this->dropColumn('Client', 'logoUri');
	}
}