<?php

class ConstHelper
{
    /**
     * @param mixed $attributes
     * @param string $fieldName
     */
    public static function reformatHtmlFields(&$attributes, $fieldName)
    {
        $formatted = [];
        if (!empty($attributes[$fieldName])) {
            $field = $attributes[$fieldName];
            $iterateAttribute = key($field);
            $fieldAttributes = array_keys($field);
            foreach ($field[$iterateAttribute] as $key => $value) {
                $entry = [];
                foreach ($fieldAttributes as $attribute) {
                    $entry[$attribute] = $field[$attribute][$key];
                }

                $formatted[] = $entry;
            }
            $attributes[$fieldName] = $formatted;
        }
    }
}