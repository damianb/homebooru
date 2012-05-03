<?php
namespace codebite\homebooru\Runtime;
use \codebite\homebooru\WebKernel as App;
use \emberlabs\GravatarLib\Gravatar;
use \emberlabs\openflame\Core\Autoloader;
use \emberlabs\openflame\Core\DependencyInjector;
use \R;

if(!defined('SHOT_ROOT')) exit;

$injector = DependencyInjector::getInstance();

// define gravatar injector
$injector->setInjector('gravatar', function() {
	$app = App::getInstance();
	$gravatar = new Gravatar();

	if($app['gravatar.secure'])
	{
		$gravatar->enableSecureImages();
	}
	$gravatar->setMaxRating($app['gravatar.rating'] ?: 'g');
	$gravatar->setAvatarSize($app['gravatar.size'] ?: 32);
	$gravatar->setDefaultImage($app['gravatar.default'] ?: 'mm');

	return $gravatar;
});

$injector->setInjector('cookie', function() {
	$app = App::getInstance();
	$cookie = new \emberlabs\openflame\Header\Helper\Cookie\Manager();
	if($app['cookie.domain'])
	{
		$cookie->setCookieDomain($app['cookie.domain']);
	}
	if($app['cookie.path'])
	{
		$cookie->setCookiePath($app['cookie.path']);
	}
	if($app['cookie.prefix'])
	{
		$cookie->setCookiePrefix($app['cookie.prefix'] . '_');
	}

	return $cookie;
});

$injector->setInjector('imagine', function() {
	$app = App::getInstance();
	$loader = Autoloader::getInstance();
	$loader->setPath(SHOT_VENDOR_ROOT . '/Imagine/lib');

	return DependencyInjector::grab('imagine.driver.' . ($app['imagine.driver'] ?: 'gd'));
});

$injector->setInjector('imagine.driver.gd', function() {
	return new \Imagine\Gd\Imagine;
});
$injector->setInjector('imagine.driver.imagick', function() {
	return new \Imagine\Imagick\Imagine;
});
$injector->setInjector('imagine.driver.gmagick', function() {
	return new \Imagine\Gmagick\Imagine;
});
$injector->setInjector('importer.driver.gelbooru', '\\codebite\\homebooru\\Importer\\Gelbooru');

$injector->setInjector('tagger', '\\codebite\\homebooru\\Tag\\Handler');
$injector->setInjector('stat', '\\codebite\\homebooru\\Stat');
$injector->setInjector('session', '\\codebite\\homebooru\\Session\\Session');
