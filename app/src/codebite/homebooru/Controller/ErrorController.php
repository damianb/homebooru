<?php
namespace codebite\homebooru\Controller;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class ErrorController
	extends ObjectController
{
	public function runController()
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
}
