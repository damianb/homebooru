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
		return $this->respond('error.twig.html', 404, array(
			'error'	=> array(
				'message'		=> 'Not found',
				'code'			=> 404,
			),
		));
	}
}
