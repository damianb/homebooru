<?php
namespace codebite\homebooru\Runtime;
use \codebite\homebooru\WebKernel as App;
use \codebite\homebooru\Controller\CachedController;
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
 */
$app->dispatcher->register('shot.hook.runtime.runcontroller', 0, function(Event $event) use ($app) {
	$cache_bind = 'page_cache_' . hash('sha1', implode('&&', $app->controller->getCacheBinds()));

	if($app['site.magic_cache'] == true && $app->controller->isCacheable())
	{
		if(($page = $app->cache->loadData($cache_bind)) !== NULL)
		{
			$controller = new CachedController($app, $app->request, $app->response);
			$controller->setOriginalController($app->controller)
				->loadCache($page);

			$app->controller = $controller;
		}

		$app->response->setHeader('Cache-Control', 'public, max-age=' . 3600 * 10)
			->setHeader('Pragma', NULL);
	}
	else
	{
		$app->response->setHeader('X-App-Magic-Cache', 'NONE');
	}
});

/**
 * caching integration
 *
 *  - check if cacheable, then cache if so.
 */
$app->dispatcher->register('shot.hook.runtime.render.post', 0, function(Event $event) use ($app) {
	if($app['site.magic_cache'] == true && $app->controller->isCacheable())
	{
		$cache_bind = 'page_cache_' . hash('sha1', implode('&&', $app->controller->getCacheBinds()));
		if(!$app->cache->loadData($cache_bind))
		{
			$app->response->setHeader('X-App-Magic-Cache', 'MISS');
			$page = array(
				'http_status'	=> $app->response->getResponseCode(),
				'content_type'	=> $app->response->getContentType(),
				'body'			=> reset($event->getData()),
			);

			$app->cache->storeData($cache_bind, $page, $app->controller->getCacheTTL());
		}
	}
});
