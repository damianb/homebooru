<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Internal\SubmitFailException;
use \codebite\homebooru\Model\BooruPostModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class AddEntryController
	extends ObjectController
{
	public function runController()
	{
		$submit = $this->app->input->getInput('POST::submit', false);
		$this->app->form->setFormSeed($this->app->session->getSessionSeed());

		if($submit->getWasSet())
		{
			$now = time();

			$form_key = $this->request->getInput('POST::formkey', '');
			$form_time = $this->request->getInput('POST::formtime', 0);

			$data = $this->request->getInput('POST::file', '');
			//$data = $this->app->seeder->buildRandomString();

			$tags = $this->request->getInput('POST::tags', '');

			$ip = $this->request->getIP();

			$success = false;
			$id = 0;
			try {
				if(!$this->app->form->checkFormKey($form_key, $form_time, 'submit'))
				{
					throw new SubmitFailException('Invalid form key submitted');
				}

				preg_match_all('#\w+[\w\(\)]*#i', $tags, $_tags);
				$tags = array_unique(array_shift($_tags));
				// run tags through alias resolver

				$bean = R::dispense('post');

				$bean->status = BooruPostModel::ENTRY_QUEUE; // default status (change to config later)

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
				if($bean->full_height > $this->app['site.small_size'] && $bean->full_width > $this->app['site.small_size'])
				{
					$resize_small = new \Imagine\Image\Box($this->app['site.small_size'], $this->app['site.small_size']);
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
				}

				// thumbnail

				$resize_thumb = new \Imagine\Image\Box($this->app['site.thumbnail_size'], $this->app['site.thumbnail_size']);
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
				unset($small);
				unset($thumb);

				// special metadata
				$bean->source = ''; // unknown, default
				$bean->rating = BooruPostModel::RATING_UNKNOWN;

				$bean->submit_time = $now;
				//$bean->handle_time = 0;

				$bean->submit_ip = $ip;
				$bean->submit_id = 1; // ANONYMOUS SUBMISSION. for later if needed.

				//$bean->score = '0';
				//$bean->totalratings = 0;
				//$bean->posrating = 0;
				//$bean->negrating = 0;

				$id = R::store($bean);
				R::tag($bean, $tags);

				$success = true;
			}
			catch(SubmitFailException $e)
			{
				$this->response->setBody('addentry.twig.html');
				$this->response->setTemplateVars(array(
					'page'	=> array(
						'submit'			=> true,
					),
					'form'	=> array(
						'submit'		=> true,
						'success'		=> false,
						'error'			=> $e->getMessage(),
						'time'			=> $this->app->form->getFormTime(),
						'key'			=> $this->app->form->buildFormKey('submit')
					),
				));

				return $this->response;
			}

			// post-submit stuff.
			$this->response->setBody('addentry.twig.html');
			$this->response->setTemplateVars(array(
				'page'				=> array(
					'submit'			=> true,
				),
				'form'				=> array(
					'submit'			=> true,
					'success'			=> true,
					'new_id'			=> $id,
					'time'				=> $this->app->form->getFormTime(),
					'key'				=> $this->app->form->buildFormKey('submit')
				),
			));
		}
		else
		{
			// display normal form
			$this->response->setBody('addentry.twig.html');
			$this->response->setTemplateVars(array(
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

		return $this->response;
	}
}
