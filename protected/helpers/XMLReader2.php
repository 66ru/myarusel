<?php

class XMLReader2 extends XMLReader
{
    /**
     * @param string $nodeName
     * @return $this
     */
    public function readUntil($nodeName) {
        while (!($this->nodeType == XMLReader::ELEMENT && $this->name == $nodeName) && $this->read()) {
        }

        return $this;
    }

    /**
     * @param string $nodeName
     * @param callable $callback
     * @return $this
     */
    public function readAll($nodeName, $callback)
    {
        while ($this->read() && (
                $this->nodeType == XMLReader::TEXT
                || $this->nodeType == XMLReader::END_ELEMENT && $this->name == $nodeName
                || $this->nodeType == XMLReader::ELEMENT && $this->name == $nodeName
            )
        ) {
            if ($this->nodeType == XMLReader::ELEMENT && $this->name == $nodeName) {
                call_user_func($callback);
            }
        }

        return $this;
    }

    public function open($URI, $encoding = null, $options = null)
    {
        if (is_null($options)) {
            $options = LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING;
        }

        return parent::open($URI, $encoding, $options);
    }
} 