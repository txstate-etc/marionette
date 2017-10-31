<?php 
/**
 * Edit the project team list on a project
 * 
 * This form will handle adding and deleting project team members.
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$doc->addCSS("label { float:none; }");
$env = new env($doc);
$doc->appendTitle('Edit Project Team');

$userList = db_layer::user_getmany();

function getUserId($list, $username) {
	if (!$list || count($list) < 1)
		return null;

	foreach ($list as $l) {
		if ($l['username'] == $username) {
			return $l['userid'];
		}
	}
	return null;
}

function check_username ($username, $projectid = 0, $userid = 0) {
	if (!db_layer::projectteam_check($userid, $projectid)) return '';
	return ' already a member of the Project Team.';
}

if ($_REQUEST['ptdelete']) {
	foreach ((array)$_REQUEST['ptdelete'] as $pt) {
		db_layer::projectteam_del($_REQUEST['id'], $pt);
	}
}

if ($_REQUEST['username']) {
	$userid = getUserId($userList, $_REQUEST['username']);
	if ($userid) {
		db_layer::projectteam_add($_REQUEST['id'], $userid);
	}
}

$p = db_layer::project_get(array('id' => $_REQUEST['id']));
$latest = db_layer::project_get(array('id' => $_REQUEST['id'], 'latestpublish' => 1));

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
		$box->addJS("onchange", "document.getElementsByName('projectteam')[0].submit()");
		$existing->br();
	}
}


$query = 'https://secure.its.txstate.edu/iphone/people/json.pl?q=CURRENTVAL&n=8';
$netidquery = 'https://secure.its.txstate.edu/iphone/people/json.pl?q=userid%3ACURRENTVAL&n=4';
$handler = '
function (ob) {
	ret = [];
	for (var i = 0; i < ob.results.length; i++) {
		ret.push({lastname: ob.results[i].lastname, firstname: ob.results[i].firstname.replace(/\s*\W.+$/, ""), display: ob.results[i].lastname+", "+ob.results[i].firstname, value: ob.results[i].userid.toLowerCase()});
	}
	return ret;
}
';
$finalcallback = '
function (item) {
	$("netid").value = item.value;
	$("lastname").value = item.lastname;
	$("firstname").value = item.firstname;
}
';

$addnew = new fieldset($form, 'Add Member');
$addnew->br();
$tbox = new textbox($addnew, 'username', $u['username']);
$tbox->setid('netid');
$tbox->setlabel('User:');
$tbox->check_key();
$tbox->check_callback('check_username', $u['userid'], $p['id']);
$tbox->autocomplete($query, $handler, true, $finalcallback);
$tbox->autocomplete($netidquery, $handler, true, $finalcallback);
$addnew->br(2);

new submit($addnew, 'Add to Project Team');
$doc->output();

?>