<?php
/**
 * @package htmlform
 * @author Nick Wing
 * @version 0.3
 */

/**
 * HTML Form
 *
 * This represents an HTML form, it will automagically set
 * itself to accept file uploads when appropriate.
 * @package htmlform
 */
class form extends container {
	private $method;
	private $enctype;
	private $target;
	private $newwindow = FALSE;
	private $preventdupes;
	private $name;
	public $preload = TRUE;
	public $preloadspecified = FALSE;
	private $errors = array();

	/**
	 * Construct a form object
	 *
	 * Fairly standard constructor.  $target is the URL to which the form
	 * should be posted.  It is equivalent to the HTML "action" attribute.
	 * It will default to PHP_SELF.
	 *
	 * You should specify a name when you need it as a javascript
	 * identifier or when you have more than one form on a page. If you have a
	 * small form in the top corner of your page for logging in, you'll have more
	 * than one form very frequently.
	 *
	 * @param container $parent
	 * @param string $name
	 * @param string $class
	 * @param string $target
	 * @param string $method
	 */
	function __construct($parent = "", $name = "", $class = "", $target = "", $method = "post") {
		parent::__construct($parent, '', $class);
		if ($method) $this->method = $method;
		else $this->method = "post";
		$this->settarget($target);
		if ($name) $this->setname($name);
	}

	/**
	 * Return form's name
	 *
	 * @return string
	 */
	public function getname() { return $this->name; }

	/**
	 * Set form's name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setname($name = "") {
		$this->name = $name;
	}

	/**
	 * Retrieve secret key for this installation
	 *
	 * This key is used to authenticate error-checking scheme generation.
	 * By default your key will be stored in the /tmp directory.  If there is
	 * is no key there, it will generate one and store it there.  If there are
	 * insufficient permissions to write there, it will give up and revert
	 * to a hard-coded key.  This will expose you to dedicated hackers who
	 * have access to your source code.
	 *
	 * You can configure the location of the secret key file by setting the
	 * global $cfg['secret_key_directory'].
	 *
	 * I wouldn't recommend using this key for password hashing salts or
	 * anything like that, because if the file gets wiped for any reason,
	 * you'd lose all your salted passwords.  For the purpose of error-check
	 * signing, the key only needs to last long enough for a user to complete
	 * a form. If the key is regenerated, the only risk is that a few
	 * users will have to submit their form twice.
	 *
	 * @return string
	 */
	public static function get_secret_key() {
		static $secretkey;
		if ($secretkey) return $secretkey;

		// do we have a configured key directory?
		global $cfg;
		$keydir[0] = $cfg['secret_key_directory'];

		// if the configuration global is not set or is bogus, we'll
		// try the directory where php is saving session data
		$keydir[1] = session_save_path();
		// there could be an argument on the front of this, separated by
		// a semicolon
		if (strpos ($keydir[1], ";") !== FALSE)
  		$keydir[1] = substr ($keydir[1], strpos ($keydir[1], ";")+1);

		// if the php session save path is bogus, we'll try to acquire the
		// proper temp directory
		if (function_exists('sys_get_temp_dir'))
			$keydir[2] = sys_get_temp_dir();
		else $keydir[2] = '/tmp';

		// try writing to each location and use the first one
		// that succeeds
		$level = error_reporting(0);
		foreach ($keydir as $dir) {
			if ($dir) {
				$file = endslash($dir).'checkformpwd.txt';
				if (!file_exists($file) || !trim(file_get_contents($file))) {
					file_put_contents($file, generatestring(15));
				}
				if ($secretkey = trim(file_get_contents($file))) break;
			}
		}
		error_reporting($level);

		// if we still haven't been able to write a file, we'll have to
		// use a hard-coded key and expose them to an (obscure) attack
		if ($secretkey) return $secretkey;
		return 'ahUw7M9Jz3Plv42';
	}

	/**
	 * Hash the error checking authentication string
	 *
	 * Hashing methods evolve and improve, this function may eventually
	 * shift to a new algorithm.
	 *
	 * @return string
	 * @access private
	 */
	public static function apply_hashing($str) {
		return md5($str);
	}

	/**
	 * @access private
	 */
	public static function apply_sorting($str) {
		$pairs = explode('-', $str);
		sort($pairs);
		return implode('-',$pairs);
	}

	/**
	 * Check form for errors
	 *
	 * This is a piece of the automatic error checking system.  Most PHP
	 * programmers who put the form and the form processing on the same
	 * page will want to replace <pre>if ($_REQUEST['submit']) {</pre> with
	 * <pre>if (form::check_error()) {</pre>
	 *
	 * The form will automatically report its own errors and preload
	 * its values as it is printed, so handling form errors
	 * is very simple with this method.
	 *
	 * If you have multiple forms on a page, specify $formname. For example:
	 *
	 * <pre>if (check_error('myform')) {
	 *     // process the form
	 * } else {
	 *     $form = new form($doc, 'myform');
	 *     // generate the rest of the form
	 * }</pre>
	 *
	 * The $dupe_timer parameter can be used to prevent duplicate form submissions
	 * that occur close together.  Specify a number of seconds, and check_error() will simply
	 * return FALSE for any repeated form submissions that occur within that many seconds from
	 * that session (i.e. this presents no concurrency issues).
	 *
	 * @param string $formname
	 * @param int $dupe_timer
	 * @return bool
	 */
	public static function check_error($formname='', $dupe_timer = 0) {
		if ((!$_REQUEST['pwo_submit'] && !$_REQUEST['pwo_submit_x'] && !$_REQUEST['pwo_submit_y']) || (($_REQUEST['whichform'] != $formname) && $formname)) return FALSE;

		$secretkey = form::get_secret_key();
		// let's make sure this is an authentic error checking profile
		foreach ($_REQUEST as $key => $valu) {
			if (preg_match('/^check_/', $key)) {
				if (is_array($valu)) $vals = $valu;
				else $vals = array($valu);
				foreach ($vals as $val) {
					$checkboth .= $key.':'.$val.'-';
				}
			}
		}
		$checkboth = form::apply_sorting($checkboth);
		$checkstr = $checkboth.$formname.$secretkey;
		$hashkey = form::apply_hashing($checkstr);
		if ($hashkey != $_REQUEST['errorcheck_auth']) return FALSE;

		// start session so we can get access
		if ($dupe_timer && doc::start_php_session()) {
			// check to see whether this is a dupe submission
			$old = $_SESSION[$hashkey];
			if ($old && microtime(TRUE) - $old < $dupe_timer) {
				return FALSE;  // dupe
			}
			// register the hashkey in the session
			$_SESSION[$hashkey] = microtime(TRUE);
		}

		$arr = (array) $_REQUEST['check_required'];
		foreach($arr as $req) {
			if (!$_REQUEST[$req] && !is_array($_FILES[$req]) && !$_REQUEST['check_disabled_'.$req]) { return FALSE; }
		}
		$arr = (array) $_REQUEST['check_empty'];
		foreach($arr as $req) {
			if ($_REQUEST[$req] || is_array($_FILES[$req])) { return FALSE; }
		}
		$arr = (array) $_REQUEST['check_greater'];
		foreach($arr as $greater) {
			list ($me, $them) = explode('|', $greater);
			if ($_REQUEST[$me] <= $_REQUEST[$them]) return FALSE;
		}
		$arr = (array) $_REQUEST['check_ifmethen'];
		foreach($arr as $ifmethen) {
			list ($me, $then) = explode('|', $ifmethen);
			if ($_REQUEST[$me] && !$_REQUEST[$then]) return FALSE;
		}
		$arr = (array) $_REQUEST['check_ifnotmethen'];
		foreach($arr as $ifmethen) {
			list ($me, $then) = explode('|', $ifmethen);
			if (!$_REQUEST[$me] && !$_REQUEST[$then]) return FALSE;
		}
		$arr = (array) $_REQUEST['check_length'];
		foreach($arr as $lendef) {
			list ($name, $len) = explode('|', $lendef);
			if (strlen($_REQUEST[$name]) > $len) return FALSE;
		}
		$arr = (array) $_REQUEST['check_unique'];
		global $cfg;
		foreach($arr as $undef) {
			list ($name, $code) = explode('|', $undef);
			if ($cfg['unique_check'][$code][consolidate($_REQUEST[$name])] && $_REQUEST[$name]) return FALSE;
			else $cfg['unique_check'][$code][consolidate($_REQUEST[$name])] = TRUE;
		}
		$arr = (array) $_REQUEST['check_filesize'];
		foreach($arr as $lendef) {
			list ($name, $len) = explode('|', $lendef);
			if ($_FILES[$name]['size'] > $len*1024) return FALSE;
		}
		$arr = (array) $_REQUEST['check_tinytext'];
		foreach($arr as $tinytext) {
			if (!form::validate_tinytext($_REQUEST[$tinytext])) return FALSE;
		}
		$arr = (array) $_REQUEST['check_integer'];
		foreach($arr as $int) {
			if ($_REQUEST[$int] != '' && (!is_numeric($_REQUEST[$int]) || $_REQUEST[$int] != intval($_REQUEST[$int]))) return FALSE;
		}
		$arr = (array) $_REQUEST['check_numeric'];
		foreach($arr as $num) {
			if ($_REQUEST[$int] != '' && !is_numeric($_REQUEST[$num])) return FALSE;
		}
		$arr = (array) $_REQUEST['check_min'];
		foreach($arr as $mindef) {
			list ($name, $min) = explode('|', $mindef);
			if ($_REQUEST[$name] != '' && $_REQUEST[$name] < $min) return FALSE;
		}
		$arr = $_REQUEST['check_max'];
		if (!is_array($arr)) $arr = array();
		foreach($arr as $maxdef) {
			list ($name, $max) = explode('|', $maxdef);
			if ($_REQUEST[$name] > $max) return FALSE;
		}
		$arr = (array) $_REQUEST['check_key'];
		foreach($arr as $key) {
			if (!form::validate_key($_REQUEST[$key])) return FALSE;
		}
		$arr = (array) $_REQUEST['check_date'];
		foreach($arr as $key) {
			if ($_REQUEST[$key] && !strtotime($_REQUEST[$key]) && $_REQUEST[$key]) return FALSE;
		}
		$arr = (array) $_REQUEST['check_email'];
		foreach($arr as $email) {
			if (!form::validate_email($_REQUEST[$email]) && $_REQUEST[$email]) return FALSE;
		}

		// check_match()
		$arr = (array) $_REQUEST['check_match'];
		foreach($arr as $entry) {
			list ($name, $othername) = explode('|', $entry);
			if ($_REQUEST[$othername] != $_REQUEST[$name]) return FALSE;
		}

		// check_regex()
		$arr = (array) $_REQUEST['check_regex'];
		foreach($arr as $entry) {
			list ($name, $regex) = explode('|', $entry);
			if (!preg_match($regex, $_REQUEST[$name])) return FALSE;
		}

		$arr = (array) $_REQUEST['check_callback'];
		foreach ($arr as $cbdef) {
			$info = explode('|', $cbdef);
			$name = $info[0];
			$cb=$info[1];
			if (!is_array($_FILES[$name])) $val = $_REQUEST[$name];
			else $val = $name;
			array_splice($info, 0, 2, array($val));
			if (strstr($cb, '::')) $cb = explode('::', $cb);
			$error = call_user_func_array($cb, $info);
			if ($error) return FALSE;
		}

		return TRUE;
	}

