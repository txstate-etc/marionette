<?php
/**
 * @package htmlsupport
 * @author Nick Wing
 */

/**
 * Standard HTML Element
 * 
 * This is the master class for any HTML entity in the framework.  It's
 * responsible for the id, name, class, and title attributes.  It handles
 * all javascript events that are attached to objects such as onmouseover,
 * onclick, etc.  It's also the storage location for parentage information,
 * as every element will have a parent.
 * 
 * Its output is just a set of attributes, it should be placed inside
 * a tag definition thusly:
 * 
 * <pre>return '&lt;a href="blah.php"'.element::output().'&gt;blah&lt;/a&gt;';</pre>
 * 
 * @package htmlsupport
 */
abstract class element {
	private $name;
	private $domid;
	private $classes = array();
	private $title;
	private $olib = array();
	private $js = array();
	public $parent;
	public $ensureid = FALSE;
	
	/**
	 * @access private
	 */
	function __construct($parent = 0, $id = "", $class = "") {
		$this->setparent($parent);
		if ($id) $this->setid($id);
		if ($class) $this->setclass($class);
		return;
	}
	
	/**
	 * Return element's CSS class as string
	 *
	 * @return string
	 */
	public function getclass() { return implode(' ', $this->classes); }
	
	/**
	 * Return element's CSS class as array
	 *
	 * @return array
	 */
	public function getclasses() { return $this->classes; }
	
	/**
	 * Set element's CSS class(es)
	 *
	 * Also accepts an array for setting multiple classes at once.  You may want to consider
	 * only using {@link element::addclass()} so that you don't overwrite classes assigned by 
	 * other pieces of code.
	 *
	 * @param mixed $class
	 * @return void
	 */
	public function setclass($class) {
		$this->classes = (array) $class;
	}
	
	/**
	 * Add a CSS class to an element
	 *
	 * This adds a CSS class without overwriting any other classes
	 * already set for the element.
	 * 
	 * Also accepts an array to add multiple classes at once.
	 *
	 * @param mixed $class
	 * @return void
	 */
	public function addclass($class) {
		$this->classes = array_merge($this->classes, array_diff((array) $class, $this->classes));
	}
	
	/**
	 * Remove a CSS class from an element
	 *
	 * Also accepts an array to remove multiple classes at once.
	 *
	 * @param mixed $class
	 * @return void
	 */
	public function removeclass($class) {
		$this->classes = array_diff($this->classes, (array) $class);
	}
	
	/**
	 * Return element's DOM identifier
	 *
	 * This is the DOM identifier, which can be used with javascript's
	 * GetElementById() function to refer to the element.  It's placed
	 * in the 'id' attribute.
	 * 
	 * @return string
	 */
	public function getid() { return $this->domid; }
	
	/**
	 * Set element's DOM identifier
	 *
	 * This is the DOM identifier, which can be used with javascript's
	 * GetElementById() function to refer to the element, or referred to in
	 * CSS with a pound (#), e.g. #myid { display: block }.  It's placed
	 * in the 'id' attribute.
	 *
	 * 'id' cannot begin with a number and ideally contains only alphanumeric
	 * characters.
	 * 
	 * @return string
	 */
	public function setid($domid = "") {
		$this->domid = $domid;
	}
	
	/**
	 * Return element's name
	 *
	 * In HTML, this is the 'name' attribute.
	 *
	 * DEPRECATED
	 *
	 * @return string
	 * @access private
	 */
	public function getname() { return $this->name; }
	
	/**
	 * Set element's name
	 *
	 * In HTML this is the 'name' attribute.  Largely useful for
	 * link anchors and form elements.
	 *
	 * DEPRECATED
	 *
	 * @param string $name
	 * @return void
	 * @access private
	 */
	public function setname($name = "") {
		$this->name = $name;
	}
	
	/**
	 * Return element's parent
	 *
	 * This will be an object descended from 'container'
	 * that contains the element.
	 *
	 * @return container
	 */
	public function getparent() {
		return $this->parent;
	}
	
	/**
	 * Set a parent for the element
	 *
	 * Normally parents are assigned in the constructor, but this
	 * method can also be used to set a new parent.
	 *
	 * An element may only have ONE parent.  Any existing parent will 
	 * be replaced by the new parent, and the element will be removed from
	 * the old parents' children array.
	 *
	 * @param container $parent
	 * @return void
	 */
	public function setparent($parent) {
		if (!($parent instanceof container)) {
			if ($this->parent instanceof container) $this->parent->removeChild($this);
			return;
		}
		if ($parent === $this->parent) return;
		$parent->addChild($this);
		return;
	}
	
	/**
	 * Return element's title
	 *
	 * This corresponds to HTML's 'title' attribute.  It will often be
	 * used (by certain browsers) to generate a tooltip when the mouse is 
	 * left hovering over the element for too long.
	 *
	 * @return string
	 */
	public function gettitle() {
		return $this->title;
	}

	/**
	 * Set element's title
	 *
	 * This corresponds to HTML's 'title' attribute.  It will often be
	 * used (by certain browsers) to generate a tooltip when the mouse is 
	 * left hovering over the element for too long.
	 *
	 * @param string
	 * @return void
	 */
	public function settitle($title) {
		$title = trim(strip_tags(preg_replace('/\[.*?\]/', '', $title)));
		$this->title = $title;
	}

	/**
	 * Add a javascript event
	 *
	 * Use this method to make the element listen for a specific
	 * Javascript event and take action accordingly.  A semicolon will
	 * be added if needed.
	 *
	 * @param string $event
	 * @param string $js
	 * @return void
	 */
	public function addJS($event, $js) {
		$event = strtolower($event);  // for xhtml compliance
		$js = trim($js);
		$end = substr($js, -1);
		if ($this->js[$event]) $this->js[$event] .= ' ';
		$this->js[$event] .= $js . ($end == ';' || $end == '}' ? "" : ";");
	}
	
			
	/**
	 * Add Javascript Tooltip
	 *
	 * This method interacts with the Overlib javascript
	 * library to pop up with a display box whenever the user mouses over
	 * the affected element.
	 *
	 * This creates a context-sensitive "tooltip" that can be used to provide
	 * detailed information or extra images that would not normally fit on the page.
	 *
	 * The $outer and $inner variables allow you to specify a CSS class for the outer
	 * box and the inner box, respectively.  This allows you to fully customize the
	 * appearance of the tooltip.
	 *
	 * $contents may be either a block of text (BBCode, in fact), or a valid element
	 * from this framework.
	 *
	 * Currently, nothing fancy is supported for the title, just plain text.
	 *
	 * @param mixed $contents
	 * @param string $title
	 * @param string $outer
	 * @param string $inner
	 * @return void
	 */
	public function overlib($contents, $title = '', $outer = 'olib_trow', $inner = 'olib_main') {
		static $once = FALSE;
		if (!$once) {
			$doc = doc::getdoc();
			$doc->includeJS('@overlib.js');
			$once = TRUE;
		}
		$this->olib['contents'] = $contents;
		$this->olib['title'] = $title;
		$this->olib['outer'] = $outer;
		$this->olib['inner'] = $inner;
	}
	
	/**
	 * Add Javascript Tooltip
	 *
	 * This is an alias for the {@link overlib()} method. It uses different
	 * default CSS classes.
	 *
	 * @param mixed $contents
	 * @param string $title
	 * @param string $outer
	 * @param string $inner
	 * @return void
	 */
	public function tooltip($contents, $title = '', $outer = 'tip_trow', $inner = 'tip_main') {
		$this->overlib($contents, $title, $outer, $inner);
	}
	
	/**
	 * Guarantee element has an ID attribute
	 *
	 * Use this method to give an element a random string for an ID unless the user
	 * sets one manually.  After element::finalize() has run, you'll be able to use
	 * element::getid() to retrieve it and use it in, for example, javascript blurbs.
	 *
	 * It's intended for internal use.  If the user wants an ID, he should set one himself.
	 * @access private
	 */
	public function ensureid() {
		return $this->ensureid = TRUE;
	}
	
