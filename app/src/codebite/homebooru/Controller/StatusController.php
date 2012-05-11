<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class StatusController
	extends BaseController
{
	protected $cacheable = false;

	public function runController()
	{
		$pdo_drivers = \PDO::getAvailableDrivers();

		$dbms_supports = array(
			'mysql'		=> in_array('mysql', $pdo_drivers),
			'pgsql'		=> in_array('pgsql', $pdo_drivers),
			'sqlite'	=> in_array('sqlite', $pdo_drivers),
		);
		$twig_env = $this->app->twig->getTwigEnvironment();

		return $this->respond('status.twig.html', 200, array(
			'status'			=> array(
				'library'			=> array(
					'redbean'			=> array(
						'version'			=> R::getVersion(),
						'driver'			=> 'pdo-' . R::$adapter->getDatabase()->getDatabaseType(),
					),
					'openflame'			=> array(
						'version'			=> $this->app->getVersion(),
					),
					'shot'			=> array(
						'version'			=> $this->app->getShotVersion(),
					),
					'twig'			=> array(
						'version'			=> $twig_env::VERSION,
						'debug'				=> $twig_env->isDebug(),
					),
					'imagine'			=> array(
						'version'			=> 'unknown',
						'driver'			=> $this->app['imagine.driver'] ?: 'gd',
					),
				),
				'php'				=> array(
					'version'			=> PHP_VERSION,
					'supports'			=> $dbms_supports,
					'url_fopen'			=> ini_get('allow_url_fopen'),
					'uploads'			=> ini_get('file_uploads'),
					'upload_max'		=> ini_get('upload_max_filesize'),
					'exec_time'			=> ini_get('max_execution_time'),
					'max_inputs'		=> @ini_get('max_input_vars') ?: 'N/A',
					'memlimit'			=> ini_get('memory_limit'),
					'safe_mode'			=> ini_get('safe_mode'),
					'curl'				=> function_exists('curl_init'),
					'gd'				=> function_exists('imagecreate'),
					'imagick'			=> class_exists('Imagick'),
				),
				'configuration'		=> array(
					'debug'				=> SHOT_DEBUG,
					'writable'			=> array(
						'full'				=> is_writable(HOMEBOORU_IMAGE_FULL_ROOT),
						'small'				=> is_writable(HOMEBOORU_IMAGE_SMALL_ROOT),
						'thumb'				=> is_writable(HOMEBOORU_IMAGE_THUMB_ROOT),
					),
				),
			),
			'page'				=> array(
				'status'				=> true,
			),
		));
	}
}
