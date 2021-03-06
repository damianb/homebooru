<?php
namespace codebite\homebooru\Controller;
use \codebite\common\Controller\BaseController;
use \codebite\common\Internal\SubmitFailException;
use \codebite\homebooru\Model\PostModel;
use \codebite\homebooru\Model\TagModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostEditController
	extends BaseController
{
	protected $cacheable = false;

	public function before()
	{
		$this->app->form->setFormSeed($this->app->session->getSessionSeed());
	}

	public function runController()
	{
		$success = $submit = false;
		$id = $this->getInput('GET::id', 0);

		$bean = R::findOne('post', 'status = ? AND id = ?', array(PostModel::ENTRY_ACCEPT, $id));

		if(empty($bean->id))
		{
			return $this->respond('error.twig.html', 404, array(
				'error'	=> array(
					'message'		=> 'Not found',
					'code'			=> 404,
				),
			));
		}

		if($this->wasInputSet('POST::submit'))
		{
			$submit = true;
			R::begin();
			try {
				$form_key = $this->getInput('POST::formkey', '');
				$form_time = $this->getInput('POST::formtime', 0);
				$tags = $this->getInput('POST::tags', '');
				$source = $this->getInput('POST::source', '');
				$rating = $this->getInput('POST::rating', '');

				if(!$this->app->form->checkFormKey($form_key, $form_time, 'editpost'))
				{
					throw new SubmitFailException('Invalid form key submitted');
				}

				$tags = $this->app->tagger->extractTags($tags);

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

				$current_tags = $bean->getTags();
				R::addTags($bean, array_diff($tags, $current_tags));
				R::untag($bean, array_diff($current_tags, $tags));

				$bean->source = htmlspecialchars_decode($source, ENT_QUOTES);
				$bean->rating = $rating;

				R::store($bean);
				R::commit();

				$success = true;
			}
			catch(SubmitFailException $e)
			{
				R::rollback();
				$success = false;
			}
			catch(\Exception $e)
			{
				R::rollback();
				$success = false;
			}
		}

		return $this->respond('editpost.twig.html', 200, array(
			'page'				=> array(
				'edit'				=> true,
			),
			'post'				=> $bean,

			'form'				=> array(
				'submit'			=> $submit,
				'success'			=> $success,
				'error'				=> ($submit && !$success) ? $e->getMessage() : false,
				'time'				=> $this->app->form->getFormTime(),
				'key'				=> $this->app->form->buildFormKey('editpost')
			),
		));
	}
}