	/**
	 * @access private
	 */
	public function output() {
	
		// deal with overlib/tooltip generation
		if (!empty($this->olib)) {
			$contents = $this->olib['contents'];
			$title = $this->olib['title'];
			$inner = $this->olib['inner'];
			$outer = $this->olib['outer'];
			
			if ($title) $title = ", CAPTION, '".addslashes($title)."'";
			if ($contents instanceof element) {
				$cont = $contents;
			} else {
				$cont = new container;
				$cont->addBBCode($contents);
			}
			$this->addJS('onmouseover', "return overlib('".addslashes($cont->output())."'".$title.", FGCLASS, '$inner', BGCLASS, '$outer');");
			$this->addJS('onmouseout', "return nd();");
		}
		
		// common html attributes
		if ($this->getclass()) $class = ' class="' . htmlspecialchars($this->getclass()) . '"';
		if ($this->getname()) $name = ' name="' . htmlspecialchars($this->getname()) . '"';
		if ($this->getid()) $id = ' id="' . htmlspecialchars($this->getid()) . '"';
		
		// deal with javascript events
		foreach ($this->js as $event => $js) {
			$outjs .= ' ' . $event . '="' . htmlspecialchars($js) . '"';
		}
		
		// done
		return $class . $name . $id . $outjs;
	}
	
	/**
	 * @access private
	 */
	public function finalize() {
		if ($this->ensureid && !$this->getid()) $this->setid(generatestring(4, 'alpha'));
	}
}

/**
 * Generic Box Element
 *
 * This is an ancestor class for any element that has a
 * rectangular display area defined by HTML width and
 * height attributes.  It provides methods for defining
 * and outputting each attribute.
 *
 * It is meant mainly for images and media objects like
 * flash movies or java applets.  Containers should generally
 * have their size set by CSS.
 * @package htmlsupport
 */
abstract class box extends element {
	private $height;
	private $width;
	
	/**
	 * Set element height
	 *
	 * Use this method to set the height, in pixels, of the element.
	 */
	public function setheight($height) {
		$this->height = $height;
	}
	/**
	 * Set element width
	 *
	 * Use this method to set the width, in pixels, of the element.
	 */
	public function setwidth($width) {
		if ($width) $this->width = $width;
	}
	
	/**
	 * Get element height
	 *
	 * Use this method to find the height in pixels of a box element.
	 *
	 * @return int
	 */
	public function getheight() {
		return $this->height;
	}
	
	/**
	 * Get element width
	 *
	 * Use this method to find the width in pixels of a box element.
	 *
	 * @return int
	 */
	public function getwidth() {
		return $this->width;
	}
	/**
	 * Set element size
	 *
	 * Use this method to set both the width and height of the element
	 * at once.
	 *
	 * @param integer $width
	 * @param integer $height
	 * @return void
	 */
	public function setsize($width, $height) {
		if ($height) $this->setheight($height);
		if ($width) $this->setwidth($width);
	}
	
	/**
	 * @access private
	 */
	public function output() {
		if (intval($this->height)) $height = ' height="' . intval($this->height) . '"';
		if (intval($this->width)) $width = ' width="' . intval($this->width) . '"';
		return element::output() . $height . $width;
	}
}

/**
 * Generic HTML container
 *
 * This should be an ancestor for any HTML object that can have other objects inside it.
 * @package htmlsupport
 */
class container extends element {
	protected $children = array();
	
	/**
	 * Add content to the container
	 *
	 * Use this method to place an object inside of the container.
	 * Order is important, first child in is the first child to be
	 * output.
	 *
	 * @param element $object
	 */
	public function addChild($object) {
		if ($object instanceof formelement) {
			$form = $this->findform();
			if ($form instanceof form) {
				if ($object instanceof filebrowser) $form->accept_upload();
				if ($form->preloadspecified) $object->nopreload($form->preload);
			}
		}

		if ($object instanceof element) {
			$this->children[] = $object;
			if ($object->parent instanceof container) $object->parent->removeChild($object);
			$object->parent = $this;
		} elseif (!is_object($object) && !is_array($object)) {
			$this->addText($object);
		} else {
			return FALSE;
		}
		return TRUE;	
	}
	
	/**
	 * Find nearest parent of type 'form'
	 * 
	 * This method is primarily for internal use, objects of type
	 * 'formelement' depend on it to find a handle for the form that
	 * they are part of.
	 * 
	 * Returns FALSE if no form could be found.
	 *
	 * @return form
	 */
	public function findform() {
		if ($this instanceof form) return $this;
		$parent = $this->getparent();
		if ($parent instanceof container) return $parent->findform();
		return FALSE;
	}
	
	/**
	 * Remove a single child
	 *
	 * This method will remove a child from the object.  You must have
	 * a valid reference to the child object; an identical but separate instance
	 * will not work - nothing will be removed.
	 *
	 * @param element $object
	 */
	public function removeChild($object) {
		$return = array();
		foreach ($this->children as $child) {
			if ($child !== $object) {
				$return[] = $child;
			}
		}
		unset($object->parent);
		$this->children = $return;
		return;
	}
	
	/**
	 * Empty the container
	 * 
	 * This method will completely empty out the contents of the container.
	 */
	public function clearChildren() {
		foreach ($this->children as $child) {
			unset($child->parent);
		}
		$this->children = array();
		return;
	}
	
	/**
	 * Add inline text to the container
	 *
	 * You may use this method with any container to add plain text
	 * to it.  See {@link text} for more information.
	 * 
	 * You may set the CSS class of the entire text string by specifying the
	 * $class parameter.  The text will be placed inside a span element with the
	 * given class.
	 *
	 * If you set $strict to TRUE, all HTML will be completely turned off and the
	 * string will only be processed for spacing and line breaks.  It will appear
	 * strictly as it appeared in the textarea.
	 * 
	 * @param string $text
	 * @param string $class
	 * @param bool $strict
	 */
	public function addText($text, $class = "", $strict = FALSE) {
		new text($this, $text, $class, $strict);
	}
	
	public function addStrict($text, $class = '') {
		$this->addText($text, $class, TRUE);
	}
	
	/**
	 * Add Raw XHTML
	 *
	 * This method may be used as a last resort to add html directly inside a container.
	 * 
	 * Be aware that you are giving up many of the features of PHPWebObjects if you use
	 * this function.  This includes things like link rewriting, guaranteed xhtml validation,
	 * etc.
	 *
	 * @param string $xhtml
	 * @return void
	 */
	public function addXHTML($xhtml) {
		new rawxhtml($this, $xhtml);
		return;
	}

	/**
	 * Add line breaks
	 *
	 * Convenience function to add line breaks in a container.  $lines specifies the
	 * number of break tags to insert.
	 *
	 * @param integer $lines
	 */
	public function br($lines = 1) {
		for ($i = 0; $i < $lines; $i++) $this->addText("\n");
	}
	
	/**
	 * Add clearing line break
	 *
	 * Sometimes when you're floating things you'll end up with lots of wierd issues.  Use this
	 * method to add a clearing line break to make absolutely sure you're on a new line.
	 */
	public function clear() {
		new clear($this);
	}

