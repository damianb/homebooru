<?php
namespace codebite\homebooru\Controller;
use \codebite\common\Controller\BaseController;
use \codebite\homebooru\Model\PostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostViewController
	extends BaseController
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
			'id' => $this->id,
		);
	}

	public function runController()
	{
		$bean = R::findOne('post', '(status = ? AND id = ?)', array(PostModel::ENTRY_ACCEPT, $this->id));

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
