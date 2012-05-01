<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \emberlabs\shot\WebKernel;
use \emberlabs\shot\Request\RequestInterface;
use \emberlabs\shot\Response\ResponseInterface;
use \R;

if(!defined('SHOT_ROOT')) exit;

abstract class BaseController
	extends ObjectController
{
	const SEARCH_MAX = 24;

	protected $cacheable = false, $cache_ttl = 0;

	final public function __construct(WebKernel $app, RequestInterface $request, ResponseInterface $response)
	{
		parent::__construct($app, $request, $response);
		$this->init();
	}

	protected function init() { }

	final public function isCacheable()
	{
		return $this->cacheable;
	}

	protected function defineCacheBinds()
	{
		// this should probably be overridden by the child controller
		return array();
	}

	final public function getCacheBinds()
	{
		return array_merge($this->defineCacheBinds(), array(
			get_class($this),
		));
	}

	final public function getCacheTTL()
	{
		return (int) $this->cache_ttl;
	}

	final public function buildPagination($page, $total, $max)
	{
		$total_pages = floor((($total % $max) != 0) ? ($total / $max) + 1 : $total / $max);

		// Run through and generate a number of page links...
		$p = array();
		for($i = -3; $i <= 3; $i++)
		{
			// outside of page range? SKIP IT!
			if(($page + $i < 1) || ($page + $i > $total_pages))
			{
				continue;
			}

			$p[] = $page + $i;
		}
		$pagination = array(
			'first'		=> 1,
			'prev'		=> ($page != 1) ? $page - 1 : false,
			'current'	=> $page,
			'next'		=> (($page + $max) > $total) ? $page + 1 : false,
			'pages'		=> $p,
			'last'		=> $total_pages,
			'total'		=> $total,
		);

		return $pagination;
	}
}
