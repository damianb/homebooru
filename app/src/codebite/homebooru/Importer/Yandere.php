<?php
namespace codebite\homebooru\Importer;
use \emberlabs\openflame\Core\Internal\RuntimeException;

class Yandere
	extends BooruImportBase
{
	protected function getAPIURL()
	{
		return 'https://yande.re/post/index.xml?tags=id:%d';
	}

	protected function getLinkURL()
	{
		return 'https://yande.re/post/show/%d';
	}
}
