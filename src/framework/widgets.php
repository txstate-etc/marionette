<?php
/**
 * widgets.php
 * 
 * A place to store widgets that seem to be generally useful, to be released
 * with the rest of phpWebObjects.
 * 
 * @package widgets
 */

/**
 * Link Group
 * 
 * This is a fairly simple widget for creating groups of links that 
 * are visually distinguished as being related.  Be aware that all calls
 * to addText() or similar will be treated as separate elements.
 * 
 * By default this will look like [ click me | click me too | click me please ], 
 * but you can specify the boundary characters with the settings array. For
 * example, the default is:
 * 
 * <pre>$grp = new linkgroup($parent, array(
 *   'separator' => '|',
 *   'left' => '[',
 *   'right' => ']',
 *   'nobound' => FALSE 
 * ));</pre>
 *
 *@package widgets
 */
class linkgroup extends widget {
	public function output($pspace='', $optin=FALSE) {
		$sep = $this->settings['separator'] ? $this->settings['separator'] : " | ";
		if (!$this->settings['nobound']) {
			$left = $this->settings['left'] ? $this->settings['left'] : '[ ';
			$right = $this->settings['right'] ? $this->settings['right'] : ' ]';
		}
		$return = array();
		foreach ($this->children() as $child) {
			$return[] = $child->output();
		}
		if (empty($return)) return '';
		$br = ($optin ? "\n" : "");
		return $pspace.$left.implode(nl2br(htmlspecialchars($sep)), $return).$right.$br;
	}
}

/**
 * Column Widget
 *
 * This widget is designed to create a set of columns based on the final count
 * of children added to it, so you don't have to do the math ahead of time.
 *
 * Example usage with default values:
 * <pre>$columns = new columns($parent, array(
 *   'numcols'=>3,
 *   'type'=>'float', // may also be 'table'
 *   'columnclass'=>'pwo_column', // each column will be given this class name
 *   'orientation'=>'vertical' // may also be 'horizontal'
 * ));</pre>
 *
 * @package widgets
 */
class columns extends widget {
	public function output($pspace='', $optin=FALSE) {
		// default settings
		$numcols = choose($this->settings['numcols'], 3);
		$float = ($this->settings['type'] != 'table');
		$class = choose($this->settings['columnclass'], 'pwo_column');
		$horiz = ($this->settings['orientation'] == 'horizontal');
		
		// gather info
		$total = $this->childCount();
		$percol = ceil($total/$numcols);
		
		if (!$total) return '';
		
		// prepare a parent object
		if ($float) $parent = new container();
		else $parent = new table();
		
		// create the columns/rows
		if ($float) for ($i = 0; $i < $numcols; $i++) $cols[] = new div($parent, '', $class);
		else for ($i = 0; $i < $percol; $i++) {
			$tr = new row($parent);
			for ($j=0; $j<$numcols; $j++) $table[$i][$j] = new cell($tr);
		}
		
		$row = 0;
		$col = 0;
		$m = $total % $numcols;
		foreach ($this->children() as $child) {
			$output = $child->output($pspace, $optin);
			if ($float) {
				$cols[$col]->addXHTML($output);
				$cols[$col]->br();
			}
			else $table[$row][$col]->addXHTML($output);
			if ($horiz) {
				$col++;
				if ($col >= $numcols) {
					$col = 0;
					$row++;
				}
			} else {
				$row++;
				if ($row >= $percol - ($m > 0 ? 0 : 1)) {
					$col++;
					$m--;
					$row = 0;
				}
			}
		}
		
		return $parent->output($pspace, $optin);
	}
}

/**
 * Joomla Module
 *
 * This is a class that provides compatibility with the Joomla framework (v1.0.11).
 * With this class, you can use this framework to create a Joomla template.  
 * You will then be able to insert this class wherever you would like
 * Joomla modules to be inserted, and use Joomla to edit the content.
 *
 * Note that you will still have to create a Joomla template directory, and an
 * index.php file that can access your design widget.
 *
 * Usage is similar to mosLoadModules() from Joomla.  Just use "type" in the settings
 * array to define the position you are declaring.  Just use "mainbody" to call mosMainBody().
 * "style" is also available, you may use numbers or an identifier as defined here:
 *
 * 1 OR 'row'
 * 0 OR 'column' (this is the default)
 * -1 OR 'raw'
 * -2 OR 'div'
 * -3 OR 'extradivs'
 *
 * "style" is not available for the main body.
 *
 * Example: new joomla_module($parent, array('type'=>'left', 'style'=>'column'));
 */
class joomla_module extends widget {
	protected $saved;
	protected function create($parent, $settings) {
		if (defined('_VALID_MOS')) {
			ob_start();
			if ($settings['type'] == "mainbody") mosMainBody();
			else mosLoadModules($settings['type'], $settings['style']);
			$this->saved = ob_get_contents();
			ob_end_clean();
		}
		return $parent;
	}
	public static function countmods($type) {
		if (!defined('_VALID_MOS')) return 0;
		return mosCountModules($type);
	}
	public function output() {
		return $this->saved;
	}
}

?>