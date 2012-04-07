<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class HomeController
	extends ObjectController
{
	public function runController()
	{
		$this->app->form->setFormSeed($this->app->session->getSessionSeed());

		$this->response->setBody('home.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'home'				=> true,
			),
		));

		return $this->response;
	}
}