	/**
	 * This is for form elements to use when they want an error message to be shown
	 * at the top of the form.
	 * @access private
	 */
	public function register_error($errors) {
		if (!is_array($errors)) $errors = array($errors);
		foreach ($errors as $error) $this->errors[] = $error;
	}

	/**
	 * Add data to a form as hidden variables
	 *
	 * This method just provides a quick way to dump an associative array
	 * into a form.
	 *
	 * If you supply a $prefix, it will be prepended onto all keys in the
	 * array.
	 *
	 * @param array $vars
	 * @param string $prefix
	 */
	public function add_data($vars, $prefix = '') {
		if (!is_array($vars)) return;
		foreach ($vars as $key => $val) {
			new hidden($this, $prefix.$key, $val);
		}
	}

	/**
	 * Mimic current form data
	 *
	 * Use this method to ensure that all data from the previous stage of
	 * a multi-page form is completely retained.  It will just look at all
	 * the data that came in on this load and push it right back into the
	 * form as hidden inputs.
	 *
	 * If you supply a $prefix, it will be prepended to the keys of all data
	 * mimicked in this fashion.
	 *
	 * @param string $prefix
	 */
	public function mimic_data($prefix = '') {
		$this->add_data(doc::create_mimic(), $prefix);
	}

	/**
	 * Retain redirection info
	 *
	 * Use this on a form when you are eliciting information from the user, but
	 * they will eventually be returned to where they were before they initiated
	 * this action.  For example, while registering for an account,
	 * you could keep passing this information along so that when they are finally
	 * done creating their account, they can be returned to the spot that
	 * made them want to register in the first place.
	 *
	 * See {@link redirect_create()} for usage details.
	 */
	public function redirect_persist() {
		$this->add_data(self::redirect_persist_vars());
	}

	public static function redirect_persist_vars() {
		$vars = array();
		foreach(doc::create_mimic() as $key => $val) {
			if (strcont($key, 'redirection_') || $key == 'redirectionscript') $vars[$key] = $val;
		}
		return $vars;
	}

	/**
	 * Save information about the current page
	 *
	 * Use this method to create a record of this page so that you can
	 * return to it later, typically after a login page.
	 *
	 * <pre>// in your site header area (displays on every page)
	 * $vars = form::redirect_create();
	 * new link($div, 'login.php', 'Login', '', $vars);
	 *
	 * // inside login.php, during elicitation
	 * $form->redirect_persist();
	 *
	 * // inside login.php, after their password clears
	 * $info = form::redirect_undo();
	 * $doc->addText('Login successful, returning you to where you were.');
	 * $doc->refresh(1, $info['href'], $info['vars']);</pre>
	 *
	 * @static
	 * @return array
	 */
	public static function redirect_create() {
		foreach(doc::create_mimic() as $key => $val) {
			$ret['redirection_'.$key] = $val;
		}
		$ret['redirectionscript'] = $_SERVER['PHP_SELF'];
		return $ret;
	}

	/**
	 * Retrieve redirection information
	 *
	 * Use this method when a user has successfully completed an action and needs
	 * to be returned to what he was doing before.
	 *
	 * Must be used in conjunction with redirect_create() and redirect_persist()
	 *
	 * See {@link redirect_create()} for a usage example.
	 *
	 * @static
	 */
	public static function redirect_undo() {
		foreach(doc::create_mimic() as $key => $val) {
			if (strcont($key, 'redirection_')) $ret[substr($key, 12)] = $val;
		}
		$href = $_REQUEST['redirectionscript'];
		return array('href'=>$href, 'vars'=>$ret);
	}

	/**
	 * deprecated in favor of the redirect_*() methods
	 * @access private
	 */
	public function mimic($mimic=array()) {
		$this->mimic_data('redirection_');
		new hidden($this, 'redirectionscript', $_REQUEST['redirectionscript']);
	}

	/**
	 * @access private
	 */
	public static function validate_email($email) {
		if (!preg_match('/^\S+@\S+\.\S+$/', $email)) return FALSE;
		if (preg_match('/[\n\r]/', $email)) return FALSE;
		return TRUE;
	}
	/**
	 * @access private
	 */
	public static function validate_tinytext($val) {
		return !preg_match('/[^\w\-\.?!@#$%\^&*()"\':;\[\]\{\}=+_<>\/~` ],/', $val);
	}
	/**
	 * @access private
	 */
	public static function validate_key($val) {
		return !preg_match('/[^\w\-]/', $val);
	}

