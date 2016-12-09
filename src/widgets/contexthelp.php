<?php 
/**
 * Contextual Help Class
 *
 * @package phpmanage
 */

/**
 * Question mark with tooltip
 * 
 * This widget is a little question mark image that can be placed in a number of places
 * to offer useful information in a tooltip.
 * 
 * It needs a unique ID, and takes it from there.  It also requires a working db_layer::help_get() 
 * and db_layer::help_set() to exist, as well as a 'contexthelp' handler in !xhr.php
 *
 * new contexthelp($parent, array('id'=>'myhelptext0101'));
 *
 * @package phpmanage
 */
class contexthelp extends widget {
	protected function create($parent, $settings) {
		$doc = doc::getdoc();
		$id = $settings['id'];
		$spanmode = $settings['mode'] == 'span';
		// make sure ID starts with a letter and has no weird characters
		$id = 'ch_'.$id;
		$id = preg_replace('/\W/', '', $id);
		
		static $setuponce = false;
		if (!$setuponce) {
			$doc->includeJS('@prototype.js');
			$doc->includeJS('!contexthelp.js');
			if (checkperm('contexthelp')) $doc->addJS("contexthelp.editprivs = true; contexthelp.bbcodelink = '".link::expand_href('@help/bbcodehelp.html')."'");
			$setuponce = true;
		}
		
		$helptext = db_layer::help_get($id);
		
		if (!$helptext && !checkperm('contexthelp')) return $parent;
		
		$bbcode = new container();
		$bbcode->addBBCode($helptext);
		$helptext = preg_replace('/\r?\n/', '\n', addslashes($helptext));
		
		$doc->addJS("new contexthelp('".addslashes($id)."', '".$helptext."', '".addslashes($bbcode->output())."')");
		
		$span = new span($parent, $id, 'contexthelp');
		if (!$spanmode) {
			new image($span, '!question.gif', 16, 16, '', 'inlinecorrect');
		}
		return $span;
	}
}

?>