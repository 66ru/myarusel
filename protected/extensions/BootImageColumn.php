<?php

class BootImageColumn extends BootDataColumn
{
	public $type='raw';

	public $filter = false;

	public $htmlOptions = array('style'=>'width:120px');

	public function init()
	{
		$this->value = '
			CHtml::link(
				CHtml::image($data->'.$this->name.'),
				$data->'.$this->name.',
				array("target" => "_blank")
			);';

		parent::init();
	}

}
