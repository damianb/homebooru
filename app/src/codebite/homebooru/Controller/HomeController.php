<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class HomeController
	extends ObjectController
{
	public function before()
	{
		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
	}

	public function runController()
	{
		return $this->respond('home.twig.html', 200, array(
			'page'				=> array(
				'home'				=> true,
			),
		));
	}
}