	/**
	 * @access private
	 */
	private static function getBBCodeTags() {
		static $ret = array(
			'url' => array(
				'widget' => 'bb_link',
				'allowable' => array('img', 'span', 'class', 'color', 'size', 'big', 'small', 'i', 'b', 'u', 's', 'strong', 'em')
			),
			'img' => array(
				'widget' => 'bb_img',
				'noparse' => 'true',
				'unclosed' => TRUE
			),
			'object' => array(
				'widget' => 'bb_object',
				'noparse' => 'true',
				'unclosed' => TRUE
			),
			'class' => array(
				'widget' => 'bb_span'
			),
			'span' => array(
				'widget' => 'bb_span'
			),
			'code' => array(
				'widget' => 'bb_code',
				'noparse' => 'true'
			),
			'quote' => array(
				'widget' => 'bb_quote'
			),
			'color' => array(
				'widget' => 'bb_span'
			),
			'size' => array(
				'widget' => 'bb_span'
			),
			'small' => array(
				'widget' => 'bb_span'
			),
			'big' => array(
				'widget' => 'bb_span'
			),
			'hr' => array(
				'widget' => 'bb_divider',
				'unclosed' => TRUE
			),
			'clear' => array(
				'widget' => 'bb_clear',
				'unclosed' => TRUE
			),
			'b' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'i' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'u' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			's' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'strong' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'em' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'h1' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'h2' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'h3' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'h4' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'h5' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
			'h6' => array(
				'autoclose' => TRUE,
				'widget' => 'bb_text'
			),
		);
		return $ret;
	}
	
	public function addBBCode($text, $options = array()) {
		// make sure our tag widget library is loaded
		global $cfg;
		require_once(endslash($cfg['library_root']).'bbcode.php');
		
		// get the list of recognized tags
		$tags = self::getBBCodeTags();
		
		// if this is the top-level call to addBBCode, we'll make a span
		// to enclose the whole thing
		if (!$options['recur']) {
			$parent = new span($this, '', 'bbcodeblock');
		}
		else $parent = $this;
		
		$offset = 0;
		$current_count = 0;
		$stack = array();
		while (preg_match('/\[(\/?)(\w+)(.*?)(\/?)\s*(\])/', $text, $match, PREG_OFFSET_CAPTURE, $offset)) {
			// grab the vital statistics for this tag
			$fulltag = $match[0][0];
			$before = substr($text, $offset, $match[0][1]-$offset);
			$close = $match[1][0];
			$tag = strtolower($match[2][0]);
			$attrstr = $match[3][0];
			$closed = $match[4][0];
			$offset = $match[5][1]+1;
			// parse the attributes
			if (substr($attrstr, 0, 1) == '=') $attrstr = $tag.$attrstr;
			$pairs = $this->analyzePairs($attrstr);
			
			if (!$current_tag) {
				// root level 
				$parent->addStrict($before);
				if (!$tags[$tag] || $tags[$current_tag]['noparse'] || ($options['allowable'] && !in_array($tag, $options['allowable']))) {
					// if it's not a valid tag, we're done 
					$parent->addStrict($fulltag); continue; 
				}
				
				if ($close) $parent->addStrict($fulltag); // in case we encounter a close tag while nothing's open
				elseif ($closed) { // deal with self-closed tags
					$widget = new $tags[$tag]['widget']($parent, array(
						'tag' => $tag,
						'attr' => $pairs
					));
				} else { // normal case, open a tag and start collecting contents
					$current_tag = $tag;
					$current_pairs = $pairs;
					$current_fulltag = $fulltag;
					$current_content = '';
				}
			} else {
				// root + 1, we just want to collect content until we reach
				// our end tag, then we'll send the content to the tag's widget
				// recursively
				$current_content .= $before;
				if (!$tags[$tag]) { $current_content .= $fulltag; continue; }
				
				// let's find out if we want to close out the tag
				$closing = FALSE;
				if ($tags[$current_tag]['unclosed']) {
					// the current tag is eligible to skip its closing tag, so no matter what tag
					// we've got here, we're closing
					$closing = TRUE;
					// if this is an opening tag, we've got to close the current tag AND open a new one
					$reopen = !($tag == $current_tag && $close);
				} elseif ($tag == $current_tag) { // if tags don't match, just keep on trucking
					if ($close) { // is it a closing tag? (e.g. [/url])
						if ($current_count) { // if this is >1 then we had nested tags and we're not done yet
							$current_count--;
						} else { // if $current_count == 0 then this is a proper closing flag
							$closing = TRUE;
						}
					} else { // if this is an opening tag and matches $current_tag, then we have nesting
							 // and need to skip the next closing tag we find
						$current_count++;
					}
				}
				
				if ($closing) {
					$widget = new $tags[$current_tag]['widget']($parent, array(
						'tag' => $current_tag,
						'attr' => $current_pairs
					));
					if ($close) {
						if ($tags[$current_tag]['noparse']) $widget->addStrict($current_content, '');
						else $widget->addBBCode($current_content, array(
							'recur'=>TRUE, 
							'allowable'=> array_intersect((array) $options['allowable'], (array) $tags[$current_tag]['allowable']) || (array) $tags[$current_tag]['allowable']
						));
					}
					else $parent->addStrict($current_content);
					$current_tag = '';
					
					if ($reopen) { // this branch handles cases where an opening tag follows a tag
								   // that is eligible to be left un-closed
						$current_tag = $tag;
						$current_pairs = $pairs;
						$current_fulltag = $fulltag;
						$current_content = '';
					}
				} else {
					$current_content .= $fulltag;
				}
			}
		}
		// if we have an open tag at the end, we'll have to clean up after it
		if ($current_tag) {
			if ($tags[$current_tag]['unclosed'] || $tags[$current_tag]['autoclose']) {
				$widget = new $tags[$current_tag]['widget']($parent, array(
					'tag' => $current_tag,
					'attr' => $current_pairs
				));
				if ($current_content)
					$widget->addBBCode($current_content, array(
						'recur'=>TRUE,
						'allowable'=> array_intersect((array) $options['allowable'], (array) $tags[$current_tag]['allowable']) || (array) $tags[$current_tag]['allowable']
					));
			} else {
				$parent->addStrict($current_fulltag,'');
				$parent->addBBCode($current_content, array(
					'recur'=>TRUE, 
					'allowable'=> (array) $options['allowable']
				));
			}
		} 
		$parent->addStrict(substr($text, $offset));
	}
		
