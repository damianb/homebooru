<?php
namespace codebite\homebooru\RedBean;
use \RedBean_IModelFormatter;

if(!defined('SHOT_ROOT')) exit;

class ModelFormatter
	implements RedBean_IModelFormatter
{
	/**
	 * Get the properly formatted (php 5.3 namespaced name, in our case) class name for the model we want to use
	 * @param string $model - The name of the model we're looking for
	 * @param mixed $bean - Unknown
	 * @return string - The full namespaced string for the model we're looking to load
	 */
	public function formatModel($model, $bean = NULL)
	{
		return 'codebite\\homebooru\\Model\\Booru' . ucfirst($model) . 'Model';
    }
}
