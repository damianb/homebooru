<?php
namespace codebite\homebooru\Internal;
use \emberlabs\openflame\Core\Internal\OpenFlameException;
use \Exception;

class SubmitFailException
	extends Exception
	implements OpenFlameException {}
