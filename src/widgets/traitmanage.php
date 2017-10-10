<?php 
/**
 * Traitmanage Class
 *
 * @package phpmanage
 */

/**
 * Form for editing various lists in the system
 * 
 * This widget is part of the system.php page.  There are several
 * lists that can all be managed in the same way.
 *
 * It's called traitsmanage because initially all the lists it was
 * managing were the "traits" of project aspects.
 * 
 * Projects have 5 aspects (scope, schedule, resource, quality, overall), each
 * has all the same traits.  So we try to re-use code when we deal with any
 * of them.
 * 
 * Some lists need more than one piece of data to be saved.  You can add more columns
 * to the elicitation by sending an array in $settings['extracontrols'].  This array should
 * be formatted as follows:
 *
 * <pre>$extracontrols = array(
 *   array(
 *     'key' => database column name
 *     'name' => human readable column name
 *     'type' => type of input, 'checkbox', 'select', or the default is a text box
 *     'size' => size attribute of the input, only applicable for text boxes
 *     'reqd' => make input a required field
 *     'options' => only for type='select', options data as accepted by {@link select::addOptionsData}
 *     	'optvalkey' => if you include options, this is the name of the field for option values
 *     	'optlabelkey' => if you include options, this is the name of the field for option labels
 *   )
 * )</pre>
 * 
 * Note that the database call db_layer::trait_save() will have to handle an extra input array
 * and actually save those values.
 *
 * @package phpmanage
 */
class traitmanage extends widget {
	
	protected static function handle_extra($row, $ec, $ent = array()) {
		foreach ($ec as $c) {
		
			if (!$ent['id']) $name = $c['key'].'new';
			else $name = $c['key'].$ent['id'];
			$val = $ent[$c['key']];
			
			$cell = new cell($row, $c['title']);
			if ($c['type'] == 'checkbox') {
				$inp = new checkbox($cell, $name, 1, $val);
			} elseif ($c['type'] == 'select') {
				$inp = new select($cell, $name);
				$inp->addOption();
				$inp->addOptionsData($c['options'], $c['optvalkey'], $c['optlabelkey']);
				$inp->setSelected($val);
			} else {
				$inp = new textbox($cell, $name, $val, $c['size']);
			}
			if ($c['reqd']) $inp->check_required();
			$inp->setlabel($settings['legend'].' '.$c['name']);
			$inp->hidelabel();
		}
	}

