<?php
/**
 * Traitsform Class
 * 
 * @package phpmanage
 */

/**
 * Common form elements for traits
 * 
 * Projects have 5 aspects (scope, schedule, resource, quality, overall), each
 * has all the same traits.  So we try to re-use code when we deal with any
 * of them.
 * 
 * @package phpmanage
 */
class traitsform extends widget {
	protected function create($parent, $settings) {
		$f = strToLower($settings['prefix']).'_';
		$t = $settings['project'][strToLower($settings['prefix'])];
		$trait = db_layer::traits_lists();
		$disable = $settings['disabled'];
					
		// put it all in table format
		$row = new row($parent);
		$cell = $row->addCell($settings['prefix']);
					
		// Flexibility
		$cell = new cell($row, 'flexibility');
		$sel = new select($cell, $f.'flexibility');
		$sel->addOptionsData($trait['flexibility'], 'id', 'name');
		$sel->setSelected($t['flexibility']);
		if ($disable) $sel->disabled();
		
		//Status
		$cell = new cell($row, 'status');
		$sel = new select($cell, $f.'status');
		$sel->addOptionsData($trait['status'], 'id', 'name');
		$sel->setSelected($t['status']);
		if ($disable) $sel->disabled();

		//Trend
		$cell = new cell($row, 'trend');
		$sel = new select($cell, $f.'trend');
		$sel->addOptionsData($trait['trend'], 'id', 'name');
		$sel->setSelected($t['trend']);
		if ($disable) $sel->disabled();

		//Risk
		$cell = new cell($row, 'risk');
		$tbox = new textbox($cell, $f.'risk', $t['risk'], 25);
		if ($disable) $tbox->disabled();

		//Mitigation
		$cell = new cell($row, 'mitigation');
		$tbox = new textbox($cell, $f.'mitigation', $t['mitigation'], 25);
		if ($disable) $tbox->disabled();
		
		//Help
		$cell = new cell($row, 'help');
		new contexthelp($cell, array('id'=>$f.'help'));

	}
}
?>