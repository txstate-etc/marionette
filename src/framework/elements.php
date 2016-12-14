<?php
/**
 * @package html
 */


/**
 * Text Element
 *
 * This element is for all plaintext to appear on the site. The most common
 * way to create a text object is to call {@link container::addText()} on
 * a container. It preserves spacing and line breaks.
 *
 * A small number of HTML tags are allowed and will be passed through intact.
 * However, if left unclosed, they will be automatically closed to enforce
 * XHTML validation.
 *
 * <pre>'b' => bold
 * 'i' => italic
 * 's' => strikethrough
 * 'u' => underline
 * 'h*' => headers 1 through 6
 * 'em' => emphasis (usually italic)
 * 'strong' => strong emphasis (usually bold)</pre>
 *
 * There are also a few tags you can use to be converted into the HTML-encoded
 * version.  These are just for convenience - you may also use the actual ascii
 * character.
 *
 * <pre>[copy] -> Standard Copyright Symbol
 * [tm]   -> Trademark Symbol
 * [reg]  -> Registered Trademark Symbol</pre>
 *
 * @package html
 */
class text extends element {
	private $contents;
	private $strict;

	/**
	 * Constructor
	 *
	 * $text is the string to be displayed
	 *
	 * Specify $class to give it a CSS style.  Text will be placed inside a
	 * span element.
	 *
	 * @param container $parent
	 * @param string $text
	 * @param string $class
	 * @return text
	 */
	function __construct($parent = 0, $text = "", $class = "", $strict = FALSE) {
		element::__construct($parent, '', $class);
		$this->settext($text);
		$this->strict = $strict;
		return;
	}

	/**
	 * Set Text
	 *
	 * Method to set the text to be displayed by this object.  Completely replaces
	 * anything that was provided to the constructor.
	 *
	 * @param string $text
	 */
	public function settext($text) {
		$this->contents = $text;
		return;
	}

	/**
	 * @access private
	 */
	public function output() {
		$return = $this->contents;
		$strict = $this->strict;
		if (!$strict) $return = text::preserveHTML($return);
		$return = htmlspecialchars($return);
		$return = str_replace("\r\n", "\n", $return);
		$return = str_replace("\r", "\n", $return);
		$return = str_replace('  ', '&nbsp;&nbsp;', $return);
		$return = str_replace("\n ", "\n&nbsp;", $return);
		$return = preg_replace('/^ /', '&nbsp;', $return);
		$return = str_replace("\n", "<br/>", $return);
		if (!$strict) {
			$return = str_replace("[copy]", "&copy;", $return);
			$return = str_replace("[tm]", "&#8482;", $return);
			$return = str_replace("[reg]", "&reg;", $return);
			$return = preg_replace('/\[#(\d+)\]/', '&#$1;', $return);
			$return = text::restoreHTML($return);
		}
		if ($this->getclass()) {
			$return = '<span class="' . $this->getclass() . '">' . $return . '</span>';
		}
		return $return;
	}

	/**
	 * @access private
	 */
	protected static function approvedtags() {
		return array('b', 'i', 'h(\d)', 'u', 's', 'strong', 'em', 'ins', 'del');
	}

	public static function close_dangling_tags($html){
		foreach (self::approvedtags() as $t) {
			$expr[] = '('.$t.')';
		}
		$alltags = implode('|', $expr);

		#put all opened tags into an array
		preg_match_all("#<(".$alltags.")>#iU",$html,$result);
		$openedtags=$result[1];

		#put all closed tags into an array
		preg_match_all("#</(".$alltags.")>#iU",$html,$result);
		$closedtags=$result[1];
		$len_opened = count($openedtags);
		# all tags are closed
		if(count($closedtags) == $len_opened){
			return $html;
		}

		$openedtags = array_reverse($openedtags);
		# close tags
		for($i=0;$i < $len_opened;$i++) {
			if (!in_array($openedtags[$i],$closedtags)){
		    	$html .= '</'.$openedtags[$i].'>';
			} else {
				unset($closedtags[array_search($openedtags[$i],$closedtags)]);
			}
		}
		return $html;
	}


	/**
	 * @access private
	 */
	public static function preserveHTML($text) {
		$tags = text::approvedtags();
		foreach ($tags as $tag) {
			$text = preg_replace('/<(\/)?(' . $tag . '(\s.*?)?)>/i', '[$1$2]', $text);
		}
		return $text;
	}

	/**
	 * @access private
	 */
	public static function restoreHTML($text) {
		$tags = text::approvedtags();
		foreach ($tags as $tag) {
			$text = preg_replace_callback('/\[(\/)?(' . $tag . '(\s.*?)?)\]/i', function ($matches) {
				return '<'.$matches[1].htmlspecialchars_decode($matches[2]).'>';
			}, $text);
		}
		return $text;
	}
}

/**
 * Raw XHTML source
 *
 * This class may be used as a last resort to add unfiltered xhtml to your document.
 *
 * Be aware that you are giving up many of the features of PHPWebObjects if you use
 * this function.  This includes things like link rewriting, guaranteed xhtml validation,
 * automatic line breaks, etc.
 */
class rawxhtml extends element {
	protected $xhtml;
	public function __construct($parent, $xhtml = '') {
		element::__construct($parent);
		$this->setXHTML($xhtml);
	}
	public function setXHTML($xhtml = '') {
		$this->xhtml = $xhtml;
	}
	public function getXHTML() {
		return $this->xhtml;
	}
	public function output() {
		return $this->getXHTML();
	}
}

/**
 * HTML Link Element
 *
 * This element is for on-site links.  It will automatically add
 * any session ids as appropriate.
 * @package html
 */
class link extends container {
	private $href;
	private $hash;
	private $variables = array();
	private $target;
	private $rel;
	private $make_secure;
	private $sel;
	private $jswindow = array();

	/**
	 * Constructor
	 *
	 * $href is the target of the link, *WITHOUT* CGI variables.  You may prepend
	 * it with an exclamation point to specify that the file is in the application root,
	 * as specified in the config file.  e.g. "!mypage.php" might represent
	 * "/myapp/mypage.php".
	 *
	 * $label is the displayed text
	 *
	 * $class is the CSS class for the link
	 *
	 * $variables is an associative array containing name=>value pairs, these
	 * will be converted to a CGI-style URI
	 *
	 * Note that you should not need to worry about session management
	 * variables in the link.  They'll be handled auto-magically.
	 *
	 * @param container $parent
	 * @param string $href
	 * @param string $label
	 * @param string $class
	 * @param array $variables
	 * @return link
	 */
	function __construct($parent = 0, $href = "", $label = "", $variables = array(), $class = "") {
		$this->setparent($parent);
		$this->sethref($href, $variables);
		if ($class) $this->setclass($class);
		if ($label) $this->addText($label);
		return;
	}

