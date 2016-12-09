<?php
/**
 * Session Management for phpmanage
 * 
 * @package phpmanage
 */

/**
 * User/Session class for phpmanage
 * 
 * @package phpmanage
 */
class manage_user implements user {
	/**** Private Variables ****/
	protected $info = array();
	
	/**** Constructor ****/
	function __construct() {
		// Spiders don't need a session
		if (self::bot_detector()) return;
		
		global $cfg;
		
		// If an instance of this class has already been created, let's
		// just use the info it already gathered
		if (is_array($cfg['sessioninfo'])) {
			$this->info = $cfg['sessioninfo'];
			return;
		}
		
		$starttime = microtime(TRUE);
		
		// check if the session key given by the cookie matches a current session
		$sessinfo = db_layer::session_check($_COOKIE[$this->sidname()]);
		
		// if not, check the key in the URL
		if (empty($sessinfo)) {
			$cookiefailed = TRUE;
			$sessinfo = db_layer::session_check($_REQUEST[$this->sidname()]);
		}
		
		// still no match -> create a new session
		if (empty($sessinfo)) $sessinfo = db_layer::session_create(array('ipaddr'=>$_SERVER['REMOTE_ADDR']));
		
		// record cookie success
		$sessinfo['cookiesuccess'] = !$cookiefailed;
		
		// record start time
		$sessinfo['starttime'] = $starttime;
		
		// save the info
		$this->info = $sessinfo;
				
		// create / update cookie
		setcookie($this->sidname(), $this->sid(), 0, '/');
		
		return;
	}

	/**** Public Accessor Functions ****/
	
	/**
	 * integer identifier for the user
	 */
	public function userid() { return $this->info['userid']; }
	
	/**
	 * string identifier for the user, what they type to log in
	 */
	public function login() { return $this->info['login']; }
	
	/**
	 * integer identifier for the current session
	 */
	public function sessid() { return $this->info['sessid']; }
	
	/**
	 * boolean for whether this session was authenticated via CAS
	 */
	public function caslogin() { return $this->info['caslogin']; }
	
	/**
	 * string identifier for the current session
	 *
	 * this string is used to communicate with the client and persist their session
	 */
	public function sid() { return $this->info['sid']; }
	
	/**
	 * URI parameter that will carry the session key
	 */
	public function sidname() { return 'sid'; }
	
	/**
	 * whether client is communicating securely (HTTPS)
	 */
	public function secure() { return ($_SERVER['https'] && $_SERVER['https'] != 'off'); }
	
	/**
	 * secure substitute for sid()
	 *
	 * It's not safe to use the same key for secure & unsecure communications.  A man in the middle could
	 * intercept the key when unsecure and use it to log in to the secure part of the site
	 */
	public function securesid() { return $this->info['securesid']; }
	
	/**
	 * whether the user sent us a valid cookie
	 *
	 * If we got a good cookie from the user, then we don't have to worry about putting the session
	 * key into URLs and form data.
	 */
	public function urlsession() { return $this->info['cookiesuccess'] ? FALSE : TRUE; }
	
	/**
	 * Store a name/value pair to a user's session
	 *
	 * Note that $name should be limited to 50 chars
	 *
	 * @param string $name
	 * @param string $val
	 * @return bool
	 */
	public function store($name, $val) {
		return db_layer::session_store($this->sessid(), $name, $val);
	}
	
	/**
	 * Retrieve the value of a name/value pair from a user's session
	 *
	 * Note that $name should be limited to 50 chars
	 *
	 * @param string $name
	 * @return string
	 */
	public function grab($name) {
		return db_layer::session_grab($this->sessid(), $name);
	}
	
	/**
	 * Retrieve time at which session was authenticated
	 *
	 * Since session authentication is frequently the first action taken to render a web page
	 * we'll provide a method for getting the time the constructor was first called.  Then it can
	 * be used to time the full page render.
	 */
	public function starttime() { return $this->info['starttime']; }
	
	/**** Public Static Functions ****/	
	public static function bot_detector() {
		$useragent = $_SERVER['HTTP_USER_AGENT'];
		if (!$useragent) return TRUE;
		global $cfg;
		$patternstr = file_get_contents($cfg['server_path'].'/includes/botlist.txt');
		$patterns = explode("\n", $patternstr);
		foreach ($patterns as $patt) {
			if ($patt && preg_match("/$patt/i", $useragent)) { return TRUE; }
		}
		return FALSE;
	}
			
}
?>