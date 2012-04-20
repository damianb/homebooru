<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class ArchiveController
	extends ObjectController
{
	const SEARCH_MAX = 24;

	public function runController()
	{
		$page = (int) $this->getRouteInput('page');
		if($page === 0)
		{
			$page = 1;
		}

		$offset = self::SEARCH_MAX * ($page - 1);
		$beans = R::find('post', 'status = ? ORDER BY id desc LIMIT ? OFFSET ?', array(BooruPostModel::ENTRY_ACCEPT, self::SEARCH_MAX, $offset));

		$pagination = array();
		if(!empty($beans))
		{
			$total = (int) R::$f->begin()
				->select('COUNT(id)')->from('post')
				->where('status = ?')->put(BooruPostModel::ENTRY_ACCEPT)
				->order('BY id')
				->get('cell');

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
		}

		return $this->respond('viewposts.twig.html', 200, array(
			'page'				=> array(
				'archive'			=> true,
			),
			'pagination'		=> $pagination,
			'posts'				=> $beans,
		));
	}
}
