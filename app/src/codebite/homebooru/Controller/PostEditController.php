<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Internal\SubmitFailException;
use \codebite\homebooru\Model\BooruPostModel;
use \codebite\homebooru\Model\BooruTagModel;
use \emberlabs\shot\Controller\ObjectController;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostEditController
	extends ObjectController
{
	public function runController()
	{
		$success = false;
		$id = $this->request->getInput('GET::id', 0);
		$this->app->form->setFormSeed($this->app->session->getSessionSeed());

		$bean = R::findOne('post', 'status = ? AND id = ?', array(BooruPostModel::ENTRY_ACCEPT, $id));

		if(empty($bean->id))
		{
			$this->response->setResponseCode(404);
			$this->response->setBody('error.twig.html');
			$this->response->setTemplateVars(array(
				'error'	=> array(
					'message'		=> 'Not found',
					'code'			=> 404,
				),
			));

			return $this->response;
		}

		$submit = $this->app->input->getInput('POST::submit', false);
		if($submit->getWasSet())
		{
			try {
				$form_key = $this->request->getInput('POST::formkey', '');
				$form_time = $this->request->getInput('POST::formtime', 0);
				$tags = $this->request->getInput('POST::tags', '');
				$source = $this->request->getInput('POST::source', '');
				$rating = $this->request->getInput('POST::rating', '');

				if(!$this->app->form->checkFormKey($form_key, $form_time, 'editpost'))
				{
					throw new SubmitFailException('Invalid form key submitted');
				}

				preg_match_all('#\w+[\w\(\)]*#i', $tags, $_tags);
				$tags = array_unique(array_shift($_tags));

				switch($rating)
				{
					case 'safe':
						$rating = BooruPostModel::RATING_SAFE;
					break;
					case 'questionable':
						$rating = BooruPostModel::RATING_QUESTIONABLE;
					break;
					case 'explicit':
						$rating = BooruPostModel::RATING_EXPLICIT;
					break;
					default:
						$rating = BooruPostModel::RATING_UNKNOWN;
				}

				$current_tags = $bean->getTags();
				R::addTags($bean, array_diff($tags, $current_tags));
				R::untag($bean, array_diff($current_tags, $tags));

				$bean->source = htmlspecialchars_decode($source, ENT_QUOTES);
				$bean->rating = $rating;

				R::store($bean);
			}
			catch(SubmitFailException $e)
			{
				$this->response->setBody('editpost.twig.html');
				$this->response->setTemplateVars(array(
					'page'		=> array(
						'edit'			=> true,
					),
					'post'		=> $bean,
					'form'		=> array(
						'submit'		=> true,
						'success'		=> false,
						'error'			=> $e->getMessage(),
						'time'			=> $this->app->form->getFormTime(),
						'key'			=> $this->app->form->buildFormKey('editpost')
					),
				));

				return $this->response;
			}
			$success = true;
		}

		$this->response->setBody('editpost.twig.html');
		$this->response->setTemplateVars(array(
			'page'				=> array(
				'edit'				=> true,
			),
			'post'				=> $bean,

			'form'				=> array(
				'submit'			=> $submit->getWasSet(),
				'success'			=> $success,
				'time'				=> $this->app->form->getFormTime(),
				'key'				=> $this->app->form->buildFormKey('editpost')
			),
		));

		return $this->response;
	}
}
