<?php
namespace codebite\homebooru\Runtime;
use \codebite\common\WebKernel as App;
use \codebite\homebooru\Controller\InstallerController;
use \emberlabs\GravatarLib\Gravatar;
use \emberlabs\openflame\Core\Autoloader;
use \emberlabs\openflame\Core\DependencyInjector;
use \R;

if(!defined('SHOT_ROOT')) exit;

$injector = DependencyInjector::getInstance();

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
$injector->setInjector('importer.driver.yandere', '\\codebite\\homebooru\\Importer\\Yandere');

$injector->setInjector('tagger', '\\codebite\\homebooru\\Tag\\Handler');

$injector->setInjector('controller.installer', function() {
	$app = App::getInstance();

	return new InstallerController($app, $app->request, $app->response);
});
