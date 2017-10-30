<?php 
/**
 * Edit the project team list on a project
 * 
 * This form will handle adding and deleting project team members.
 * 
 * @package phpmanage
 */

require_once("common.php");
require_once("widgets/projectTeamList.php");

$doc = doc::getdoc();
$env = new env($doc);
$doc->appendTitle('Edit Project Team');

if (form::check_error('projectteam')) {
	foreach ((array)$_REQUEST['ptdelete'] as $pt) {
		db_layer::projectteam_del($_REQUEST['id'], $pt);
	}

	foreach ((array)$_REQUEST['ptadd'] as $pt) {
		db_layer::projectteam_add($_REQUEST['id'], $pt);
	}
}

$p = db_layer::project_get(array('id' => $_REQUEST['id']));
$latest = db_layer::project_get(array('id' => $_REQUEST['id'], 'latestpublish' => 1));

$userList = db_layer::user_getmany();

$grp = new linkgroup($env);
new link($grp, 'project.php', 'Return to Project', array('id'=>$p['id']));
$env->br(2);

$form = new form($env, 'projectteam');
new hidden($form, 'id', $p['id']);

if ($p['projectteam']) {
	$existing = new fieldset($form, 'Delete Project Team Members');
	foreach ($p['projectteam'] as $member) {
		$box = new checkbox($existing, 'ptdelete', $member['userid']);
		$box->setlabel($member['displayname']);
		$existing->br();
	}
}

$addnew = new fieldset($form, 'Add Member');
$settings = array(
	'users' => $userList,
	'projectteam' => $p['projectteam'],
	'editable' => true,
	'checkboxtag' => 'ptadd'
);
$ptControl = new projectTeamList($addnew, $settings);

new submit($form, 'Submit Changes');
$doc->output();

?>