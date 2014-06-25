<?php

class CleanupCommand extends ConsoleCommand
{
    public function actionIndex()
    {
        /** @var $fs FileSystem */
        $fs = Yii::app()->fs;
        /** @var Client[] $clients */
        $clients = Client::model()->findAll();

        $preserve = [];
        foreach ($clients as $client) {
            $filePath = $fs->getFilePath($client->logoUid);
            $preserve[] = pathinfo($filePath, PATHINFO_FILENAME);
        }

        $root = opendir($fs->storagePath);
        while ($dir = readdir($root)) {
            
        }
    }
} 