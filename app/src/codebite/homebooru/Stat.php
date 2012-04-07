<?php
namespace codebite\homebooru;

class Stat
{
	public function server()
	{
		$name = explode('.', php_uname('n'));
		return strtolower(array_shift($name));
	}

	public function time()
	{
		return round(microtime(true) - SHOT_LOAD_START, 5);
	}

	public function mem()
	{
		return \codebite\homebooru\Runtime\formatBytes(memory_get_usage(), 2);
	}

	public function commit()
	{
		if(file_exists(SHOT_CONFIG_ROOT . '/.commit'))
		{
			return substr(file_get_contents(SHOT_CONFIG_ROOT . '/.commit'), 0, 7);
		}
	}
}