	/**
	 * Add richly formatted text to the container
	 *
	 * This method parses a piece of text conforming to the txt2tags standard into objects, and places
	 * the result inside your container.
	 *
	 * @param string $text
	 * @return void
	 */
	public function addWikiMarkup($text) {
		$lines = explode("\r\n", $text);
		$superparent = new div($this, '', 'wikimarkup');
		$parent = $superparent;
		$verbatim = false;
		foreach ($lines as $line) {
			// get a trimmed version of the line to use
			$trimmed = trim($line);
			// get the first character of the line
			$firstchar = substr($trimmed, 0, 1);
		
			// Are we going to break our paragraph on this line?
			if (!$verbatim && $paragraph && in_array($firstchar, array('+', '*', '|', '`', '>', '-', '=', ':', '')) || $definition_list || $current_list_depth || $table_open) {
				if (!($definition_list || $current_list_depth || $table_open)) $p = new p($parent);
				else $p = $parent;
				$p->addWikiPara($paragraph);
				$paragraph = '';
			}
			
			// close out definition list
			if ($definition_list > 1 && $firstchar != ':') { $parent = $parent->getparent(); $definition_list = 0; }
			elseif ($definition_list && $firstchar != ':') { $definition_list = 2; }
			
			// close out list(s)
			if ($current_list_depth && $firstchar != '*' && $firstchar != '+') { 
				for ($i = 0; $i < $current_list_depth; $i++) {
					$parent = $parent->getparent()->getparent();
				}
				$current_list_depth = 0;
			}
			// close out table
			if ($table_open && $firstchar != '|') { 
				$parent = $parent->getparent(); 
				$table_open = 0;
				
				// let's try to figure out what alignment is
				// intended for each column
				foreach($avglen as $i => $col) {
					$avgl = array_sum($col['ltrim']) / count($col['ltrim']);
					$avgr = array_sum($col['rtrim']) / count($col['rtrim']);
					if ($avgr - $avgl > 0.5) $align = 'right';
					elseif ($avgl - $avgr > 0.5) $align = 'left';
					else $align = 'center';
					doc::getdoc()->addCSS('#'.$table_id.' .col'.$i.' { text-align: '.$align.' }');
				}
				// reset the tracking array
				$avglen = array();
			}
		
			// Ignore comment lines
			if ($firstchar == '%') { /* do nothing */ }
			
			// Verbatim Blocks
			elseif ($trimmed == '````') {  // open a codeblock for the verbatim section
				if (!$verbatim) $parent = new codeblock($parent);
				else $parent = $parent->getparent();
				$verbatim = !$verbatim;
			}
			elseif ($trimmed == '```') { // just start a verbatim section
				if (!$verbatim) $parent = new div($parent, '', 'verbatim');
				else $parent = $parent->getparent();
				$verbatim = !$verbatim;
			}
			elseif (substr($trimmed, 0, 4) == '````') { // single-line verbatim with codeblock
				// single line verbatim with monospace
				$codeblock = new codeblock($parent);
				$codeblock->addText(trim(substr($trimmed, 3)));
			}
			elseif (substr($trimmed, 0, 3) == '```') { // single line verbatim
				// single line verbatim
				$parent->addText(trim(substr($trimmed, 3))."\n");
			}
			elseif ($verbatim) {
				$parent->addText($line."\n");
			}
			
			// Blank Line - print current paragraph with double line breaks
			elseif (!$trimmed) {
				//$parent->br();
				// close out any multi-line objects
				$parent = $superparent;
				$current_blockquote_depth = 0;
			}
			
			// Separator Lines
			elseif (substr($trimmed, 0, 10) == '----------' &&  !$multiline) {
				$hr = new divider($parent);
				$hr->noshade();
			} elseif (substr($trimmed, 0, 10) == '==========' && !$multiline) {
				$hr = new divider($parent);
				$hr->setsize(5);
				$hr->noshade();
			}
			
			// Headers... == MY HEADER ==
			elseif (preg_match('/^\s*(=+)\s*(.*?)\s*(\1)\s*$/', $line, $match)) {
				$header_tag = 'h'.strlen($match[1]);
				$parent->addText('<'.$header_tag.'>'.$match[2].'</'.$header_tag.'>');
			}
			
			// Numbered Headers... ++ NUMBERED HEADER ++
			elseif (preg_match('/^\s*(\++)\s*(.*?)\s*(\1)\s*$/', $line, $match)) {
				$level = strlen($match[1]);
				$header_tag = 'h'.$level;
				$numeric_tag = '';
				$new = array();
				for ($i = 0; $i < $level; $i++) {
					if ($i == $level-1) $numbered_headers[$i]++;
					$numeric_tag .= $numbered_headers[$i].'.';
					$new[] = $numbered_headers[$i];
				}
				$numbered_headers = $new;
				
				$parent->addText('<'.$header_tag.'>'.$numeric_tag.' '.$match[2].'</'.$header_tag.'>');
			}
			
			// Lists
			elseif ($firstchar == '+' || $firstchar == '*') {
				// how deep of a list are they requesting?
				preg_match('/^(\+|\*)+/', $trimmed, $match);
				$depth = strlen($match[0]);
				
				// are we opening a deeper list?
				if ($depth > $current_list_depth) {
					for ($i = $current_list_depth; $i < $depth; $i++) {
						$parent = new htmllist($parent);
						if ($firstchar == '+') $parent->ordered();
						$parent = new listitem($parent);
					}
				}
				// are we closing a list?
				elseif ($depth <= $current_list_depth) {
					$parent = $parent->getparent();
					for($i = $depth; $i < $current_list_depth; $i++) {
						$parent = $parent->getparent();
						$parent = $parent->getparent();
					}
					if ($parent->isordered() == ($firstchar != '+')) {
							$parent = $parent->getparent();
							$parent = new htmllist($parent);
							if ($firstchar == '+') $parent->ordered();
					}
					$parent = new listitem($parent);
				}
								
				// finally we can add the text
				$paragraph .= trim(substr($trimmed, $depth)).' ';
				$current_list_depth = $depth;
			}
			
			// Blockquotes
			elseif ($firstchar == '>') {
				// how deep of a blockquote are they requesting?
				preg_match('/^(>\s*)+/', $trimmed, $match);
				$depth = strlen(preg_replace('/\s/', '', $match[0]));
				
				// are we opening a deeper blockquote?
				if ($depth > $current_blockquote_depth) {
					for ($i = $current_blockquote_depth; $i < $depth; $i++) {
						$parent = new blockquote($parent);
					}
				}
				// are we closing a blockquote?
				elseif ($depth < $current_blockquote_depth) {
					for($i = $depth; $i < $current_blockquote_depth; $i++) {
						$parent = $parent->getparent();
					}
				}
				
				$paragraph .= trim(substr($trimmed, 1)).' ';
				$current_blockquote_depth = $depth;
			}
			
			// Definition List
			elseif ($firstchar == ':') {
				if (!$definition_list) {
					$parent = new deflist($parent);
				}
				$paragraph .= trim(substr($trimmed, 1)).' ';
				$definition_list = 1;
			}
			
			// Tables
			elseif ($firstchar == '|') {
				if (!$table_open) {
					$table_id = generatestring(5, 'alpha');
					$parent = new table($parent);
					$parent->setid($table_id);
					$table_open = 1;
				}
				$row = new row($parent);
				
				preg_match('/^(\|{1,2})(.*?)(\|?)$/', $trimmed, $match);
				$trow = strlen($match[1]) > 1;
				$cellstr = $match[2];
				
				$cells = explode('|', $cellstr);
				foreach ($cells as $i => $content) {
					$cell = new cell($row, 'col'.$i);
					if ($trow) $cell->header();
					$cell->addWikiPara(trim($content));
					// store some alignment data for later
					$avglen[$i]['ltrim'][] = strlen(ltrim($content));
					$avglen[$i]['rtrim'][] = strlen(rtrim($content));
				}
			}
			
			// default - add a line to the current paragraph
			else {
				$paragraph .= $trimmed.' ';
			}
		}
		// End of Input - print what's left in our buffer and we're done
		if (!($definition_list || $current_list_depth || $table_open)) $p = new p($parent);
		else $p = $parent;
		$p->addWikiPara($paragraph);
	}
	
	/**
	 * @access private
	 */
	private function addWikiPara($para) {
		$para = preg_replace('/\'\'\'(.*?)\'\'\'/', '<i>$1</i>', $para);
		$para = preg_replace('/\'\'(.*?)\'\'/', '<b>$1</b>', $para);
		$para = preg_replace('/__(.*?)__/', '<u>$1</u>', $para);
		$para = preg_replace('/--(.*?)--/', '<s>$1</s>', $para);
		$para = preg_replace('/``(.*?)``/', '<code>$1</code>', $para);
		
		// look for images and links
		if (preg_match_all('/\[(\[(.*)\])?(.*?)\]/', $para, $matches, PREG_SET_ORDER)) {
			$pieces = preg_split('/\[(\[(.*)\])?(.*?)\]/', trim($para));
			$alignedimg = new container($this);
			$lastmatch = count($matches)-1;
			$last = count($pieces)-1;
			foreach ($pieces as $i => $p) {
				$this->addText($p);
				if ($matches[$i]) {
					// handle a link
					$sublink = $matches[$i][2];
					$linktext = $matches[$i][3];
					preg_match('/^(.*?)\s*(\S+)$/', $linktext, $match);
					$linkname = $match[1];
					$orighref = $match[2];
					
					// we need to parse the links we get so we can use our own system for variables
					$info = link::parse_href($orighref, FALSE);
					$linkhref = $info['file'];
					$linkvars = $info['vars'];
					$linkhash = $info['hash'];
					
					// clean up href and linknames
					if (!$linkname && !$sublink) $linkname = $linkhref;
					if (strtolower(substr($linkhref, 0, 4)) == 'www.') $linkhref = 'http://'.$linkhref;
					if (preg_match('/^\S+@\S+\.\S+$/', $linkhref)) $linkhref = 'mailto:'.$linkhref;
					
					// does this link to an image?
					if (in_array(strtolower(file_extension($orighref)), array('gif','jpg','jpeg','png')))
						$image = TRUE;
					
					// figure out if this image should be aligned
					if (($sublink || $image) && (($i==0 && !$pieces[0]) || ($i==$lastmatch && !$pieces[$last]))) {
						$cont = $alignedimg;
						if (!$pieces[0]) $align = 'imgleft';
						else $align = 'imgright';
					} else {
						$cont = $this;
						$align = '';
					}
					
					if ($image) {
						$img = new image($cont, $linkhref, 0, 0, $linkname, $align);
						$img->setsrc($linkhref, $linkvars);
					} else {
						$lnk = new link($cont, $linkhref, $linkname, $linkvars, $align);
						$lnk->sethash($linkhash);
						if ($sublink) {
							preg_match('/^(.*?)\s*(\S+)$/', $sublink, $match);
							$alttext = $match[1];
							
							// make sure we parse the image's source so we can properly add variables
							$info = link::parse_href($match[2], FALSE);
							$myhref = $info['file'];
							$myvars = $info['vars'];
							
							// create the image
							$img = new image($lnk, $myhref, 0, 0, $alttext);
							$img->setsrc($myhref, $myvars);
						}
					}
				}
			}
			if ($alignedimg->childCount()) new clear($this);
			else $this->removeChild($alignedimg);
		} else {
			$this->addText($para);
		}
	}
	
