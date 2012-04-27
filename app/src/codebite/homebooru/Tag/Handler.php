<?php
namespace codebite\homebooru\Tag;
use \R;

class Handler
{
	public function extractTags($tag_string)
	{
		preg_match_all('#\w+[\w\-\(\)]*#i', $tag_string, $tags);
		return array_unique(array_shift($tags));
	}

	public function extractSearchTags($search_string)
	{
		return array_unique(preg_grep('#^\-?[\w]+[\w\-\(\)]*$#i', explode(' ', $search_string)));
	}

	public function extractSearchParams($search_string)
	{
		return array_unique(preg_grep('#^\-?[\w]+[\w\-\(\)]+(\:|\=)[\w\-\.\(\)]*$#i', explode(' ', $search_string)));
	}

	public function resolveTags(array &$tags)
	{
		R::$f->begin()
			->select('t.*, a.title as old_tag')->from('tag t')
			->inner_join('tag_alias a on t.id = a.tag_id')
			->where('a.title in('  . implode(',', array_fill(0, count($tags), '?')) . ')');
		foreach($tags as $tag)
		{
			R::$f->put($tag);
		}

		$replace_tags = $remove_tags = array();
		foreach(R::$f->get() as $result)
		{
			$remove_tags[] = $result['old_tag'];
			$replace_tags[] = $result['replacement'];
		}

		$tags = array_merge(array_diff($tags, $remove_tags), $replace_tags);
	}

	public function addAlias($alias, \RedBean_OODBBean $target_bean)
	{
		$bean = R::dispense('tag_alias');
		$bean->title = $alias;
		$bean->tag = $target_bean;

		R::store($bean);
	}
}