	/**
	 * Set the URL to which to send form data
	 *
	 * This corresponds to the "action" HTML attribute.  Defaults
	 * to PHP_SELF.
	 *
	 * @param string $target
	 */
	public function settarget($target) {
		if ($target) {
			if (substr($target, 0, 1) == '!') {
				global $cfg;
				$target = endslash($cfg['link_root']).substr($target, 1);
			}
			$this->target = $target;
		} else {
			$this->target = $_SERVER['PHP_SELF'];
		}
	}

	/**
	 * Choose GET or POST
	 *
	 * Most forms will default to POST, but you can use this method to set it
	 * to GET.
	 *
	 * @param string $method
	 */
	public function setmethod($method) {
		$this->method = $method;
	}

	/**
	 * Open form result in new window
	 *
	 * This will throw a target="_blank" into the form so that it opens
	 * a new window when the user submits.
	 *
	 * @return void
	 */
	public function newwindow() {
		$this->newwindow = TRUE;
	}

	/**
	 * Use Javascript to prevent multiple submissions
	 *
	 * This method will cause the form to write itself some javascript that prevents
	 * users from accidentally submitting duplicate forms by double-clicking or other such
	 * nonsense.
	 *
	 * @param bool $flag
	 * @return void
	 */
	public function prevent_dupes($flag = TRUE) {
		$this->preventdupes = $flag;
	}

	/**
	 * Generate recursive Javascript-powered select boxes.
	 *
	 * This generates a number of select boxes that are linked by
	 * javascript to create a deep menu.  When you choose a value
	 * from the first box, the second box gets a whole new set of
	 * options.  When you choose something from the second box, the
	 * third box is loaded, etc.
	 *
	 * This function will generate as many boxes as you provide data
	 * for, but keep in mind that the amount of data involved with such
	 * recursion could grow very large and translate to a lot of
	 * transfer time for the client.
	 *
	 * Just pass a big array full of data, and then form names for all the
	 * select boxes.
	 *
	 * If you use the database framework that complements this
	 * HTML library, you can use {@link db::getmenudata()} to easily
	 * generate appropriately formatted data from an SQL statement.
	 *
	 * It will then return an array of select boxes.  These boxes are NOT
	 * part of the document yet!
	 *
	 * This is so that you can format your page however you choose.
	 * You should use addChild() or setparent() to insert the boxes when you
	 * are ready for them.
	 *
	 * <pre>// Get data for the select boxes so user can choose settings
	 * $data = $db->getmenudata("SELECT DISTINCT server, script, mode FROM ".
	 *                          "systemtiming ORDER BY server");
	 * // Generate the select boxes
	 * list ($server, $script, $mode) = $form->getlinkedselects($data, "server", "script", "mode");
	 * // Put the select boxes in the document
	 * $form->addChild($server);
	 * $form->addChild($script);
	 * $form->addChild($mode);</pre>
	 *
	 * If you want the select boxes to have different name and value parameters,
	 * you need to pass data with a '%|%' separator.  For example,
	 * <pre>$data['lv1%|%levelone']['lv2%|%leveltwo'] = 'lv3%|%levelthree';
	 * Produces options like:
	 * &lt;option value="lv1"&gt;levelone&lt;/option&gt;</pre>
	 *
	 * @return array
	 * @param array $data
	 * @param string $name1
	 * @param string $name2
	 * @param [string $name3...]
	 */
	public function getlinkedselects($data) {
		$doc = doc::getdoc();
		$args = func_get_args();
		$depth = array_depth($data);
		if (!$this->getname()) $this->setname("recurseform");
		$formname = $this->getname();
		//if (count($args) != $depth + 1) return array();

		static $start = 0;

		if ($start == 0) $java = "var names=new Array(); var fdepth=new Array();";
		foreach ($args as $i => $arg) {
			if ($i > 0) {
				$selects[$i-1] = new select(0,$arg);
				$java .= "names[".($i-1+$start)."]='$arg';";
				if ($i > 1) {
					$java .= "fdepth[".($i-1+$start)."]=$start;";
					$selects[$i-2]->addJS("onchange", "reloadmenu(" . ($i-1+$start) . ", this.form);");
					$java .= "var $arg=new Array();"
							."var end".$arg."=new Array();"
							."var start".$arg."=new Array();";
				}
				$names[$i-1] = $arg;
			}
		}

		$selects[0]->addOption("", "------------");
		foreach (array_keys($data) as $firstval) {
			list ($value, $label) = explode('%|%', $firstval);
			$selects[0]->addOption($value, $label);
		}
		if ($_REQUEST[$names[0]]) $selects[0]->setSelected($_REQUEST[$names[0]]);

		for ($i = 1; $i < $depth; $i++) {
			$doc->addJS_afterload("reloadmenu(".($i+$start).", document.$formname);");
			$doc->addJS_afterload("document.$formname.".$names[$i].".value='" . addslashes($_REQUEST[$names[$i]]) . "';");
		}

		$java .= form::writelinkedjava($data, $names, 0, $tracki);
		$doc->addJS($java);
		$doc->includeJS("@reloadmenu.js");
		$start += count($args) - 1;
		return $selects;
	}

	/**
	 * Set form not to preload data
	 *
	 * You may use this to toggle off automatic preloading for an entire form.  Individual
	 * form elements can be toggled with their own methods, but this will make sure the
	 * whole form is not preloaded.
	 *
	 * @param bool $flag
	 */
	public function nopreload($flag = TRUE) {
		$this->preload = $flag ? FALSE : TRUE;
	}

	/**
	 * @access private
	 */
	public function preloading() {
		return ($this->preload);
	}

	/**
	 * Recursive helper function for getlinkedselects()
	 * @access private
	 */
	protected static function writelinkedjava($data, $names, $depth, &$tracki) {
		$i =& $tracki[$depth];
		if (!$i) $i = 0;
		$start = $i;
		if ($depth > 0) $javascript .= "start" . $names[$depth] . "[" . $tracki[$depth-1] . "]=" . $i . ";";
		foreach (array_keys($data) as $val) {
			if (is_array($data[$val])) {
				if ($depth > 0) $javascript .= $names[$depth] . "[$i]='" . addslashes($val) . "';";
				$javascript .= form::writelinkedjava($data[$val], $names, $depth + 1, $tracki);
			} else {
				if ($depth > 0) $javascript .= $names[$depth] . "[$i]='" . addslashes($data[$val]) . "';";
			}
			$i++;
		}
		if ($depth > 0) $javascript .= "end" . $names[$depth] . "[" . $tracki[$depth-1] . "]=" . $i . ";";
		return $javascript;
	}

	/**
	 * Make sure form can handle uploads
	 * @access private
	 */
	public function accept_upload() {
		$this->method = "post";
		$this->enctype = "multipart/form-data";
	}

	/**
	 * Is our target secure?
	 * @access private
	 */
	public function secure($user) {
		if (strtolower(substr($this->target, 0, 5)) == 'https') $secure = TRUE;
		if (strtolower(substr($this->target, 0, 5)) == 'http:') $secure = FALSE;
		if (doc::issecure()) $secure = TRUE;
		return $secure;
	}

	/**
	 * @access private
	 */
	public function finalize() {
		parent::finalize();
		// disable submit buttons if requested
		if ($this->preventdupes) {
			if (!$this->getid()) $this->setid(generatestring(4,'alpha'));
			$this->addJS('onsubmit', 'if (this.submitted==1) { return false; } else { this.submitted=1; return true; }');
			$doc = doc::getdoc();
			$doc->addJS_afterload('document.getElementById(\''.$this->getid().'\').submitted=0');
		}
	}

