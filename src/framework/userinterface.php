<?php
/**
 * @package htmlsupport
 */
interface user {
	// Run microtime(TRUE) at the beginning of the constructor
	// This method should retrieve that number.
	public function starttime();
	
	// This returns a boolean that specifies whether the sid will
	// be placed into the URL of all internal links
	public function urlsession();
	
	// This returns the form input name to be used to identify
	// a session id.  Something like "sid", which would translate
	// to "sid=RANDOMSTRING" in the URL.
	public function sidname();
	
	// This returns the session id, a securely random string
	public function sid();
}
?>