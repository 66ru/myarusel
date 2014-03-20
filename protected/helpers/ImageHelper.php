<?php

class ImageHelper {
	public static function checkImageCorrect($imagePath) {
		$cmd       = "PATH=\$PATH:/usr/local/bin identify -format \"%w|%h|%k\" ".escapeshellarg($imagePath)." 2>&1";
		$returnVal = 0;
		$output    = array();
		exec($cmd, $output, $returnVal);

		if ($returnVal == 0 && count($output) == 1) return true;
		if ($returnVal == 127) throw new CException('Can\'t find identify');
		return false;
	}
}
