<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostViewController
	extends ObjectController
{
	public function runController()
	{
		$id = $this->request->getRoute()->get('id');

		$bean = R::findOne('post', '(status = ? AND id = ?)', array(BooruPostModel::ENTRY_QUEUE, $id));

		//$tag = R::findOne('tag', 'title = ?', array('shakugan_no_shana'));
		//$tag->type = \codebite\homebooru\Model\BooruTagModel::TAG_PLANE;
		//R::store($tag);
		//R::addTags($bean, 'shakugan_no_shana');

		if(empty($bean))
		{
			$this->response->setResponseCode(404);
			$this->response->setBody('error.twig.html');
			$this->response->setTemplateVars(array(
				'error'	=> array(
					'message'		=> 'Not found',
					'code'			=> 404,
				),
			));

			return $this->response;
		}

		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
		$this->response->setBody('viewsingle.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'single'			=> true,
			),
			'post'				=> $bean,
		));

		return $this->response;
	}
}
