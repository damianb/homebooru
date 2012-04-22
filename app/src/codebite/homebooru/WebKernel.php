<?php
namespace codebite\homebooru;
use \emberlabs\openflame\Event\Dispatcher;
use \emberlabs\openflame\Event\Instance as Event;
use \emberlabs\shot\WebKernel as ShotKernel;
use \R;

if(!defined('SHOT_ROOT')) exit;

/**
 * SigmaBB - Web Kernel
 * 	     Extended version of the shot WebKernel, provides hooking and other functionality specific to Sigma's needs.
 *
 * @package     sigmabb
 * @license     Dual licensed (MIT and GPLv2)
 * @link        @link@
 */
class WebKernel
	extends ShotKernel
{
	const HOMEBOORU_VERSION = '1.0.0dev';

	public function blindHook($name)
	{
		$hook = Event::newEvent($name)
			->setSource($this);

		$this->dispatcher->trigger($hook, Dispatcher::TRIGGER_MIXEDBREAK);
	}

	public function hook($name, array &$data)
	{
		$hook = Event::newEvent($name)
			->setSource($this)
			->setData($data);

		$this->dispatcher->trigger($hook, Dispatcher::TRIGGER_MIXEDBREAK);

		$data = $hook->getData();
	}

	public function boot()
	{
		parent::boot();

		// redbean setup
		$_db_type = isset($this['db.type']) ? $this['db.type'] : 'sqlite';
		switch($_db_type)
		{
			case 'sqlite':
				R::setup(sprintf('sqlite:%s', $this['db.file'] ?: SHOT_ROOT . '/develop/db/red.db'));
			break;

			case 'mysql':
			case 'mysqli': // in case someone doesn't know that pdo doesn't do mysqli
				R::setup(sprintf('mysql:charset=utf8;host=%s;dbname=%s', ($this['db.host'] ?: 'localhost'), $this['db.name']), $this['db.user'], $this['db.password']);
			break;

			case 'pgsql':
			case 'postgres':
			case 'postgresql':
				R::setup(sprintf('pgsql:host=%s;dbname=%s', ($this['db.host'] ?: 'localhost'), $this['db.name']), $this['db.user'], $this['db.password']);
			break;
		}

		// freeze the database if not in debug mode
		if(!SHOT_DEBUG)
		{
			R::freeze(true);
		}

		// get random application seed
		$beans = R::findOrDispense('config', 'config_name = ?', array('app_seed'));
		$bean = array_shift($beans);
		if(!$bean->id)
		{
			$bean->config_name = 'app_seed';
			$bean->config_type = 4;
			$bean->config_str_value = $this->seeder->buildRandomString(14);
			$bean->config_int_value = 0;
			$bean->config_live = 0;

			R::store($bean);
		}

		$this['app.seed'] = $bean->config_str_value;
		$this->seeder->setApplicationSeed($this['app.seed']);

		$this->setBasePath($this['site.urlbase'] ?: '/');

		// load specified addons

		/**
		 * @hook hook.runtime.boot.post
		 *  - post application "boot" hook point
		 */
		$this->blindHook('hook.runtime.boot.post');
	}

	public function run()
	{
		/**
		 * @hook hook.runtime.run.pre
		 *  - pre-run application hook point, executed before controller route is loaded, controller run
		 */
		$this->blindHook('hook.runtime.run.pre');

		try {
			parent::run();
		}
		catch(\Exception $e)
		{
			throw $e;
		}

		/**
		 * @hook hook.runtime.run.post
		 *  - post-run application hook point, executed after controller route is loaded, controller run
		 */
		$this->blindHook('hook.runtime.run.post');
	}

	public function display()
	{
		/**
		 * @hook hook.runtime.display.pre
		 *  - pre-display application hook point, executed before view is rendered and page output
		 */
		$this->blindHook('hook.runtime.display.pre');

		if($this->response->isUsingTemplating())
		{
			// global template variables to use...
			$global_template_vars = array(
				'DEBUG'					=> SHOT_DEBUG,

				'version'				=> array(
					'homebooru'				=> self::HOMEBOORU_VERSION,
					'shot'					=> $this->getShotVersion(),
					'openflame'				=> $this->getVersion(),
				),

				'admin'					=> array(
					'gravatar'				=> $this->gravatar->get($this['admin.email']),
					'name'					=> $this['admin.name'],
					'message'				=> $this['admin.message'],
				),

				'site'					=> array(
					'nav'					=> $this['site.navigation'],
					'thumburl'				=> $this['site.thumburl'],
					'smallurl'				=> $this['site.smallurl'],
					'imageurl'				=> $this['site.imageurl'],
				),
			);

			/**
			 * @hook hook.template.globalvars
			 *  - used to modify application-wide template variables
			 */
			$this->hook('hook.template.globalvars', $global_template_vars);
			$this->response->setTemplateVars(array_merge($global_template_vars, $this->response->getTemplateVars()));

			$twig = $this->twig->getTwigEnvironment();
			$twig->addGlobal('stat', $this->stat);
		}

		// app-wide headers to send...
		$global_headers = array(
			// drop PHP's x-powered-by header
			'X-Powered-By'					=> NULL,

			// prevent caching
			'Cache-Control'					=> 'no-cache',
			'Pragma'						=> 'no-cache',

			// NO FRAMES.
			'X-Frame-Options'				=> 'DENY',

			// IE8 header
			'X-XSS-Protection'				=> '1; mode=block',

			// Chromium, IE8 implement this.
			'X-Content-Type-Options'		=> 'nosniff',

			'X-App-Mode'					=> 'TSUNDERE',
		);

		/**
		 * @hook hook.header.globalheaders
		 *  - used to modify application-wide headers to send
		 */
		$this->hook('hook.header.globalheaders', $global_headers);
		$this->response->setHeaders(array_merge($global_headers, $this->response->getHeaders() ?: array()));

		try {
			parent::display();
		}
		catch(\Exception $e)
		{
			throw $e;
		}

		/**
		 * @hook hook.runtime.display.post
		 *  - post-display application hook point, executed after view is rendered and page output
		 */
		$this->blindHook('hook.runtime.display.post');
	}

	public function shutdown()
	{
		/**
		 * @hook hook.runtime.shutdown
		 *  - application shutdown hook point, executed before exit is called and script terminated.
		 */
		$this->blindHook('hook.runtime.shutdown');

		parent::shutdown();

		exit;
	}
}
