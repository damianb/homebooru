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

	public function memPeak()
	{
		return \codebite\homebooru\Runtime\formatBytes(memory_get_peak_usage(), 2);
	}
}
