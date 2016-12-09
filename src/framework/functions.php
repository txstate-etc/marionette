<?php
/**
 * function.php
 * 
 * A place to store all the random nifty functions I come up with.
 * 
 * @package functions
 */

/**
 * Add a slash at the end of a path if needed
 * 
 * Does nothing if the path provided already ends in a slash.
 * 
 * @param string $dirname
 * @return string
 */
function endslash($dirname) {
	$dirname .= substr($dirname,-1) == '/' ? '' : '/';
	return $dirname;
}

/**
 * Given a path or filename, return file extension
 * 
 * Central place to store this regex, so it can be improved over time.
 * 
 * @param string $filename
 * @return string
 */
function file_extension($filename) {
	list($filename) = explode('?', $filename);
	preg_match('/\.([^\.]+)$/', $filename, $match);
	return $match[1];
}

/**
 * Detect maximum array depth
 * 
 * Recursive function to go as deep as possible into an
 * array tree and return the maximum depth it finds.
 * 
 * @param array $array
 * @return int
 */
function array_depth($array) {
	if (!is_array($array)) return 0;
	foreach ($array as $val) {
		$newdepth = 1 + array_depth($val);
		if ($newdepth > $maxdepth) $maxdepth = $newdepth;
	}
	return $maxdepth;
}

/**
 * Convert a number of seconds into hours:minutes:seconds
 * 
 * $skip_secs=TRUE will make this return only hours:minutes
 * 
 * @param int $secs
 * @param bool $skip_secs
 * @return string
 */
function secs_to_time($secs, $skip_secs = FALSE) {
	$mins = sprintf("%02d", intval(($secs % 3600) / 60));
	$hours = intval($secs / 3600);
	$secs = sprintf("%02d", $secs % 60);
	if ($control) {
		return "$hours:$mins";
	} else {
		return "$hours:$mins:$secs";
	}
}

/**
 * Find the number of seconds between two DateTime objects
 * 
 * @param DateTime $now
 * @param DateTime $then
 * @return int
 */
function seconds_between($now, $then) {
	$now_secs = strtotime($now->format('YmdHis'));
	$then_secs = strtotime($then->format('YmdHis'));
	return abs($now_secs - $then_secs);
}

function interval_between($now, $then, $skip_seconds = TRUE) {
	$secs = seconds_between($now, $then);
	$mins = intval(($secs % 3600) / 60);
	$hours = intval(($secs % (3600*24)) / 3600);
	$days = intval($secs / (3600*24));
	$seconds = sprintf("%02d", $secs % 60);
	if ($days) $ret .= $days.'d';
	if ($hours || $days) $ret .= $hours.'h';
	$ret .= $mins.'m';
	if (!$skip_seconds) $ret .= $seconds.'s'; 
	return $ret;
}

function days_between($now, $then) {
	$info = parse_interval($now->diff($then, TRUE));
}

/**
 * Parse a DateInterval object
 *
 * Use this function to parse the enigmatic DateInterval object into something
 * you can actually use.  Returns an array:
 * <pre>array(
 *   'years' => int
 *   'months' => int
 *   'days' => int
 *   'hours' => int
 *   'minutes' => int
 *   'seconds' => int
 * )</pre>
 *
 * @param DateInterval $interval
 * @return array
 */
function parse_interval($interval) {
	preg_match('/(P((\d+)Y)?((\d+)M)?((\d+)D)?)?(T((\d+)H)?((\d+)M)?((\d+)S)?)?/', $interval, $match);
	return array(
		'years' => $match[3],
		'months' => $match[5],
		'days' => $match[7],
		'hours' => $match[10],
		'minutes' => $match[12],
		'seconds' => $match[14]
	);
}

/**
 * Get a human readable datetime stamp
 *
 * Uses relative terms when possible, e.g. 'Next Week', 'Tomorrow', 'Yesterday',
 * [this] 'Thursday', etc.
 *
 * @param DateTime $date
 * @return string
 */
function relative_date($date, $skip_year = TRUE) {
	if ($date instanceof timestamp) $date = new DateTime($date->format('YmdHis'));
	$format = ($skip_year ? 'l, M j - g:ia' : 'l, M j, Y - g:ia');
	$dateonly = $date->format('l, M j -');
	$timeonly = ($date->format('i') == 0 ? $date->format('ga') : $date->format('g:ia'));
	$today = new DateTime();
	$days = ($date->format('Y') - $today->format('Y'))*365 + $date->format('z') - $today->format('z');
	if ($days == 0) $rel = 'Today';
	elseif ($days == 1) $rel = 'Tomorrow';
	elseif ($days == -1) $rel = 'Yesterday';
	elseif ($days < 0 && $days > -8) $rel = 'Last '.$date->format('l');
	elseif ($days < 0) $rel = $dateonly;
	elseif ($days < 7) $rel = $date->format('l');
	elseif ($days < 14) $rel = 'Next '.$date->format('l');
	else $rel = $dateonly;
	return $rel.' '.$timeonly;
}

