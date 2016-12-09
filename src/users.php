<?php
/**
 * Master list of Authorized Users
 * 
 * A lot of code is commented out here because I scaled back the
 * support for groups of users (and therefore group-based permissions).
 * 
 * Someday that support might re-appear.
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();

$env = new env($doc);
$doc->appendTitle('Users/Groups');
$doc->includeCSS('!users.css');

$users = db_layer::user_getmany();
$table = new table($env, 'usertable');
$toprow = new row($table, 'toprow');
$pad = $toprow->addCell('Users', 'title');
$pad->setwidth(8);
//$cell = $toprow->addCell('Permissions', 'permissions');
//$cell->setwidth(5);
//$pad = new cell($toprow, 'pad');

$trow = new row($table, 'trow');
$trow->addCell('NetID', 'netid');
$trow->addCell('Name', 'fullname');
$trow->addCell('Area', 'area');
$trow->addCell('Project Manager', 'manager boolean');
$trow->addCell('Program Manager', 'progman boolean');
$trow->addCell('Sysadmin', 'sysadmin boolean');
$trow->addCell('Create Projects', 'create boolean');
//$trow->addCell('View Published', 'viewpub');
//$trow->addCell('View Current', 'viewcurr');
//$trow->addCell('Edit Current', 'editcurr');
//$trow->addCell('Publish', 'publish');
$trow->addCell('Actions', 'actions');

foreach ($users as $u) {
	$class = ($class == 'even' ? 'odd' : 'even');
	$row = new row($table, $class);
	$row->addCell($u['username'], 'netid');
	$row->addCell($u['lastfirst'], 'fullname');
	$row->addCell($u['areaname'], 'area');
	$row->addCell(($u['manager'] ? 'X' : ''), ($u['manager'] ? 'granted' : 'denied'));
	$row->addCell(($u['progman'] ? 'X' : ''), ($u['progman'] ? 'granted' : 'denied'));
	$row->addCell(($u['permissions']['sysadmin'] ? 'X' : ''), ($u['permissions']['sysadmin'] ? 'granted' : 'denied'));
	$row->addCell(($u['permissions']['createproject'] ? 'X' : ''), ($u['permissions']['createproject'] ? 'granted' : 'denied'));
//	$row->addCell(($u['permissions']['viewpublished'] ? 'X' : ''), ($u['permissions']['viewpublished'] ? 'granted' : 'denied'));
//	$row->addCell(($u['permissions']['viewcurrent'] ? 'X' : ''), ($u['permissions']['viewcurrent'] ? 'granted' : 'denied'));
//	$row->addCell(($u['permissions']['editcurrent'] ? 'X' : ''), ($u['permissions']['editcurrent'] ? 'granted' : 'denied'));
//	$row->addCell(($u['permissions']['publish'] ? 'X' : ''), ($u['permissions']['publish'] ? 'granted' : 'denied'));
	$cell = new cell($row, 'actions');
	$grp = new linkgroup($cell);
	new link($grp, 'user.php', 'Edit', array('id'=>$u['userid']));
	new link($grp, 'user_delete.php', 'Delete', array('id'=>$u['userid']));
}

$grp = new linkgroup($env);
new link($grp, 'user.php', 'Add a User');

$env->br(2);

/* we'll ignore groups for now, until we add project-specific permissions
$groups = db_layer::group_getmany();
$table = new table($env, 'grouptable');
$toprow = new row($table, 'toprow');
$pad = $toprow->addCell('Groups', 'title');
$cell = $toprow->addCell('Permissions', 'permissions');
$cell->setwidth(4);
$pad = new cell($toprow, 'pad');

$trow = new row($table, 'trow');
$trow->addCell('Name', 'name');
$trow->addCell('View Published', 'viewpub');
$trow->addCell('View Current', 'viewcurr');
$trow->addCell('Edit Current', 'editcurr');
$trow->addCell('Publish', 'publish');
$trow->addCell('Actions', 'actions');

foreach ($groups as $g) {
	$class = ($class == 'even' ? 'odd' : 'even');
	$row = new row($table, $class);
	$row->addCell($g['lastfirst'], 'fullname');
	$row->addCell(($g['viewpublished'] ? 'X' : ''), ($g['viewpublished'] ? 'granted' : 'denied'));
	$row->addCell(($g['viewcurrent'] ? 'X' : ''), ($g['viewcurrent'] ? 'granted' : 'denied'));
	$row->addCell(($g['editcurrent'] ? 'X' : ''), ($g['editcurrent'] ? 'granted' : 'denied'));
	$row->addCell(($g['publish'] ? 'X' : ''), ($g['publish'] ? 'granted' : 'denied'));
	$cell = new cell($row, 'actions');
	$grp = new linkgroup($cell);
	new link($grp, 'group.php', 'Edit', array('id'=>$g['id']));
}
new link($env, 'group.php', 'Add a Group');
*/
$doc->output();
?>