	protected function create($parent, $settings) {
		$d = $settings['data'];  // all the data to be listed
		$ec = (array) $settings['extracontrols'];  // a list of extra columns that need to be elicited on each line
		if (!$settings['submit']) $settings['submit'] = 'Save Entry Changes';
		if (form::check_error($settings['type'])) {
			if (preg_match('/move(\d*)/', $_REQUEST['submit'], $match)) {
				$id = $match[1];
				db_layer::trait_move($settings['type'], $id);
			} elseif (preg_match('/delete(\d*)/', $_REQUEST['submit'], $match)) {
				$id = $match[1];
				db_layer::trait_delete($settings['type'], $id);
			} else {
				foreach ($d as $entry)
					if ($_REQUEST['entry'.$entry['id']]) { 
						$id = $entry['id'];
						$name = $_REQUEST['entry'.$entry['id']];
						foreach ($ec as $c) $more[$c['key']] = $_REQUEST[$c['key'].$entry['id']];
						db_layer::trait_save($settings['type'], $id, $name, $more);
					}
				if ($_REQUEST['entrynew']) {
					foreach ($ec as $c) $more[$c['key']] = $_REQUEST[$c['key'].'new'];
					db_layer::trait_save($settings['type'], 0, $_REQUEST['entrynew'], $more);
				}
			}
			$doc = doc::getdoc();
			$doc->refresh(0, 'system.php');
			$doc->output();
			exit;
		} else {
			$form = new form($parent, $settings['type'], 'traitsform');
			new hidden($form, $settings['type'].'_edit_id');
			new imgsubmit($form, '!spacer.gif', 'save', 1, 1, 'Submit Form');
			$fs = new fieldset($form, $settings['legend']);
			$table = new table($fs, 'traitlist');
			$trow = new row($table, 'trow');
			$trow->addCell('Del', 'del');
			$trow->addCell('Move', 'move');
			$trow->addCell('Edit', 'edit');
			$trow->addCell('Name', 'name');
			foreach ($ec as $c)
				$trow->addCell($c['name'], $c['key']);
						
			foreach ($d as $i => $ent) {
				$editing = ($_REQUEST[$settings['type'].'_edit_id'] == $ent['id']);
				
				// is this row protected from deletion?
				$allowdeletion = TRUE;
				foreach ((array) $settings['protect'] as $key => $vals) {
					foreach ((array) $vals as $v) if ($ent[$key] == $v) $allowdeletion = FALSE;
				}
				
				$class = ($class == 'odd' ? 'even' : 'odd');
				$row = new row($table, $class);
				
				$cell = new cell($row, 'del');
				if ($allowdeletion) new imgsubmit($cell, '!delete.gif', 'delete'.$ent['id'], 11, 12, 'Delete Entry', 'arrow');
				
				$cell = new cell($row, 'move');
				if ($i > 0) new imgsubmit($cell, '!up.gif', 'move'.$ent['id'], 11, 12, 'Move Entry Up', 'arrow');
				
				$cell = new cell($row, 'edit');
				if ($editing) {
					new imgsubmit($cell, '!left.gif', 'save', 10, 11, $settings['submit'], 'arrow');
				} else {
					$lnk = new link($cell, 'system.php', '', array($settings['type'].'_edit_id'=>$ent['id']), 'arrow');
					new image($lnk, '!right.gif', 10, 11, 'Edit This Entry');
				}
				
				$cell = new cell($row, 'name');
				if ($editing) {
					$tbox = new textbox($cell, 'entry'.$ent['id'], $ent['name'], 20);
					$tbox->check_required();
					$tbox->setlabel($settings['legend'].' Name');
					$tbox->hidelabel();
					$tbox->setid('activeentry');
					
					// handle extra inputs
					self::handle_extra($row, $ec, $ent);
				} else {
					$cell->addText($ent['name']);
					
					// handle extra inputs
					foreach ($ec as $c) {
						$cell = new cell($row, $c['title']);
						if ($c['type'] == 'checkbox') {
							$otpt = $ent[$c['key']] ? 'Yes' : 'No';
						} elseif ($c['type'] == 'select') {
							foreach ($c['options'] as $o) {
								if ($o[$c['optvalkey']] == $ent[$c['key']]) $otpt = $o[$c['optlabelkey']];
							}
						} else {
							$otpt = $ent[$c['key']];
						}
						$cell->addText($otpt);
					}
				}
			}
			
			// Row for new entry
			$editing = ($_REQUEST[$settings['type'].'_edit_id'] == 'new');
			$class = ($class == 'odd' ? 'even' : 'odd');
			$row = new row($table, $class);
			
			// title cell
			$cell = $row->addCell('New Entry');
			$cell->setwidth(2);
			
			// button to make editable
			$cell = new cell($row, 'edit');
			if ($editing) {
				new imgsubmit($cell, '!left.gif', 'savenew', 10, 11, 'Save New Entry', 'arrow');
			} else {
				$lnk = new link($cell, 'system.php', '', array($settings['type'].'_edit_id'=>'new'), 'arrow');
				new image($lnk, '!right.gif', 10, 11, 'Create New Entry');
			}
			$cell = new cell($row, 'name');
			if ($editing) {
				$tbox = new textbox($cell, 'entrynew', '', 20);
				$tbox->setid('activeentry');
			
				// handle extra inputs
				self::handle_extra($row, $ec);
			}
			
			// make sure we put the cursor in the box if we're editing
			if ($_REQUEST[$settings['type'].'_edit_id']) {
				doc::getdoc()->includeJS('!scriptaculous/prototype.js');
				doc::getdoc()->addJS_afterload("$('activeentry').focus()");
			}
		}
	}
}

?>