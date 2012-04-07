<?php
namespace codebite\homebooru\Model;
use \R;
use \RedBean_SimpleModel;
use \codebite\homebooru\WebKernel as App;
use \emberlabs\openflame\Core\Utility\JSON;

if(!defined('SHOT_ROOT')) exit;

class BooruSessionModel
	extends RedBean_SimpleModel
{
	public function dispense()
	{
		$this->bean->time = time();
		$this->bean->setMeta('data.store', array());
	}

	public function open()
	{
		$this->bean->time = time();
		$this->bean->setMeta('data.store', JSON::decode($this->bean->data));
	}

	public function update()
	{
		$this->bean->time = time();
		$this->bean->data = JSON::encode($this->bean->getMeta('data.store'));
	}

}
