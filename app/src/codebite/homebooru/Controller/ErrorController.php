<?php
namespace codebite\homebooru\Controller;
use \codebite\common\Controller\BaseController;

if(!defined('SHOT_ROOT')) exit;

class ErrorController
	extends BaseController
{
	protected $cacheable = true, $cache_ttl = 300;

	public function runController()
	{
		return $this->respond('error.twig.html', 404, array(
			'error'	=> array(
				'message'		=> 'Not found',
				'code'			=> 404,
			),
		));
	}
}
