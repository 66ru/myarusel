<?php

use Imagecow\Image;

/**
 * Upload files, images. Resize images. Get URLs.
 */
class FileSystem extends CComponent
{
    /**
     * @var null|string path to storage dir. Default = www/storage
     */
    public $storagePath = null;
    /**
     * @var null|string url to storage dir. Default = /storage
     */
    public $storageUrl = null;

    /**
     * @var int how much intermediate folders must be created in storage folder for publishing file. <br />
     * <b>WARNING!</b> Do not change if storage dir is not empty!
     */
    public $nestedFolders = 0;

    /**
     * @var int jpeg compress quality (0-100)
     */
    public $jpegQuality = 90;

    public function init()
    {
        if (is_null($this->storagePath)) {
            $this->storagePath = Yii::app()->basePath . '/../www/storage';
        }
        if (is_null($this->storageUrl)) {
            $this->storageUrl = '/storage';
        }
        if (!file_exists($this->storagePath)) {
            mkdir($this->storagePath, 0777, true);
        }
        $this->storagePath = realpath($this->storagePath);
        if (!is_dir($this->storagePath)) {
            throw new CException('FileSystem->storagePath is not dir (' . $this->storagePath . ')');
        }
    }

    /**
     * @return string
     */
    private function getUniqId()
    {
        return uniqid();
    }

    /**
     * @param string $uid
     * @return string
     */
    public function getIntermediatePath($uid)
    {
        $path = [];
        $fileName = pathinfo($uid, PATHINFO_FILENAME);
        for ($i = 0; $i < $this->nestedFolders; $i++) {
            $path []= substr($fileName, -$i - 1, 1);
        }

        return implode('/', $path);
    }

    /**
     * @param $carouselId
     * @return string
     */
    public function getIntermediateCarouselPath($carouselId)
    {
        return 'carousel' . $carouselId;
    }

    /**
     * @param string $uid
     * @return string full path in filesystem to file
     */
    public function getFilePath($uid)
    {
        return $this->storagePath . '/' . $this->getIntermediatePath($uid) . '/' . $uid;
    }

    /**
     * @param $carouselId
     * @param string $uid
     * @return string full path in filesystem to file
     */
    public function getCarouselFilePath($carouselId, $uid)
    {
        return $this->storagePath . '/' . $this->getIntermediateCarouselPath($carouselId) . '/' . $uid;
    }

    /**
     * @param string $uid
     * @return string Url to file
     */
    public function getFileUrl($uid)
    {
        return $this->storageUrl . '/' . $this->getIntermediatePath($uid) . '/' . $uid;
    }

    /**
     * @param int $carouselId
     * @param string $uid
     * @return string Url to file
     */
    public function getCarouselFileUrl($carouselId, $uid)
    {
        return $this->storageUrl . '/' . $this->getIntermediateCarouselPath($carouselId) . '/' . $uid;
    }

    /**
     * @param string $fileName uploaded file
     * @param string $originalName original file name. Used for getting extension. If null - using $fileName.
     * @return string Uid of new file
     */
    public function publishFile($fileName, $originalName = null)
    {
        if (is_null($originalName)) {
            $originalName = $fileName;
        }
        $ext = strtolower(CFileHelper::getExtension($originalName));
        if (empty($ext)) { // we have empty extension. Trying determine using mime type
            $ext = EFileHelper::getExtensionByMimeType($fileName);
        }
        if (!empty($ext)) {
            $ext = '.' . $ext;
        }

        $uid = $this->getUniqId() . $ext;
        $publishedFileName = $this->getFilePath($uid);
        $newDirName = pathinfo($publishedFileName, PATHINFO_DIRNAME);
        if (!file_exists($newDirName)) {
            mkdir($newDirName, 0777, true);
        }

        copy($fileName, $publishedFileName);

        return $uid;
    }