	/**
	 * @access private
	 */
	protected function analyzePairs($str) {
		$attr = array();
		
		// match the pairs that do not use quotes, assume that whitespace breaks the pairing
		// for instance, id=myfirstdiv
		$matches = array();
		preg_match_all('/(\S*?)=([^"\']\S*)/i', $str, $matches);
		foreach ($matches[1] as $i=>$key) $attr[strtolower($key)] = $matches[2][$i];
		
		// match the pairs that do use quotes, capture everything up until the next instance of
		// the quote character used
		// for instance, id="myfirstdiv" OR id='myfirstdiv'
		$matches = array();
		preg_match_all('/(\S*?)=(["\'])(.*?)\\2/si', $str, $matches);
		foreach ($matches[1] as $i=>$key) $attr[strtolower($key)] = $matches[3][$i];
		
		return $attr;
	}
	
	/**
	 * Return number of children inside container
	 *
	 * Note that this only counts the immediate children, there
	 * could be many more at lower depths.
	 *
	 * @return int
	 */
	public function childCount() {
		return count($this->children);
	}
	
	/**
	 * Return container's children
	 *
	 * Returns an array of objects that directly descend from this
	 * container.
	 *
	 * Note that you cannot directly edit this array, but you can
	 * do things like call the setparent or removeChild method.
	 *
	 * @return array
	 */
	public function children() { return $this->children; }
	
	/**
	 * @access private
	 */
	public function output($pspace = '', $optin=FALSE) {
		$this_abstract = ($this instanceof widget || $this instanceof pseudoparent || get_class($this) == 'container');
		$simple = (count($this->children) == 0 || (count($this->children) == 1 && ($this->children[0] instanceof element) && !($this->children[0] instanceof container)));
		if (!$this_abstract && $optin && !$simple) $return .= "\n";
		
		foreach($this->children as $i => $child) {
			$abstract = ($child instanceof widget || $child instanceof pseudoparent || get_class($child) == 'container');
			if (!$abstract && $optin && !$simple) {
				$return .= $pspace;
				$inc = '    ';
			}
			$return .= $child->output($pspace.$inc, $optin);
			if (!$abstract && $optin && !$simple) {
				$return .= "\n";
			}
		}
		
		if (!$this_abstract && $optin && !$simple) $return .= substr($pspace, 0, -4);
		
		return $return;
	}
	
	/**
	 * @access private
	 */
	public function finalize() {
		foreach ($this->children as $child) {
			$child->finalize();
		}
	}
}

/**
 * HTML Widget
 *
 * This class can be used as an ancestor for creating widget classes
 * that can accept children and do things with them.  It can be used 
 * to create a header/footer environment, or to create a true widget
 * with complex formatting of its children.
 *
 * There are three important methods available when creating a 
 * widget: {@link create()}, {@link finalize()}, and {@link output()}.
 *
 * It is important to note that once a widget is assigned to a parent,
 * this cannot be undone.  The widget's environment has been placed
 * permanently into the parent and is difficult to change (though
 * possible).
 *
 * The typical methodology for a widget is to put all the settings in at
 * object construction time through the $settings parameter of the constructor.  
 * If you need to change the environment after construction, you will have to keep
 * track of the HTML objects that may be modified, and then write additional 
 * widget methods to modify them or provide access to them.
 *
 * For example, you could create a widget with multiple content areas.  
 * You keep track of all the necessary containers generated 
 * during {@link create()}, and provide methods for returning each parent
 * object your user needs.
 *
 * @package htmlsupport
 */
class widget extends container {

	protected $settings;
	protected $pseudoparent;
	protected $contentbox;
	
	/**
	 * Constructor
	 *
	 * Typically a widget only recieves one extra parameter, an associative
	 * array that can contain all manner of different settings
	 *
	 * This is not required, and it's possible to design a widget
	 * with its own methods for controlling behavior.  In this case you
	 * usually want to move most of the work to the 'finalize()' method instead
	 * of the create() method, which is essentially a constructor.  This way your
	 * user has time to call methods and set member variables as he chooses.
	 * 
	 * @param container $parent
	 * @param array $settings
	 */
	function __construct($parent = 0, $settings = array()) {
		if (is_array($settings)) $this->settings = $settings;
		$this->setparent($parent);
		
		// create a container that will house everything the widget creates
		$this->pseudoparent = new pseudoparent($this);
		$this->contentbox = new container();

		// let the widget do its work
		$box = $this->create($this->pseudoparent, $this->settings);
		
		// the create() method returns a container $box into which all the children of
		// this widget should be placed.  We just put the widget itself inside $box, 
		// while the pseudoparent remains as the overall container that
		// houses everything. This allows the user to treat a widget like any other
		// container.
		if ($box instanceof container) $box->addChild($this->contentbox);
		else $this->pseudoparent->addChild($this->contentbox);
	}
			
	/**
	 * These methods are defined in the container class, but need to be overloaded 
	 * for widgets.  Since widgets often consist of a series of elements, we create
	 * a special container for the widget's children, so that when you call
	 * $widget->output(), you get the whole widget, but if you add a child or
	 * request the list of children, you see what's in the content area.
	 *
	 * @access private
	 */
	public function addChild($child) {
		if ($child instanceof pseudoparent) container::addChild($child);
		else $this->contentbox->addChild($child);
	}
	
	/**
	 * @access private
	 */
	public function removeChild($child) {
		$this->contentbox->removeChild($child);
	}
	
	/**
	 * @access private
	 */
	public function children() {
		return $this->contentbox->children();
	}
	
	/**
	 * @access private
	 */
	public function childCount() {
		return $this->contentbox->childCount();
	}
	
	/**
	 * @access private
	 */
	public function clearChildren() {
		$this->contentbox->clearChildren();
	}
		
	/**
	 * Create objects for the widget
	 *
	 * create() is used to generate an environment around the widget.  You take
	 * a parent container as input, and you create several new HTML objects 
	 * inside the parent, including one that will contain all the widget's 
	 * children.  This object is returned at the end.  If the widget will not 
	 * have children, return the parent object.
	 */
	protected function create($widgetbox, $settings = array()) {
		return $widgetbox;
	}
	
	/**
	 * Finalize objects before output
	 *
	 * finalize() is run for ALL objects before any output is generated. It is impossible
	 * for the user to call any methods after finalize(), so this is the stage
	 * when you can be sure of the names and settings of all the objects.  
	 *
	 * This is especially useful when the widget requires the use of further
	 * methods to fully define its behavior.  At this point you can be without fear 
	 * that the user will later change the settings and break the code.  
	 * If you are generating the whole widget upon construction, you will probably
	 * not need to overload this method.  
	 *
	 * If you do overload this method, remember to call 
	 * widget::finalize() at the end of your method because it does important work.
	 *
	 * Note: you will not want to generate javascript in the beginning of the output() 
	 * method because the <head> section has already been generated. Instead, overload
	 * finalize() if you need javascript in the header.
	 *
	 * Finally, make sure you never alter another object during finalize unless
	 * it is your direct descendant; you could accidentally violate its finalize() assumption.
	 */
	public function finalize() {
		return container::finalize();
	}
	