	/**
	 * Find out if a link is "local" or "external"
	 *
	 * This detection is based on the list of "local" domain patterns as
	 * specified in a config.php file.  Just set the global $cfg['local_domains'] to an
	 * array of strings
	 *
	 * @return bool
	 * @param string $link
	 * @static
	 */
	public static function checklocal($link) {
		global $cfg;
		if (!preg_match('/^[a-z]{1,6}:\/\//i', $link)) return TRUE;
		if (!is_array($cfg['local_domains'])) $cfg['local_domains'] = array();
		foreach ($cfg['local_domains'] as $domain) {
			if (!(strpos($link, $domain)===FALSE)) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Find out if a link goes to a new server
	 *
	 * We need this detection in case we have multiple DNS names that all
	 * share session tracking.  We can't be sure that the client has cookies for
	 * both domains, so we need to put the session information in the link
	 * just in case.
	 *
	 * @param string $link
	 * @return bool
	 * @static
	 */
	public static function checkservermove($link) {
		if (!preg_match('/^[a-z]{1,6}:\/\//i', $link)) return FALSE;
		$parsed = parse_url($link);
		if (strtolower($parsed['host']) == strtolower($_SERVER['HTTP_HOST'])) return FALSE;
		return TRUE;
	}

	/**
	 * Find out if a link points to an executable
	 *
	 * This detection is based on the list of "code" extensions as
	 * specified in a config.php file.  Just set the global $cfg['code_extensions'] to an
	 * array of strings.  'php', 'cgi', 'phtml', and 'pl' are built in and will always
	 * be recognized as code.
	 *
	 * Additionally, if you link directly to a directory or domain, it will assume that
	 * the index file is code. (May break if you end with a directory that looks like
	 * it has an extension, e.g. "/myfolder/my.stuff".)
	 *
	 * Note that this function is used internally for session management.  URL-based session
	 * numbers will only be sent to links that look like code.
	 *
	 * @return bool
	 * @param string $link
	 * @static
	 */
	public static function checkcode($link) {
		global $cfg;
		if (!is_array($cfg['code_extensions'])) $cfg['code_extensions'] = array();

		// try to detect a directory or domain link
		if (!preg_match('/\.\w{3,5}$/', $link)) return TRUE;
		if (preg_match('/http:\/\/[^\/]+\/?$/', $link)) return TRUE;

		// if we have a file with an extension, make sure it's code
		$builtin = array('php', 'cgi', 'phtml', 'pl');
		$checklist = array_unique(array_merge($builtin, $cfg['code_extensions']));
		foreach ($checklist as $ext)
			if (preg_match('/\.'.$ext.'$/i', $link)) return TRUE;
		return FALSE;
	}

	/**
	 * Build a URL (static function)
	 *
	 * Use this static function to build a query from an href and some variables.
	 * If you use this religiously to build your URLs, you can rely on it to
	 * implement your site's SEO behavior, via the $doc->setseo() method.
	 *
	 * I plan to eventually come up with a SEO-friendly strategy and an AJAX-friendly
	 * strategy and add a toggle to $cfg so that you can change your site's behavior
	 * instantly in your config.php file.
	 *
	 * @param string $href
	 * @param array $variables
	 * @param integer $make_secure
	 * @param string $separator
	 * @return string
	 * @static
	 */
	public static function buildquery($href, $variables = array(), $make_secure = 0, $separator = '&amp;', $hash = '', $force_session = 0) {
		$href = self::expand_href($href);

		// muck with the url if we're supposed to force its secure status
		if ($make_secure != 0) $href = doc::absolute_url($href, $make_secure);

		// add session variables if appropriate
		$user = doc::getuser();
		if ($user instanceof user) {
			if (($force_session > 0 || $user->urlsession() || self::checkservermove($href)) && self::checklocal($href) && $user->sid() && self::checkcode($href)) {
				if ($force_session > -1) $variables[$user->sidname()] = $user->sid();
			}
		}

		//check and see if we have an seo conversion function
		$doc = doc::getdoc();
		if (is_callable($doc->getseo())) {
			$seohref = call_user_func($doc->getseo(), $href, $variables, $separator, $hash);
			if (!empty($seohref)) {
				if ($make_secure != 0) $seohref = doc::absolute_url($seohref, $make_secure);
				return $seohref;
			}
		}

		$vars = self::build_qstr($variables, $separator);
		if ($vars) $href .= '?' . $vars;
		if ($hash) $href .= '#' . $hash;
		return $href;
	}

	/**
	 * @access private
	 */
	public static function expand_href($href) {
		if (!$href) return $_SERVER['PHP_SELF'];
		// we don't care about anchors
		if (substr($href, 0, 1) == '#') return $href;
		global $cfg;
		if (substr($href, 0, 1) == '!')
			return endslash($cfg['link_root']).substr($href, 1);
		if (substr($href, 0, 1) == '@')
			return endslash($cfg['library_path']).substr($href, 1);
		return $href;
	}

	/**
	 * Get an absolute link using the same syntax as link->set_href()
	 *
	 * Sometimes when sending emails or creating bookmarkable links you just need a fully
	 * qualified URL.  Use this method to maintain consistency with all your other
	 * links and get access to your base path with the ! modifier.
	 *
	 * Example: $emailbody = "See this link:\n\n".link::absolute('!mypage.php', array('id'=>$myid));
	 *
	 * Evaluates to: 'http://www.yourdomain.com/yourpath/mypage.php?id=12'
	 *
	 * @param string $href
	 * @param array $vars
	 * @param string $hash
	 * @return string
	 */
	public static function absolute($href, $vars = array(), $hash = '') {
		$href = self::buildquery($href, $vars, 0, '&', $hash, -1);
		return doc::absolute_url($href);
	}

	/**
	 * Convert an array to a query string.
	 *
	 * Use this static function to build a query string from an array of
	 * variables.  After using this to build a link, clicking the link will
	 * make $_GET match the given array, exactly.
	 *
	 * $sep is the separator to use, defaults to the separator specified in your
	 * php.ini file.  Typically you should use '&amp;' for links and '&' for HTTP
	 * headers like Location.
	 *
	 * $prefix is an optional command so that if you provide a non-associative
	 * array, you can give the numbers a prefix to conform to the standard (CGI variables
	 * are not allowed to start with a number).  This will make variables that
	 * (assuming you set $prefix to 'prefix_') look like prefix_0, prefix_1, etc.. .
	 *
	 * $key is an input used for recursion; you should avoid using it.
	 *
	 * @param array $vars
	 * @param string $sep
	 * @param string $prefix
	 * @param string $key
	 * @return string
	 * @static
	 * @access private
	 */
	public static function build_qstr($vars, $sep='', $prefix='', $key='') {
		$ret = array();
		foreach ((array) $vars as $k => $v) {
			if (is_int($k) && $prefix) $k = $prefix.$k;
			elseif ($key) $k = $key.'['.$k.']';

			if (is_array($v)) array_push($ret, self::build_qstr($v,$sep,'',$k));
			else array_push($ret, urlencode($k).($v != '' || $v == '0' ? "=" : '').urlencode($v));
		}

		if(!$sep) $sep = ini_get("arg_separator.output");

		return implode($sep, $ret);
	}

	/**
	 * Parse an HREF
	 *
	 * Assumes that you have pulled an href from a link in an XHTML document
	 * and returns the PHP_SELF portion along with an array of the variables.
	 *
	 * Returns an array with two keys, 'file' and 'vars'.
	 *
	 * @param string $href
	 * @return array
	 * @static
	 */
	public static function parse_href($href, $htmlencoded = TRUE) {
		if ($htmlencoded) $href = htmlspecialchars_decode($href);
		// parse the query string
		list($href, $hash) = explode('#', $href, 2);
		list ($fname, $qstring) = explode('?', $href, 2);
		if ($qstring) $pairs = explode('&', $qstring);
		else $pairs = array();
		$vars = array();
		foreach ($pairs as $pair) {
			list ($name, $value) = explode('=', $pair, 2);
			$vars[urldecode($name)] = urldecode($value);
		}
		return array('file'=>urldecode($fname), 'vars'=>$vars, 'hash'=>urldecode($hash));
	}

	/**
	 * Set new HREF and variables
	 *
	 * Normally you'd set a link's location & variables at creation, but you
	 * can use this method to set new ones from scratch at some later time.
	 *
	 * You may prepend $href with an exclamation point to specify that the
	 * file is in the application root, as specified in the config file.
	 * e.g. "!mypage.php" might represent "/myapp/mypage.php".
	 *
	 * @param string $href
	 * @param array $variables
	 * @return void
	 */
	public function sethref($href, $variables = 'nochange') {
		global $cfg;
		$this->href = $href;
		if ($variables == 'nochange') return;
		$this->variables = array();
		foreach ((array) $variables as $key => $val) {
			if (is_array($val))
				foreach ($val as $valu) $this->addvar($key, $valu);
			else $this->addvar($key, $val);
		}
		return;
	}

	public function gethref() {
		$href = $this->href;
		if (substr($href, 0, 1) == '!') {
			global $cfg;
			return endslash($cfg['link_root']).substr($href, 1);
		}
		return $href;
	}

	public function sethash($hash = '') {
		$this->hash = $hash;
	}

	public function setrel($rel = '') {
		$this->rel = $rel;
	}

	/**
	 * Add an input variable to the link
	 *
	 * Normally you'd specify all the link variables at creation,
	 * but you can use this method to add them after the fact.
	 *
	 * @param string $key
	 * @param string $val
	 * @return void
	 */
	public function addvar($key, $val) {
		$ex = &$this->variables[$key];
		if (isset($ex)) {
			if (is_array($ex)) $ex[] = $val;
			else $ex = array($ex, $val);
		} else {
			$ex = $val;
		}
	}

	/**
	 * Set "selected" CSS class
	 *
	 * Use this method to specify a CSS class to be used when the href/vars
	 * of the link match that of the current page.  This gives you a quick way to
	 * provide visual feedback in a menu system.
	 *
	 * You can use the sel_criteria() method to choose a smaller set of variables to
	 * match on.  If you do not, only exact matches will be altered.
	 *
	 * If you use sel_criteria(), then there will be two classes of links, 'match' and
	 * 'exact'.  Where a 'match' only has to match the input variables you specified, and an
	 * 'exact' match still has to match all input variables.  You can set the behavior
	 * for each one individually.
	 *
	 * For instance, imagine a menu system where you click a link and when the page reloads
	 * there is a new submenu underneath.  The parent link should now be plain text (since
	 * you're already there), and the child links should be visible.
	 *
	 * Now click a child link.  The child link becomes text (because you're there),
	 * but the parent link should be clickable again (to get back to the overview page).  However,
	 * you still want the parent link to have a different style to denote that it has children showing.
	 *
	 * All you need to do to define ALL that behavior is this:
	 * <pre>$parent->sel_class('myselectedclass_parent');
	 * $parent->sel_criteria('parentid');
	 * $parent->sel_unclickable('exact');
	 * foreach ($children as $child) {
	 *   $child->setclass('invisibleclass');
	 *   $child->sel_class('notinvisibleclass');
	 *   $child->sel_criteria('parentid');
	 *   $child->sel_unclickable('exact');
	 * }</pre>
	 *
	 * @param string $class
	 */
	public function sel_class($class) {
		$this->sel['class'] = $class;
	}

	/**
	 * Set CSS class for exact "selected" match
	 *
	 * This method allows you to specify a CSS class when the link matches the
	 * current page *exactly*, instead of just based on the criteria set by {@link sel_criteria()}
	 *
	 * By default all links that match, exact or not, will be set to the class
	 * specified by {@link sel_class()}, so this method is completely optional unless
	 * you need a special CSS class for exact matches.
	 * @param string $class
	 */
	public function sel_exactclass($class) {
		$this->sel['exactclass'] = $class;
	}

	/**
	 * Set rules for link clickability
	 *
	 * Use this method to make selected links appear as plain text, and thus,
	 * unable to be clicked.  This is a nice feedback mechanism for your users to
	 * understand where they are in your navigation.
	 *
	 * You have two behavior choices:
	 * <pre>   'match' => link should not be clickable if it matches criteria set by {@link sel_criteria()}
	 *    'exact' => not clickable only if there is an exact match</pre>
	 *
	 * The default is 'match' and covers both cases.  If the link is an exact match, then
	 * obviously it is also a match according to the restricted criteria.
	 *
	 * @param string $when
	 * @return void
	 */
	public function sel_unclickable($when = 'match') {
		$this->sel['clickable'] = $when;
		if ($when == 'match' || $when == 'exact') return TRUE;
		else return FALSE;
	}

	/**
	 * Force link to be "selected" or not
	 *
	 * Normally links will attempt to automatically figure out that they
	 * match the current page, and thus are "selected".  Use this method
	 * to override the automatic detection, one way or the other.
	 * @param bool $bool
	 * @return void
	 */
	public function sel_force($bool) {
		$this->sel['forced'] = TRUE;
		$this->sel['selected'] = $bool;
	}

	/**
	 * Force link to be "exact" or not
	 *
	 * Normally links will attempt to automatically figure out that they
	 * match the current page, and thus are "selected" or "exact".  Use this
	 * method to override the automatic detection and set (or unset) the "exact"
	 * flag.
	 * @param bool $bool
	 * @return void
	 */
	public function sel_forceexact($bool) {
		$this->sel['forcedexact'] = TRUE;
		$this->sel['exact'] = $bool;
	}

	/**
	 * Specify "selected" keys
	 *
	 * Use this method to specify the query string inputs to be used to
	 * determine whether the link is "selected"
	 *
	 * For instance, if you want the link to be selected based only on two
	 * keys called 'mode' and 'submode', then pass array('mode', 'submode')
	 * as the parameter.
	 *
	 * If you are only passing one criteria, you do not need to put it inside
	 * an array.
	 */
	public function sel_criteria($keys) {
		if (!is_array($keys)) $keys = array($keys);
		$this->sel['criteria'] = $keys;
	}

	/**
	 * Determine if href/vars match the current page
	 *
	 * This function determines whether a given link is in fact a
	 * "refresh" link that points back to the same page.  This will allow us
	 * to alter the link visually or even deactivate it and make it plain text.
	 *
	 * @param string $href
	 * @param array $vars
	 * @return bool
	 * @access private
	 * @static
	 */
	public static function matches_current($href, $vars, $exact = FALSE, $criteria = array()) {
		$href = self::expand_href($href);
		$current = doc::absolute_url($_SERVER['PHP_SELF']);
		$thislink = doc::absolute_url($href);
		if ($current != $thislink) return FALSE;

		if (!$exact) $totalvars = (array) $criteria;
		else {
			$totalvars = array_merge(array_keys($vars), array_keys($_GET), array_keys($_POST));
			$user = doc::getuser();
			if ($user instanceof user) $totalvars = array_diff($totalvars, array($user->sidname()));
			$totalvars = array_diff($totalvars, array('whichform'));
		}

		foreach ($totalvars as $key) {
			if ($_REQUEST[$key] != $vars[$key]) {
				if (!strcont($key, 'errorcheck') && !strcont($key, 'whichform')) return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Set the frame target
	 *
	 * If using frames, or if you'd like to open a new window with target="_blank",
	 * you can use this method to set the target frame for your link.
	 *
	 * Defaults to '_blank' if no parameter provided.
	 *
	 * @param string $target
	 */
	public function target($target = '_blank') {
		$this->target = $target;
	}

	/**
	 * Set link to open in a javascript window
	 *
	 * Tired of looking up the javascript for opening new windows?  Just toss this
	 * method onto your link and it'll open in a new window
	 *
	 * By default the window will be no-frills - no menu bar, no address bar, etc. The
	 * $attr parameter array can be used to turn some of those on.  Just use standard javascript
	 * window attributes.
	 *
	 * @param int $width
	 * @param int $height
	 * @param string $name
	 * @param array $attr
	 * @return void
	 */
	public function JSWindow($width = 320, $height = 240, $name = '', $attr = array()) {
		// set link to open in javascript window
		$this->jswindow['boolean'] = TRUE;

		// set up the attributes list
		if (!$attr['width']) $attr['width'] = $width;
		if (!$attr['height']) $attr['height'] = $height;
		if (!$attr['resizable']) $attr['resizable'] = 'yes';
		if (!$attr['scrollbars']) $attr['scrollbars'] = 'yes';
		if (!$attr['toolbar']) $attr['toolbar'] = 'no';
		if (!$attr['location']) $attr['location'] = 'no';
		if (!$attr['directories']) $attr['directories'] = 'no';
		if (!$attr['status']) $attr['status'] = 'no';
		if (!$attr['menubar']) $attr['menubar'] = 'no';
		if (!$attr['copyhistory']) $attr['copyhistory'] = 'no';
		foreach ($attr as $key => $val) $attrs[] = $key.'='.$val;
		$this->jswindow['attr'] = join(',', $attrs);

		// generate a somewhat predictable name if we weren't given one
		if (!$name) $name = preg_replace('/\W/', '', basename($this->href));
		$this->jswindow['name'] = $name;

		return;
	}

	/**
	 * Force a secure link
	 *
	 * If you need to make sure a link goes to a secure page,
	 * use this method.  It will force an absolute URL beginning
	 * with https://
	 */
	public function make_secure($flag = TRUE) {
		$this->make_secure = $flag ? 1 : 0;
	}

	/**
	 * Force an unsecure link
	 *
	 * If you need to make sure a link goes to an unsecure page,
	 * use this method.  It will force an absolute URL beginning
	 * with http://
	 */
	public function make_unsecure($flag = TRUE) {
		$this->make_secure = $flag ? -1 : 0;
	}

	/**
	 * @access private
	 */
	private function locallink() {
		return link::checklocal($this->href);
	}

	/**
	 * Check a URL to see if it will be secure (https)
	 */
	public function checksecure($href, $user, $make_secure = 0) {
		if ($make_secure == 1) return TRUE;
		if ($make_secure == -1) return FALSE;

		if (strtolower(substr($href, 0, 5)) == 'https') $secure = TRUE;
		if ($user->secure()) $secure = TRUE;
		return $secure;
	}

	/**
	 * @access private
	 */
	private function secureurl($user) {
		return self::checksecure($this->href, $user, $this->make_secure);
	}

	/**
	 * @access private
	 */
	private function needsession() {
		return link::checkcode($this->href);
	}

	/**
	 * @access private
	 */
	public function output($pspace='') {
		// deal with "selected" links
		if (!$this->sel['forced']) $this->sel['selected'] = self::matches_current($this->href, $this->variables, FALSE, $this->sel['criteria']);
		if (!$this->sel['forcedexact']) $this->sel['exact'] = self::matches_current($this->href, $this->variables, TRUE);
		if ($this->sel['exact']) {
			if ($this->sel['exactclass']) $this->setclass($this->sel['exactclass']);
			elseif ($this->sel['class']) $this->setclass($this->sel['class']);
			if ($this->sel['clickable']) $spanvers = TRUE;
		} elseif ($this->sel['selected']) {
			if ($this->sel['class']) $this->setclass($this->sel['class']);
			if ($this->sel['clickable'] == 'match') $spanvers = TRUE;
		}

		$href = link::buildquery(htmlspecialchars($this->href), $this->variables, $this->make_secure, '&amp;', $this->hash);
		if ($this->target) $target = ' target="' . htmlspecialchars($this->target) . '"';
		if ($this->gettitle()) $title = ' title="'.htmlspecialchars($this->gettitle()).'"';
		if ($this->rel) $rel = ' rel="'.htmlspecialchars($this->rel).'"';

		// are we opening into a javascript window?
		if ($this->jswindow['boolean']) $this->addJS('onclick', "var ".$this->jswindow['name']." = window.open('".$href."', '".$this->jswindow['name']."', '".$this->jswindow['attr']."'); ".$this->jswindow['name'].'.focus(); '."return false;");

		if ($spanvers) return '<span'.element::output().'>'.container::output($pspace).'</span>';
		return '<a href="' . $href . '"' . element::output() . $target . $title . $rel . '>' . container::output($pspace) . '</a>';
	}
}

/**
 * Header Element
 *
 * Use this object to represent h1 through h6
 * @package html
 */
class header extends container {
	protected $level;
	/**
	 * Constructor
	 *
	 * @param container $parent
	 * @param int $level
	 * @param string $class
	 * @return header
	 */
	function __construct($parent = 0, $level = 1, $class = "") {
		if (!$level) $level = 1;
		$this->setparent($parent);
		$this->setclass($class);
		$this->setlevel($level);
	}

	public function setlevel($level) {
		$this->level = $level;
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		return '<h'.$this->level.element::output().'>'.container::output($pspace, $optin).'</h'.$this->level.'>';
	}
}


/**
 * Image Element
 *
 * This object represents any kind of image.
 * @package html
 */
class image extends box {
	protected $src;
	protected $vars;
	protected $alt;
	protected $map;
	protected $mapareas;
	protected $rollover;

	/**
	 * Constructor
	 *
	 * @param container $parent
	 * @param string $src
	 * @param int $width
	 * @param int $height
	 * @param string $alt
	 * @param string $class
	 * @return image
	 */
	function __construct($parent = 0, $src = "!spacer.gif", $width = "", $height = "", $alt = "", $class = "") {
		$this->setparent($parent);
		$this->setsrc($src);
		$this->setalt($alt);
		$this->setsize($width, $height);
		$this->setclass($class);
		return;
	}

	/**
	 * Set image source URL
	 *
	 * Use this method to set the "src" attribute for the image.  You can
	 * prepend it with an exclamation point to denote that it's in your
	 * standard images directory, as set in the config file.
	 *
	 * This works a little like setting a link href. Since you could be calling
	 * a script to grab your image from a database, you might have variables.
	 *
	 * e.g. $img->setsrc('!hello.gif');
	 *
	 * @param string $src
	 * @param array $vars
	 * @return void
	 */
	public function setsrc($src, $vars = array()) {
		global $cfg;
		if (substr($src, 0, 1) == "!") {
			$this->src = endslash($cfg['image_root']).substr($src, 1);
		} elseif (substr($src, 0, 1) == "#") {
			$this->src = endslash($cfg['link_root']).substr($src, 1);
		} else {
			$this->src = $src;
		}
		$this->vars = $vars;
		return;
	}

	/**
	 * Get image source URL
	 *
	 * Returns the contents of the image's "src" attribute.  If you
	 * use an exclamation point for setsrc(), it will already have
	 * been replaced with the actual path, so this will always return
	 * exactly what is to be rendered in HTML.
	 *
	 * @return string
	 */
	public function getsrc() {
		return $this->src;
	}

	/**
	 * Set alternate text for your image
	 *
	 * This is an HTML attribute that is commonly used to assist in
	 * browsing for the blind and otherwise disabled.  It should be
	 * a plain-English description of the image and its purpose.
	 *
	 * If you do not specify any alt text the image will print with the alt
	 * set to the empty string, because the XHTML standard requires an alt tag.
	 *
	 * @param string $alt
	 * @return string
	 */
	public function setalt($alt) {
		$alt = trim(strip_tags(preg_replace('/\[.*?\]/', '', $alt)));
		$this->alt = $alt;
	}

	public function getalt() {
		return $this->alt;
	}

	/**
	 * Add an image rollover
	 *
	 * This method will add javascript to make your image roll over
	 * to something else when the mouse hovers over it.  It also preloads
	 * the rollover image to prevent delay.
	 *
	 * See {@link image::setsrc()} for information about the parameters,
	 * this works the same way.
	 *
	 * @param string $new_src
	 * @return void
	 */
	public function rollover($new_src, $vars = array()) {
		global $cfg;
		if (substr($new_src, 0, 1) == "!") {
			$this->rollover = endslash($cfg['image_root']).substr($new_src, 1);
		} elseif (substr($new_src, 0, 1) == "#") {
			$this->rollover = endslash($cfg['link_root']).substr($new_src, 1);
		} else {
			$this->rollover = $new_src;
		}
		$this->rollover = link::buildquery($this->rollover, $vars);
		return;
	}

	/**
	 * Set a link map for this image
	 *
	 * This is an ALTERNATIVE to {@link image::add_map_area()} if, for some
	 * reason, you have a map created somewhere outside the framework - in an
	 * included file or something.
	 *
	 * If you use {@link image::add_map_area()} at all, this setting will be
	 * ignored in favor of the framework-generated map.
	 *
	 * @param string $mapname
	 * @return void
	 */
	public function usemap($mapname) {
		if (substr($mapname, 0, 1) == '#') $mapname = substr($mapname, 1);
		$this->map = $mapname;
	}

	/**
	 * Add a link to a small portion of the image
	 *
	 * This is the framework's way of adding a "map" to an image.  See
	 * documentation for the HTML "map" tag for information on how it works.
	 *
	 * $type should be the type of area you're adding - "rect" or "circle", etc.
	 * It corresponds to the "shape" attribute of an "area" tag.
	 *
	 * $coords should be a string of comma-separated coordinates, exactly what
	 * should be placed in the "coords" attribute of an "area" tag.
	 *
	 * $href and $variables work like all the other links in the framework, will
	 * maintain session info, etc.
	 *
	 * @param string $type
	 * @param string $coords
	 * @param string $href
	 * @param array $variables
	 * @param string $alt
	 * @return void
	 */
	public function add_map_area($type, $coords, $href, $variables = array(), $alt = '') {
		$href = link::buildquery($href, $variables);
		if (!$alt) $alt = basename($href);
		$this->mapareas[] = array('type'=>$type, 'coords'=>$coords, 'href'=>$href, 'alt'=>$alt);
	}

	/**
	 * @access private
	 */
	public function finalize() {
		static $once = FALSE;
		static $preload = array();
		if ($this->rollover) {
			if (!$once) {
				$doc = doc::getdoc();
				$doc->addJS('function rollimage(image, newsrc, rolling) {
					image.src = newsrc;
				}');
				$once = TRUE;
			}
			if (!$preload[$this->rollover]) {
				$doc = doc::getdoc();
				$doc->addJS('var img = new Image(); img.src="'.$this->rollover.'"');
				$preload[$this->rollover] = TRUE;
			}
		}
		parent::finalize();
	}

	/**
	 * @access private
	 */
	public function output() {
		$this->src = link::buildquery($this->src, $this->vars);
		if ($this->rollover) {
			$this->addJS('onmouseover', 'rollimage(this, "'.$this->rollover.'", 1)');
			$this->addJS('onmouseout', 'rollimage(this, "'.$this->getsrc().'", 0)');
		}
		if ($this->gettitle()) $title = ' title="'.htmlspecialchars($this->gettitle()).'" ';
		if (count($this->mapareas)) {
			$this->map = generatestring(4,'alpha');
			$usemap = '<map name="'.$this->map.'">';
			foreach ($this->mapareas as $a)
				$usemap .= '<area shape="'.htmlspecialchars($a['type']).'" coords="'.htmlspecialchars($a['coords']).'" href="'.htmlspecialchars($a['href']).'" alt="'.htmlspecialchars($a['alt']).'"/>';
			$usemap .= '</map>';
		}
		if ($this->map) $map = ' usemap="#'.htmlspecialchars($this->map).'" ';
		return '<img src="' . $this->src . '" alt="' .
			   htmlspecialchars($this->alt) . '"' . $title . $map . box::output().' />'.$usemap;
	}
}

/**
 * Media Object
 *
 * This class is designed to handle embedded content like flash and streaming
 * video and audio.  It's pretty simple at the moment - it just keys off of
 * the file extension to build the appropriate HTML tags. Recognized tags at the
 * moment include .swf, .mid, .mp3, .mov, .mpg, .mpeg, and .avi.
 *
 * You'll have to know the appropriate parameters to control things like looping
 * and autoplay.  Hopefully in the future we can add methods with that kind of
 * knowledge built in.
 *
 * @package html
 */
class mediaobject extends image {
	protected $params;
	protected $sources;

	/**
	 * Constructor
	 *
	 * @param container $parent
	 * @param string $src
	 * @param int $width
	 * @param int $height
	 * @param array $params
	 * @param string $class
	 * @return mediaobject
	 */
	function __construct($parent = 0, $src = "", $width=0, $height=0, $params = array(), $class = "") {
		$this->params = $params;
		if ($params['poster']) $this->poster($params['poster']);
		image::__construct($parent, $src, $width, $height, '', $class);
	}

	public function addfile($src, $vars = array()) {
		$this->sources[] = array('src'=>$src, 'vars'=>$vars);
	}

	public function addparam($key, $val=TRUE) {
		$this->params[$key] = $val;
	}

	public function autoplay($flag=TRUE) {
		if ($flag) $flag = 'autoplay';
		$this->params['autoplay'] = $flag;
	}
	public function loop($flag=TRUE) {
		if ($flag) $flag = 'loop';
		$this->params['loop'] = $flag;
	}
	public function poster($src, $vars = array()) {
		global $cfg;
		if (substr($src, 0, 1) == "!") {
			$src = endslash($cfg['image_root']).substr($src, 1);
		} else {
			$src = $src;
		}
		$this->params['poster'] = link::buildquery($src, $vars);
		return;
	}

	public static function mediatype($path) {
		$ext = file_extension($path);
		if ($ext == 'mov') $type = 'video/quicktime';
		if ($ext == 'mp4') $type = 'video/mp4';
		if ($ext == 'm4v') $type = 'video/x-m4v';
		if ($ext == 'mpg' || $ext == 'mpeg') $type = 'video/mpeg';
		if ($ext == 'avi') $type = 'video/avi';
		if ($ext == 'ogv') $type = 'video/ogg';
		if ($ext == 'ogm') $type = 'video/ogg';
		if ($ext == 'mkv') $type = 'video/x-matroska';
		if ($ext == 'wmv') $type = 'video/x-ms-wmv';

		if ($ext == 'mid' || $ext == 'midi') $type = 'audio/midi';
		if ($ext == 'ogg') $type = 'audio/ogg';
		if ($ext == 'wav') $type = 'audio/x-wav';
		if ($ext == 'mp3') $type = 'audio/mpeg';
		if ($ext == 'wma') $type = 'audio/x-ms-wma';
		return $type;
	}

	/**
	 * @access private
	 */
	public function output() {
		$path = link::buildquery($this->src, $this->vars);
		$ext = file_extension($path);
		$returnopen='';

		if (doc::html5()) {
			$type = self::mediatype($path);
			if ($type) {
				if ($this->params['autoplay'] || $this->params['play'] || $this->params['autostart']) $attr .= ' autoplay="autoplay"';
				if ($this->params['loop']) $attr .= ' autoplay="autoplay"';
				if ($this->params['controls']) $attr .= ' autoplay="autoplay"';
				if ($this->params['preload'] == 'none' || $this->params['preload'] == 'auto') $attr .= ' preload="'.$this->params['preload'].'"';
				else $attr .= ' preload="metadata"';
				if (strcont($type, 'video') && $this->params['poster']) $attr .= ' poster="'.$this->params['poster'].'"';

				if (strcont($type, 'audio')) $tag = 'audio';
				else $tag = 'video';

				return '<'.$tag.box::output().$attr.'><source src="'.$path.'" type="'.$type.'"/>Your browser does not support the '.$tag.' element.</'.$tag.'>';
			}
		}

		if ($ext == "swf") {
			$returnopen .= '<object'.box::output().' type="application/x-shockwave-flash" data="'.$path.'">'.
			'<param name="movie" value="'.$path.'" />';
			$returnclose = '</object>';
		} elseif ($ext == "mid") {
			$returnopen .= '<object'.box::output().' type="audio/mid" data="'.$path.'">'.
			'<param name="src" value="'.$path.'" />';
			$returnclose = '</object>';
		} elseif ($ext == "mp3") {
			$returnopen .= '<object'.box::output().' type="audio/mp3" data="'.$path.'">'.
			'<param name="src" value="'.$path.'" />';
			$returnclose = '</object>';
		} elseif ($ext == "mov") {
			$returnopen .= '<object'.box::output().' type="video/quicktime" data="'.$path.'">'.
			'<param name="src" value="'.$path.'" />';
			$returnclose = '</object>';
		} elseif ($ext == "mpg" || $ext == "mpeg") {
			$returnopen .= '<object'.box::output().' type="video/mpg" data="'.$path.'">'.
			'<param name="src" value="'.$path.'" />';
			$returnclose = '</object>';
		} elseif ($ext == "avi") {
			$returnopen .= '<object'.box::output().' type="video/avi" data="'.$path.'">'.
			'<param name="src" value="'.$path.'" />';
			$returnclose = '</object>';
		} else {
			return '';
		}
		foreach ($this->params as $key => $val) $return .= '<param name="'.$key.'" value="'.$val.'">';
		return $returnopen . $return . $returnclose;
	}
}

/**
 * Horizontal Rule
 *
 * This element is a simple horizontal line meant to visually divide two
 * areas of a page.  It creates an HTML "hr" tag.
 *
 * @package html
 */
class divider extends element {
	private $width;
	private $size;
	private $noshade;

	/**
	 * Divider Constructor
	 *
	 * @param container $parent
	 * @param int $width
	 * @param string $class
	 */
	function __construct($parent = "", $width = "", $class = "") {
		$this->setwidth($width);
		element::__construct($parent, "", $class);
	}

	/**
	 * Set hr width
	 *
	 * @param int $width
	 */
	public function setwidth($width) {
		if (intVal($width)) $this->width = $width;
	}

	/**
	 * Set hr size attribute
	 *
	 * @param int $size
	 */
	public function setsize($size) {
		if (intVal($size)) $this->size = $size;
	}

	public function noshade($flag = TRUE) {
		$this->noshade = $flag;
	}

	/**
	 * Generate Output
	 *
	 * @access private
	 * @return string
	 */
	public function output() {
		if ($this->size) $size = ' size="'.$this->size.'"';
		if ($this->noshade) $noshade = ' noshade="noshade"';
		if (strstr($this->width, '%')) { $width = ' style="width: ' . $this->width . '%;"'; }
		elseif ($this->width) { $width = ' style="width: ' . $this->width . 'px;"'; }
		return '<hr' . $noshade . $size . $width . element::output().'/>';
	}
}

/**
 * Division Container
 *
 * This is the standard layout container.  Use it to define a box of content,
 * and use a CSS class definition to define its layout properties.
 * @package html
 */
class div extends container {
	/**
	 * Constructor
	 *
	 * Note that the second parameter will set the DOM ID.  A div never gets a
	 * "name" attribute, only "id".
	 *
	 * @param container $parent
	 * @param string $id
	 * @param string $class
	 * @return div
	 */
	function __construct($parent = 0, $id = "", $class = "") {
		element::__construct($parent, "", $class);
		$this->setid($id);
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		$this->setname('');
		return '<div'.element::output().'>'.container::output($pspace, $optin).'</div>';
	}
}

/**
 * Paragraph Container
 *
 * This is the standard layout container.  Use it to define a box of content,
 * and use a CSS class definition to define its layout properties.
 * @package html
 */
class p extends container {
	/**
	 * Constructor
	 *
	 * Create a new paragraph object.
	 *
	 * @param container $parent
	 * @param string $class
	 * @return p
	 */
	function __construct($parent = 0, $class = "") {
		element::__construct($parent, "", $class);
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		$this->setname('');
		return '<p'.element::output().'>'.container::output($pspace, $optin).'</p>';
	}
}

/**
 * Code Block Container
 *
 * This is a container for monospaced content like programming code.
 * @package html
 */
class codeblock extends container {
	/**
	 * Constructor
	 *
	 * @param container $parent
	 * @param string $class
	 * @return codeblock
	 */
	function __construct($parent = 0, $class = "") {
		element::__construct($parent, "", $class);
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		$this->setname('');
		return '<code'.element::output().'>'.container::output($pspace, $optin).'</code>';
	}
}

/**
 * Blockquote container
 *
 * Indents whatever's inside
 * @package html
 */
class blockquote extends container {
	/**
	 * Constructor
	 *
	 * Note that $name will be used as the DOM ID.  A blockquote never gets a
	 * "name" attribute, only "id".
	 *
	 * @param container $parent
	 * @param string $domid
	 * @param string $class
	 * @return blockquote
	 */
	function __construct($parent = 0, $domid = "", $class = "") {
		element::__construct($parent, "", $class);
		$this->setid($domid);
	}

	/**
	 * @access private
	 */
	public function output($pspace = '', $optin = FALSE) {
		return '<blockquote'.element::output().'>'.container::output($pspace, $optin).'</blockquote>';
	}
}

/**
 * Span container
 *
 * Does not break line
 * @package html
 */
class span extends container {
	/**
	 * Constructor
	 *
	 * Note that $name will be used as the DOM ID.  A span never gets a
	 * "name" attribute, only "id".
	 *
	 * @param container $parent
	 * @param string $domid
	 * @param string $class
	 * @return span
	 */
	function __construct($parent = 0, $domid = "", $class = "") {
		element::__construct($parent, "", $class);
		$this->setid($domid);
	}

	/**
	 * @access private
	 */
	public function output() {
		return '<span'.element::output().'>'.container::output().'</span>';
	}
}

/**
 * HTML Table
 *
 * A standard HTML table.  Use CSS to define a style.  Refrain from using
 * tables for layout, use divs instead.
 * @package html
 */
class table extends container {
	/**
	 * Constructor
	 *
	 * @param container $parent
	 * @param string $class
	 * @param string $name
	 * @return table
	 */
	function __construct($parent = 0, $class='', $id='') {
		element::__construct($parent, $id, $class);
	}

	/**
	 * Load an array of data into a table
	 *
	 * This method can easily translate a 2-d array, from a database call, for instance,
	 * into a table structure.
	 *
	 * It automatically sets appropriate CSS classes for the table elements to make it easy
	 * to style your data.  If $titlerow is TRUE, the first row will have the CSS class 'titlerow',
	 * while all other rows will be classed alternately as 'evenrow' and 'oddrow'.  This allows
	 * you to use two background colors for your data rows, which makes it much easier to read.
	 *
	 * Additionally, all TD elements will be classed based on the number of the column they're in,
	 * so 'col1', 'col2', 'col3', etc.  This makes it easy to configure columns as bold or right
	 * justified or whatever.  If $lastcol is TRUE, the final column will have the class 'lastcol',
	 * which is sometimes a more reliable way to write CSS (if you add columns to the data at a
	 * later date).
	 *
	 * Default behavior is to automatically create a title row from the column names returned
	 * from the database, and class the rightmost column as 'lastcol'.  However there are three
	 * flags to alter behavior:
	 * <pre>
	 * $titlerow = TRUE : This toggles whether or not you want loaddata to generate a title
	 *                    row.  There are two ways it can build it.  If you set $firstrow to
	 *                    FALSE, then it will use the associative array column names from the
	 *                    array itself.
	 * $firstrow = FALSE : If you set this and $titlerow to TRUE, the first row of the data
	 *                     you sent will be assumed to be (exactly) the title row.
	 * $lastcol = TRUE : When set to TRUE the CSS class of the final column's TD elements will
	 *                   be 'lastcol'.  When FALSE, it will be classed somthing like 'col4' if
	 *                   it's the fourth and final column.
	 * </pre>
	 *
	 * @param array $array
	 * @param bool $titlerow
	 * @param bool $firstrow
	 * @param bool $lastcol
	 * @return void
	 */
	public function loaddata($array, $titlerow = TRUE, $firstrow = FALSE, $lastcol = TRUE, $updatecols = array()) {
		if (empty($array)) return;

		if (empty($updatecols)) $updatecols = get_result_columns($array);
		if (!$this->getclass()) { $this->setclass("datatable"); }

		if ($array[0][0] || $firstrow) {
			$firstrow = TRUE;
			foreach ($updatecols as $key) {
				$titlecols[] = $array[0][$key];
			}
			array_shift($array);
		}

		if ($titlerow) {
			if (!$firstrow) $titlecols = $updatecols;
			$row = new row($this, "titlerow");
			$row->input_array($titlecols, '', $lastcol);
		}

		foreach ($array as $i => $rowarray) {
			$class = ($class == "oddrow" ? "evenrow" : "oddrow");
			$row = new row($this, $class);
			$row->input_array($rowarray, $updatecols, $lastcol);
		}
	}

	/**
	 * @access private
	 */
	protected function repair_table() {
		foreach ($this->children() as $i => $tr) {
			$width = $tr->width();
			if ($width != $maxwidth && $i > 0) $problem = TRUE;
			if ($width > $maxwidth) $maxwidth = $width;
		}
		if ($problem) {
			foreach ($this->children() as $tr) {
				$kids = $tr->children();
				if (!empty($kids)) {
					$width = $tr->width();
					if ($width < $maxwidth) {
						$kids = $tr->children();
						$cell = end($kids);
						$cell->setwidth($maxwidth - $width + $cell->width());
					}
				}
			}
		}
	}

	/**
	 * @access private
	 */
	public function output($pspace = '', $optin = FALSE) {
		return "<table" . element::output() . ">". container::output($pspace, $optin) ."</table>";
	}
}

/**
 * HTML Table Row
 *
 * This represents one horizontal row of a table.
 * @package html
 */
class row extends container {
	function __construct($parent = 0, $class = "") {
		element::__construct($parent, "", $class);
	}

	/**
	 * Quickly add data to a row
	 *
	 * This method provides an easy way to load database data into a row without
	 * laboriously creating all the TD elements.
	 *
	 * If you don't want to use all the columns in your data, you can send an array
	 * as the $updatecols parameter, this should be an array of associative array keys
	 * for the data you DO want to display.
	 *
	 * When $lastcol is set to TRUE the CSS class of the final column's TD element
	 * will be 'lastcol'.  When FALSE, it will be classed somthing like 'col4' if
	 * it's the fourth and final column.
	 *
	 * @param array $array
	 * @param array $updatecols
	 * @param bool $lastcol
	 */
	public function input_array($array, $updatecols = "", $lastcol = TRUE) {
		if (is_array($updatecols)) {
			foreach ($updatecols as $key) {
				$usearray[] = $array[$key];
			}
		} else $usearray = array_values($array);
		foreach ($usearray as $i => $cellval) {
			$col = $i + 1;
			$class = ($col == count($usearray) && $lastcol ? "lastcol" : "col$col");
			$cell = new cell($this, $class);
			if ($cellval instanceof element) $cell->addChild($cellval);
			elseif ($cellval instanceof DateTime) $cell->addChild($cellval->format('YmdHis'));
			else $cell->addText($cellval);
		}
	}

	/**
	 * Add a cell to your row
	 *
	 * A slightly quicker way to make a cell than creating a new TD object
	 * and putting data in it.  If you send a string, it will be treated as BBCode
	 * (see container::addBBCode()).  If you send a framework object (something descended
	 * from 'element') it will be placed inside the cell.
	 *
	 * @param mixed $contents
	 * @param string $class
	 * @return cell
	 */
	public function addCell($contents='', $class='') {
		$cell = new cell($this, $class);
		if ($contents instanceof element) $contents->setparent($cell);
		else $cell->addBBCode($contents);
		return $cell;
	}

	/**
	 * Add a cell to the header row that calls for a different sort for the table
	 *
	 * Use this method to quickly add cells to your header row that are actually links
	 * requesting different sort parameters.  Your code will have to pick up on the request
	 * and pass it on to the database query.
	 *
	 * By default the sort parameters will be added as ?sort=columnname&desc=1
	 *
	 * If you don't specify a column, $label will be used by default.
	 *
	 * If you don't specify whether the column is descending, it is assumed to be ascending
	 * by default.
	 *
	 * @param string $label
	 * @param string $column
	 * @param string $class
	 * @param bool $desc
	 * @param string $input_name
	 * @return cell
	 */
	public function addSortable($label, $column = '', $class = '', $desc = FALSE, $input_name = 'sort') {
		if (!$column) $column = $label;
		if ($_REQUEST[$input_name] == $column && ($_REQUEST['desc'] == $desc)) $mydesc = !$desc;
		else $mydesc = $desc;
		$cell = new cell($this, $class);
		$lnk = new link($cell, '', $label, array($input_name=>$column, 'desc'=>$mydesc) + doc::create_mimic());
		return $cell;
	}

	/**
	 * Return the number of cells currently in this row
	 *
	 * Quickly find the width in number-of-cells of this row.  If some cells are set
	 * to have a bigger colspan than 1, they'll be counted as more than 1.
	 *
	 * NOTE: this will fail if an above row has a TD with a rowspan greater than 1,
	 * needs further work.
	 *
	 * @return int
	 */
	public function width() {
		foreach ($this->children() as $child) {
			if ($child instanceof cell) $width += $child->width();
		}
		return $width;
	}

	public function output($pspace = '', $optin=FALSE) {
		return "<tr" . element::output() . ">".container::output($pspace, $optin) ."</tr>";
	}
}

/**
 * HTML Table Cell
 *
 * This represents one cell of a row of a table.
 * @package html
 */
class cell extends container {
	private $colspace = 1;
	private $rowspace = 1;
	private $header;

	/**
	 * Constructor
	 *
	 * @param container $parent
	 * @param string $class
	 * @param int $width
	 * @param int $height
	 * @return cell
	 */
	function __construct($parent = 0, $class = "", $width = 1, $height = 1) {
		element::__construct($parent, "", $class);
		$this->setwidth($width);
		$this->setheight($height);
	}

	/**
	 * Get the column span of the cell
	 *
	 * This is the "colspan" attribute, in HTML terms.
	 * @return int
	 */
	public function width() {
		return ($this->colspace ? $this->colspace : 1);
	}

	/**
	 * Set the column span of the cell
	 *
	 * Use this method to make the cell eat up X cells to the right of it.
	 * @param int $width
	 * @return void
	 */
	public function setwidth($width) {
		$this->colspace = $width;
	}

	/**
	 * Get the row height of the cell
	 *
	 * This is the "rowspan" attribute, in HTML terms
	 * @return int
	 */
	public function height() {
		return ($this->rowspace ? $this->rowspace : 1);
	}

	/**
	 * Set the row height of the cell
	 *
	 * Use this method to make the cell eat up X cells beneath it.
	 *
	 * @param int $height
	 * @return void
	 */
	public function setheight($height) {
		$this->rowspace = $height;
	}

	/**
	 * Set this cell as a table header
	 *
	 * Using this method will make the cell print out a 'th' element instead
	 * of 'td'.
	 *
	 * @param bool $flag
	 * @return void
	 */
	public function header($flag = TRUE) {
		$this->header = $flag;
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		$contents = container::output($pspace, $optin);
		$td = ($this->header ? 'th' : 'td');
		if (trim($contents) == '' && $contents != '0') $contents = '&nbsp;';
		if ($this->colspace > 1) $colspace = ' colspan="'.$this->colspace.'"';
		if ($this->rowspace > 1) $rowspace = ' rowspan="'.$this->rowspace.'"';
		return '<'.$td. element::output() . $colspace.$rowspace.'>'. $contents ."</".$td.">";
	}
}

/**
 * HTML List
 *
 * This is used for making bullet lists or ordered lists, using
 * the html tags "ul", "li", and "ol".
 *
 * Note that each call to {@link container::addText()} or {@link container::addBBCode()} will
 * create a new list item.  Also, anything you create with this as the parent will first
 * be placed into a new list item.  So be careful not to try to construct long
 * items with multiple calls.  Instead, create a {@link listitem} and
 * then fill it with whatever you want.
 *
 * @package html
 */
class htmllist extends container {
	private $ordered;
	function __construct($parent, $class='') {
		element::__construct($parent, '', $class);
	}

	/**
	 * Add a List Item
	 *
	 * Use this to add a anything to the list. Essentially it just puts
	 * whatever you give it inside a {@link listitem} object, whether
	 * you give it text or a framework object.
	 *
	 * Returns the {@link listitem} object so you can manipulate it further.
	 *
	 * @param mixed $object
	 * @param string $class
	 * @return listitem
	 */
	public function addItem($object, $class='') {
		$li = new listitem($this, $class);
		if ($object instanceof element)	$li->addChild($object, $class);
		elseif (is_string($object)) $li->addText($object);
		return $li;
	}

	/**
	 * @access private
	 */
	public function addChild($object) {
		if ($object instanceof listitem) {
			return parent::addChild($object);
		} else {
			$lst = new listitem($this);
			return $lst->addChild($object);
		}
	}

	/**
	 * overload the container method
	 * @access private
	 */
	public function addBBCode($object, $allowobjects = TRUE) {
		$item = new listitem($this);
		$item->addBBCode($object, $allowobjects);
	}

	/**
	 * overload the container method
	 * @access private
	 */
	public function addWikiPara($para) {
		$item = new listitem($this);
		$item->addWikiPara($para);
	}

	/**
	 * overload the container method
	 * @access private
	 */
	public function addText($text, $class='') {
		$item = new listitem($this, $class);
		$item->addText(rtrim($text));
	}

	/**
	 * Create ordered list
	 *
	 * Use this method to set the list to an ordered list, so
	 * line items will be numbered instead of have bullet points.
	 *
	 * @param bool $flag
	 * @return void
	 */
	public function ordered($flag = TRUE) {
		$this->ordered = $flag;
	}

	/**
	 * Determine whether this is an ordered list
	 *
	 * @return bool
	 */
	public function isordered() {
		return $this->ordered;
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		// xhtml does not allow empty lists, so if we don't
		// have any child content we should just skip output
		// entirely
		if ($this->children()) {
			$ele = $this->ordered ? 'ol' : 'ul';
			return '<'.$ele.element::output().'>'.container::output($pspace, $optin).'</'.$ele.'>';
		} else {
			return '';
		}
	}
}

/**
 * Definition List
 *
 * This is used for making dictionary lists with a series of titles and their descriptions.
 *
 * Note that each call to {@link container::addText()} or {@link container::addBBCode()} will
 * create a new list item.  Also, anything you create with this as the parent will first
 * be placed into a new list item.  So be careful not to try to construct long
 * items with multiple calls.  Instead, create a {@link listitem} and
 * then fill it with whatever you want.
 *
 * @package html
 */
class deflist extends container {
	function __construct($parent, $class='') {
		element::__construct($parent, '', $class);
	}

	/**
	 * Add a List Item
	 *
	 * Use this to add a anything to the list. Essentially it just puts
	 * whatever you give it inside a {@link listitem} object, whether
	 * you give it text or a framework object.
	 *
	 * Returns the {@link listitem} object so you can manipulate it further.
	 *
	 * @param mixed $object
	 * @param string $class
	 * @return listitem
	 */
	public function addItem($object, $class='') {
		$li = new listitem($this, $class);
		if ($object instanceof element)	$li->addChild($object, $class);
		elseif (is_string($object)) $li->addText($object);
		return $li;
	}

	/**
	 * @access private
	 */
	public function addChild($object) {
		if ($object instanceof listitem) {
			return parent::addChild($object);
		} else {
			$lst = new listitem($this);
			return $lst->addChild($object);
		}
	}

	/**
	 * overload the container method
	 * @access private
	 */
	public function addBBCode($object, $allowobjects = TRUE) {
		$item = new listitem($this);
		$item->addBBCode($object, $allowobjects);
	}

	/**
	 * overload the container method
	 * @access private
	 */
	public function addWikiPara($para) {
		$item = new listitem($this);
		$item->addWikiPara($para);
	}

	/**
	 * overload the container method
	 * @access private
	 */
	public function addText($text, $class='') {
		$item = new listitem($this, $class);
		$item->addText(rtrim($text));
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		$ele = 'dl';
		foreach ($this->children() as $child) {
			$cele = ($cele != 'dt' ? 'dt' : 'dd');
			$child->defitem($cele);
		}
		return '<'.$ele.element::output().'>'.container::output($pspace, $optin).'</'.$ele.'>';
	}
}

/**
 * List Item
 *
 * A container representing the "li" tag.
 *
 * @package html
 */
class listitem extends container {
	protected $defitem;

	function __construct($parent, $class='') {
		element::__construct($parent, '', $class);
	}

	/**
	 * @access private
	 */
	public function defitem($type) {
		$this->defitem = $type;
	}

	/**
	 * @access private
	 */
	public function output($pspace='', $optin=FALSE) {
		if ($this->defitem) $ele = $this->defitem;
		else $ele = 'li';
		return '<'.$ele.element::output().'>'.container::output($pspace, $optin).'</'.$ele.'>';
	}
}

/**
 * Anchor
 *
 * This is a secondary use of the "a" tag, which I have decided
 * to break out into an individual entity of its own.  Just give it
 * a name.
 *
 * @package html
 */
class anchor extends element {
	function __construct($parent, $name) {
		element::__construct($parent, $name, '');
	}
	/**
	 * @access private
	 */
	function output() {
		return '<a'.element::output().'></a>';
	}
}

/**
 * Clear Both element
 *
 * This will create a div with its CSS style set to clear: both, it's
 * useful for making sure you don't have issues with float: left
 *
 * @package html
 */
class clear extends element {
	function __construct($parent) {
		element::__construct($parent);
	}
	/**
	 * @access private
	 */
	function output() {
		return '<div class="clearbothdiv"></div>';
	}
}
?>
