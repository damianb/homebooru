<?php
/**
 *
 *===================================================================
 *
 *  Sigma Bulletin Board
 *-------------------------------------------------------------------
 * @package     sigmabb
 * @copyright   (c) 2012 @copy@
 * @license     Dual licensed (MIT and GPLv2)
 * @link        @link@
 *
 *===================================================================
 *
 * This source file is subject to the licenses that are bundled
 * with this package in the files MIT-LICENSE.txt and GPL-LICENSE.txt
 *
 */

use \codebite\homebooru\WebKernel as App;

define('SHOT_ROOT', dirname(__DIR__));
define('SHOT_ADDON_ROOT', SHOT_ROOT . '/app/addons');
define('SHOT_CONFIG_ROOT', SHOT_ROOT . '/config');
define('SHOT_LANGUAGE_ROOT', SHOT_ROOT . '/app/language');
define('SHOT_LIB_ROOT', SHOT_ROOT . '/app/src');
define('SHOT_INCLUDE_ROOT', SHOT_ROOT . '/app/src');
define('SHOT_VENDOR_ROOT', SHOT_ROOT . '/app/vendor');
define('SHOT_VIEW_ROOT', SHOT_ROOT . '/app/views');

define('HOMEBOORU_IMAGE_IMPORT_ROOT', SHOT_ROOT . '/import');
define('HOMEBOORU_IMAGE_FULL_ROOT', SHOT_ROOT . '/upload/full');
define('HOMEBOORU_IMAGE_SMALL_ROOT', SHOT_ROOT . '/upload/small');
define('HOMEBOORU_IMAGE_THUMB_ROOT', SHOT_ROOT . '/upload/thumb');

define('SHOT_DEBUG', true);

define('SHOT_IN_PHAR', false);
define('HOMEBOORU_IN_PHAR', false);
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