	/**
	 * Output HTML for the widget
	 *
	 * output() is the final method that generates the HTML for the widget.  
	 * Many widgets will not need to overload this, unless they need to 
	 * generate pure HTML before, after, or between their children.  
	 * Remember to maintain XHTML validity.
	 *
	 * If you overload this method, you can access your children's raw HTML output
	 * by calling container::output($pspace, $optin), or by iterating through
	 * the array returned by $this->children(), and calling $child->output($pspace, $optin)
	 * on each of them individually.
	 *
	 * Make sure to take $pspace and $optin as input and pass them along to
	 * container::output() or $child->output().  They are instructions for creating 
	 * nicely indented HTML source when $doc->useprettycode() has been called.
	 *
	 * @return string
	 */
	public function output($pspace = "", $optin = FALSE) {
		return container::output($pspace, $optin);
	}
}

/**
 * @access private
 */
class pseudoparent extends container {
	// does nothing, just has a different name	
}

/**
 * @package htmlsupport
 */
abstract class formelement extends element {
	private $div;
	private $accesskey;
	private $tabindex;
	private $formlabel;
	private $labelclass;
	private $name;
	protected $check = array();
	protected $preload = TRUE;
	protected $disabled = FALSE;
	protected $labelhidden = FALSE;
	
	/**
	 * @access private
	 */
	function __construct($parent = 0, $name = "", $class = "") {
		element::__construct($parent, '', $class);
		$this->setname($name);
	}
	
	/**
	 * Return form element's name
	 *
	 * In HTML, this is the 'name' attribute.
	 *
	 * @return string
	 * @access private
	 */
	public function getname() { return $this->name; }
	
	/**
	 * Set form element's name
	 *
	 * In HTML this is the 'name' attribute.
	 *
	 * @param string $name
	 * @return void
	 * @access private
	 */
	public function setname($name = "") {
		$this->name = $name;
	}

	
	/**
	 * Set a label
	 *
	 * This is a convenience method that will set an HTML label for the 
	 * form element.  This uses the actual {@link label} object and places
	 * it in front of the form element (or after, for checkboxes and radio buttons). If
	 * you want your label placed somewhere else, like another table cell, you should
	 * just create a label object directly and skip this method.
	 * 
	 * Clicking the text of the label activates the form element.
	 *
	 * You may pass a CSS class to format the label, typically width: 150px
	 * and text-align: right look good.  You may also format the HTML tag 
	 * globally like so: "label { width: 150px; text-align: right; }"
	 * 
	 * @param string $label
	 * @param string $class
	 * @return void
	 */
	public function setlabel($label, $class='') {
		$this->formlabel = $label;
		if (!$class && ($this instanceof checkbox || $this instanceof radio)) $class = 'checkbox';
		$this->labelclass = $class;
	}
	
	/**
	 * Get Label
	 * 
	 * Returns the label for this element.  Used by the error-checking routines.
	 */
	public function getlabel() {
		return $this->formlabel;
	}
	
	/**
	 * Find parent form
	 * 
	 * Find the form object that this element is a part of.
	 * 
	 * @return form
	 */
	public function findform() {
		return $this->getparent()->findform();
	}
	
	/**
	 * Set as Required
	 *
	 * Using this method will make the field required.  The user
	 * must enter a value to pass error detection.
	 * 
	 * @return void
	 */
	public function check_required() {
		$this->check['required'] = TRUE;
	}
	
	/**
	 * If object is given a value, then X object becomes required
	 *
	 * This sets another form element as required, when this element
	 * has a value set.  This differs slightly from {@link check_bothorneither()}
	 * in that you could have one empty value, as long as the target (X element)
	 * is the one that has content.
	 * 
	 * @param formelement $linked
	 * @return void
	 */
	public function check_ifmethen($linked = '') {
		if ($linked instanceof formelement) {
			$linked->check['requiredkey'] = $this;
		}
	}
	
	/**
	 * Check two objects for a less than/greater than relationship
	 *
	 * Requires that the formelement object passed is less than or equal
	 * to the element that you are checking.
	 * 
	 * @param formelement $linked
	 * @return void
	 */
	public function check_greater($linked = '') {
		if ($linked instanceof formelement) {
			$linked->check['greater'] = $this;
		}
	}
	
	/**
	 * If object is not given a value, then X object becomes required
	 *
	 * This sets another form element as required, when this element
	 * has no value set.  It becomes impossible to leave both blank.
	 * 
	 * @param formelement $linked
	 * @return void
	 */
	public function check_ifnotmethen($linked = '') {
		if ($linked instanceof formelement) {
			$linked->check['requiredkeynot'] = $this;
		}
	}
	
	/**
	 * Check that two inputs match
	 *
	 * This check requires that the linked element be given the same
	 * value as this element.  For example, confirming a password or
	 * email address.
	 * 
	 * @param formelement $linked
	 * @return void
	 */
	public function check_match($linked = '') {
		if ($linked instanceof formelement) {
			$linked->check['match'][] = $this;
		}
	}
	
	/**
	 * Set fields so that they must be filled together
	 *
	 * This is useful for creating a small subset of the form that is optional,
	 * but should be filled out completely or not at all.  You may use
	 * this method on multiple objects to link them all together as 
	 * all-or-none.
	 * 
	 * @param formelement $linked
	 * @return void
	 */
	public function check_bothorneither($linked = '') {
		if ($linked instanceof formelement) {
			$this->check['requiredkey'] = $linked;
			$linked->check['requiredkey'] = $this;
		}
	}
	
