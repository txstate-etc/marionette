<?php
/**
 * XHR interface for various tasks
 * 
 * This is an extremely lightweight interface to the database, for use by javascript XHRs.
 *
 * It should return an array or object in JSON.
 * 
 * @package phpmanage
 */

require_once('common.php');
header('Content-type: application/json');

if ($_REQUEST['xhr'] == 'contexthelp') {
	if (!checkperm('contexthelp')) { echo '{"success": false}'; exit; }
	$bbcode = new container();
	$bbcode->addBBCode($_REQUEST['text']);
	echo '{ "bbcode": "'.addslashes($bbcode->output()).'", "success": '.(db_layer::help_set($_REQUEST['id'], $_REQUEST['text']) ? 'true' : 'false').'}';
}
?>
