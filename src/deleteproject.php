<?php
/**
 * Confirmation page for project deletion
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$env = new env($doc);
$doc->appendTitle('Confirm Delete');

if (form::check_error('deleteproject')) {
	db_layer::project_delete($_REQUEST['id']);
	$doc->refresh(0,'index.php');
} else {
	$p = db_layer::project_get(array('id'=>$_REQUEST['id']));
	$env->addText('Are you sure you wish to delete this project?'."\n\n".$p['name']."\n\n");

	$form = new form($env, 'deleteproject');
	new hidden($form, 'id', $p['id']);
	
	//Submit
	new submit($form, 'Delete Project');
}

$doc->output();
?>
