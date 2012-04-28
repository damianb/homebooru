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
	const TAG_AUTHOR = 4;
	const TAG_PLANE = 5;
	const TAG_MEDIUM = 6;
	const TAG_ALIAS = 7;

	protected $encounters = 0;

	public function getType()
	{
		switch($this->bean->type)
		{
			case self::TAG_CHARACTER:
				return 'character';
			case self::TAG_AUTHOR:
				return 'author';
			case self::TAG_PLANE:
				return 'plane';
			case self::TAG_MEDIUM:
				return 'medium';
			case self::TAG_ALIAS:
				return 'alias';
			case self::TAG_GENERAL:
			default:
				return 'general';
		}
	}

	public function getDescription()
	{
		switch($this->bean->type)
		{
			case self::TAG_CHARACTER:
				return 'character tag';
			case self::TAG_AUTHOR:
				return 'author tag';
			case self::TAG_PLANE:
				return 'anime/manga series tag';
			case self::TAG_MEDIUM:
				return 'medium/art form tag';
			case self::TAG_ALIAS:
				return 'aliased tag';
			case self::TAG_GENERAL:
			default:
				return 'general use tag';
		}
	}

	public function encounter()
	{
		$this->encounters++;
	}

	public function getEncounters()
	{
		return $this->encounters;
	}
}
