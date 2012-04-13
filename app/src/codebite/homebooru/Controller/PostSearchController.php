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
		$beans = $exclude_normal_tags = $normal_tags = $param_tags = $exclude_param_tags = array();
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
			foreach($exclude_param_tags as $param => $value)
			{
				switch($param)
				{
					case 'rating':
						switch($value)
						{
							case 'u':
							case 'unknown':
								$exclude_param_tags['rating'] = BooruPostModel::RATING_UNKNOWN;
							break;

							case 'e':
							case 'explicit':
								$exclude_param_tags['rating'] = BooruPostModel::RATING_EXPLICIT;
							break;

							case 'q':
							case 'questionable':
								$exclude_param_tags['rating'] = BooruPostModel::RATING_QUESTIONABLE;
							break;

							case 's':
							case 'safe':
								$exclude_param_tags['rating'] = BooruPostModel::RATING_SAFE;
							break;

							default:
								unset($exclude_param_tags['rating']);
							break;
						}
					break;

					case 'md5':
					case 'sha1':
						if(!ctype_xdigit($value))
						{
							unset($exclude_param_tags[$param]);
						}
						else
						{
							$exclude_param_tags['full_' . $param] = $value;
							unset($exclude_param_tags[$param]);
						}
					break;

					case 'id':
						if(!ctype_digit($value))
						{
							unset($exclude_param_tags['id']);
						}
						else
						{
							$exclude_param_tags['id'] = (int) $exclude_param_tags['id'];
						}
					break;
				}
			}
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

		// at this point we should have four possible arrays of search parameters. now we need to grab their tag data...
		$where_array = array_merge($normal_tags, $exclude_normal_tags);
		if(!empty($where_array) || !empty($param_tags))
		{
			$search = $drop = array();
			if(!empty($where_array))
			{
				$wheres = '(' . substr(str_repeat('title = ? OR ', count($where_array)), 0, -4) . ')';

				$state = R::$f->begin()
					->select('*')->from('tag')
					->where($wheres);
				foreach($where_array as $put)
				{
					$state->put($put);
				}
				$tag_data = $state->get();

				unset($wheres, $where_array);
				// we now have tag id's and stuffs. PROCEED WITH CATGIRL^W WORLD DOMINATION. >:3

				$normal_flip = array_flip($normal_tags);
				$include_ids = $exclude_ids = array();
				foreach($tag_data as $tag)
				{
					if(isset($normal_flip[$tag['title']]))
					{
						$include_ids[] = (int) $tag['id'];
					}
					else
					{
						$exclude_ids[] = (int) $tag['id'];
					}
				}

				$where_array = array_merge($include_ids, $exclude_ids);
				$wheres =  '(' . implode(' OR ', array_fill(0, count($where_array), 'tag_id = ?')) . ')';

				$state = R::$f->begin()
					->select('*')->from('post_tag')
					->where($wheres);
				foreach($where_array as $put)
				{
					$state->put($put);
				}
				$posts_tagged = $state->get();

				$exclude_flip = array_flip($exclude_ids);

				foreach($posts_tagged as $key => $entry)
				{
					if(isset($drop[$entry['post_id']]))
					{
						continue;
					}

					if(isset($exclude_flip[$entry['tag_id']]))
					{
						$drop[$entry['post_id']] = true;
						unset($search[$entry['post_id']]);
						continue;
					}

					$search[$entry['post_id']] = (int) $entry['post_id'];
				}
			}

			// please don't kill me for this, I have to do it because RedBean's tagging API just doesn't have the balls for what we're trying to do ;_;
			$wheres = '(';
			$wheres .= (!empty($search)) ? implode(' OR ', array_fill(0, count($search), 'id = ?')) : '';

			if(!empty($param_tags) || !empty($exclude_param_tags))
			{
				if(!empty($search))
				{
					$wheres = '(' . $wheres . ') AND (';
				}
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
				$wheres .= (!empty($search)) ? ')' : '';
			}
			$wheres .= ')';

			if(strlen($wheres) > 2)
			{
				$beans = R::find('post', $wheres, array_merge($search, array_values($param_tags), array_values($exclude_param_tags)));
			}
		}

		$this->app->form->setFormSeed($this->app->session->getSessionSeed());

		$this->response->setBody('viewposts.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'search'			=> true,
			),
			'posts'				=> $beans,
			'search_tags'		=> $_search,
		));

		return $this->response;
	}
}