/**
 * Parse a date string with specified format
 * 
 * PHP doesn't provide this until 5.3.0, so I'm building one.  Returns
 * date in YmdHis format, which is recognized easily by strtotime or
 * an SQL database.
 * 
 * $format should be specified according to the rules used by PHP's strftime().
 * 
 * @param string $date
 * @param string $format
 * @return string
 */
function parse_formatted_date($date, $format) {
	$info = strptime($date, $format);
	$year = $info['tm_year']+1900;
	$month = sprintf("%02d", $info['tm_mon']+1);
	$day = sprintf("%02d", $info['tm_mday']);
	$hours = sprintf("%02d", $info['tm_hour']);
	$mins = sprintf("%02d", $info['tm_min']);
	$secs = sprintf("%02d", $info['tm_sec']);
	return $year.$month.$day.$hours.$mins.$secs;
}

/**
 * Search through data array and find all keys
 * 
 * Expects a 2-d data array similar to what might be returned from a database,
 * but with the possibility that some rows have omitted some columns.
 * 
 * This searches all rows and makes sure to find all the columns that are
 * in use.
 * 
 * @param array $data
 * @return array
 */
function get_result_columns($data) {
	$updatecols = array();
	foreach ($data as $row) {
		foreach ($row as $key => $val) {
			if (!$used[$key]) {
				$updatecols[] = $key;
				$used[$key] = TRUE;
			}
		}
	}
	return $updatecols;
}

/**
 * Generate a string of random characters
 * 
 * There are three possible modes this can operate in,
 * 'alpha', 'numeric', and the default, both.  Should be
 * pretty self-explanatory.
 * 
 * One thing to note is that this function is guaranteed not
 * to repeat a string in a single page load.  This is important for
 * the framework because we often auto-generate DOM IDs for elements
 * that need them and don't have them.  DOM IDs should always be
 * unique.
 * 
 * @param int $len
 * @param string $mode
 * @return string
 */
function generatestring($len = 12, $mode = '') {
	static $used = array();
	
	if ($mode == "alpha")
		$ch = array_merge(range('a','z'), range('A','Z'));
	elseif ($mode == 'numeric')
		$ch = range(0,9);
	else 
		$ch = array_merge(range('a','z'), range('A','Z'), range(0,9));

	$max = count($ch) - 1;
	do {
		for ($i = 0; $i < $len; $i++) {
			$idx = mt_rand(0,$max);
			$return .= $ch[$idx];
		}
	} while ($used[$return]);
	$used[$return] = 1;
	return $return;
}

/**
 * Clean up text before displaying
 * 
 * Sometimes people will paste HTML source from Word or something into one
 * of your text fields.  You may want to use this function on the data
 * before you send it to the database.  It will decode all HTML encodings such
 * as &amp; and &quot;, and some other things that html_entity_decode() will miss.
 * 
 * This is NOT used by default in the framework.  You should use it in specific
 * places where you expect plain text from your users and don't want to see any
 * HTML encoded text go in the database.
 * 
 * @param string $text
 * @return string
 */
function cleantext($text) {
	$text = str_replace('&#8216;', "'", $text);
	$text = str_replace('&#8217;', "'", $text);
	$text = str_replace('&#8220;', '"', $text);
	$text = str_replace('&#8221;', '"', $text);
	$text = str_replace('&#8482;', '[tm]', $text);
	$text = str_replace('&#8230;', '...', $text);
	$text = str_replace('&#8211;', '--', $text);
	$text = html_entity_decode($text);
	return trim($text);
}

/**
 * Truncate a string to a given length
 * 
 * Guarantees a maximum string length.  An ellipsis is added only
 * if truncation occurs and DOES count toward the total.  For example:
 * 
 * <pre>echo partial_str('This is a very long string.', 12); //output: This is a...
 * echo partial_str('Shorter one.', 12); //output: Shorter one.</pre>
 * 
 * @param string $str
 * @param int $len
 * @return string
 */
function partial_str($str, $len) {
	if (strlen($str) > ($len)) {
		$str = trim(substr($str, 0, $len-3)) . '...';
	}
	return $str;
}

/**
 * Substring search
 * 
 * Checks if $haystack contains $needle. This is the fastest way to
 * accomplish this in PHP, better than using preg_match() or similar.
 * 
 * @param string $haystack
 * @param string $needle
 * @return bool
 */
