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
		$beans = R::findAll('tag', 'ORDER BY title ASC');

		return $this->respond('viewtags.twig.html', 200, array(
			'page'				=> array(
				'tags'				=> true,
			),
			'tags'				=> $beans,
		));
	}
}
