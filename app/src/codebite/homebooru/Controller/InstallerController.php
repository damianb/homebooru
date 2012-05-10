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

		$db_drivers = array(
			1		=> $dbms_supports['sqlite'] ? 'sqlite' : false,
			2		=> $dbms_supports['mysql'] ? 'mysql' : false,
			3		=> $dbms_supports['pgsql'] ? 'pgsql' : false,
		);

		$steps = $db = array();

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
		$submit = $this->wasInputSet('POST::submit');
		if($submit)
		{
			$steps = array(
				'db_connect'			=> false,
				'gen_app_seed'			=> false,
				'add_sample_image'		=> false,
				'add_sample_tags'		=> false,
				'add_triggers'			=> false,
				'drop_sample_data'		=> false,
			);

			switch($this->getInput('POST::db_type', 1))
			{
				default:
				case 1: // sqlite
					$db_type = 'sqlite';
				break;
				case 2: // mysql
					$db_type = 'mysql';
				break;
				case 3: // pgsql
					$db_type = 'pgsql';
				break;
			}

			$db = array(
				'type'		=> $db_type,
				'file'		=> $this->getInput('POST::db_host', ''),
				'host'		=> $this->getInput('POST::db_host', ''),
				'name'		=> $this->getInput('POST::db_name', ''),
				'user'		=> $this->getInput('POST::db_user', ''),
				'password'	=> $this->getInput('POST::db_password', ''),
			);

			$error = false;
			try {
				// redbean setup
				switch(($db['type'] ?: 'sqlite'))
				{
					case 'sqlite':
						R::setup(sprintf('sqlite:%s', $db['file'] ?: SHOT_ROOT . '/develop/db/red.db'));
					break;

					case 'mysql':
						R::setup(sprintf('mysql:charset=utf8;host=%s;dbname=%s', ($db['host'] ?: 'localhost'), $db['name']), $db['user'], $db['password']);
					break;

					case 'pgsql':
						R::setup(sprintf('pgsql:host=%s;dbname=%s', ($db['host'] ?: 'localhost'), $db['name']), $db['user'], $db['password']);
					break;
				}

				// dump the p/w from memory
				$app['password'] = NULL;

				// mark this step as completed! (DB CONNECTION SUCCESSFUL!)
				$steps['db_connect'] = true;

				// add our config entry, our app seed
				$bean = R::dispense('config');
				$bean->config_name = 'app_seed';
				$bean->config_type = 4;
				$bean->config_str_value = $this->seeder->buildRandomString(14);
				$bean->config_int_value = 0;
				$bean->config_live = 0;
				R::store($bean);

				// mark this step as completed.
				$steps['gen_app_seed'] = true;

				// add triggers to the database
				/*
				CREATE TRIGGER tag_magic_update_count
				AFTER INSERT ON post_tag
				BEGIN
				   UPDATE tag_count SET amount = amount + 1 WHERE tag_id = new.tag_id;
				END;
				CREATE TRIGGER tag_magic_delete_count
				AFTER DELETE ON post_tag
				BEGIN
				   UPDATE tag_count SET amount = amount - 1 WHERE tag_id = new.tag_id;
				END;
				CREATE TRIGGER tag_magic_new_count
				AFTER INSERT ON tag
				BEGIN
				   INSERT INTO tag_count (tag_id, amount) VALUES (new.tag_id, 1);
				END;
				CREATE TRIGGER tag_magic_drop_count
				AFTER DELETE ON tag
				BEGIN
				   DELETE FROM tag_count WHERE tag_id = old.tag_id;
				END;
				*/

				/*
				FOR A REINDEX, THE FOLLOWING QUERY IS AVAILABLE:

				UPDATE tag_count SET amount = (SELECT COUNT(tag_id) FROM post_tag WHERE tag_id = new.tag_id) WHERE tag_id = new.tag_id;
				*/

				R::$f->begin()
					->create_trigger('tag_magic_update_count')
					->after('insert')
					->on('post_tag')
					->addSQL('BEGIN')
						->update('tag_count')
						->set('amount = amount + 1')
						->where('tag_id = new.tag_id')
						->addSQL(';')
					->addSQL('END');

				R::$f->begin()
					->create_trigger('tag_magic_delete_count')
					->after('delete')
					->on('post_tag')
					->addSQL('BEGIN')
						->update('tag_count')
						->set('amount = amount - 1')
						->where('tag_id = new.tag_id')
						->addSQL(';')
					->addSQL('END');

				R::$f->begin()
					->create_trigger('tag_magic_new_count')
					->after('insert')
					->on('post_tag')
					->addSQL('BEGIN')
						->insert_into('tag_count')
						->addSQL('(tag_id, amount)')
						->values('(new.tag_id, 1)')
						->addSQL(';')
					->addSQL('END');

				R::$f->begin()
					->create_trigger('tag_magic_drop_count')
					->after('delete')
					->on('post_tag')
					->addSQL('BEGIN')
						->delete_from('tag_count')
						->where('tag_id = old.tag_id')
						->addSQL(';')
					->addSQL('END');

				$steps['add_triggers'] = true;
			}
			catch(\Exception $e)
			{
				$error = $e->getMessage();
			}
		}

		return $this->respond('installapp.twig.html', 200, array(
			'form'				=> array(
				'submit'			=> $submit,
				'db_drivers'		=> $db_drivers,
				'db_file_write'		=> $db_file_writeable,
				'db_prev'			=> $db,
				'steps'				=> $steps,
			),
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
