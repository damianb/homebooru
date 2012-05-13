<?php
use \codebite\homebooru\WebKernel as App;

define('SHOT_DEBUG', true);
define('SHOT_IN_PHAR', false);
define('HOMEBOORU_IN_PHAR', false);

define('SHOT_ROOT', dirname(__DIR__));
define('SHOT_ADDON_ROOT', SHOT_ROOT . '/app/addons');
define('SHOT_CONFIG_ROOT', SHOT_ROOT . '/config');
define('SHOT_LANGUAGE_ROOT', SHOT_ROOT . '/app/language');
define('SHOT_LIB_ROOT', SHOT_ROOT . '/app/src');
define('SHOT_INCLUDE_ROOT', SHOT_ROOT . '/app/src');
define('SHOT_VENDOR_ROOT', SHOT_ROOT . '/app/vendor');
define('SHOT_VIEW_ROOT', SHOT_ROOT . '/app/views');

define('SHOT_CORE_PHAR', 'shot.phar');
define('HOMEBOORU_CORE_PHAR', 'homebooru.phar');

define('_SHOT_MAGIC_LOAD_DIR', (!SHOT_IN_PHAR) ? SHOT_INCLUDE_ROOT : sprintf('phar://%s/%s.phar', SHOT_LIB_ROOT, SHOT_CORE_PHAR));
define('_HOMEBOORU_MAGIC_LOAD_DIR', (!HOMEBOORU_IN_PHAR) ? SHOT_INCLUDE_ROOT : sprintf('phar://%s/%s.phar', SHOT_LIB_ROOT, HOMEBOORU_CORE_PHAR));

require _HOMEBOORU_MAGIC_LOAD_DIR . '/codebite/homebooru/Runtime/Bootstrap.php';

$app = App::getInstance();

$app->boot();
$app->run();
$app->display();
$app->shutdown();
