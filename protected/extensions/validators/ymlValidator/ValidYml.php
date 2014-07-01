<?php

use m8rge\CurlHelper;

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
            self::$filesCache[$value] = $xmlFile;
			try {
				CurlHelper::downloadToFile($value, $xmlFile);
			} catch (\m8rge\CurlException $e) {
                $this->addError($object, $attribute, 'Произошла ошибка при получении yml файла клиента: ' . $e->getMessage());
                return;
            } catch (CException $e) {}
		}

        $res = false;
		if (file_exists($xmlFile)) {
            $res = $this->validateFile($xmlFile);
            if ($res === true) {
                $res = $this->validateCategoriesTree($xmlFile);
            }
        }
        if ($res !== true) {
            $message = $this->message!==null ? $this->message : $res;
            $this->addError($object, $attribute, $message);
        }
	}

    /**
     * @param string $file
     */
    public function normalizeDTD($file)
    {
        $temp = tempnam(Yii::app()->runtimePath, 'yml-');
        $f = fopen($file, 'r');
        $fw = fopen($temp, 'w');

        $str = fread($f, 512);
        $doctypeMask = '/(?:<!doctype.*?>)?\s*?<yml_catalog/is';
        if (preg_match($doctypeMask, $str, $matches)) {
            $newLines = substr_count($matches[0], "\n");
            $newLines = str_repeat("\n", $newLines);
            $str = preg_replace($doctypeMask, "<!DOCTYPE yml_catalog SYSTEM \"" . __DIR__ . "/shops.dtd\">$newLines<yml_catalog", $str);
            fwrite($fw, $str);
            while (fwrite($fw, fread($f, 1024*512))) {
            }
            fclose($f);
            fclose($fw);
            rename($temp, $file);
        } else {
            fclose($f);
            fclose($fw);
            unlink($temp);
        }
    }

    /**
     * @param $xmlFile
     * @return bool|string true or text error
     */
    public function validateFile($xmlFile)
    {
        $this->normalizeDTD($xmlFile);

        $errors = [];
        libxml_use_internal_errors(true);
        $r = new XMLReader();
        $r->open($xmlFile, null, LIBXML_DTDLOAD | LIBXML_DTDVALID | LIBXML_NONET | LIBXML_NOBLANKS);
        while ($r->read()) {
        }
        $libXmlErrors = libxml_get_errors();
        libxml_clear_errors();

        foreach ($libXmlErrors as $error) {
            if ($error->code == 505) {
                continue; // skip warning due not determinist dtd
            }
            $message = trim($error->message);
            $errors[] = "Line $error->line:$error->column: $message";
        }
        if (!empty($errors)) {
            return Yii::t(
                'ValidYml.app',
                'Following errors occurred during document validate: {errors}',
                array('{errors}' => '<ul><li>'.implode('<li>', array_unique($errors)).'</ul>')
            );
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

    public function validateItems()
    {

    }
}
