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
            if (!empty($client->logoUid)) {
                $filePath = $fs->getFilePath($client->logoUid);
                $preserve[] = pathinfo($filePath, PATHINFO_FILENAME);
            }
        }

        $rootHandle = opendir($fs->storagePath);
        while (false !== $dir = readdir($rootHandle)) {
            if ($dir != '.' && strlen($dir) == 1 && is_dir($fs->storagePath.'/'.$dir)) {
                $innerHandle = opendir($fs->storagePath.'/'.$dir);
                while ($file = readdir($innerHandle)) {
                    if (strpos($file, '.') === 0) {
                        continue;
                    }
                    $uid = pathinfo($file, PATHINFO_FILENAME);
                    $uid = preg_replace('/-.*?$/', '', $uid);
                    if (!in_array($uid, $preserve)) {
                        unlink($fs->storagePath.'/'.$dir.'/'.$file);
                    }
                }
                closedir($innerHandle);
            }
        }
        closedir($rootHandle);
    }
} 