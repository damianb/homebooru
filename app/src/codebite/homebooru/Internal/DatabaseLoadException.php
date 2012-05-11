<?php
namespace codebite\homebooru\Internal;
use \emberlabs\openflame\Core\Internal\OpenFlameException;
use \Exception;

class DatabaseLoadException
	extends Exception
	implements OpenFlameException {}