function strcont($haystack, $needle) {
	return !(strpos($haystack, $needle) === FALSE);
}

/**
 * Convert a numeric rank to a string
 *
 * Sometimes you want to clue users in to the fact that a number is a ranking, by
 * using "1st" or "2nd" instead of "1" or "2".  Use this function to do the conversion
 * according to the rules commonly observed by English speakers.
 * 
 * @param int $rank
 * @return string
 */
function rank_to_placing($rank) {
	$last = substr($rank, -1);
	$lasttwo = substr($rank, -2);
	if ($lasttwo == 11) return $rank.'th';
	elseif ($last == 1) return $rank.'st';
	elseif ($lasttwo == 12) return $rank.'th';
	elseif ($last == 2) return $rank.'nd';
	elseif ($lasttwo == 13) return $rank.'th';
	elseif ($last == 3) return $rank.'rd';
	else return $rank.'th';
}

/**
 * Clean input to ensure array data
 * 
 * This function is designed to clean up the input from a select multiple 
 * form element when you have a "None" option with empty value.  When users
 * select "None", php will send an array with one element, an empty string.
 * 
 * This makes it look like the array is !empty which in this case is wrong. This
 * function will convert an array with one emptystring element into an empty array.
 *
 * @param array $arr
 * @return array
 */
function clean_input_array($arr) {
	if (!is_array($arr)) return $arr;
	if (count($arr) == 1 && !$arr[0]) return array();
	return $arr;
}

/**
 * Get a resized JPEG version of image data
 *
 * Only works with gd installed and if the data is a validly formatted
 * image that PHP's gd library can handle.
 *
 * The $max_height and $max_width parameters should describe the area in
 * which the image will be placed.  It will be stretched to fill the area
 * but maintain its aspect ratio.
 *
 * Image is automatically sharpened based on the characteristics of the
 * resize.
 *
 * You may specify the JPEG quality of the final image, from 0 to 100.  Note
 * that image quality will depend greatly on the quality of the input image (of
 * course).
 *
 * @param resource $image_resource
 * @param int $max_width
 * @param int $max_height
 * @param int $quality
 * @return string
 */
function resize_image($image_resource, $max_width = 160, $max_height = 120, $quality = 60) {
	if (!function_exists('imagecreatefromstring')) return false;
	if (!is_resource($image_resource)) $img = imagecreatefromstring($image_resource);
	else $img = $image_resource;
	
	if (!$img) return false;
	
	// determine the proper dimensions
	$old_x = imagesx($img);
	$old_y = imagesy($img);
	if (($old_y / $max_height) > ($old_x / $max_width)) {
		$thumb_h = $max_height;
		$thumb_w = $old_x * ($max_height / $old_y);
	} else {
		$thumb_h = $old_y * ($max_width / $old_x);
		$thumb_w = $max_width;
	}
	
	// create the new image
	$dest = imagecreatetruecolor($thumb_w,$thumb_h);
	imagecopyresampled($dest, $img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
	
	// sharpen the new image
	$final	= $final * (750.0 / $old_x);
	$a		= 52;
	$b		= -0.27810650887573124;
	$c		= .00047337278106508946;
	$result = $a + $b * $thumb_w + $c * $thumb_w * $thumb_w;
	$sharpness = max(round($result), 0);
	$sharpenMatrix	= array(
		array(-1, -2, -1),
		array(-2, $sharpness + 12, -2),
		array(-1, -2, -1)
	);
	$divisor		= $sharpness;
	$offset			= 0;
	imageconvolution($dest, $sharpenMatrix, $divisor, $offset);
	
	// output the image and capture it as a string
	ob_start();
	imagejpeg($dest, NULL, $quality);
	$data = ob_get_contents();
	ob_end_clean();
	
	// done
	imagedestroy($dest);
	return $data;
}

function diff($old, $new) {
	global $cfg;
	require_once(endslash($cfg['library_root']).'includes/Diff/Diff.php');
	require_once(endslash($cfg['library_root']).'includes/Diff/Renderer/pwo.php');
	
	$lines1 = preg_split('/\r?\n/', $old, -1, PREG_SPLIT_DELIM_CAPTURE);
	$lines2 = preg_split('/\r?\n/', $new, -1, PREG_SPLIT_DELIM_CAPTURE);

	/* Create the Diff object. */
	$diff = new Text_Diff('auto', array($lines1, $lines2));
	
	/* Output the diff in unified format. */
	$renderer = new Text_Diff_Renderer_pwo();
	return $renderer->render($diff);
}

function defvar(&$var, $defaultval) {
	if (empty($var)) $var = $defaultval;
}

function choose($a, $b) {
	if (empty($a)) return $b;
	else return $a;
}
?>