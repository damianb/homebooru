<?php
namespace codebite\homebooru;
use \codebite\homebooru\RedBean\ModelFormatter;
use \codebite\homebooru\WebKernel as App;
use \R;
use \RedBean_ModelHelper;

if(!defined('SHOT_ROOT')) exit;

// load up shot
require _SHOT_MAGIC_LOAD_DIR . '/emberlabs/shot/Runtime/Bootstrap.php';

// load our own functions
require _HOMEBOORU_MAGIC_LOAD_DIR . '/codebite/homebooru/Runtime/Functions.php';
require _HOMEBOORU_MAGIC_LOAD_DIR . '/codebite/homebooru/Runtime/Injectors.php';

$app = App::getInstance();

// feature detection stuff
define('SIGMA_USE_OPENSSL', (defined('OPENSSL_VERSION_NUMBER') && !$app['disable.openssl']) ? true : false);

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
