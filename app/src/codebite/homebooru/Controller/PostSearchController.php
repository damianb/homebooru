<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostSearchController
	extends ObjectController
{
	public function runController()
	{
		$max = 20;

		$tags = $this->request->getInput('REQUEST::q', '');

		preg_match_all('#\w+[\w\(\)]*#i', $tags, $_tags);
		$tags = array_unique(array_shift($_tags));

		$beans = R::taggedAll('post', $tags);

		$this->app->form->setFormSeed($this->app->session->getSessionSeed());

		$this->response->setBody('viewposts.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'search'			=> true,
			),
			'posts'				=> $beans,
		));

		return $this->response;
	}
}
