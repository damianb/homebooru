<?php
namespace codebite\homebooru\Model;
use \R;
use \RedBean_SimpleModel;

if(!defined('SHOT_ROOT')) exit;

class BooruTagModel
	extends RedBean_SimpleModel
{
	const TAG_GENERAL = 2;
	const TAG_CHARACTER = 3;
	const TAG_ENVIRONMENT = 4;
	const TAG_PLANE = 5;

	public function getType()
	{
		switch($this->bean->type)
		{
			case self::TAG_CHARACTER:
				return 'character';
			case self::TAG_ENVIRONMENT:
				return 'environment';
			case self::TAG_PLANE:
				return 'plane';
			case self::TAG_GENERAL:
			default:
				return 'general';
		}
	}
}
