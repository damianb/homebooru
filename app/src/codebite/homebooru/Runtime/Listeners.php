<?php
namespace codebite\homebooru\Runtime;
use \codebite\homebooru\WebKernel as App;
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
