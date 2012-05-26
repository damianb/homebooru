<?php
namespace codebite\homebooru\Tag;
use \R;

class Handler
{
	const TAG_INSERT_REGEXP = '#\w+[\w\-\(\)\!]*#i';
	const TAG_EXTRACT_REGEXP = '#^\-?[\w]+[\w\-\(\)\!]*$#i';
	const TAG_EXTRACTPARAM_REGEXP = '#^\-?[\w]+[\w\-\(\)]+(\:|\=)[\w\-\.\(\)]*$#i';

	public function extractTags($tag_string)
	{
		preg_match_all(self::TAG_INSERT_REGEXP, $tag_string, $tags);
		return array_unique(array_shift($tags));
	}

	public function extractSearchTags($search_string)
	{
		return array_unique(preg_grep(self::TAG_EXTRACT_REGEXP, explode(' ', $search_string)));
	}

	public function extractSearchParams($search_string)
	{
		return array_unique(preg_grep(self::TAG_EXTRACTPARAM_REGEXP, explode(' ', $search_string)));
	}

	public function resolveTags(array &$tags)
	{
		try {
			R::$f->begin()
				->select('t.*, a.title as old_tag')->from('tag t')
				->left_join('tag_alias a on t.id = a.tag_id')
				->where('a.title in('  . R::genSlots($tags) . ')');
			array_walk($tags, function($value, $key) { R::$f->put($value); });

			$replace_tags = $remove_tags = array();
			foreach(R::$f->get() as $result)
			{
				$remove_tags[] = $result['old_tag'];
				$replace_tags[] = $result['replacement'];
			}

			$tags = array_merge(array_diff($tags, $remove_tags), $replace_tags);
		}
		catch(\Exception $e)
		{
			return;
		}
	}

	public function addAlias($alias, \RedBean_OODBBean $target_bean)
	{
		$bean = R::dispense('tag_alias');
		$bean->title = $alias;
		$bean->tag = $target_bean;

		R::store($bean);
	}
}