	/**
	 * @access private
	 */
	public function output($pspace = '', $optin=FALSE) {
		$user = doc::getuser();
		if (is_object($user)) {
			if ($user->urlsession()) {
				new hidden($this, $user->sidname(), $user->sid());
			}
			if ($this->secure($user)) {
				new hidden($this, $user->securename(), $user->securesid());
			}
		}
		if ($this->getname()) {
			$hidden = new hidden($this, 'whichform', $this->getname());
			$hidden->nopreload();
		}
		if ($this->enctype) {
			$enctype = ' enctype="' . $this->enctype . '"';
		}
		if (!empty($this->errors)) $errors = '<div class="formerrors">'.implode("<br/>", array_unique($this->errors)).'</div>';
		if ($this->newwindow) $target = ' target="_blank"';
		$children = container::output($pspace, $optin);

		// create the authentication key
		global $cfg;
		$secretkey = form::get_secret_key();
		$checkboth = form::apply_sorting($cfg['error_check_profile'][$this->getname()]);
		$checkstr = $checkboth.$this->getname().$secretkey;
		$hashkey = form::apply_hashing($checkstr);
		$authipt = "\n".$pspace.'<input type="hidden" name="errorcheck_auth" value="'.$hashkey.'" />';

		return '<form' . element::output() . $target . ' action="' . $this->target . '" method="' . strtolower($this->method) . '"' . $enctype . '>'. $authipt . $errors . $children .'</form>';
	}
}

/**
 * Uploaded File
 *
 * This class contains information about, and is able to manipulate
 * uploaded files.
 *
 * @package htmlform
 */
class file {
	private $info;

	/**
	 * Constructor
	 *
	 * The $name parameter will be passed to $this->setname()
	 *
	 * @param string $name
	 */
	function __construct($name) {
		$this->setname($name);
	}

	/**
	 * Set form element name under which this file was uploaded
	 *
	 * This class supports two types of form elements.  A filebrowser element
	 * is the traditional way to upload a file.
	 *
	 * However, you could also use a textbox to elicit a URL and this class
	 * will go grab the data from the internet and treat it like a file
	 * upload.
	 *
	 * @param $name
	 * @return void
	 */
	public function setname($name) {
		$this->info['filename'] = basename($_FILES[$name]['name']);
		$level = error_reporting();
		if (!$_FILES[$name] && parse_url($_REQUEST[$name]))
			$stream = $_REQUEST[$name];
		else
			$stream = $_FILES[$name]['tmp_name'];
		if ($stream) {
			if (function_exists('getimagesize'))
				$this->info['imgdata'] = getimagesize($stream);
			$this->info['data'] = file_get_contents($stream);
		}
		error_reporting($level);
	}

	/**
	 * Get the filename of the file
	 *
	 * If you've made a call to unique_in_directory() or unique_in_array(), this
	 * filename will have been modified from the original.
	 *
	 * @return string
	 */
	public function filename() {
		return $this->info['filename'];
	}

	/**
	 * Get the file data
	 *
	 * @return binary
	 */
	public function data() {
		return $this->info['data'];
	}

	/**
	 * Get the file's type extension
	 *
	 * If the file is an image supported by PHP's getimagesize(), this returns
	 * the file extension one would expect for a file of the type returned.
	 *
	 * Otherwise, it just parses the extension out of the filename and returns that.
	 *
	 * @return string
	 */
	public function file_type() {
		$type = $this->info['imgdata'][2];
		switch ($type) {
			case IMAGETYPE_GIF: return 'gif';
			case IMAGETYPE_JPEG: return 'jpg';
			case IMAGETYPE_PNG: return 'png';
			case IMAGETYPE_SWF: return 'swf';
			case IMAGETYPE_PSD: return 'psd';
			case IMAGETYPE_BMP: return 'bmp';
			case IMAGETYPE_TIFF_II: return 'tiff';
			case IMAGETYPE_TIFF_MM: return 'tiff';
			case IMAGETYPE_JPC: return 'jpc';
			case IMAGETYPE_JP2: return 'jp2';
			case IMAGETYPE_JPX: return 'jp2';
			case IMAGETYPE_JB2: return 'jb2';
			case IMAGETYPE_SWC: return 'swc';
			case IMAGETYPE_IFF: return 'iff';
			case IMAGETYPE_WBMP: return 'wbmp';
			case IMAGETYPE_XBM: return 'xbm';
			default: return file_extension($this->filename());
		}
	}

	public function mime_type() {
		if ($this->info['imgdata']) return $this->info['imgdata']['mime'];
		else return 'application/octet-stream';
	}

	public function height() {
		return $this->info['imgdata'][1];
	}

	public function width() {
		return $this->info['imgdata'][0];
	}

	public function size() {
		return sprintf("%.2f", strlen($this->data()) / 1024);
	}

	/**
	 * Detect a manipulable image
	 *
	 * Returns true if the gd library can load the file data as
	 * an image.
	 *
	 * @return bool
	 */
	public function is_image() {
		return (bool) $this->gd_resource();
	}

	/**
	 * Get a gd resource object for the file, if applicable
	 *
	 * This is primarily used internally to avoid needlessly creating multiple
	 * resources with gd.  But you could use it if you wanted to do some gd
	 * manipulation.
	 *
	 * @return resource
	 */
	public function gd_resource() {
		if (!function_exists('imagecreatefromstring')) return false;
		if (!$this->info['gd_resource_tried']) {
			$this->info['gd_resource_tried'] = TRUE;
			$level = error_reporting(0);
			$this->info['gd_resource'] = imagecreatefromstring($this->data());
			error_reporting($level);
		}
		return $this->info['gd_resource'];
	}

	/**
	 * Get a resized JPEG version of the file
	 *
	 * Only works with gd installed and if the file is a validly formatted
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
	 * @param int $max_width
	 * @param int $max_height
	 * @param int $quality
	 * @return string
	 */
	public function resized_data($max_width = 120, $max_height = 160, $quality = 60) {
		return resize_image($this->gd_resource(), $max_width, $max_height, $quality);
	}

	/**
	 * Ensure filename is unique in a particular directory
	 *
	 * This will alter the filename of this object until it no longer matches
	 * anything in the given directory.  This will prevent naming conflicts.
	 *
	 * If you try to write() without first calling this method, files can be
	 * accidentally overwritten!
	 *
	 * $dir should be relative to the current script, or if $cfg['server_path'] is set,
	 * relative to that.
	 *
	 * @param string $dir
	 * @return void
	 */
	public function unique_in_directory($dir) {
		$dir = self::clean_serverpath($dir);
		$fname = preg_replace('/[^\w\-\.]/', '', $this->filename());
		while (file_exists($dir.'/'.$fname)) {
			preg_match('/(.*?)(-(\d+))?(\.[^\.]*)$/', $fname, $match);
			$fname = $match[1] . '-' . ($match[3]+1) . $match[4];
		}
		$this->info['filename'] = $fname;
	}

	/**
	 * Ensure filename is unique within given array
	 *
	 * This will alter the filename of this object until it no longer matches
	 * anything in the given array.  This will prevent naming conflicts.
	 *
	 * @param array $names
	 * @return void
	 */
	public function unique_in_array($names = array()) {
		if (!is_array($names)) {
			if ($names) $names = array($names);
			else return;
		}
		$fname = preg_replace('/[^\w\-\.]/', '', $this->filename());
		while (in_array($fname, $names)) {
			preg_match('/(.*?)(-(\d+))?(\.[^\.]*)$/', $fname, $match);
			$fname = $match[1] . '-' . ($match[3]+1) . $match[4];
		}
		$this->info['filename'] = $fname;
	}

	/**
	 * Write file data to disk
	 *
	 * When you're ready to save a file upload, use this to write it.
	 *
	 * $dir should be relative to the current script, or if $cfg['server_path'] is set,
	 * relative to that.
	 *
	 * You may want to call safe_file() before this, or you could be opening yourself to attack.
	 *
	 * @param string $dir
	 * @return bool
	 */
	public function write($dir) {
		$loc = self::clean_serverpath($dir).'/'.$this->filename();
		$ret = file_put_contents($loc, $this->data());
		chmod($loc, 0644);
		return $ret;
	}

	/**
	 * @access private
	 */
	public static function clean_serverpath($dir) {
		global $cfg;
		if ($cfg['server_path']) $dir = $cfg['server_path'] . '/' . $dir;
		if (substr($dir, -1) == '/') $dir = substr($dir, 0, -1);
		return $dir;
	}

