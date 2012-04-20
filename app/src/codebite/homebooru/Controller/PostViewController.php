<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostViewController
	extends ObjectController
{
	public function before()
	{
		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
	}

	public function runController()
	{
		$id = $this->getRouteInput('id');

		$bean = R::findOne('post', '(status = ? AND id = ?)', array(BooruPostModel::ENTRY_ACCEPT, $id));

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
