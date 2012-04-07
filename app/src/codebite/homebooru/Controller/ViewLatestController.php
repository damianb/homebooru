<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class ViewLatestController
	extends ObjectController
{
	public function runController()
	{
		$max = 20;
		$beans = R::find('post', '(status = ? OR status = ?) ORDER BY id desc LIMIT ?', array(BooruPostModel::ENTRY_ACCEPT, BooruPostModel::ENTRY_NOVOTE, $max));

		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
		$this->response->setBody('viewtsun.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'latest'			=> true,
			),
			'entries'			=> $beans,
		));

		return $this->response;
	}
}
