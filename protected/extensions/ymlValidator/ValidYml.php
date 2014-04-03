<?php

use m8rge\CurlHelper;
require_once __DIR__ . '/SilentDOMDocument.php';

/**
 * fetch file from url from attribute and check it for valid yml inside
 */
class ValidYml extends CValidator
{
	public $useCache = true;

	private static $filesCache = array();

	function __construct()
	{
        $eventHandlers = Yii::app()->getEventHandlers('onEndRequest');
        foreach ($eventHandlers as $eventHandler) {
            if ($eventHandler == array('ValidYml', 'removeTemporaryFiles')) {
                return;
            }
        }

        Yii::app()->attachEventHandler('onEndRequest', array('ValidYml', 'removeTemporaryFiles'));
	}

	public static function removeTemporaryFiles(){
		foreach(self::$filesCache as $file) {
			if (file_exists($file)) {
				unlink($file);
			}
		}
	}

    /**
	 * Validates a single attribute.
	 * This method should be overridden by child classes.
	 * @param CModel $object the data object being validated
	 * @param string $attribute the name of the attribute to be validated.
	 */
	protected function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if ($this->useCache && !empty(self::$filesCache[$value])) {
			$xmlFile = self::$filesCache[$value];
		} else {
			$xmlFile = tempnam(Yii::app()->getRuntimePath(), 'xml');
			try {
				CurlHelper::downloadToFile($value, $xmlFile);
				self::$filesCache[$value] = $xmlFile;
			} catch (\m8rge\CurlException $e) {
                $this->addError($object, $attribute, 'Произошла ошибка при получении yml файла клиента: ' . $e->getMessage());
                return;
            } catch (CException $e) {}
		}

        $res = false;
		if (file_exists($xmlFile)) {
            $res = $this->validateFile($xmlFile);
            if ($res) {
                $res = $this->validateCategoriesTree($xmlFile);
            }
        }
        if ($res !== true) {
            $message = $this->message!==null ? $this->message : $res;
            $this->addError($object, $attribute, $message);
        }
	}

    /**
     * @param $xmlFile
     * @return bool|string true or text error
     */
    public function validateFile($xmlFile)
    {
        $root = 'yml_catalog';

        $xmlDocument = new DOMDocument();
        $validXml = @$xmlDocument->loadXML(file_get_contents($xmlFile));
        if ($validXml === false) {
            return Yii::t('ValidYml.app','{attribute} doesn\'t contain valid XML.');
        } else {
            $creator = new DOMImplementation;
            $docType = $creator->createDocumentType($root, null, __DIR__ . '/shops.dtd');
            $validateXmlDocument = $creator->createDocument($root, null, $docType);
            $validateXmlDocument->encoding = $xmlDocument->encoding ? $xmlDocument->encoding : 'utf-8';

            $oldNode = $xmlDocument->getElementsByTagName($root)->item(0);
            $newNode = $validateXmlDocument->importNode($oldNode, true);
            $validateXmlDocument->appendChild($newNode);
            $validateXmlDocument = new SilentDOMDocument($validateXmlDocument);
            if (@$validateXmlDocument->validate() === false) {
                return Yii::t(
                    'ValidYml.app',
                    'Following errors occurred during document validate: {errors}',
                    array('{errors}' => '<ul><li>'.implode('<li>', $validateXmlDocument->errors).'</ul>')
                );
            }
        }

        return true;
    }

    public function validateCategoriesTree($xmlFile)
    {
        $categories = YMLHelper::getCategories($xmlFile);
        $missingCategories = array();
        foreach ($categories as $category) {
            if (!empty($category['parentId']) && !array_key_exists($category['parentId'], $categories)) {
                $missingCategories[] = $category['parentId'];
            }
        }
        if (!empty($missingCategories)) {
            $missingCategories = array_unique($missingCategories);
            return Yii::t(
                'ValidYml.app',
                'Missing category ids: {errors}',
                array('{errors}' => '<ul><li>'.implode('<li>', $missingCategories).'</ul>')
            );
        } else {
            return true;
        }
    }
}
