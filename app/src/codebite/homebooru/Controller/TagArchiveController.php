<?php
namespace codebite\homebooru\Controller;
use \codebite\common\Controller\BaseController;
use \codebite\homebooru\Model\PostModel;
use \codebite\homebooru\Model\TagModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class TagArchiveController
	extends BaseController
{
	const SEARCH_MAX = 50;

	protected $cacheable = true, $cache_ttl = 120;
	private $page;

	public function init()
	{
		$this->page = $this->getRouteInput('page');
		if($this->page == 0)
		{
			$this->page = 1;
		}
	}

	protected function defineCacheBinds()
	{
		return array(
			'page' => $this->page,
		);
	}

	public function runController()
	{
		$offset = self::SEARCH_MAX * ($this->page - 1);

		R::$f->begin()
			->select('t.*, COUNT(pt.tag_id) as tag_count')
			->from('tag t')
			->inner_join('post_tag pt')
				->on('pt.tag_id = t.id')
			->group_by('t.id')
			->order_by('t.title ASC')
			->limit(self::SEARCH_MAX)
			->offset((int) $offset);
		$beans = R::convertToBeans('tag', R::$f->get());
		$total = R::count('tag');

		$pagination = $this->buildPagination($this->page, $total, self::SEARCH_MAX);

		return $this->respond('viewtags.twig.html', 200, array(
			'page'				=> array(
				'tags'				=> true,
				'tag_archive'		=> true,
			),
			'tags'				=> $beans,
			'pagination'		=> $pagination,
		));
	}
}
