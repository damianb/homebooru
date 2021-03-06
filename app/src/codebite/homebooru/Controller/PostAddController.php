<?php
namespace codebite\homebooru\Controller;
use \codebite\common\Controller\BaseController;
use \codebite\common\Internal\SubmitFailException;
use \codebite\homebooru\Model\PostModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostAddController
	extends BaseController
{
	protected $cacheable = false;

	public function before()
	{
		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
	}

	public function runController()
	{
		if($this->wasInputSet('POST::submit'))
		{
			$now = time();

			$form_key = $this->getInput('POST::formkey', '');
			$form_time = $this->getInput('POST::formtime', 0);

			$data = $this->getInput('POST::file', '');

			$source = $this->getInput('POST::source', '');
			$rating = $this->getInput('POST::rating', '');
			$tags = $this->getInput('POST::tags', '');

			$ip = $this->request->getIP();

			$success = $e = false;
			$id = 0;
			try {
				if(!$this->app->form->checkFormKey($form_key, $form_time, 'submit'))
				{
					throw new SubmitFailException('Invalid form key submitted');
				}

				$tags = $this->app->tagger->extractTags($tags);

				// Resolve tag aliases.
				$this->app->tagger->resolveTags($tags);

				R::begin();
				$bean = R::dispense('post');

				$bean->status = PostModel::ENTRY_ACCEPT; // default status (change to config later)

				$file = HOMEBOORU_IMAGE_IMPORT_ROOT . '/' . basename(trim($data));
				$ext = str_replace(array('jpg'), array('jpeg'), substr($data, strrpos($data, '.') + 1));
				try {
					$image = $this->app->imagine->open($file);
				}
				catch(\Exception $e)
				{
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

				// special metadata
				switch($rating)
				{
					case 'safe':
						$rating = PostModel::RATING_SAFE;
					break;
					case 'questionable':
						$rating = PostModel::RATING_QUESTIONABLE;
					break;
					case 'explicit':
						$rating = PostModel::RATING_EXPLICIT;
					break;
					default:
						$rating = PostModel::RATING_UNKNOWN;
				}

				$bean->source = htmlspecialchars_decode($source, ENT_QUOTES);
				$bean->rating = $rating;
				$bean->submit_time = $now;
				$bean->submit_ip = $ip;
				$bean->user_id = 1; // ANONYMOUS SUBMISSION. for later if needed.

				$id = R::store($bean);
				R::tag($bean, $tags);

				R::commit();
				$success = true;
			}
			catch(SubmitFailException $e)
			{
				R::rollback();
				$success = false;
			}

			// post-submit stuff.
			return $this->respond('addentry.twig.html', 200, array(
				'page'				=> array(
					'submit'			=> true,
				),
				'form'				=> array(
					'submit'			=> true,
					'success'			=> $success,
					'new_id'			=> ($success) ? $id : false,
					'error'				=> (!$success) ? $e->getMessage() : false,
					'time'				=> $this->app->form->getFormTime(),
					'key'				=> $this->app->form->buildFormKey('submit')
				),
			));
		}
		else
		{
			// display normal form
			return $this->respond('addentry.twig.html', 200, array(
				'page'				=> array(
					'submit'			=> true,
				),
				'form'				=> array(
					'submit'			=> false,
					'time'				=> $this->app->form->getFormTime(),
					'key'				=> $this->app->form->buildFormKey('submit')
				),
			));
		}
	}
}
