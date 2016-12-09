<?php
/**
 * Delete a user confirmation dialog
 *
 * Also has some settings for how to deal with the consequences.
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$user = doc::getuser();

$env = new env($doc);
$doc->appendTitle('Delete User');
$u = db_layer::user_get(array('userid'=>$_REQUEST['id']));

if (!$u['userid']) {
	$env->addText('There\'s been an error.');
	$doc->output();
	exit;
}
if (!checkperm('user_delete')) {
	$env->addText('You do not have permission to delete users.');
	$doc->output();
	exit;
}

if (form::check_error('deleteconfirm')) {
	$success = db_layer::user_delete(array(
		'userid' => $_REQUEST['id'],
		'replacewith' => $_REQUEST['replacewith'],
		'publishfirst'=> $_REQUEST['publishfirst'],
		'publishafter'=> $_REQUEST['publishafter'],
		'currentuser'=>$user->userid()
	));
	if ($success) {
		$env->addText("Deletion successful.  Redirecting you...");
		$doc->refresh(3, 'users.php');
	} else $env->addText("Deletion was unsuccessful.");
} else {
	$form = new form($env, 'deleteconfirm');
	new hidden($form, 'id');
	
	$form->addText("Confirm for deletion: ".$u['username']);
	$form->br(2);
	$form->addText("Transfer all current projects to:\n");
	
	$managers = db_layer::user_managers();
	$slct = new select($form, 'replacewith');
	$slct->addOption();
	$slct->addOptionsData($managers, 'userid', 'lastfirst');
	$slct->setSelected(db_layer::setting('default_manager'));
	$form->br(2);
	
	$cbox = new checkbox($form, 'publishfirst', 1, 1);
	$cbox->setlabel('create a publish before the change');
	$form->br();
	
	$cbox = new checkbox($form, 'publishafter', 1, 1);
	$cbox->setlabel('create a publish after the change');
	$form->br(2);
	
	new submit($form, 'Delete User');
}

$doc->output();
?>
