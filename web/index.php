<?php
use \codebite\common\WebKernel as App;

define('SHOT_DEBUG', true);
define('SHOT_IN_PHAR', false);
define('APP_IN_PHAR', false);

define('SHOT_ROOT', dirname(__DIR__));
define('SHOT_ADDON_ROOT', SHOT_ROOT . '/app/addons');
define('SHOT_CONFIG_ROOT', SHOT_ROOT . '/config');
define('SHOT_LANGUAGE_ROOT', SHOT_ROOT . '/app/language');
define('SHOT_LIB_ROOT', SHOT_ROOT . '/app/src');
define('SHOT_INCLUDE_ROOT', SHOT_ROOT . '/app/src');
define('SHOT_VENDOR_ROOT', SHOT_ROOT . '/app/vendor');
define('SHOT_VIEW_ROOT', SHOT_ROOT . '/app/views');

define('APP_NAMESPACE', 'codebite\\homebooru');
define('SHOT_CORE_PHAR', 'shot.phar');
define('APP_CORE_PHAR', 'homebooru.phar');

define('_APP_PATH', '/' . str_replace('\\', '/', APP_NAMESPACE));
define('_SHOT_MAGIC_LOAD_DIR', (!SHOT_IN_PHAR) ? SHOT_INCLUDE_ROOT : sprintf('phar://%s/%s.phar', SHOT_LIB_ROOT, SHOT_CORE_PHAR));
define('_APP_MAGIC_LOAD_DIR', (!APP_IN_PHAR) ? SHOT_INCLUDE_ROOT : sprintf('phar://%s/%s.phar', SHOT_LIB_ROOT, APP_CORE_PHAR));

require _APP_MAGIC_LOAD_DIR . '/codebite/common/Runtime/Bootstrap.php';

$app = App::getInstance();

$app->boot();
$app->run();
$app->display();
$app->shutdown();
