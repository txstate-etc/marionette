<?php
/**
 * @package timestamp
 * @author Nickolaus Wing
 */

/**
 * Timestamp Class
 *
 * This class is designed to make keeping track of timezone and daylight
 * savings time information more convenient.  It requires PHP 5.2.0 or greater.
 *
 * The main concept is that you will want to store all dates in your database
 * with a single timezone, so that you don't have to record the timezone along
 * with each date & time you submit.
 *
 * Your users, on the other hand, live in several different time zones.  You may
 * set a default for them, or you can allow them to set their preferred time zone,
 * and all you have to do is retrieve it and call timestamp::setzone() on each page
 * load.
 *
 * Then, when you create an instance of timestamp, just specify whether it's coming
 * from user input, or was retrieved from your database.  You can do this in the
 * constructor (new timestamp('user', 'now')), or with the fromdb() and fromuser()
 * methods.
 *
 * Similarly, when you need to output it, use todb() when writing to database,
 * and format() when displaying to a user.
 *
 * It will default to UTC for your database, and America/Chicago (US Central Time)
 * for your users.  If you use date_default_timezone_set() before creating any
 * timestamp objects, that will be used as your user default.
 */
class timestamp {
	protected $dt;
	protected static $userzone;
	protected static $dbzone;

	/**
	 * @access private
	 *
	 * Retrieves the current timezone setting for either the user or the
	 * database.  $type may be 'user' or 'db', and defaults to 'db'.
	 *
	 * @param string $type
	 */
	public static function tzone($type) {
		if (!self::$userzone) self::setzone(date_default_timezone_get());
		if (!self::$userzone) self::setzone('America/Chicago');
		if (!self::$dbzone) self::setdbzone('UTC');
		if ($type == 'user') return self::$userzone;
		else return self::$dbzone;
	}

	/**
	 * Set user's preferred time zone
	 *
	 * You'll generally want to call this method once per page load, informing
	 * of the current user's preference.  If you do not offer users the ability to
	 * specify a time zone, this will simply default to the zone set by
	 * date_default_timezone_set() or 'America/Chicago' (US Central Time).
	 *
	 * $tzone should be a string recognized by the DateTimeZone constructor. Examples:
	 * 'America/Chicago', 'GMT', 'UTC', 'America/New_York'
	 *
	 * @param string $tzone
	 */
	public static function setzone($tzone) {
		if (!$tzone) return;
		self::$userzone = new DateTimeZone($tzone);
	}

	/**
	 * Set time zone that is assumed for all database entries
	 *
	 * Normally this should be UTC, but if you are adding this class to an existing project,
	 * you may have a great deal of data stored in your local time zone.  Use this method
	 * to specify which time zone that is.

	 * $tzone should be a string recognized by the DateTimeZone constructor. Examples:
	 * 'America/Chicago', 'GMT', 'UTC', 'America/New_York'
	 *
	 * @param string $tzone
	 */
	public static function setdbzone($tzone) {
		self::$dbzone = new DateTimeZone($tzone);
	}

	/**
	 * Create a new timestamp instance
	 *
	 * When constructing a timestamp, you may provide the date string and its
	 * source.  If the date was pulled from the database, set $source to 'db'. If it comes
	 * from the user or is created in response to a user's action (like a last-modified date),
	 * then set $source to 'user'.
	 *
	 * Defaults to 'now' in the user's zone.
	 *
	 * $source should be 'user' or 'db'.
	 *
	 * $stamp should be a string recognized by strtotime().
	 *
	 * @param string $source
	 * @param string $stamp
	 * @return timestamp
	 */
	public function __construct($source='user', $stamp = '') {
		if (!$stamp) $stamp = 'now';
		if ($source == 'user') $this->fromuser($stamp);
		else $this->fromdb($stamp);
	}

	/**
	 * @access private
	 */
	protected function setstamp($datetime, $tzone) {
		$this->dt = new DateTime($datetime, $tzone);
	}

	/**
	 * @access private
	 */
	protected function getstamp($tzone, $format = '') {
		if (!$format) $format = 'YmdHis';
		$this->dt->setTimeZone($tzone);
		return $this->dt->format($format);
	}

	/**
	 * Return the current timestamp as a DateTime object
	 */
	public function getdatetime() {
		$this->dt->setTimeZone(self::tzone('user'));
	  return $this->dt;
	}

	/**
	 * Return a formatted date
	 *
	 * This works exactly like PHP's DateTime::format().  See the documentation at
	 * php.net for details.
	 *
	 * $format defaults to 'YmdHis'.
	 *
	 * @param string $format
	 * @return string
	 */
	public function format($format = '') {
		if (!$format) $format = 'D, M j Y g:ia';
		return $this->getstamp(self::tzone('user'), $format);
	}

	/**
	 * Accept a date-time string from a user
	 *
	 * $datetime should be a string recognized by strtotime().
	 *
	 * @param string $datetime
	 * @return void
	 */
	public function fromuser($datetime) {
		$this->setstamp($datetime, self::tzone('user'));
	}

	/**
	 * Return a date-time string suitable for database storage
	 *
	 * By default, returns a string with 'YmdHis' format, which should be easy for any SQL database
	 * to parse.  If for some odd reason yours cannot, you may specify the format.
	 *
	 * @param string $format
	 * @return string
	 */
	public function todb($format = '') {
		return $this->getstamp(self::tzone('db'), $format);
	}

	/**
	 * Accept a date-time string from the database
	 *
	 * $datetime should be a string recognized by strtotime().
	 *
	 * @param string $datetime
	 * @return void
	 */
	public function fromdb($datetime) {
		$this->setstamp($datetime, self::tzone('db'));
	}
}
?>