	/**
	 * Check for unusual characters unsuitable for a short piece of text.
	 *
	 * Use this method to ensure that the value of this form element contains no
	 * strange characters.  The standard characters are the ones available on a US keyboard
	 * with nothing more than the shift key.  This will allow quote characters through,
	 * so it is not an excuse to avoid using addslashes().
	 * 
	 * @return void
	 */
	public function check_tinytext() {
		$this->check['tinytext'] = TRUE;
	}
	/**
	 * Check for a numeric input
	 *
	 * Requires that input be a valid numeric format, as determined by PHP's
	 * isNumeric() function.  An empty input will pass.
	 * 
	 * @return void
	 */
	public function check_numeric() {
		$this->check['numeric'] = TRUE;
	}
	/**
	 * Check for an integer
	 *
	 * Requires that the input is an integer.  It can be positive or negative,
	 * zero or even an empty string, but a decimal value or any alphabetic characters
	 * will fail.
	 * 
	 * @return void
	 */
	public function check_integer() {
		$this->check['integer'] = TRUE;
	}
	/**
	 * Check for a calendar date
	 *
	 * Requires a parseable date format, as determined by running it through PHP's
	 * strtotime() function, which recognizes many common date formats.
	 * 
	 * @return void
	 */
	public function check_date() {
		$this->check['date'] = TRUE;
	}
	/**
	 * Restrict input to a maximum
	 *
	 * Requires that the input value must be less than the value
	 * specified.  Numeric check is NOT implicit, as Z (in combination 
	 * with a minimum A) could be used to ensure a string starts 
	 * with a capital letter.  
	 * 
	 * However, if both the input and $max are
	 * numbers (or strings that could be evaluated to numbers), the check
	 * will be numeric.  So in combination with check_numeric(),
	 * you could be sure that the input is a number less than $max.
	 * 
	 * @param mixed $max
	 * @return void
	 */
	public function check_max($max) {
		$this->check['max'] = $max;
		$this->check['max_spec'] = TRUE;
	}
	/**
	 * Restrict input to a minimum
	 *
	 * Requires that the input value must be greater than the value
	 * specified.  Numeric check is NOT implicit, as A (in combination 
	 * with a maximum Z) could be used to ensure a string starts 
	 * with a capital letter.
	 * 
	 * However, if both the input and $min are numbers (or strings 
	 * that could be evaluated to numbers), the check will be numeric.  
	 * So in combination with check_numeric(), you could be sure that 
	 * the input is a number greater than $min.
	 * 
	 * @param mixed $min
	 * @return void
	 */
	public function check_min($min) {
		$this->check['min'] = $min;
		$this->check['min_spec'] = TRUE;
	}
	/**
	 * Restrict input to characters used for string keys
	 *
	 * Currently this restricts input to alphanumeric characters and
	 * underscore.  This is useful for passwords and keys that must often
	 * pass "==" checks.  Spaces and special characters can cause ambiguity.
	 * 
	 * @return void
	 */
	public function check_key() {
		$this->check['key'] = TRUE;
	}
	/**
	 * Check for a valid email address
	 *
	 * Checks an email address for the basic .*@.*\..* format.
	 * Does no further evaluation.
	 * 
	 * @return void
	 */
	public function check_email() {
		$this->check['email'] = TRUE;
	}
	/**
	 * Check input against custom regular expression.
	 *
	 * Pass a custom regular expression to this check to require it to
	 * pass through a preg_match().  Don't forget to enclose your expression
	 * with slashes and add options (like 'i' for case insensitive). e.g.:
	 * 
	 * <pre>$textbox->check_regex('/mypattern/i');</pre>
	 * 
	 * @param string $regex
	 * @return void
	 */
	public function check_regex($regex) {
		$this->check['regex'][] = $regex;
	}
	/**
	 * Set a custom error check function
	 *
	 * Requires input to pass through a custom function.  For example, to
	 * check the input against a database query. Takes standard PHP callback
	 * format as its first parameter.
	 * 
	 * For a review on PHP's callback format:
	 * 
	 * <pre>standalone function: 'functionname'
	 * static class method: array('classname', 'methodname')
	 * instantiated class method: array($myclass, 'methodname') (don't use one of these!)</pre>
	 * 
	 * Callback functions defined as part of a class MUST be static functions.
	 * Member functions, or methods, require an instance of the class that will
	 * NOT be available during the actual check.
	 *
	 * The callback will receive the user's input as the first parameter. Further 
	 * parameters may be specified when you call this method by simply adding them 
	 * as parameters. However, these can only be scalar values. Objects and 
	 * arrays will be lost.
	 *
	 * The callback must return an empty string if validation succeeds, 
	 * and an error message if it fails.  The error message will be appended 
	 * to the element's label, so the error message should usually be a partial 
	 * sentence. (e.g. ' already exists in our database.')
	 * 
	 * @param callback $callback
	 * @return void
	 */
	public function check_callback() {
		$args = func_get_args();
		// check for instantiated callbacks, these aren't allowed
		if (is_array($args[0]) && is_object($args[0][0])) return;

		// cleanse non-scalars from the input
		foreach ($args as $i => $arg) if ($i == 0 || is_scalar($arg)) $newargs[] = $arg;
		// add this callback to the list of checks
		$this->check['callback'][] = $newargs;
	}
	/**
	 * Check the string length of the input
	 *
	 * Requires that the string length of the input be less than
	 * or equal to the value you specify.
	 */
	public function check_length($length) {
		$this->check['length'] = $length;
	}
	/**
	 * Check file size of an upload
	 * 
	 * Requires uploaded files to be smaller than the specified size, in
	 * bytes.  Sometimes it helps to do multiplication in your call, e.g.
	 * $formelement->check_filesize(50*1024)
	 */
	public function check_filesize($size) {
		$this->check['filesize'] = $size;
	}
	/**
	 * Check for uniqueness
	 *
	 * When used on multiple form elements, requires that the user must not
	 * duplicate any values on any elements marked as unique.  It is still possible
	 * to duplicate a value if only one of the elements had this check applied.
	 *
	 * You may specify a code to identify groups.  In other words, if you need to
	 * use this check on two different sets of form elements, you may not want to
	 * error out when one group's member has a duplicate in another group.
	 */
	public function check_unique($code) {
		$this->check['unique'][] = $code;
	}
	
	/**
	 * Ensure value comes back empty
	 *
	 * This is the opposite of making a field required.  It will have to be left empty.
	 *
	 * This check is automatically included when you disable a form element, so that
	 * malicious users cannot use other means to specify a value for a disabled form field.
	 */
	public function check_empty() {
		$this->check['empty'] = TRUE;
	}
	
	/**
	 * Override form preloading
	 *
	 * Form elements in this framework will automatically preload content
	 * if there is form input that matches the name.  This preload will
	 * overwrite any value or content that you set in a widget or normal
	 * page.
	 *
	 * For example, if you have a select box for choosing your country, and 
	 * you set it to default to "United States", then it will be set to "United States"
	 * the first time they load the page.  However, if they try to submit the form and
	 * run into an error, the form will be redrawn with whatever country they chose. 
	 * Otherwise they would lose all their work when they submit a form with a minor
	 * error.
	 *
	 * In a few rare cases this bevavior is not desired (especially when you have multiple
	 * forms on page with identically named elements).  This method is provided to 
	 * let you avoid this behavior and use your pre-decided default on every load.
	 */
	public function nopreload($flag = TRUE) {
		$this->preload = $flag ? FALSE : TRUE;
	}

	/**
	 * Set an Alt-Key shortcut
	 *
	 * This will set the accesskey attribute for a form element, which
	 * the browser will recognize as a shortcut key.  For instance, if you
	 * set the access key to 'S', then the user pressing Alt-S will move the 
	 * focus to that form element.
	 *
	 * Avoid A, B, E, F, G, H, T, and V as they will conflict with common browser
	 * menus.
	 */
	public function accesskey($key) {
		$this->accesskey = $key;
	}
	
	/**
	 * Assign a Tab Index
	 *
	 * Assigning a Tab Index to a form element will allow the user to
	 * iterate through form fields by pressing the tab key.  On many browsers
	 * it will also prevent the tab key from highlighting hyperlinks, which may
	 * not be desired behavior.
	 *
	 * A value of zero will deactivate the Tab Index, if one has been set.
	 */
	public function tabindex($index) {
		$this->tabindex = $index;
	}
	
	/**
	 * Disable Form Element
	 *
	 * This will set the disabled attribute for the form element, causing it to
	 * appear greyed out and uninteractive.  In the disabled state, it will NOT
	 * contribute any data when the form is submitted.
	 */
	public function disabled() {
		$this->disabled = TRUE;
		$this->check_empty();
	}
	
	/**
	 * Do not display a form element's label
	 *
	 * Use this method to disable an element's label.
	 *
	 * This is meant to help you create meaningful error messages for things like
	 * radio buttons, where the label is not concise enough for error messages.
	 */
	public function hidelabel() {
		$this->labelhidden = TRUE;
	}
	
	/**
	 * @access private
	 */
	public function genlabel() {
		if ($this->formlabel && !$this->labelhidden) {
			$label = new label(0, $this->formlabel, $this, $this->labelclass);
			$ret = $label->output();
		}
		return $ret;
	}

