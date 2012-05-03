<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Internal\SubmitFailException;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\openflame\Core\Internal\RuntimeException;
use \emberlabs\openflame\Core\DependencyInjector;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostImportController
	extends BaseController
{
	protected $cacheable = false;

	public function before()
	{
		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
	}

	public function runController()
	{
		$drivers = array(
			// soon to expand...
			1		=> 'gelbooru',
		);

		if($this->wasInputSet('POST::submit'))
		{
			$now = time();

			$form_key = $this->getInput('POST::formkey', '');
			$form_time = $this->getInput('POST::formtime', 0);

			$remote_id = $this->getInput('POST::remote_id', 0);
			$import_driver = $this->getInput('POST::driver', 0);
			$ip = $this->request->getIP();

			$success = $e = $in_transaction = false;
			$id = 0;
			try {
				if(!$this->app->form->checkFormKey($form_key, $form_time, 'import'))
				{
					throw new SubmitFailException('Invalid form key submitted');
				}

				if(!isset($drivers[$import_driver]))
				{
					throw new SubmitFailException('Invalid import source specified');
				}

				try {
					$importer = DependencyInjector::grab('importer.driver.' . $drivers[$import_driver]);
					$importer->fetch($remote_id);
					$importer->fetchImage();
				}
				catch(RuntimeException $e)
				{
					throw new SubmitFailException($e->getMessage(), $e->getCode(), $e);
				}

				$tags = $this->app->tagger->extractTags($importer->tags);

				// Resolve tag aliases.
				$this->app->tagger->resolveTags($tags);

				R::begin();
				$in_transaction = true;

				$bean = R::dispense('post');

				$bean->status = BooruPostModel::ENTRY_ACCEPT; // default status (change to config later)

				$file = HOMEBOORU_IMAGE_IMPORT_ROOT . '/' . basename(trim($importer->local_filename));
				$ext = str_replace(array('jpg'), array('jpeg'), substr($importer->local_filename, strrpos($importer->local_filename, '.') + 1));
				try {
					$image = $this->app->imagine->open($file);
				}
				catch(\Exception $e)
				{
					@unlink(HOMEBOORU_IMAGE_IMPORT_ROOT . '/' . basename(trim($importer->local_filename)));
					throw new SubmitFailException($e->getMessage(), $e->getCode(), $e);
				}
				$size = $image->getSize();

				// begin image processing
				$hash = hash_file('sha1', $file);
				$beans = R::find('post', 'full_sha1 = ?', array($hash));
				if(!empty($beans))
				{
					throw new SubmitFailException('Submission rejected; duplicate checksum found');
				}

				$image->save(HOMEBOORU_IMAGE_FULL_ROOT . '/' . $hash . '.' . $ext);

				$bean->full_file = $hash . '.' . $ext;
				$bean->full_height = $size->getHeight();
				$bean->full_width = $size->getWidth();

				$bean->full_md5 = hash_file('md5', $file);
				$bean->full_sha1 = $hash;
				$bean->full_size = filesize($file);

				// small version, only if needed
				if($bean->full_height > $this->app['site.small_size'] || $bean->full_width > $this->app['site.small_size'])
				{
					$_size = $this->app['site.small_size'] ?: 650;
					$resize_small = new \Imagine\Image\Box($_size, $_size);
					$small = $image->thumbnail($resize_small);

					$small_size = $small->getSize();
					$small_file = hash('sha1', $small->get($ext)) . '.' . $ext;
					$small->save(HOMEBOORU_IMAGE_SMALL_ROOT . '/' . $small_file);

					$bean->small_file = $small_file;
					$bean->small_height = $small_size->getHeight();
					$bean->small_width = $small_size->getWidth();

					$bean->small_md5 = hash_file('md5', HOMEBOORU_IMAGE_SMALL_ROOT . '/' . $small_file);
					$bean->small_sha1 = hash_file('sha1', HOMEBOORU_IMAGE_SMALL_ROOT . '/' . $small_file);
					$bean->small_size = filesize(HOMEBOORU_IMAGE_SMALL_ROOT . '/' . $small_file);

					unset($small);
				}

				// thumbnail
				$_size = $this->app['site.thumbnail_size'] ?: 150;
				$resize_thumb = new \Imagine\Image\Box($_size, $_size);
				$thumb = $image->thumbnail($resize_thumb);

				$thumb_size = $thumb->getSize();
				$thumb_file = hash('sha1', $thumb->get('jpeg')) . '.jpeg';
				$thumb->save(HOMEBOORU_IMAGE_THUMB_ROOT . '/' . $thumb_file, array('quality' => 100));

				$bean->thumb_file = $thumb_file;
				$bean->thumb_height = $thumb_size->getHeight();
				$bean->thumb_width = $thumb_size->getWidth();

				$bean->thumb_md5 = hash_file('md5', HOMEBOORU_IMAGE_THUMB_ROOT . '/' . $thumb_file);
				$bean->thumb_sha1 = hash_file('sha1', HOMEBOORU_IMAGE_THUMB_ROOT . '/' . $thumb_file);
				$bean->thumb_size = filesize(HOMEBOORU_IMAGE_THUMB_ROOT . '/' . $thumb_file);

				unset($image);
				unset($thumb);

				$bean->source = $importer->source;
				$bean->rating = $importer->rating;

				$bean->submit_time = $now;

				$bean->submit_ip = $ip;
				$bean->submit_id = 1; // ANONYMOUS SUBMISSION. for later if needed.

				//$bean->score = '0';
				//$bean->totalratings = 0;
				//$bean->posrating = 0;
				//$bean->negrating = 0;

				$id = R::store($bean);
				R::tag($bean, $tags);

				R::commit();
				@unlink(HOMEBOORU_IMAGE_IMPORT_ROOT . '/' . basename(trim($importer->local_filename)));
				$success = true;
			}
			catch(SubmitFailException $e)
			{
				if($in_transaction)
				{
					R::rollback();
				}
				$success = false;
			}

			// post-submit stuff.
			return $this->respond('importentry.twig.html', 200, array(
				'page'				=> array(
					'submit'			=> true,
				),
				'form'				=> array(
					'submit'			=> true,
					'success'			=> $success,
					'new_id'			=> ($success) ? $id : false,
					'error'				=> (!$success) ? $e->getMessage() : false,
					'drivers'			=> $drivers,
					'time'				=> $this->app->form->getFormTime(),
					'key'				=> $this->app->form->buildFormKey('import')
				),
			));
		}
		else
		{
			// display normal form
			return $this->respond('importentry.twig.html', 200, array(
				'page'				=> array(
					'submit'			=> true,
				),
				'form'				=> array(
					'submit'			=> false,
					'drivers'			=> $drivers,
					'time'				=> $this->app->form->getFormTime(),
					'key'				=> $this->app->form->buildFormKey('import')
				),
			));
		}
	}
}
