<?php
namespace codebite\homebooru\Importer;
use \emberlabs\openflame\Core\Internal\RuntimeException;

class Gelbooru
	extends BooruImportBase
{
	protected function getAPIURL()
	{
		return 'http://gelbooru.com/index.php?page=dapi&s=post&q=index&tags=id:%d';
	}

	protected function getLinkURL()
	{
		return 'http://gelbooru.com/index.php?page=post&s=view&tags=id:%d';
	}
}
