<?php
namespace codebite\homebooru\Importer;
use \codebite\common\WebKernel as App;
use \emberlabs\openflame\Core\Internal\RuntimeException;

class Yandere
	extends BooruImportBase
{
	protected function getAPIURL()
	{
		return 'https://yande.re/post/index.xml?useragent=homebooru_' . rawurlencode(App::HOMEBOORU_VERSION) . '&tags=id:%d';
	}

	protected function getLinkURL()
	{
		return 'https://yande.re/post/show/%d';
	}
}
