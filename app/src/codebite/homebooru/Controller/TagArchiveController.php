<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \codebite\homebooru\Model\BooruTagModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class TagArchiveController
	extends ObjectController
{
	public function runController()
	{
		$max = 100;
		$page = $this->request->getRoute()->get('page') ?: 1;
		$beans = R::findAll('tag', 'ORDER BY title ASC');

		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
		$this->response->setBody('viewtags.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'tags'				=> true,
			),
			'tags'				=> $beans,
		));

		return $this->response;
	}
}
