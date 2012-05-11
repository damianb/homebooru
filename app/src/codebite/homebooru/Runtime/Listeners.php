<?php
namespace codebite\homebooru\Runtime;
use \codebite\homebooru\WebKernel as App;
use \codebite\homebooru\Controller\InstallerController;
use \codebite\homebooru\Controller\CachedController;
use \codebite\homebooru\Internal\DatabaseLoadException;
use \emberlabs\openflame\Core\Utility\JSON;
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
 * Database connection
 *
 *  - connects to the database, prepares the connection, and
 */
$app->dispatcher->register('shot.hook.runtime.runcontroller', -10, function(Event $event) use ($app) {
	// redbean setup
	$no_db = false;
	try {
		if(!file_exists(SHOT_CONFIG_ROOT . '/database.json'))
		{
			throw new DatabaseLoadException();
		}

		// load db file
		$db = JSON::decode(SHOT_CONFIG_ROOT . '/database.json');

		if(!isset($db['db.type']))
		{
			throw new DatabaseLoadException();
		}
		switch($db['db.type'])
		{
			case 'sqlite':
				$db_file = $db['db.file'] ?: SHOT_ROOT . '/develop/db/red.db';
				if(!file_exists($db_file))
				{
					throw new DatabaseLoadException();
				}
				R::setup('sqlite:' . $db_file);
			break;

			case 'mysql':
			case 'mysqli': // in case someone doesn't know that pdo doesn't do mysqli
				R::setup(sprintf('mysql:charset=utf8;host=%s;dbname=%s', ($db['db.host'] ?: 'localhost'), $db['db.name']), $db['db.user'], $db['db.password']);
			break;

			case 'pgsql':
			case 'postgres':
			case 'postgresql':
				R::setup(sprintf('pgsql:host=%s;dbname=%s', ($db['db.host'] ?: 'localhost'), $db['db.name']), $db['db.user'], $db['db.password']);
			break;
		}

		// dump the p/w from memory
		$db['db.password'] = NULL;

		// freeze the database if we so wish...
		if($app['site.freeze_db'])
		{
			R::freeze(true);
		}

		// get random application seed
		$beans = R::findOrDispense('config', 'config_name = ?', array('app_seed'));
		$bean = array_shift($beans);
		if(!$bean->id)
		{
			// abort! database setup is borked! probably a botched install.
			R::close();
			throw new DatabaseLoadException();
		}

		$app['app.seed'] = $bean->config_str_value;
		$app->seeder->setApplicationSeed($app['app.seed']);
	}
	catch(DatabaseLoadException $e)
	{
		$no_db = true;
	}

	// being careful here...we want some sort of bypass if there's no "install" present
	if($no_db && !$app->controller->canRunWithoutInstall())
	{
		// pull up the installer controller, override the current controller.
		$controller = new InstallerController($app, $app->request, $app->response);
		$controller->setOriginalController($app->controller);

		$app->controller = $controller;
	}
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
		$app->response->setHeader('X-App-Magic-Cache', 'NOCACHE');
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
