<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class CachedController
	extends BaseController
{
	protected $cacheable = false, $cache = '';
	private $original_controller;

	public function setOriginalController(BaseController $controller)
	{
		$this->original_controller = $controller;

		return $this;
	}

	public function loadCache($cache)
	{
		$this->cache = $cache;

		return $this;
	}

	public function runController()
	{

		$this->response->disableTemplating()
			->setHeader('X-App-Magic-Cache', 'HIT')
			->setContentType($this->cache['content_type'])
			->setResponseCode($this->cache['http_status'])
			->setBody($this->cache['body']);

		return $this->response;
	}
}
