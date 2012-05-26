<?php
namespace codebite\homebooru\Runtime;
use \codebite\common\WebKernel as App;
use \codebite\homebooru\Controller\InstallerController;
use \codebite\homebooru\Controller\CachedController;
use \codebite\homebooru\Internal\DatabaseLoadException;
use \emberlabs\openflame\Core\Utility\JSON;
use \emberlabs\openflame\Event\Dispatcher;
use \emberlabs\openflame\Event\Instance as Event;
use \R;

if(!defined('SHOT_ROOT')) exit;

$app = App::getInstance();

$app->dispatcher->register('app.hook.template.globalvars', 15, function(Event $event) use ($app) {
	$data = $event->getData();
	$data = array_merge($data, array(
		'admin'					=> array(
			'gravatar'				=> $app->gravatar->get($app['admin.email']),
			'name'					=> $app['admin.name'],
			'message'				=> $app['admin.message'],
		),

		'site'					=> array(
			'nav'					=> $app['site.navigation'],
			'use_less'				=> $app['site.use_less_stylesheet'],
			'thumburl'				=> $app['site.thumburl'],
			'smallurl'				=> $app['site.smallurl'],
			'imageurl'				=> $app['site.imageurl'],
		),
	));
	$event->setData($data);
});

$app->dispatcher->register('app.hook.constant.defaults', 10, function(Event $event) use ($app) {
	$data = $event->getData();
	$data = array_merge($data, array(
		'HOMEBOORU_IMAGE_IMPORT_ROOT'	=> SHOT_ROOT . '/import',
		'HOMEBOORU_IMAGE_FULL_ROOT'		=> SHOT_ROOT . '/upload/full',
		'HOMEBOORU_IMAGE_SMALL_ROOT'	=> SHOT_ROOT . '/upload/small',
		'HOMEBOORU_IMAGE_THUMB_ROOT'	=> SHOT_ROOT . '/upload/thumb',
	));
	$event->setData($data);
});
