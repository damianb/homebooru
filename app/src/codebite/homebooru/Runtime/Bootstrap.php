<?php
namespace codebite\homebooru;
use \codebite\homebooru\RedBean\ModelFormatter;
use \codebite\homebooru\WebKernel as App;
use \R;
use \RedBean_ModelHelper;

if(!defined('SHOT_ROOT')) exit;

// load up shot
require _SHOT_MAGIC_LOAD_DIR . '/emberlabs/shot/Runtime/Bootstrap.php';

// defaults
$_defaults = array(
	'HOMEBOORU_EXHANDLER_UNWRAP'	=> false,

	'HOMEBOORU_IMAGE_IMPORT_ROOT'	=> SHOT_ROOT . '/import',
	'HOMEBOORU_IMAGE_FULL_ROOT'		=> SHOT_ROOT  . '/upload/full',
	'HOMEBOORU_IMAGE_SMALL_ROOT'	=> SHOT_ROOT . '/upload/small',
	'HOMEBOORU_IMAGE_THUMB_ROOT'	=> SHOT_ROOT . '/upload/thumb',
);
foreach($_defaults as $_const => $_default)
{
	if(!defined($_const))
	{
		define($_const, $_default);
	}
}

// load our own functions
require _HOMEBOORU_MAGIC_LOAD_DIR . '/codebite/homebooru/Runtime/Functions.php';
require _HOMEBOORU_MAGIC_LOAD_DIR . '/codebite/homebooru/Runtime/Injectors.php';

$app = App::getInstance();

set_exception_handler('\\codebite\\homebooru\\Runtime\\ExceptionHandler::invoke');

// prepare the cache
$app['cache.path'] = SHOT_ROOT . '/cache/';
if(function_exists('apc_cache_info'))
{
	$app['cache.engine'] = 'apc';
}

// prepare twig
$app['twig.lib_path'] = SHOT_VENDOR_ROOT . '/Twig/lib/Twig/';
$app['twig.cache_path'] = SHOT_ROOT . '/cache/viewcache/';
$app['twig.template_path'] = SHOT_VIEW_ROOT . '/';
$app['twig.debug'] = SHOT_DEBUG;

// load redbean
require SHOT_VENDOR_ROOT . '/redbean/rb.php';

// use custom model formatter
RedBean_ModelHelper::setModelFormatter(new ModelFormatter);
