<?php
namespace codebite\homebooru\Runtime;

if(!defined('SHOT_ROOT')) exit;

/**
 * A time-insensitive string comparison function, to help deter highly accurate timing attacks.
 * @param string $a - The first string to compare
 * @param string $b - The second string to compare
 * @return boolean - Do the strings match?
 *
 * @license - Public Domain - http://twitter.com/padraicb/status/41055320243437568
 * @link http://blog.astrumfutura.com/2010/10/nanosecond-scale-remote-timing-attacks-on-php-applications-time-to-take-them-seriously/
 * @author http://twitter.com/padraicb
 */
function full_compare($a, $b)
{
	if(strlen($a) !== strlen($b))
		return false;

	$result = 0;

	for($i = 0, $size = strlen($a); $i < $size; $i++)
		$result |= ord($a[$i]) ^ ord($b[$i]);

	return $result == 0;
}

function formatBytes($size, $precision = 2)
{
    $base = log($size) / log(1024);
    $suffixes = array('', 'KiB', 'MiB', 'GiB', 'TiB');

    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}

/**
 * Trim characters from either (or both) ends of a string in a way that is
 * multibyte-friendly.
 *
 * Mostly, this behaves exactly like trim() would: for example supplying 'abc' as
 * the charlist will trim all 'a', 'b' and 'c' chars from the string, with, of
 * course, the added bonus that you can put unicode characters in the charlist.
 *
 * We are using a PCRE character-class to do the trimming in a unicode-aware
 * way, so we must escape ^, \, - and ] which have special meanings here.
 * As you would expect, a single \ in the charlist is interpretted as
 * "trim backslashes" (and duly escaped into a double-\ ). Under most circumstances
 * you can ignore this detail.
 *
 * As a bonus, however, we also allow PCRE special character-classes (such as '\s')
 * because they can be extremely useful when dealing with UCS. '\pZ', for example,
 * matches every 'separator' character defined in Unicode, including non-breaking
 * and zero-width spaces.
 *
 * It doesn't make sense to have two or more of the same character in a character
 * class, therefore we interpret a double \ in the character list to mean a
 * single \ in the regex, allowing you to safely mix normal characters with PCRE
 * special classes.
 *
 * *Be careful* when using this bonus feature, as PHP also interprets backslashes
 * as escape characters before they are even seen by the regex. Therefore, to
 * specify '\\s' in the regex (which will be converted to the special character
 * class '\s' for trimming), you will usually have to put *4* backslashes in the
 * PHP code - as you can see from the default value of $charlist.
 *
 * @param string
 * @param charlist list of characters to remove from the ends of this string.
 * @param boolean trim the left?
 * @param boolean trim the right?
 * @return string
 *
 * @link http://www.php.net/manual/en/ref.mbstring.php#87047
 * @license assumed public domain
 */
function mb_trim($string, $charlist='\\\\s', $ltrim=true, $rtrim=true)
{
	$both_ends = $ltrim && $rtrim;

	$char_class_inner = preg_replace(
		array( '/[\^\-\]\\\]/S', '/\\\{4}/S' ),
		array( '\\\\\\0', '\\' ),
		$charlist
	);

	$work_horse = '[' . $char_class_inner . ']+';
	$ltrim && $left_pattern = '^' . $work_horse;
	$rtrim && $right_pattern = $work_horse . '$';

	if($both_ends)
	{
		$pattern_middle = $left_pattern . '|' . $right_pattern;
	}
	elseif($ltrim)
	{
		$pattern_middle = $left_pattern;
	}
	else
	{
		$pattern_middle = $right_pattern;
	}

	return preg_replace("/$pattern_middle/usSD", '', $string);
}
