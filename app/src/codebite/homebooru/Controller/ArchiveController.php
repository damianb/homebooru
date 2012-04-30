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

		$_beans = $bean_ids = $tags = $pagination = array();
		if(!empty($beans))
		{
			$total = (int) R::$f->begin()
				->select('COUNT(id)')->from('post')
				->where('status = ?')->put(BooruPostModel::ENTRY_ACCEPT)
				->order('BY id')
				->get('cell');

			$total_pages = floor((($total % self::SEARCH_MAX) != 0) ? ($total / self::SEARCH_MAX) + 1 : $total / self::SEARCH_MAX);

			foreach($beans as $bean)
			{
				if($bean->id)
				{
					$bean_ids[] = $bean->id;
					$_beans[$bean->id] = $bean;
				}
			}
			$beans = $_beans;

			// get all tags, their metadata, and combine them into one
			R::$f->begin()
				->select('pt.post_id, t.*,')
					->addSQL('(')
					->select('count(tag_id)')
						->from('post_tag')
						->where('tag_id = pt.tag_id')
					->addSQL(')')
					->as('tag_count')
				->from('tag t')
				->left_join('post_tag pt')
					->on('pt.tag_id = t.id')
				->where('pt.post_id in(' . implode(',', array_fill(0, count($bean_ids), '?')) . ')')
				->group_by('pt.post_id, pt.tag_id')
				->order_by('t.title ASC, pt.post_id ASC');
			array_walk($bean_ids, function($value, $key) { R::$f->put($value); });

			foreach(R::$f->get() as $entry)
			{
				$id = $entry['id'];
				if(!isset($tags[$id]))
				{
					// No such thing as R::convertToBean() :C
					$tags[$id] = reset(R::convertToBeans('tag', array($entry)));
				}
				$tags[$id]->encounter($entry['post_id']);
				$beans[$entry['post_id']]->liveAppendTag($tags[$id]);
			}

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
			'post_tags'			=> $tags,
		));
	}
}
