<?php
/**
 * Functions
 *
 * A file to hold all the random functions I require.
 *
 * @package phpmanage
 */

/**
 * Convert DateTime to something appropriate for SQL insertion/update
 *
 * @param DateTime $date
 * @return string
 */
function date_for_sql($date) {
	$mydate = new DateTime($date);
	return $mydate->format('YmdHis');
}

/**
 * Check whether current user has a permission
 *
 * This is the standard function for authorizing specific sections of a page.
 * It tests whether the current user has the permission $perm.
 *
 * If the user has the 'sysadmin' permission, this always returns TRUE.
 *
 * @param string $perm
 * @return bool
 */
function checkperm($perm, $projectid = 0) {

	// Grab info about the user requesting permission
	$user = doc::getuser();
	static $u;
	if (!is_array($u)) $u = db_layer::user_get(array('userid'=>$user->userid()));

	// System Administrator OR has the permission being queried
	if ($u['permissions']['sysadmin'] || $u['permissions'][$perm]) return true;

	// Program Managers can create new projects in their area
	if ($perm == 'createnew' && (db_layer::active_progman($u['userid']) || ($u['permissions']['createproject'] && $u['unitid']))) return true;

	if ($projectid) {
		// Project Manager Permissions
		if (in_array($perm, array('publish', 'editcurrent', 'viewcurrent', 'owner', 'prioritize', 'addcomment'))) {
			$p = db_layer::project_get(array('id'=>$projectid));
			if ($p['current_manager_id'] == $u['userid']) return TRUE;
		}

		// Program Manager Permissions
		if (in_array($perm, array('editcurrent', 'completeproject', 'reassign', 'prioritize', 'addcomment'))) {
			$progmans = db_layer::project_progmans($projectid);
			foreach ($progmans as $pg) {
				if ($pg['userid'] == $u['userid']) return TRUE;
			}
		}

	}
	return false;
}

function readable_project($p) {
	unset($p['current_manager']);
	unset($p['current_manager_id']);
	unset($p['createdby']);
	unset($p['modifiedby']);
	unset($p['completedby']);
	unset($p['master']);
	unset($p['manager']);
	unset($p['classification']);
	unset($p['unit']);
	unset($p['phaseid']);
	unset($p['phasefree']);
	unset($p['scope']['flexibility']);
	unset($p['scope']['status']);
	unset($p['scope']['trend']);
	unset($p['schedule']['flexibility']);
	unset($p['schedule']['status']);
	unset($p['schedule']['trend']);
	unset($p['resource']['flexibility']);
	unset($p['resource']['status']);
	unset($p['resource']['trend']);
	unset($p['quality']['flexibility']);
	unset($p['quality']['status']);
	unset($p['quality']['trend']);
	unset($p['overall']['flexibility']);
	unset($p['overall']['status']);
	unset($p['overall']['trend']);
	foreach ((array) $p['attach'] as $i => $val) unset($p['attach'][$i]['id']);
	foreach ((array) $p['links'] as $i => $val) unset($p['links'][$i]['id']);
}

function report_project_changes($p1, $p2) {
	return report_changes(readable_project($p1), readable_project($p2));
}

function report_changes($arr1, $arr2, $prefix = '') {
	$arr1 = (array) $arr1;
	$arr2 = (array) $arr2;
	$keys = array_keys($arr1 + $arr2);
	foreach ($keys as $key) {
		if (is_array($arr1[$key]) || is_array($arr2[$key])) $ret .= report_changes($arr1[$key], $arr2[$key], $prefix.$key.':');
		elseif ($arr1[$key] != $arr2[$key]) $ret .= $prefix.$key.': '.$arr2[$key]."\n";
	}
	return $ret;
}

function notify_user($userid, $subject, $message) {
	if (db_layer::setting('test_server')) $prefix = ' (TEST SERVER)';
	$user = db_layer::user_get(array('userid'=>$userid));
	if ($user['email']) $email = $user['email'];
	else $email = $user['username'].'@txstate.edu';
	return mail($email, 'Marionette'.$prefix.' - '.$subject, $message, 'From: marionette@txstate.edu', '-f marionette@txstate.edu');
}
?>
