<?php
namespace codebite\homebooru\Model;
use \codebite\homebooru\Internal\SubmitFailException;
use \R;
use \RedBean_SimpleModel;
use \codebite\homebooru\WebKernel as App;

if(!defined('SHOT_ROOT')) exit;

class BooruPostModel
	extends RedBean_SimpleModel
{
	protected $_app, $tags;

	const ENTRY_QUEUE = 2;
	const ENTRY_DENY = 3;
	const ENTRY_SPAM = 4;
	const ENTRY_ACCEPT = 5;
	const ENTRY_LOCKED = 6;

	const RATING_SAFE = 2;
	const RATING_QUESTIONABLE = 3;
	const RATING_EXPLICIT = 4;
	const RATING_UNKNOWN = 5;

	public function __construct()
	{
		$this->_app = App::getInstance();
	}

	public function update()
	{
		if(!SHOT_DEBUG)
		{
			if(filter_var($this->submitter_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 & FILTER_FLAG_IPV6) === false)
			{
				throw new SubmitFailException('Invalid IP address');
			}
		}

		$this->tags = NULL;

		// NUL-byte check.
		//if(strpos($this->text, chr(0)) !== false || strpos($this->submitter_name, chr(0)) !== false || strpos($this->submitter_email, chr(0)) !== false)
		//{
		//	throw new SubmitFailException('Take your NUL bytes elsewhere kthxbai');
		//}
	}

	public function getTags()
	{
		if(!$this->tags)
		{
			$this->tags = R::tag($this->bean);
			sort($this->tags, SORT_STRING);
		}

		return $this->tags;
	}

	public function getFullTags()
	{
		if(!$this->tags)
		{
			$this->tags = R::tag($this->bean);
			sort($this->tags, SORT_STRING);
		}

		if(!$this->tag_beans)
		{
			$this->tag_beans = R::find('tag', implode(' OR ', array_fill(0, count($this->tags), 'title = ?')) . ' ORDER BY title ASC', $this->tags);
		}

		return $this->tag_beans;
	}

	public function getRating()
	{
		switch($this->bean->rating)
		{
			case self::RATING_SAFE:
				return 'safe';
			case self::RATING_QUESTIONABLE:
				return 'questionable';
			case self::RATING_EXPLICIT:
				return 'explicit';
			case self::RATING_UNKNOWN:
			default:
				return 'unknown';
		}
	}

	public function sourceLinkable()
	{
		return (bool) filter_var($this->bean->source, FILTER_VALIDATE_URL);
	}

	public function bestFit()
	{
		if($this->bean->small_file)
		{
			return array(
				'file'		=> $this->bean->small_file,
				'path'		=> $this->_app['site.smallurl'],
				'height'	=> $this->bean->small_height,
				'width'		=> $this->bean->small_width,
			);
		}
		else
		{
			return array(
				'file'		=> $this->bean->full_file,
				'path'		=> $this->_app['site.imageurl'],
				'height'	=> $this->bean->full_height,
				'width'		=> $this->bean->full_width,
			);
		}
	}

	public function getFullSize()
	{
		return \codebite\homebooru\Runtime\formatBytes($this->bean->full_size);
	}

	public function getSmallSize()
	{
		return \codebite\homebooru\Runtime\formatBytes($this->bean->small_size);
	}

	public function getThumbSize()
	{
		return \codebite\homebooru\Runtime\formatBytes($this->bean->thumb_size);
	}
}