	/**
	 * @access private
	 */
	protected function checkme($formname) {
		$errors = array();
		$val = $_REQUEST[$this->getname()];
		if (is_array($_FILES[$this->getname()])) $val = $this->getname();
		if (!$_REQUEST['pwo_submit'] || $_REQUEST['whichform'] != $formname) return $errors;

		if ($this->check['required'] && !$this->disabled && !$val) $errors[] = $this->getlabel().' is a required field.';
		if ($this->check['empty'] && $val) $errors[] = $this->getlabel().' must be empty.';
		
		if ($this->check['tinytext'] && !form::validate_tinytext($val))
			$errors[] = $this->getlabel().' contains unsupported character(s).';
		if ($this->check['date'] && strtotime($val) == 0 && $val)
			$errors[] = $this->getlabel().' does not appear to be a valid date.';
		if ($this->check['integer'] && $val != '' && (!is_numeric($val) || intval($val) != $val))
			$errors[] = $this->getlabel().' does not appear to be an integer.';
		if ($this->check['numeric'] && $val != '' && !is_numeric($val))
			$errors[] = $this->getlabel().' does not appear to be a number.';
		if ($this->check['max_spec'] && $val > $this->check['max'])
			$errors[] = $this->getlabel().' over maximum value ('.$this->check['max'].').';
		if ($this->check['min_spec'] && $val != '' && $val < $this->check['min'])
			$errors[] = $this->getlabel().' below minimum value ('.$this->check['min'].').';
		
		if ($this->check['key'] && !form::validate_key($val))
			$errors[] = $this->getlabel().' contains unsupported character(s). Allowed: Alphanumeric, dash and underscore.';
			
		if ($this->check['length'] && strlen($val) > $this->check['length']) $errors[] = $this->getlabel().' too long (max '.$this->check['length'].').';
		if ($this->check['filesize'] && $_FILES[$this->getname()]['size'] > $this->check['filesize'] * 1024) $errors[] = $this->getlabel().' too big (max '.$this->check['filesize'].'kB).';
		if ($this->check['email'] && !form::validate_email($val) && $val) $errors[] = $this->getlabel().' doesn\'t appear to be a valid email.';
		if ($this->check['greater'] instanceof formelement && ($val <= $_REQUEST[$this->check['greater']->getname()]))
			$errors[] = $this->getlabel() . ' must be greater than '.$this->check['requiredkey']->getlabel().'.';
		if ($this->check['requiredkey'] instanceof formelement && !$val && $_REQUEST[$this->check['requiredkey']->getname()]) {
			$present = ($this->check['requiredkey'] instanceof radio ? 'selected' : 'present');
			$errors[] = $this->getlabel() . ' required when '.$this->check['requiredkey']->getlabel().' '.$present.'.';
		}
		if ($this->check['requiredkeynot'] instanceof formelement && !$val && !$_REQUEST[$this->check['requiredkeynot']->getname()])
			$errors[] = $this->getlabel() . ' required when '.$this->check['requiredkeynot']->getlabel().' unspecified.';
			
		// check_callback()
		$cbs = $this->check['callback'];
		if (!is_array($cbs)) $cbs = array();
		foreach ($cbs as $cb) {
			$cbf = $cb[0];
			array_splice($cb, 0, 1, array($val));
			$error = call_user_func_array($cbf, $cb);
			if ($error) $errors[] = $this->getlabel().$error;
		}
		
		// check_match()
		$uns = $this->check['match'];
		if (!is_array($uns)) $uns = array();
		foreach ($uns as $ele) if ($ele instanceof formelement && $_REQUEST[$ele->getname()] != $val) $errors[] = $this->getlabel().' must match '.$ele->getlabel().'.';
		
		// check_regex()
		$uns = $this->check['regex'];
		if (!is_array($uns)) $uns = array();
		foreach ($uns as $regex) if (!preg_match($regex, $val)) $errors[] = $this->getlabel().' does not appear to be a valid input.';
		
		// check_unique()
		global $cfg;
		$uns = $this->check['unique'];
		if (!is_array($uns)) $uns = array();
		foreach ($uns as $code)
			if ($cfg['unique_eles'][$code][consolidate($val)] && $val) $errors[] = $this->getlabel().' "'.$val.'" was already used.';
			else $cfg['unique_eles'][$code][consolidate($val)] = TRUE;
	
		return $errors;
	}
	
	/**
	 * @access private
	 */
	public function finalize() {
		$parent = $this->getparent();
		if ($this->check['required'] && !$this->disabled) new hidden($parent, 'check_required[]', $this->getname());
		if ($this->check['empty']) new hidden($parent, 'check_empty[]', $this->getname());
		if ($this->disabled) new hidden($parent, 'check_disabled_'.$this->getname(), 1);
		if ($this->check['greater'] instanceof formelement) 
			new hidden($parent, 'check_greater[]', $this->getname().'|'.$this->check['greater']->getname());
		if ($this->check['requiredkey'] instanceof formelement) 
			new hidden($parent, 'check_ifmethen[]', $this->getname().'|'.$this->check['requiredkey']->getname());
		if ($this->check['requiredkeynot'] instanceof formelement) 
			new hidden($parent, 'check_ifnotmethen[]', $this->getname().'|'.$this->check['requiredkeynot']->getname());
		if ($this->check['tinytext']) new hidden($parent, 'check_tinytext[]', $this->getname());
		if ($this->check['integer']) new hidden($parent, 'check_integer[]', $this->getname());
		if ($this->check['numeric']) new hidden($parent, 'check_numeric[]', $this->getname());
		if ($this->check['min_spec']) new hidden($parent, 'check_min[]', implode('|',array($this->getname(), $this->check['min'])));
		if ($this->check['max_spec']) new hidden($parent, 'check_max[]', implode('|',array($this->getname(), $this->check['max'])));
		if ($this->check['key']) new hidden($parent, 'check_key[]', $this->getname());
		if ($this->check['date']) new hidden($parent, 'check_date[]', $this->getname());
		if ($this->check['email']) new hidden($parent, 'check_email[]', $this->getname());
		if ($this->check['length']) new hidden($parent, 'check_length[]', implode('|',array($this->getname(), $this->check['length'])));
		
		$uns = $this->check['unique'];
		if (!is_array($uns)) $uns = array();
		foreach ($uns as $code)
			new hidden($parent, 'check_unique[]', implode('|',array($this->getname(), $code)));
		
		if ($this->check['filesize']) new hidden($parent, 'check_filesize[]', implode('|',array($this->getname(), $this->check['filesize'])));

		$cbs = $this->check['callback'];
		if (!is_array($cbs)) $cbs = array();
		foreach ($cbs as $cb) {
			array_splice($cb, 0, 0, array($this->getname()));
			if (is_array($cb[1])) $cb[1] = implode('::', $cb[1]);
			new hidden($parent, 'check_callback[]', implode('|', $cb));
		}
		$cbs = $this->check['match'];
		if (!is_array($cbs)) $cbs = array();
		foreach ($cbs as $cb) {
			new hidden($parent, 'check_match[]', implode('|', array($this->getname(), $cb->getname())));
		}
		$cbs = $this->check['regex'];
		if (!is_array($cbs)) $cbs = array();
		foreach ($cbs as $cb) {
			new hidden($parent, 'check_regex[]', implode('|', array($this->getname(), $cb)));
		}
		
		$form = $parent->findform();
		if (!$form instanceof form) trigger_error('Cannot find parent form for "'.get_class($this).'" form element named "'.$this->getname().'".  All form elements MUST belong to a form.', E_USER_ERROR);
		$errors = $this->checkme($form->getname());
		if (!empty($errors)) {
			$this->haderror = TRUE;
			if ($form instanceof form) $form->register_error($errors);
		}
		
		// deal with preloading
		if (!$form->preloading()) $this->nopreload();
		
		// if we are set to print out a label and don't have an ID, generate one
		if ($this->formlabel && !$this->labelhidden) $this->ensureid();
		
		parent::finalize();
	}
	
	/**
	 * @access private
	 */
	public function output() {
		global $cfg;
		if ($this->accesskey) $akey = ' accesskey="'.$this->accesskey.'"';
		$bgcolor = $cfg['form_error_bg'] ? $cfg['form_error_bg'] : 'pink';
		if ($this->haderror) $style = ' style="background-color: '.$bgcolor.'"';
		if ($this->disabled) $disable = ' disabled="disabled"';
		if ($this->tabindex) $tabidx = ' tabindex="'.htmlspecialchars($this->tabindex).'"';
		return element::output() . $tabidx . $akey . $style . $disable;
	}
}

?>