	/**
	 * Is this filename safe for a web-accessible directory?
	 *
	 * Anything apache will recognize as executable is unsafe to place in a web
	 * accessible directory.  There may be special server conventions that are dangerous
	 * too, such as a user uploading an .htaccess file
	 *
	 * NOTE: This method should provide pretty good protection by default, but will
	 * only recognize the extensions of a few languages.  You should add your own
	 * extensions to the list by adding them to global $cfg['file_unsafe'];
	 *
	 * @return bool
	 */
	public function safe_file() {
		$name = $this->filename();
		$ext = file_extension($name);
		global $cfg;
		if (!$cfg['file_unsafe']) $cfg['file_unsafe'] = array();
		return !in_array($ext, array_merge($cfg['file_unsafe'], array(
			'php','phtml','cgi','pl','asp', 'aspx','cfm', 'jsp', 'jhtml', 'php3', 'php4', 'php5', 'htaccess'
		)));
	}
}

/**
 * Field Set
 *
 * This is a way of grouping form elements together.  You can
 * use CSS to make it nifty looking.
 *
 * @package htmlform
 */
class fieldset extends container {
	private $legend;

	function __construct($parent = '', $legend = '', $class = '', $id = '') {
		if ($legend) $this->setlegend($legend);
		element::__construct($parent, $id, $class);
	}

	/**
	 * Set Legend
	 *
	 * Fieldsets have a property called a legend that labels
	 * a group of form elements.  Use this method to set that label.
	 *
	 * @param string $legend
	 * @return void
	 */
	public function setlegend($legend) {
		$this->legend = trim(strip_tags($legend));
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		if ($this->legend) $legend = '<legend>'.htmlspecialchars($this->legend).'</legend>';
		return '<fieldset'.element::output().'>'.$legend.container::output($pspace, $optin).'</fieldset>';
	}
}

/**
 * POST button
 *
 * This is a complete form in one element.  It is for creating buttons that seem like links, but
 * in reality are POST style form submissions.  This helps get around the "state change on GET"
 * design issue in cases where you really want a single-click database update.
 *
 * Using this object instead of a simple link will help protect you against spiders and web
 * accelerators that explore every link on a page.  You wouldn't want those automatically deleting
 * things, adding products to carts, or making any database changes at all.
 *
 * @param container $parent
 * @param string $imagesrc
 * @param string $formname
 * @param array $vars
 * @param string $formaction
 * @param int $width
 * @param int $height
 * @param string $alt
 * @param string $class
 */
class post_button extends element {
	public function __construct($parent = "", $imagesrc = "", $formname = "", $vars = array(), $formaction = "", $width = "", $height = "", $alt = "", $class = "") {
		$form = new form($parent, $formname, '', $formaction, 'post');
		foreach ($vars as $key => $val) new hidden($form, $key, $val);
		new imgsubmit($form, $imagesrc, '', $width, $height, $alt, $class);
	}
}

/**
 * Textbox Form Element
 *
 * This is a small box that accepts text.
 *
 * @package htmlform
 */
class textbox extends formelement {
	private $size;
	private $value;
	private $password;
	private $cleardefault;
	private $clearvalue;
	private $search;

	/**
	 * Construct a text box
	 *
	 * Pretty standard constructor for form input of type "text".  Note that this class
	 * can be toggled into a password box.
	 *
	 * @param container $parent
	 * @param string $name
	 * @param string $value
	 * @param int $size
	 * @param string $class
	 * @param bool $password
	 * @return textbox
	 */
	function __construct($parent = "", $name = "", $value = "", $size = "", $class = "", $password = "") {
		if (strlen($value)) $this->setvalue($value);
		if ($size) $this->setsize($size);
		if ($password) $this->password(TRUE);
		formelement::__construct($parent, $name, $class);
	}

	/**
	 * Set display value
	 *
	 * Set a default value.  Won't work for password boxes.
	 *
	 * Please note that if preloading is turned on, the preload
	 * value will override this one unless you turn preloading
	 * off with the {@link formelement::nopreload()} method.
	 *
	 * @param string $value
	 * @return void
	 */
	public function setvalue($value) {
		$this->value = $value;
	}

	/**
	 * Set textbox width
	 *
	 * Set width in characters.  This will not limit input.
	 * Use {@link formelement::check_length()} for that.
	 *
	 * @param int $size
	 */
	public function setsize($size) {
		$this->size = intval($size);
	}

	/**
	 * Make this a password box
	 *
	 * Toggle whether or not this is a password box.  If it is a password
	 * box, it will show the user **** instead of the letters they type.
	 *
	 * @param bool $flag
	 */
	public function password($flag = TRUE) {
		$this->password = $flag ? TRUE : FALSE;
	}

	/**
	 * Use javascript to empty textbox when clicked
	 *
	 * Only gets rid of the default value, or you can specify a value
	 * to be cleared.  This will happen 'onfocus', so tabbing to the field
	 * can also trigger it.
	 *
	 * @param string $def_value
	 * @return void
	 */
	public function cleardefault($def_value = '') {
		$this->cleardefault = TRUE;
		$this->clearvalue = $def_value;
	}

	/**
	 * Set up an autocomplete javascript for this textbox
	 *
	 * Use this method to add autocomplete functionality to, usually, a search
	 * box.  It will send a query to the specified location, using a local relay if necessary
	 * to work around XMLHttpRequest's security.
	 *
	 * $query is the fully specified URL that returns the results, somewhere in $query should be
	 * the string CURRENTVAL which will be replaced by the value of the textbox.
	 *
	 * $handler is a javascript function responsible for parsing the server's response into
	 * rows.  Its one parameter will be the responseText or responseJSON from the XMLHttpRequest.<br>
	 * The handler should be a string that looks something like:
	 * <pre>"function (response) {
	 *   for (row in response) {
	 *     ret.push({value: response[row].newvalue, display: response[row].suggestion});
	 *   }
	 *   return ret;
	 * }"</pre>
	 * The handler function is optional and by default will assume that the server's response is
	 * already organized into rows (either a JSON array or line by line).
	 *
	 * $json is a boolean that forces the server's response to be treated as JSON, regardless of
	 * the mime-type returned by the server.  Even when false, JSON may be automatically detected,
	 * parsed, and the object passed to the handler.
	 *
	 * $callback is an optional javascript function that takes a row chosen by the user and updates
	 * the state of the text box.  By default it assumes that the entire entry should appear in the
	 * textbox (or, if the row is an object, the 'value' property, or if an array, the first element
	 * in it).
	 */
	public function autocomplete($query, $handler = '', $json = false, $callback = '') {
		$this->search[] = array('q' => $query, 'h' => $handler, 'json' => $json);
		if ($callback) $this->search_cback = $callback;
	}

	public function finalize() {
		if (empty($this->search)) {
			parent::finalize();
			return;
		}
		$doc = doc::getdoc();
		static $finalized = false;
		if (!$finalized) {
			$doc->includeJS('@prototype.js');
			$doc->includeJS('@autocomplete.js');
			$finalized = true;
		}
		$this->ensureid();
		parent::finalize();
		$doc->addJS("var ac = new autocomplete('".$this->getid()."', {xhrrelay: '".link::buildquery('@xhrrelay.php', array('url'=>'SOURCEURL'))."'});");
		foreach ($this->search as $s) {
			$doc->addJS("ac.addsource('".addslashes($s['q'])."', ".$s['h'].", ".($s['json'] ? 'true' : 'false').");");
		}
		if ($this->search_cback) $doc->addJS("ac.setcallback(".$this->search_cback.");");
	}

	/**
	 * Generate Output
	 * @access private
	 */
	public function output() {
		$type = ' type="' . ($this->password ? "password" : "text") . '"';
		if ($this->cleardefault) {
			if (!$this->clearvalue) $this->clearvalue = $this->value;
			$this->addJS('onfocus', "if (this.value=='".addslashes($this->clearvalue)."') this.value=''");
		}
		if ($this->preload) {
			$val = $_REQUEST[$this->getname()];
			if (isset($_REQUEST[$this->getname()])) $this->value = $val;
		}
		if (strlen($this->value)) $value = ' value="' . htmlspecialchars(rtrim($this->value)) . '"';
		if ($this->size) $size = ' size="' . $this->size . '"';
		return $this->genlabel().'<input' . $type . formelement::output() . $size . $value . ' />';
	}
}

