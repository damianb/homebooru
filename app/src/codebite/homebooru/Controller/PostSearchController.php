<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostSearchController
	extends ObjectController
{
	const SEARCH_MAX = 40;

	public function runController()
	{
		$page = $this->request->getInput('REQUEST::page', 1);
		if($page === 0)
		{
			$page = 1;
		}
		$offset = self::SEARCH_MAX * ($page - 1);

		$_search = $tags = $this->request->getInput('REQUEST::q', '');
		$tags = explode(' ', $tags);

		// tag search, now with only TWO DAMN PCRE'S! LIKE A BAWSS

		// sort out "normal" tags
		$normal_tags = preg_grep('#^\-?[\w]+[\w\(\)]*$#i', $tags);
		$_normal_tags = array_unique($normal_tags);

		// sort out magical "parameter" tags
		$param_tags = preg_grep('#^\-?[\w]+[\w\(\)]+\:[\w\.\(\)]*$#i', $tags);
		$_param_tags = array_unique($param_tags);

		// nuke out the old variables for resorting in a bit
		unset($normal_tags, $param_tags);

		// these params are handled special, so that we can do things like "md5:somemd5here" and search the posts themselves instead of searching for an md5 tag
		// also allows us to exclude/look for "rating:safe", "id:5", etc...
		$whitelist_params = array(
			//'source', // commented out for now, don't want to mess with validation (yes i'm lazy)
			'rating',
			'md5',
			'sha1',
			'id',
		);

		// mass-define a bunch of array vars
		$beans = $pagination = $search = $exclude_normal_tags = $normal_tags = $param_tags = $exclude_param_tags = array();
		if(!empty($_param_tags))
		{
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
						$exclude_param_tags[$param] = $value;
					}
					else
					{
						$param_tags[$param] = $value;
					}
				}
			}

			// post-processing of param tags, for validation and stuff
			$this->validateParamTags($param_tags);
			$this->validateParamTags($exclude_param_tags);
		}
		// parse through the normal tags and find any exclusions specified
		if(!empty($_normal_tags))
		{
			foreach($_normal_tags as $tag)
			{
				if($tag[0] != '-')
				{
					$normal_tags[] = $tag;
				}
				else
				{
					$exclude_normal_tags[] = substr($tag, 1);
				}
			}
		}
		unset($_normal_tags, $_param_tags);

		// handle search conflicts where we search for both "tag" and "-tag" (or "rating:safe" and "-rating:safe")
		// (we basically drop both out of the search parameters)
		$drop = array_intersect_key($param_tags, $exclude_param_tags);
		if(!empty($drop))
		{
			$param_tags = array_diff($param_tags, $drop);
			$exclude_param_tags = array_diff($exclude_param_tags, $drop);
		}
		$drop = array_intersect($normal_tags, $exclude_normal_tags);
		if(!empty($drop))
		{
			$normal_tags = array_diff($normal_tags, $drop);
			$exclude_normal_tags = array_diff($exclude_normal_tags, $drop);
		}

		$fn_put = function($value, $key, $state) {
			$state->put($value);
		};

		// at this point we should have four possible arrays of search parameters. now we need to grab their tag data...
		if(!empty($normal_tags) || !empty($param_tags))
		{
			// start building query
			$state = R::$f->begin()
				->select('p.*')->from('post p');

			// add tag search stuff
			if(!empty($normal_tags))
			{
				if(!empty($exclude_normal_tags))
				{
					$state->left_outer_join()
						->addSQL('(')
							->select('pt.post_id')
							->from('post_tag pt')
							->inner_join('tag t on pt.tag_id = t.id')
							->where('t.title in(' . implode(',', array_fill(0, count($exclude_normal_tags), '?')) . ')')
						->addSQL(') notag on p.id = notag.post_id'); // part of the left_outer_join
				}
				$state->inner_join()
					->addSQL('(')
						->select('pt.post_id')
						->from('post_tag pt')
						->inner_join('tag t on pt.tag_id = t.id')
						->where('t.title in(' . implode(',', array_fill(0, count($normal_tags), '?')) . ')')
						->group_by('pt.post_id')
						->having('count(distinct t.title) = ' . (int) count($normal_tags))
					->addSQL(') yestag on p.id = yestag.post_id'); // part of the inner_join
			}

			// build where conditions
			$wheres = '(';
			if(!empty($exclude_normal_tags))
			{
				$wheres .= 'notag.post_id is null AND ';
			}

			$wheres .= 'status = ' . BooruPostModel::ENTRY_ACCEPT;

			// build extra param conditions
			if(!empty($param_tags) || (!empty($normal_tags) && !empty($exclude_param_tags)))
			{
				$wheres .= ' AND (';
				if($param_tags)
				{
					$_param_tags = $param_tags;
					array_walk($_param_tags, function(&$value, $key) {
						$value = sprintf('%s = ?', $key);
					});
					$wheres .= implode(' AND ', $_param_tags);
				}
				if($exclude_param_tags)
				{
					$_exclude_param_tags = $exclude_param_tags;
					array_walk($_exclude_param_tags, function(&$value, $key) {
						$value = sprintf('%s <> ?', $key);
					});
					$wheres .= implode(' AND ', $_exclude_param_tags);
				}
				$wheres .= ')';
			}
			$wheres .= ')';

			$state->where($wheres)
				->order_by('id desc');

			$where_array = array_merge($exclude_normal_tags, $normal_tags, array_values($param_tags), array_values($exclude_param_tags));
			array_walk($where_array, $fn_put, $state);

			// Duplicate this query so we can get a total result count (for pagination) and a query set
			list($query, $params) = $state->getQuery();

			$state = R::$f->begin()
				->addSQL(str_replace('p.*', 'count(p.id) as total_results', $query));
			foreach($params as $param)
			{
				$state->put($param);
			}
			$total = $state->get('cell');

			$state = R::$f->begin()
				->addSQL($query);
			foreach($params as $param)
			{
				$state->put($param);
			}
			$state->limit(self::SEARCH_MAX)
				->offset($offset);

			$beans = R::convertToBeans('post', $state->get());

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

		$this->app->form->setFormSeed($this->app->session->getSessionSeed());

		$this->response->setBody('viewposts.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'search'			=> true,
			),
			'posts'				=> $beans,
			'pagination'		=> $pagination,
			'search_tags'		=> $_search,
		));

		return $this->response;
	}

	protected function validateParamTags(array &$param_tags)
	{
		foreach($param_tags as $param => $value)
		{
			switch($param)
			{
				case 'rating':
					switch($value)
					{
						case 'u':
						case 'unknown':
							$param_tags['rating'] = BooruPostModel::RATING_UNKNOWN;
						break;

						case 'e':
						case 'explicit':
							$param_tags['rating'] = BooruPostModel::RATING_EXPLICIT;
						break;

						case 'q':
						case 'questionable':
							$param_tags['rating'] = BooruPostModel::RATING_QUESTIONABLE;
						break;

						case 's':
						case 'safe':
							$param_tags['rating'] = BooruPostModel::RATING_SAFE;
						break;

						default:
							unset($param_tags['rating']);
						break;
					}
				break;

				case 'md5':
				case 'sha1':
					if(!ctype_xdigit($value))
					{
						unset($param_tags[$param]);
					}
					else
					{
						$param_tags['full_' . $param] = $value;
						unset($param_tags[$param]);
					}
				break;

				case 'id':
					if(!ctype_digit($value))
					{
						unset($param_tags['id']);
					}
					else
					{
						$param_tags['id'] = (int) $param_tags['id'];
					}
				break;
			}
		}
	}
}
