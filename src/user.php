<?php
/**
 * Edit an individual user and their permissions
 *
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();

$env = new env($doc);
$doc->appendTitle('Edit User');
$doc->includeCSS('!user.css');

function check_username ($username, $userid = 0) {
	if (db_layer::unique_username($userid, $username)) return '';
	return ' already exists in database.';
}

if (form::check_error('edituser')) {
	$info = array(
		'userid' => $_REQUEST['id'],
		'username' => $_REQUEST['username'],
		'lastname' => $_REQUEST['lastname'],
		'firstname' => $_REQUEST['firstname'],
		'unitid' => $_REQUEST['unitid'],
		'manager' => $_REQUEST['manager'],
		'progman' => $_REQUEST['progman'],
		'permissions' => array(
			'sysadmin' => $_REQUEST['sysadmin'],
			'createproject' => $_REQUEST['createproject'],
			'addcomment' => $_REQUEST['addcomment'],
			'viewpub' => $_REQUEST['viewpub'],
			'viewcurr' => $_REQUEST['viewcurr'],
			'editcurr' => $_REQUEST['editcurr'],
			'publish' => $_REQUEST['publish']
		)
	);
	db_layer::user_update($info);
	$env->addText('The user information has been saved.  Returning you to user management page.');
	$doc->refresh(0, 'users.php');
} else {
	$u = db_layer::user_get(array('userid'=>$_REQUEST['id']));
	$form = new form($env, 'edituser');
	new hidden($form, 'id', $u['userid']);

	// autocompletion details
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

	// Net ID
	$tbox = new textbox($form, 'username', $u['username']);
	$tbox->setid('netid');
	$tbox->setlabel('NetID*:');
	$tbox->check_required();
	$tbox->check_key();
	$tbox->check_callback('check_username', $u['userid']);
	$tbox->autocomplete($query, $handler, true, $finalcallback);
	$tbox->autocomplete($netidquery, $handler, true, $finalcallback);
	$form->br();

	// Last Name
	$tbox = new textbox($form, 'lastname', $u['lastname']);
	$tbox->setid('lastname');
	$tbox->setlabel('Last Name*:');
	$tbox->check_required();
	$tbox->autocomplete($query, $handler, true, $finalcallback);
	$form->br();

	// First Name
	$tbox = new textbox($form, 'firstname', $u['firstname']);
	$tbox->setid('firstname');
	$tbox->setlabel('First Name:');
	$tbox->autocomplete($query, $handler, true, $finalcallback);
	$form->br();

	// Unit / Department
	function unit_recur($units, $userid, $currentid) {
	  $ret = array();
	  foreach ((array) $units as $unit) {
		  if ($unit['manager'] != $userid) {
				$ret = array_merge($ret,unit_recur($unit['children'], $userid, $currentid));
				if ($currentid != $unit['id']) $ret[] = $unit['id'];
		  }
	  }
	  return $ret;
	}

	$sel = new select($form, 'unitid');
	$sel->setlabel('Unit/Area:');
	$units = db_layer::units_gethierarchy();
	$sel->addOption('', '------');
	$sel->addOptionHierarchy($units, 'id', 'name', 'children', '     ');
	$sel->setSelected($u['unitid']);
	if (!checkperm('sysadmin')) $sel->setDisabled(unit_recur($units, doc::getuser()->userid(), $u['unitid']));
	$form->br();

	// Permissions
	$fs = new fieldset($form, 'Setting Flags', 'flags');
	$cbox = new checkbox($fs, 'manager', 1, $u['manager']);
	$cbox->setlabel('Project Manager', 'cbox');
	$fs->br();
	$cbox = new checkbox($fs, 'progman', 1, $u['progman']);
	$cbox->setlabel('Program Manager', 'cbox');
	$fs->br();

	$fs = new fieldset($form, 'Permissions', 'permissions');
	$cbox = new checkbox($fs, 'sysadmin', 1, $u['permissions']['sysadmin']);
	$cbox->setid('sysadmin');
	$cbox->setlabel('System Administrator', 'cbox');
	$fs->br();

	$cbox = new checkbox($fs, 'createproject', 1, $u['permissions']['createproject']);
	$cbox->setid('createproject');
	$cbox->setlabel('Create Projects (in their area)', 'cbox');
	$fs->br();

	$cbox = new checkbox($fs, 'addcomment', 1, $u['permissions']['addcomment']);
	$cbox->setid('addcomment');
	$cbox->setlabel('Discuss Projects', 'cbox');
	$fs->br();

	/*
	$cbox = new checkbox($fs, 'viewcurr', 1, $u['permissions']['viewcurrent']);
	$cbox->setid('viewcurr');
	$cbox->setlabel('View Unpublished Projects', 'cbox');
	$fs->br();

	$cbox = new checkbox($fs, 'editcurr', 1, $u['permissions']['editcurrent']);
	$cbox->setid('editcurr');
	$cbox->setlabel('Edit Projects', 'cbox');
	$fs->br();

	$cbox = new checkbox($fs, 'publish', 1, $u['permissions']['publish']);
	$cbox->setid('publish');
	$cbox->setlabel('Publish Projects', 'cbox');
	$fs->br();
	*/
	new clear($form);
	$form->br();
	$sbt = new submit($form, 'Save User');
	$sbt->setlabel('  ');
}

$doc->output();
?>