/**
 * Text Area Form Element
 *
 * This accepts a large amount of text.
 * @package htmlform
 */
class textarea extends formelement {
	private $rows;
	private $cols;
	private $contents;

	/**
	 * Text Area Constructor
	 *
	 * Note that height and width are character rows and columns, respectively.
	 *
	 * @param container $parent
	 * @param string $name
	 * @param int $width
	 * @param int $height
	 * @param string $contents
	 * @param string $class
	 */
	function __construct($parent = "", $name = "", $width = "", $height = "", $contents = "", $class = "") {
		if ($contents) $this->setcontents($contents);
		if ($width) $this->setwidth($width);
		if ($height) $this->setheight($height);
		formelement::__construct($parent, $name, $class);
	}

	/**
	 * Set width
	 *
	 * Number of text columns
	 *
	 * @param int $width
	 */
	public function setwidth($width) {
		$this->cols = intval($width);
	}

	/**
	 * Set height
	 *
	 * Number of text rows
	 *
	 * @param int $height
	 */
	public function setheight($height) {
		$this->rows = intval($height);
	}

	/**
	 * Set contents
	 *
	 * Give the textarea some initial content.
	 *
	 * @param string $contents
	 */
	public function setcontents($contents) {
		$this->contents = $contents;
	}

	/**
	 * @access private
	 */
	public function output() {
		if ($this->cols) $cols = ' cols="' . $this->cols . '"';
		if ($this->rows) $rows = ' rows="' . $this->rows . '"';
		if ($this->preload) {
			$content = $_REQUEST[$this->getname()];
			if (isset($_REQUEST[$this->getname()])) $this->contents = $content;
		}
		return $this->genlabel().'<textarea' . $cols . $rows . formelement::output() . '>' . htmlspecialchars($this->contents) . '</textarea>';
	}
}

/**
 * Selection Box Form Element
 *
 * This is a drop down selection box.  There are several functions for loading
 * the options.
 * @package htmlform
 */
class select extends formelement {

	public $container;
	public $optgrp;
	private $multiple = 0;
	private $autobrackets = TRUE;

	/**
	 * Selection Box Constructor
	 *
	 * Nothing special here.
	 * @param container $parent
	 * @param string $name
	 * @param string $class
	 */
	function __construct($parent = 0, $name = "", $class = "") {
		formelement::__construct($parent, $name, $class);
		$this->container = new container;
	}

	/**
	 * Add an Option
	 *
	 * Use this method to add an option to a box.  It will be selected
	 * by default if you set the last parameter to TRUE.  You can also
	 * set the default option with the {@link setSelected()} method.
	 *
	 * Please note that default selection will be overridden by preload
	 * data unless you use {@link formelement::nopreload()}.
	 * @param string $value
	 * @param string $label
	 * @param bool $selected
	 * @param string $class
	 */
	public function addOption($value = '', $label = "", $selected = FALSE, $class = '') {
		if (!$value && !$label) $label = '------';
		if ($this->optgrp instanceof container) $par = $this->optgrp;
		else $par = $this->container;
		new option($par, $value, $label, $selected, $class);
	}

	/**
	 * Start an OPTGROUP tag
	 *
	 * Use this method to begin a grouping of options with the optgroup tag, the label
	 * will be displayed at the top of each group.
	 *
	 * When you want to start a second grouping, just call this method again.
	 *
	 * @param string $label
	 * @return void
	 */
	public function addOptionGroup($label) {
		$this->optgrp = new optgroup($this->container, $label);
	}

	/**
	 * End an OPTGROUP tag
	 *
	 * Use this method to bring an optgroup tag to an end.  This is NOT
	 * necessary just before addOptionGroup().  This should only be used if you want
	 * to add options directly to the select box without being in any optgroup.
	 */
	public function endOptionGroup() {
		unset($this->optgrp);
	}

	/**
	 * Deprecated
	 * @access private
	 */
	public function removeOptionGroup() {
		unset($this->optgrp);
	}

	/**
	 * Add Options from a Database array
	 *
	 * This method assumes you are using an array of rows from a database, so
	 * it accepts two column names - to use as the 'value' and 'label' of each option
	 * it adds.
	 *
	 * If you only provide one column name, it will be used for both the value and the label.
	 *
	 * If you don't provide any column names, the 0 element of the array will be used for the value, and
	 * the 1 element will be used for the label.
	 *
	 * If you provide a single-dimensional array, each element will be an option, with the same value
	 * and label.
	 *
	 * <pre>$presidents = get_presidents_from_database();
	 * $sel = new select($parent, 'myselect');
	 * $sel->addOptionsData($president, 'presid', 'lastname');</pre>
	 *
	 * @param array $data
	 * @param string $valuekey
	 * @param string $labelkey
	 */
	public function addOptionsData($data, $valuekey = "", $labelkey = "") {
		if (!$valuekey && !$labelkey) {
			$valuekey = 0;
			$labelkey = 1;
		}
		if (!$labelkey) $labelkey = $valuekey;
		foreach ($data as $row) {
			if (is_array($row)) $this->addOption($row[$valuekey], $row[$labelkey]);
			else $this->addOption($row, $row); // we got a single dimension array
		}
	}

	/**
	 * Add Options from Data with inherent Hierarchy
	 *
	 * Frequently a select box will be used to choose from data that has some
	 * hierarchical organization to it.  Use this method to do that easily.
	 *
	 * It assumes you have data that looks something like in the following usage:
	 *
	 * <pre>$mydata = array(
	 *   array('id'=>1,'name'=>'coders', 'children'=>array(
	 *     array('id'=>2, 'name'=>'Perl coders'),
	 *     array('id'=>3, 'name'=>'PHP coders')
	 *   )),
	 *   array('id'=>4, 'name'=>'designers', 'children=>array(
	 *     array('id'=>5, 'name'=>'Web designers'),
	 *     array('id'=>6, 'name'=>'Print designers')
	 *     array('id'=>7, 'name'=>'iPhone designers'),
	 *     array('id'=>8, 'name'=>'Desktop designers')
	 *   ))
	 * );
	 *
	 * $sel->addOptionHierarchy($mydata, 'id', 'name', 'children', '    ');</pre>
	 *
	 * With that, your options should come out looking like:
	 *
	 * <pre>
	 * coders
	 *     Perl coders
	 *     PHP coders
	 * designers
	 *     Web designers
	 *     Print designers
	 *     iPhone designers
	 *     Desktop designers
	 * </pre>
	 *
	 * @param array $data
	 * @param string $valuekey
	 * @param string $labelkey
	 * @param string $childkey
	 * @param string $prefix
	 * @return void
	 */
	public function addOptionHierarchy($data, $valuekey = 0, $labelkey = 1, $childkey = 2, $prefix = '    ') {
		$this->addOptionHierarchyRecursive($data, $valuekey, $labelkey, $childkey, $prefix);
	}

	/**
	 * Recursive helper function for addOptionHierarchy
	 *
	 * @param array $data
	 * @param string $valuekey
	 * @param string $labelkey
	 * @param string $childkey
	 * @param string $prefix
	 * @param int $depth
	 * @return void
	 * @access private
	 */
	private function addOptionHierarchyRecursive($data, $valuekey, $labelkey, $childkey, $prefix, $depth = 0) {
		if (!is_array($data)) return;
		foreach ($data as $row) {
			$this->addOption($row[$valuekey], str_repeat($prefix, $depth).$row[$labelkey]);
			$this->addOptionHierarchyRecursive($row[$childkey], $valuekey, $labelkey, $childkey, $prefix, $depth+1);
		}
	}

	/**
	 * Set options as default selections
	 *
	 * Use this method to set options as selected by default, based
	 * on their value.  If you use this method multiple times it will
	 * overwrite any previously set defaults.  If you desire multiple default
	 * selections, you may pass it an array; however, the behavior is
	 * undefined if you set multiple defaults without using {@link multiple()}.
	 *
	 * Please note that default selection will be overridden by preload
	 * data unless you use {@link formelement::nopreload()}.
	 * @param string $value
	 * @return void
	 */
	public function setSelected($value = '') {
		if (is_array($value))
			foreach ($value as $val) $sel[$val] = TRUE;
		else $sel[$value] = TRUE;
		foreach ($this->container->children() as $child) {
			if ($child instanceof option) {
				if ($sel[$child->getvalue()]) $child->selected(TRUE);
				else $child->selected(FALSE);
			}
		}
	}

