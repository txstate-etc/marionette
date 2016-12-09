<?php
/**
 * Deal with AJAX commands
 * 
 * This file will be a central place to deal with any database-changing
 * Ajax commands.  Meant to be lightweight.
 * 
 * @package phpmanage
 */

require_once('common.php');

// Deal with Unit/Area Movement commands
if ($_REQUEST['action'] == 'movearea' && checkperm('sysadmin')) {
	if (preg_match('/area(\d+)/', $_REQUEST['drag'], $match))
		$id = $match[1];
	if ($_REQUEST['drop'] == 'areatrash')
		db_layer::unit_delete($id);
	elseif (preg_match('/area(\d+)/', $_REQUEST['drop'], $match))
		db_layer::unit_change_parent($id, $match[1]);
	elseif (preg_match('/above(\d+)/', $_REQUEST['drop'], $match))
		db_layer::unit_insertbefore($id, $match[1]);
}

if ($errormsg) echo $errormsg;
else echo 'fine';
?>