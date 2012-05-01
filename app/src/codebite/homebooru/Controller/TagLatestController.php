<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \codebite\homebooru\Model\BooruTagModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class TagLatestController
	extends BaseController
{
	const SEARCH_MAX = 20;

	protected $cacheable = false;

	public function runController()
	{
		R::$f->begin()
			->select('t.*, COUNT(pt.tag_id) as tag_count')
			->from('tag t')
			->inner_join('post_tag pt')
				->on('pt.tag_id = t.id')
			->group_by('t.id')
			->order_by('t.id DESC')
			->limit(self::SEARCH_MAX);
		$beans = R::convertToBeans('tag', R::$f->get());

		return $this->respond('viewtags.twig.html', 200, array(
			'page'				=> array(
				'tags'				=> true,
				'tag_latest'		=> true,
			),
			'tags'				=> $beans,
		));
	}
}