	public function setDisabled($value = '') {
		if (empty($value)) return;
		$values = (array) $value;
		foreach ($values as $v) {
			$hash[$v] = true;
		}
		foreach ($this->container->children() as $child) {
			if ($child instanceof option && $hash[$child->getvalue()]) $child->disabled();
		}
	}

	/**
	 * Turn select box into select multiple
	 *
	 * This will create the kind of selection box that you cntrl-click (or
	 * cmd-click for mac users) to select more than one option.  Pass it the
	 * height of the box you want.  For instance, height of 5 would mean the top
	 * 5 options are shown in the box without scrolling.
	 *
	 * @param int $size
	 * @return void
	 */
	public function multiple($size = 5) {
		$this->multiple = $size;
	}

	/**
	 * Toggle auto-bracket settings
	 *
	 * PHP requires that form elements with multiple values be
	 * named with "[]" at the end.  PHP will interpret this
	 * automatically as an array.
	 *
	 * @param bool $flag
	 * @return void
	 */
	public function hidebrackets($flag = TRUE) {
		$this->autobrackets = !$flag;
	}

	/**
	 * @access private
	 */
	public function output($pspace = '', $optin = FALSE) {
		$doc = doc::getdoc();
		if ($doc->prettycode()) $br = "\n";
		else $pspace = '';
		if (!count($this->container->children())) $this->addOption("", "----");
		if ($this->preload) {
			$vals = $_REQUEST[$this->getname()];
			if (isset($_REQUEST[$this->getname()])) $this->setSelected($vals);
		}
		if ($this->multiple > 1) {
			if ($this->autobrackets) $this->setname($this->getname() . "[]");
			$multi = ' multiple="multiple" size="'.$this->multiple.'"';
		}

		$return = $this->genlabel().$br.substr($pspace, 0, -4).'<select' . $multi . formelement::output() . '>'.$br;
		$return .= $this->container->output($pspace, $optin);
		$return .= substr($pspace, 0, -4).'</select>';

		return $return;
	}
}

/**
 * @access private
 * @package htmlsupport
 */
class optgroup extends container {
	private $label;
	function __construct($parent = 0, $label = '') {
		$this->setparent($parent);
		$this->label = $label;
	}
	public function output($pspace = '', $optin = FALSE) {
		return '<optgroup'.element::output().' label="'.htmlspecialchars($this->label).'">'.container::output().'</optgroup>';
	}
}

/**
 * @access private
 * @package htmlsupport
 */
class option extends element {
	private $value;
	private $label;
	private $selected;
	private $disabled;

	function __construct($parent = 0, $value = "", $label = "", $selected = FALSE, $class = '') {
		if (!$label) $label = $value;
		$this->setparent($parent);
		$this->value = $value;
		$this->label = new text(0, $label);
		$this->selected($selected);
		if ($class) $this->setclass($class);
	}

	/**
	 * Retrieve option value
	 *
	 * @return string
	 */
	public function getvalue() { return $this->value; }

	/**
	 * Set option value
	 *
	 * @param string $value
	 */
	public function setvalue($value = '') { $this->value = $value; }

	/**
	 * Set this option as the selected option
	 *
	 * @param bool $selected
	 * @return void
	 */
	public function selected ($selected = TRUE) {
		$this->selected = $selected ? TRUE : FALSE;
	}

	/**
	 * Set this option as disabled
	 *
	 * @param bool $disabled
	 * @return void
	 */
	public function disabled ($disabled = TRUE) {
	  $this->disabled = $disabled ? TRUE : FALSE;
	}

	/**
	 * @access private
	 */
	public function output() {
		if ($this->selected) $slctd = ' selected="selected"';
		if ($this->disabled) $dsbld = ' disabled="disabled"';
		return '<option'.element::output().' value="' . htmlspecialchars($this->value) . '"'.$slctd.$dsbld.'>' . $this->label->output() . '</option>';
	}
}

/**
 * File Upload Form Element
 *
 * This element allows a user to upload a file.  Simply creating one of these
 * will make all the necessary changes, such as switching the form's enctype.
 *
 * @package htmlform
 */
class filebrowser extends formelement {
	private $size;

	/**
	 * Consctructor
	 *
	 * @param container $parent
	 * @param string $name
	 * @param int $size
	 * @param string $class
	 * @return filebrowser
	 */
	function __construct($parent = 0, $name = "file", $size = "", $class = "") {
		$this->setparent($parent);
		if ($name) $this->setname($name);
		if ($size) $this->setsize($size);
		if ($class) $this->setclass($class);
	}

	/**
	 * Set size in number of characters
	 *
	 * @param int $size
	 * @return void
	 */
	public function setsize($size) {
		$this->size = $size;
	}

	/**
	 * @access private
	 */
	public function output() {
		return $this->genlabel().'<input type="file"' . formelement::output() . ' size="' . $this->size . '"/>';
	}
}

/**
 * Set of Radio Buttons
 *
 * Create a radio button.  The user is allowed to select only one radio
 * button with any given name, so if you make several of these and give
 * them the same name, only one value will make it to PHP.
 * @package htmlform
 */
class radio extends formelement {
	private $checked;
	private $value;
	private $default;

	/**
	 * Constructor
	 *
	 * @param container $parent
	 * @param string $name
	 * @param string $value
	 * @param bool $checked
	 * @param $class
	 * @return unknown_type
	 */
	function __construct($parent = '', $name = '', $value = '', $checked = FALSE, $class = '') {
		formelement::__construct($parent, $name, $class);
		$this->checked = $checked;
		$this->setvalue($value);
	}

	/**
	 * Set value to send through when this radio is selected
	 *
	 * @param string $value
	 * @return void
	 */
	public function setvalue($value) {
		$this->value = $value;
	}

	/**
	 * Make this the default selected radio button
	 *
	 * @return void
	 */
	public function setdefault() {
		$this->default = TRUE;
	}

	/**
	 * Get value that will be sent through if this radio is selected
	 *
	 * @return string
	 */
	public function getvalue() {
		return $this->value;
	}

	/**
	 * @access private
	 */
	public function finalize() {
		static $defaults;
		if ($this->default && !$defaults[$this->getname()]) {
			$defaults[$this->getname()] = TRUE;
			$this->checked = TRUE;
		}
		parent::finalize();
	}

	/**
	 * @access private
	 */
	public function output() {
		if ($this->preload) {
			$val = $_REQUEST[$this->getname()];
			if (isset($_REQUEST[$this->getname()])) {
				if (is_array($val)) $this->checked = FALSE;
				else {
					if ($val == $this->value) $this->checked = TRUE;
					else $this->checked = FALSE;
				}
			}
		}
		return '<input type="radio"' . formelement::output() .
			($this->checked ? ' checked="checked"' : '') .
			' value="'.htmlspecialchars($this->value).'">'.$this->genlabel();
	}
}

/**
 * Checkbox Button
 *
 * Create a checkbox.  The user is allowed to select as many checkboxes as
 * he likes.  All checkboxes with the same name will be passed to PHP as
 * an unordered array of values that have been set to TRUE.
 * @package htmlform
 */
class checkbox extends formelement {
	private $checked;
	private $value;
	private $multiple = FALSE;
	function __construct($parent = '', $name = '', $value = '', $checked = FALSE, $class = '') {
		formelement::__construct($parent, $name, $class);
		$this->checked = $checked;
		if ($value) $this->setvalue($value);
	}

	/**
	 * Set the value that is sent through if the checkbox is checked
	 *
	 * If unchecked, nothing will be sent in the form data.
	 *
	 * @param string $value
	 * @return void
	 */
	public function setvalue($value) {
		$this->value = $value;
	}

	/**
	 * Get the value of the checkbox
	 *
	 * @return string
	 */
	public function getvalue() { return $this->value; }

