<?php
/**
 * Confirmation page for project completion
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$env = new env($doc);
$doc->appendTitle('Confirm Completion');

// make sure they have permission to do this
if (checkperm('completeproject', $_REQUEST['id'])) {
	$req = FALSE;
} elseif (checkperm('editcurrent', $_REQUEST['id'])) {
	$req = TRUE;
} else {
	$env->addText('You do not have permission to do that.');
	$doc->output();
	exit;
}

if (form::check_error('complete')) {
	db_layer::project_complete($_REQUEST['id'], array('request'=>$req, 'currentuser'=>doc::getuser()->userid()));
	if ($req) {
		$p = db_layer::project_get(array('id'=>$_REQUEST['id']));
		$pms = db_layer::project_progmans($p['id']);
		foreach ($pms as $pm) notify_user($pm['userid'], '(Completion Request) '.$p['name'],
			"The following project has been marked for completion and requires attention from a Program Manager:\n\n".link::absolute('!project.php', array('id'=>$p['id']))
		);
	}
	$doc->refresh(0,'index.php');
} else {
	$p = db_layer::project_get(array('id'=>$_REQUEST['id']));
	
	if ($req) $env->addText('The following projected will be moved to the "Pending Completion" phase and await approval for final completion.');
	else $env->addText('The following project will be moved to the "Complete" phase and (mostly) hidden from view.');
	
	$env->addText("\n\n".$p['name']."\n\nContinue?\n\n");

	$form = new form($env, 'complete');
	new hidden($form, 'id', $p['id']);
	
	//Submit
	new submit($form, ($req ? 'Request Completion' : 'Complete Project'));
}

$doc->output();
?>
