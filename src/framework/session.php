<?php 
/**
 * session.php
 * @package htmlsupport 
 */

/**
 * Default User/Session management class
 * 
 * This is the default session management for phpWebObjects.  It
 * uses the built-in PHP session management with a few modifications.
 * 
 * You can write your own version to use a database backend instead, or
 * to alter the way your sessions are tracked.  Just make sure to
 * implement user, found in userinterface.php, as those methods are needed
 * elsewhere in the library.
 * 
 * @package htmlsupport
 */
class default_user implements user {
	protected $starttime;
	function __construct() {
		// save off the starting time
		$this->starttime = microtime(TRUE);

		// we're going to create our own directory for saving cookies, so that we
		// can extend the amount of time before they're garbage collected.
		$path = session_save_path();
		if ($path) $ourpath = endslash($path).'pwo/';
		else $ourpath = endslash(sys_get_temp_dir()).'pwo/';
		if (!file_exists($ourpath)) mkdir($ourpath);
		session_save_path($ourpath);
		
		// set the max lifetime to 30 days, so that extend() will work
		ini_set('session.gc_maxlifetime', 60*60*24*30);
		
		// let's set the cookie path to our link root, if we have one
		global $cfg;
		if ($cfg['link_root']) {
			$info = session_get_cookie_params(); 
			session_set_cookie_params($info['lifetime'], $cfg['link_root'], $info['domain'], $info['secure'], $info['httponly']);
		}
		
		// we do our own URL re-writing, so make sure PHP doesn't try
		// to do it for us
		ini_set('session.use_trans_sid', false);
		// use the long-term cookie if session cookie is missing
		if (!$_REQUEST[$this->sidname()] && $_COOKIE['cookiesid']) {
			session_id($_COOKIE['cookiesid']); }
						
		// check to see if the session can be continued
		session_name($this->sidname());
		session_start();
				
		// if this is a good session, "initiated" will be TRUE
		if (!$_SESSION['initiated']) {
			session_destroy();
			session_id(generatestring(12));
			session_start();
			$_SESSION['initiated'] = TRUE;
		} else {
			// if we just moved from secure to non-secure or vice versa,
			// generate a new session id.  This helps prevent session hijack
			// exploits.
			if ($_SESSION['wassecure'] != doc::issecure()) $this->escalate();
		}
		$_SESSION['wassecure'] == doc::issecure();
		
		// set the save path back to what it was
		session_save_path($path);
	}
	/**
	 * Return the current session name
	 * @return string
	 */
	public function sidname() {
		return 'sid';
	}
	/**
	 * Return the current session id string
	 * @return string
	 */
	public function sid() {
		return session_id();
	}
	/**
	 * Return the start time (as float) for this page load
	 * 
	 * Typically session management will be one of the first things to be processed,
	 * so this can be used to help time page loading times.
	 * 
	 * @return float
	 */
	public function starttime() {
		return $this->starttime;
	}
	/**
	 * Determine whether we should append session info to URLs
	 * @return bool
	 */
	public function urlsession() {
		return $_COOKIE[$this->sidname()] ? 0 : 1;
	}
	/**
	 * Escalate the user to another security level
	 * 
	 * This method will assign a new session id when requested.  This method
	 * should be used after the user provides credentials or
	 * is given greater access.  Failure to do so exposes them to
	 * attack.
	 * 
	 * By default, this method is called every time the user switches to or away
	 * from an SSL encrypted section of the site.
	 * 
	 * @return unknown_type
	 */
	public function escalate() {
		$saved = $_SESSION;
		session_destroy();
		session_id(generatestring(12));
		session_start();
		$_SESSION = $saved;
	}
	/**
	 * Get session variable
	 * 
	 * Retrieve a name/value pair from the user's session.
	 * 
	 * @param string $name
	 * @return string
	 */
	public function get($name) {
		return $_SESSION['data'][$name];
	}
	/**
	 * Set session variable
	 * 
	 * Use this method to store a name/value pair with the user's session.
	 * 
	 * Returns the old value of the variable so you can preserve it in some
	 * situations.
	 * 
	 * @param string $name
	 * @param string $value
	 * @return string
	 */
	public function set($name, $value) {
		$ret = $_SESSION['data'][$name];
		$_SESSION['data'][$name] = $value;
		return $ret;
	}
	/**
	 * Create a cookie with a longer expiration date.
	 * 
	 * Use this method to extend this session beyond the typical
	 * browser-window session length, by setting an additional
	 * cookie with a longer expiration date (specified by $days).
	 * 
	 * @param int $days
	 * @return void
	 */
	public function extend($days = 30) {
		global $cfg;
		setcookie('cookiesid', $this->sid(), time()+3600*24*$days, $cfg['link_root']);
	}
	
	public function destroy() {
		session_destroy();
	}
}
?>