	/**
	 * @access private
	 */
	public function finalize() {
		static $rtrack;
		$name = $this->getname();
		if ($rtrack[$name] instanceof checkbox) {
			$rtrack[$name]->multiple = TRUE;
			$this->multiple = TRUE;
		} else {
			$rtrack[$name] = $this;
		}
		formelement::finalize();
	}

	/**
	 * @access private
	 */
	public function output() {
		if ($this->preload) {
			$val = $_REQUEST[$this->getname()];
			if (isset($_REQUEST[$this->getname()])) {
				if (is_array($val)) {
					if (in_array($this->value, $val)) $this->checked = TRUE;
					else $this->checked = FALSE;
				} else {
					if ($val == $this->value) $this->checked = TRUE;
					else $this->checked = FALSE;
				}
			}
		}
		if ($this->multiple && !strstr($this->getname(), '[]')) $this->setname($this->getname().'[]');
		return '<input type="checkbox"' . formelement::output() .
			($this->checked ? ' checked="checked"' : '') .
			($this->value ? ' value="'.htmlspecialchars($this->value).'"' : '') . ' />'.$this->genlabel();
	}
}

/**
 * Hidden form variables
 *
 * This class represents the input tag with type set to "hidden".  Sends
 * a name/value pair through the form without exposing any interface objects to
 * the user.
 *
 * @package htmlform
 */
class hidden extends formelement {
	private $content;

	function __construct($parent, $name, $value='') {
		formelement::__construct($parent, $name);
		if ($value) $this->setvalue($value);
		else $this->setvalue($_REQUEST[$name]);
		$this->preload = FALSE;
	}

	/**
	 * Set the value of the hidden input
	 *
	 * @param string $val
	 * @return void
	 */
	public function setvalue($val) {
		$this->content = $val;
	}

	/**
	 * Get the value of the hidden input
	 *
	 * @return string
	 */
	public function getvalue() { return $this->content; }

	/**
	 * @access private
	 */
	public function output() {
		global $cfg;
		if ($this->preload) {
			$val = $_REQUEST[$this->getname()];
			if (isset($_REQUEST[$this->getname()])) $this->content = $val;
		}
		if (preg_match('/^check_/', $this->getname())) {
			$parent = $this->getparent();
			$form = $parent->findform();
			$name = preg_replace('/\[\]$/', '', $this->getname());
			$cfg['error_check_profile'][$form->getname()] .= $name . ':' . $this->content . '-';
		}
		return '<input type="hidden" value="' . htmlspecialchars($this->content) . '"' . formelement::output() . ' />';
	}
}

/**
 * Submit Button
 *
 * This class is for the submit button at the end of the form.  Pretty simple.
 *
 * @package htmlform
 */
class submit extends formelement {
	private $label;

	/**
	 * Constructor
	 *
	 * @param container $parent
	 * @param string $label
	 * @param string $class
	 * @return submit
	 */
	function __construct($parent = 0, $label = "Submit", $class = "") {
		$this->setname("pwo_submit");
		$this->setparent($parent);
		$this->setdisplay($label);
		if ($class) $this->setclass($class);
	}

	/**
	 * Set Display Text
	 *
	 * This will set the text to be used in the button.  In HTML terms, this
	 * is placed in the 'value' parameter.
	 *
	 * @param string $label
	 * return void
	 */
	public function setdisplay($label) {
		$this->label = $label;
	}

	/**
	 * @access private
	 */
	public function output() {
		return $this->genlabel().'<input type="submit" value="' . htmlspecialchars($this->label) . '"' . formelement::output() . ' />';
	}
}

/**
 * Submit button using image
 *
 * Use this to make a stylized form submission button.  It will
 * appear like any image, but when clicked will submit the form
 * that encloses it.
 *
 * If you need it to pass a value through, it will automatically use
 * the <button> element with appropriate CSS styles to make it appear
 * just like an image.  This will make internet explorer pass the value
 * through, since it will not do so if you use <input type="image">.
 *
 * @package htmlform
 */
class imgsubmit extends image {
	private $value;
	private $disableme;

	/**
	 * Constructor
	 *
	 * Same as the constructor for an image.
	 * @param container $parent
	 * @param string $src
	 * @param int $width
	 * @param int $height
	 * @param string $alt
	 * @param string $class
	 */
	function __construct($parent = 0, $src = '', $value = '', $width = 0, $height = 0, $alt = "", $class = "") {
		$this->setname('pwo_submit');
		$this->setparent($parent);
		$this->setsrc($src);
		$this->setsize($width, $height);
		$this->setalt($alt);
		if ($value) $this->setvalue($value);
		else $this->setvalue('Submit');
		if ($class) $this->setclass($class);
	}

	/**
	 * Set the value passed through when clicked
	 *
	 * This will only appear in the form data if this particular button is
	 * pressed.  By default, the name in this name/value pair is 'submit'.
	 *
	 * @param string $value
	 * @return void
	 */
	public function setvalue($value) {
		$this->value = $value;
	}

	/**
	 * Get the value passed through when clicked
	 *
	 * @return string
	 */
	public function getvalue() { return $this->value; }

	/**
	 * @access private
	 */
	public function output() {
		static $printed;
		if ($this->value == 'Submit') {
			$form = $this->getparent()->findform();
			if (!$printed[$form->getname()]) {
				$hid = '<input type="hidden" name="pwo_submit" value="Submit">';
			}
			return $hid.'<input type="image" name="pwo_submit" src="'.$this->root.htmlspecialchars($this->src).'" alt="'.htmlspecialchars($this->alt).'"'.box::output().'/>';
		} else {
			if ($this->getwidth()) $width = ' width="'.$this->getwidth().'"';
			if ($this->getheight()) $height = ' height="'.$this->getheight().'"';
			return '<button type="submit" value="'.htmlspecialchars($this->value).'"'.box::output().' style="display: inline; border: 0px; padding: 0px; background-color: transparent; cursor: pointer;"><img src="' . $this->root . htmlspecialchars($this->src) . '"'.$width.$height.' alt="'.htmlspecialchars($this->alt).'" /></button>';
		}
	}
}


/**
 * Label container
 *
 * This is used for forms.  You can click it and it will place the focus
 * in the form object that's associated with it.  It's a container, so you can place
 * images and such inside.
 *
 * @package htmlform
 */
class label extends container {
	protected $for;

	function __construct($parent = 0, $label = '', $for = '', $class = '') {
		parent::__construct($parent, '', $class);
		$this->addText($label);
		$this->isfor($for);
	}

	/**
	 * Associate this label with a form element
	 *
	 * Places the DOM ID of the associated element in the label's 'for'
	 * attribute.
	 *
	 * @param formelement $ele
	 * @return void
	 */
	public function isfor($ele) {
		if (!($ele instanceof formelement)) return;
		$this->for = $ele;
	}

	/**
	 * @access private
	 */
	public function finalize() {
		parent::finalize();
		if ($this->for instanceof formelement) {
			if (!$this->for->getid()) $this->for->setid(generatestring(4, 'alpha'));
			if (!$this->for->getlabel()) {
				$this->for->setlabel(strip_tags(container::output()));
				$this->for->hidelabel();
			}
		}
	}

	/**
	 * @access private
	 */
	public function output() {
		if ($this->for instanceof formelement) $for = ' for="'.htmlspecialchars($this->for->getid()).'"';
		return '<label'.$for.element::output().'>'.container::output().'</label>';
	}
}

// legacy support for people who check $_REQUEST['submit'] to see which
// submit button was pressed
if ($_REQUEST['pwo_submit']) $_REQUEST['submit'] = $_REQUEST['pwo_submit'];
else $_REQUEST['pwo_submit'] = $_REQUEST['submit'];
if ($_POST['pwo_submit']) $_POST['submit'] = $_POST['pwo_submit'];
else $_POST['pwo_submit'] = $_POST['submit'];
if ($_GET['pwo_submit']) $_GET['submit'] = $_GET['pwo_submit'];
else $_GET['pwo_submit'] = $_GET['submit'];
?>
