<?php
namespace codebite\homebooru\Runtime;
use \codebite\homebooru\WebKernel as App;
use \codebite\homebooru\Controller\CacheController;
use \emberlabs\openflame\Event\Dispatcher;
use \emberlabs\openflame\Event\Instance as Event;
use \R;

if(!defined('SHOT_ROOT')) exit;

$app = App::getInstance();

$app->dispatcher->register('shot.hook.runtime.render.post', 15, function(Event $event) use ($app) {
	$search = array(
		'{$mem}',
		'{$mempeak}',
		'{$time}',
	);
	$replace = array(
		$app->stat->mem(),
		$app->stat->memPeak(),
		$app->stat->time(),
	);
	$event->setData(array(str_replace($search, $replace, reset($event->getData()))));
});

/**
 * caching integration
 *
 *  - check if cacheable, then see if cached - if so, overload with CacheController and appropriate cache entry name
 *
$app->dispatcher->register('shot.hook.runtime.runcontroller', 0, function(Event $event) use ($app) {
	// extract controller
	$controller = reset($event->getData());

	if($controller->isCacheable())
	{
		if(false)
		{
			$cache_binds = $controller->getCacheBinds();

			$controller = new CacheController($app, $app->request, $app->response);
			$controller->setCacheBinds($cache_binds);
		}
	}
});
 */

/**
 * caching integration
 *
 *  - check if cacheable, then cache if so.
 */
