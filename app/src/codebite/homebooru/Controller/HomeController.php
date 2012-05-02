<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class HomeController
	extends BaseController
{
	protected $cacheable = true, $cache_ttl = 900;

	public function runController()
	{
		return $this->respond('home.twig.html', 200, array(
			'page'				=> array(
				'home'				=> true,
			),
		));
	}
}
