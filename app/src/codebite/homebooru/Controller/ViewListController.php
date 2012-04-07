<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class ViewListController
	extends ObjectController
{
	public function runController()
	{
		$page = (int) $this->request->getRoute()->get('page');
		if($page === 0)
		{
			$page = 1;
		}

		$max = 50;
		$offset = $max * ($page - 1);
		$beans = R::find('post', 'status = ? ORDER BY id desc LIMIT ? OFFSET ?', array(BooruPostModel::ENTRY_QUEUE, $max, $offset));

		$pagination = array();
		if(!empty($beans))
		{
			$total = (int) R::$f->begin()
				->select('COUNT(id)')->from('post')
				->where('status = ?')->put(BooruPostModel::ENTRY_QUEUE) // ENTRY_ACCEPT
				->order('BY id')
				->get('cell');

			$total_pages = floor((($total % $max) != 0) ? ($total / $max) + 1 : $total / $max);

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
				'next'		=> (($page + $max) > $total) ? $page + 1 : false,
				'pages'		=> $p,
				'last'		=> $total_pages,
				'total'		=> $total,
			);
		}

		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
		$this->response->setBody('viewposts.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'archive'			=> true,
			),
			'pagination'		=> $pagination,
			'posts'				=> $beans,
		));

		return $this->response;
	}
}
