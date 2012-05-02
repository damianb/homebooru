<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class CachedController
	extends BaseController
{
	protected $cacheable = false, $bind = '';
	private $original_controller;

	public function setOriginalController(BaseController $controller)
	{
		$this->original_controller = $controller;

		return $this;
	}

	public function setCacheBind($bind)
	{
		$this->bind = $bind;

		return $this;
	}

	public function runController()
	{
		$page = $this->app->cache->loadData($this->bind);

		$this->response->disableTemplating()
			->setResponseCode($page['http_status'])
			->setHeader('X-App-Magic-Cache', 'SERVE')
			->setBody($page['body']);

		return $this->response;
	}
}
