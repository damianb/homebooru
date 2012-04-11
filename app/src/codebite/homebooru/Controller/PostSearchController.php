<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostSearchController
	extends ObjectController
{
	const SEARCH_MAX = 20;

	public function runController()
	{
		$max = self::SEARCH_MAX;

		$tags = $this->request->getInput('REQUEST::q', '');

		$tags = explode(' ', $tags);

		// sort out "normal" tags
		$normal_tags = preg_grep('#^\-?[\w]+[\w\(\)]*$#i', $tags);
		$_normal_tags = array_unique($normal_tags);

		// sort out magical "parameter" tags
		$param_tags = preg_grep('#^\-?[\w]+[\w\(\)]+\:[\w\.\(\)]*$#i', $tags);
		$_param_tags = array_unique($param_tags);

		// nuke out the old variables for resorting in a bit
		unset($normal_tags, $param_tags);

		$whitelist_params = array(
			'source',
			'rating',
			'id',
		);

		$exclude_normal_tags = $normal_tags = $param_tags = $exclude_param_tags = array();
		foreach($_param_tags as $tag)
		{
			$exclude = false;
			list($param, $value) = explode(':', $tag, 2);

			if($param[0] == '-')
			{
				$param = substr($param, 1);
				$exclude = true;
			}
			if(in_array($param, $whitelist_params))
			{
				if($exclude)
				{
					$param_tags[] = array($param, $value);
				}
				else
				{
					$exclude_param_tags[] = array($param, $value);
				}
			}
		}
		foreach($_normal_tags as $tag)
		{
			if($tag[0] == '-')
			{
				$normal_tags[] = substr($tag, 1);
			}
			else
			{
				$exclude_normal_tags[] = $tag;
			}
		}

		// @todo handle tag use properly

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
