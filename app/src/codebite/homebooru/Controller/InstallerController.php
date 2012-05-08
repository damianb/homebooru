<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Model\BooruPostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class InstallerController
	extends BaseController
{
	protected $bypass_install_check = true, $cacheable = false;
	private $original_controller;

	public function setOriginalController(BaseController $controller)
	{
		$this->original_controller = $controller;

		return $this;
	}

	public function runController()
	{
		/**
		 * Installer workflow:
		 *
		 *  - Obtain database auth creds from user
		 *  - Attempt to create database cred file
		 *    - barring failure to write the file, provide it to the user and tell them to put it in place before continuing
		 *  - Create app seed, insert into config
		 *  - Create a sample image, add to the installation.
		 *  - Add sample tags for new sample image
		 *  - Add triggers for count tables
		 *  - Drop sample data
		 */

		$pdo_drivers = \PDO::getAvailableDrivers();

		$dbms_supports = array(
			'mysql'		=> in_array('mysql', $pdo_drivers),
			'pgsql'		=> in_array('pgsql', $pdo_drivers),
			'sqlite'	=> in_array('sqlite', $pdo_drivers),
		);

		// see if we can write the DB file...
		$db_file_writeable = false;
		if(file_exists(SHOT_CONFIG_ROOT . '/database.json') && is_writeable(SHOT_CONFIG_ROOT . '/database.json'))
		{
			$db_file_writeable = true;
		}
		elseif(is_writeable(SHOT_CONFIG_ROOT . '/'))
		{
			$db_file_writeable = true;
		}

		// are we installing this shiz?
		if($this->wasInputSet('POST::submit'))
		{
			/*
			$bean->config_name = 'app_seed';
			$bean->config_type = 4;
			$bean->config_str_value = $this->seeder->buildRandomString(14);
			$bean->config_int_value = 0;
			$bean->config_live = 0;

			R::store($bean);
			*/
		}
		else
		{
			return $this->respond('installapp.twig.html', 200, array(
				'status'			=> array(
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
					'install'				=> true,
				),
			));
		}
	}
}