    /**
     * @param string $fileName
     * @param string $fileUniqueId
     * @param int $carouselId
     * @return string Uid of new file
     */
    public function publishFileForCarousel($fileName, $fileUniqueId, $carouselId)
    {
        $ext = EFileHelper::getExtensionByMimeType($fileName);
        if (!empty($ext)) {
            $ext = '.' . $ext;
        }

        $uid = md5($fileUniqueId) . $ext;
        $publishedFileName = $this->getCarouselFilePath($carouselId, $uid);
        $newDirName = pathinfo($publishedFileName, PATHINFO_DIRNAME);
        if (!file_exists($newDirName)) {
            mkdir($newDirName, 0777, true);
        }

        copy($fileName, $publishedFileName);

        return $uid;
    }

    /**
     * @param string $uid
     */
    public function removeFile($uid)
    {
        $filePath = $this->getFilePath($uid);
        $dirName = pathinfo($filePath, PATHINFO_DIRNAME);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);
        foreach (glob($dirName . '/' . $fileName . '*') as $file) {
            unlink($file);
        }
    }

    /********************************/

    /**
     * @param string $uid
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getResizedImageUrl($uid, $width, $height)
    {
        $fileName = pathinfo($uid, PATHINFO_FILENAME);
        $ext = pathinfo($uid, PATHINFO_EXTENSION);
        if (!empty($ext)) {
            $ext = '.' . $ext;
        }

        return $this->getFileUrl($fileName) . $this->getSizeSuffix($width, $height) . $ext;
    }

    /**
     * @param int $carouselId
     * @param string $uid
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getResizedCarouselImageUrl($carouselId, $uid, $width, $height)
    {
        $fileName = pathinfo($uid, PATHINFO_FILENAME);
        $ext = pathinfo($uid, PATHINFO_EXTENSION);
        if (!empty($ext)) {
            $ext = '.' . $ext;
        }

        return $this->getCarouselFileUrl($carouselId, $fileName) . $this->getSizeSuffix($width, $height) . $ext;
    }

    /**
     * @param string $uid
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getResizedImagePath($uid, $width, $height)
    {
        $fileName = pathinfo($uid, PATHINFO_FILENAME);
        $ext = pathinfo($uid, PATHINFO_EXTENSION);
        if (!empty($ext)) {
            $ext = '.' . $ext;
        }

        return $this->getFilePath($fileName) . $this->getSizeSuffix($width, $height) . $ext;
    }

    /**
     * @param int $carouselId
     * @param string $uid
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getResizedCarouselImagePath($carouselId, $uid, $width, $height)
    {
        $fileName = pathinfo($uid, PATHINFO_FILENAME);
        $ext = pathinfo($uid, PATHINFO_EXTENSION);
        if (!empty($ext)) {
            $ext = '.' . $ext;
        }

        return $this->getCarouselFilePath($carouselId, $fileName) . $this->getSizeSuffix($width, $height) . $ext;
    }

    /**
     * @param int $width
     * @param int $height
     * @return string
     */
    public function getSizeSuffix($width, $height)
    {
        return "-{$width}x{$height}";
    }

    /**
     * @param string $uid
     * @param int $width
     * @param int $height
     * @param bool $forceCreate
     * @return void
     */
    public function resizeImage($uid, $width, $height, $forceCreate = false)
    {
        $resizedImagePath =  $this->getResizedImagePath($uid, $width, $height);
        if (!file_exists($resizedImagePath) || $forceCreate) {
            $image = Image::create($this->getFilePath($uid));
            $image->resize($width, $height)->setCompressionQuality(95)->save($resizedImagePath);
        }
    }

    /**
     * @param int $carouselId
     * @param string $uid
     * @param int $width
     * @param int $height
     * @param bool $forceCreate
     */
    public function resizeCarouselImage($carouselId, $uid, $width, $height, $forceCreate = false)
    {
        $resizedImagePath = $this->getResizedCarouselImagePath($carouselId, $uid, $width, $height);
        if (!file_exists($resizedImagePath) || $forceCreate) {
            $image = Image::create($this->getCarouselFilePath($carouselId, $uid));
            $image->resize($width, $height)->setCompressionQuality(95)->save($resizedImagePath);
        }
    }
}
