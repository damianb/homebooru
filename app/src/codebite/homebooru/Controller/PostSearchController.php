<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostSearchController
	extends BaseController
{
	const SEARCH_MAX = 24;

	protected $cacheable = false;
	private $page, $tags, $search;

	public function init()
	{
		$this->page = $this->getInput('REQUEST::page', 1);
		if($this->page == 0)
		{
			$this->page = 1;
		}

		$this->search = $this->tags = $this->getInput('REQUEST::q', '');
	}

	public function runController()
	{
		$offset = self::SEARCH_MAX * ($this->page - 1);

		// tag search, now with only TWO DAMN PCRE'S! LIKE A BAWSS

		// sort out "normal" tags and search parameters
		$_normal_tags = $this->app->tagger->extractSearchTags($this->tags);
		$_param_tags = $this->app->tagger->extractSearchParams($this->tags);

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
		$beans = $tags = $pagination = $search = $exclude_normal_tags = $normal_tags = $param_tags = $exclude_param_tags = array();
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

		$fn_put = function($value, $key) {
			R::$f->put($value);
		};

		// at this point we should have four possible arrays of search parameters. now we need to grab their tag data...
		if(!empty($normal_tags) || !empty($param_tags))
		{
			// start building query
			R::$f->begin()
				//->select('p.*')
				->from('post p');

			// add tag search stuff
			if(!empty($normal_tags))
			{
				if(!empty($exclude_normal_tags))
				{
					R::$f->left_outer_join()
						->addSQL('(')
							->select('pt.post_id')
							->from('post_tag pt')
							->inner_join('tag t on pt.tag_id = t.id')
							->where('t.title in(' . implode(',', array_fill(0, count($exclude_normal_tags), '?')) . ')')
						->addSQL(') notag on p.id = notag.post_id'); // part of the left_outer_join
				}
				R::$f->inner_join()
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

			R::$f->where($wheres)
				->order_by('id desc');

			$where_array = array_merge($exclude_normal_tags, $normal_tags, array_values($param_tags), array_values($exclude_param_tags));
			//array_walk($where_array, $fn_put);

			// Duplicate this query so we can get a total result count (for pagination) and a query set
			list($query, ) = R::$f->getQuery();

			R::$f->begin()
				->select('count(p.id) as total_results')
				->addSQL($query);

			array_walk($where_array, $fn_put);

			$total = R::$f->get('cell');

			R::$f->begin()
				->select('p.*')
				->addSQL($query);

			array_walk($where_array, $fn_put);

			R::$f->limit(self::SEARCH_MAX)
				->offset($offset);

			$beans = R::convertToBeans('post', R::$f->get());

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
			array_walk($bean_ids, $fn_put);

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

			$pagination = $this->buildPagination($this->page, $total, self::SEARCH_MAX);
		}

		$this->response->setBody('viewposts.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'search'			=> true,
			),
			'posts'				=> $beans,
			'post_tags'			=> $tags,
			'pagination'		=> $pagination,
			'search_tags'		=> $this->search,
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
