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
	const SEARCH_MAX = 50;

	public function runController()
	{
		$page = $this->getRouteInput('page');
		if($page == 0)
		{
			$page = 1;
		}
		$offset = self::SEARCH_MAX * ($page - 1);

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

		$total_pages = floor((($total % self::SEARCH_MAX) != 0) ? ($total / self::SEARCH_MAX) + 1 : $total / self::SEARCH_MAX);

		// Run through and generate a number of page links...
		$p = array();
		for($i = -3; $i <= 3; $i++)
		{
			// "before" first page?
			if($page + $i < 1)
			{
				continue;
			}
			elseif($page + $i > $total_pages)
			{
				continue;
			}

			$p[] = $page + $i;
		}
		$pagination = array(
			'first'		=> 1,
			'prev'		=> ($page != 1) ? $page - 1 : false,
			'current'	=> $page,
			'next'		=> (($page + self::SEARCH_MAX) > $total) ? $page + 1 : false,
			'pages'		=> $p,
			'last'		=> $total_pages,
			'total'		=> $total,
		);

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
