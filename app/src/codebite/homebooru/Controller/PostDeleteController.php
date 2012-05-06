<?php
namespace codebite\homebooru\Controller;
use \codebite\homebooru\Internal\SubmitFailException;
use \codebite\homebooru\Model\BooruPostModel;
use \codebite\homebooru\Model\BooruTagModel;
use \R;

if(!defined('SHOT_ROOT')) exit;

class PostDeleteController
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

		$bean = R::findOne('post', 'status = ? AND id = ?', array(BooruPostModel::ENTRY_ACCEPT, $id));

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

				if(!$this->app->form->checkFormKey($form_key, $form_time, 'deletepost'))
				{
					throw new SubmitFailException('Invalid form key submitted');
				}

				R::trash($bean);
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

		return $this->respond('deletepost.twig.html', 200, array(
			'page'				=> array(
				'delete'			=> true,
			),
			'post'				=> $bean,

			'form'				=> array(
				'submit'			=> $submit,
				'success'			=> $success,
				'error'				=> ($submit && !$success) ? $e->getMessage() : false,
				'time'				=> $this->app->form->getFormTime(),
				'key'				=> $this->app->form->buildFormKey('deletepost')
			),
		));
	}
}
