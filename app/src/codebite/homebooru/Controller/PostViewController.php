<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostViewController
	extends ObjectController
{
	protected $cacheable = true, $cache_ttl = 60;
	private $id;

	public function init()
	{
		$this->id = $this->getRouteInput('id');
	}

	protected function defineCacheBinds()
	{
		return array(
			'id' => $id,
		);
	}

	public function runController()
	{
		$bean = R::findOne('post', '(status = ? AND id = ?)', array(BooruPostModel::ENTRY_ACCEPT, $this->id));

		if(empty($bean))
		{
			return $this->respond('error.twig.html', 404, array(
				'error'	=> array(
					'message'		=> 'Not found',
					'code'			=> 404,
				),
			));
		}

		return $this->respond('viewsingle.twig.html', 200, array(
			'page'				=> array(
				'single'			=> true,
			),
			'post'				=> $bean,
		));
	}
}
