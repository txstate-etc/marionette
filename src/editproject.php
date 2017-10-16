<?php
/**
 * Edit an individual project
 *
 * @package phpmanage
 */

require_once("common.php");
require_once("widgets/traitsform.php");
require_once("widgets/contexthelp.php");

$doc = doc::getdoc();
$env = new env($doc);
$doc->appendTitle('Edit Project');
$doc->includeCSS('!editproject.css');
$doc->includeCSS('!jscal/calendar.css');
$doc->includeJS('!jscal/calendar.js');
$doc->includeJS('!jscal/calendar-en.js');
$doc->includeJS('!jscal/calendar-setup.js');

$p = db_layer::project_get(array('id'=>$_REQUEST['id']));
$creating = !$p['id'];
$editing = !$creating;
$owner = checkperm('owner', $p['id']);

if ($creating && !checkperm('createnew')) {
	$env->addText('You do not have permission to create new projects.');
	$doc->output();
	exit;
}
if ($editing && !checkperm('editcurrent', $p['id'])) {
	$env->addText('You do not have permission to edit this project.');
	$doc->output();
	exit;
}
if ($p['publishof']) {
	$env->addText('You cannot edit the published version of a project.');
	$doc->output();
	exit;
}

if (form::check_error('project')) {
	$env->addText('The project has been saved. Redirecting you to project page.');
	if ($p['comment'] != $_REQUEST['comment']) {
		$dt = new DateTime();
		$commentdate = $dt->format('YmdHis');
	} else {
		$commentdate = $p['commentdate'];
	}
	if ($p['activity'] != $_REQUEST['activity']) {
		$dt = new DateTime();
		$activitydate = $dt->format('YmdHis');
	} else {
		$activitydate = $p['activitydate'];
	}

	function trait_data($trait, $p) {
		return array(
			'id'=>$p[$trait]['id'],
			'flexibility'=>($_REQUEST[$trait.'_flexibility'] ? $_REQUEST[$trait.'_flexibility'] : $p[$trait]['flexibility']),
			'status'=>($_REQUEST[$trait.'_status'] ? $_REQUEST[$trait.'_status'] : $p[$trait]['status']),
			'trend'=>($_REQUEST[$trait.'_trend'] ? $_REQUEST[$trait.'_trend'] : $p[$trait]['trend']),
			'risk'=>$_REQUEST[$trait.'_risk'],
			'mitigation'=>$_REQUEST[$trait.'_mitigation']
		);
	}

	$data = array(
		'id'=>$_REQUEST['id'],
		'name'=> ($_REQUEST['name'] ? $_REQUEST['name'] : $p['name']),
		'master'=>($_REQUEST['master'] ? $_REQUEST['master'] : $p['master']),
		'goal'=>$_REQUEST['goal'],
		'classification'=>($_REQUEST['classification'] ? $_REQUEST['classification'] : $p['classification']),
		'manager' => ($_REQUEST['manager'] ? $_REQUEST['manager'] : $p['manager']),
		'unit'=>($_REQUEST['unit'] ? $_REQUEST['unit'] : $p['unit']),
		'start'=>($_REQUEST['start'] ? date_for_sql($_REQUEST['start']) : $p['start']),
		'target'=>($_REQUEST['target'] ? date_for_sql($_REQUEST['target']) : $p['target']),
		'priority'=>$_REQUEST['priority'],
		'phaseid'=>($_REQUEST['phaseid'] == 'custom' ? '' : $_REQUEST['phaseid']),
		'phasefree'=>($_REQUEST['phaseid'] == 'custom' ? $_REQUEST['phasefree'] : ''),
		'activity'=>$_REQUEST['activity'],
		'activitydate'=>$activitydate,
		'scope'=>trait_data('scope', $p),
		'schedule'=>trait_data('schedule', $p),
		'resource'=>trait_data('resource', $p),
		'quality'=>trait_data('quality', $p),
		'overall'=>trait_data('overall', $p),
		'comment'=>$_REQUEST['comment'],
		'commentdate'=>$commentdate
	);
	if (!$owner && $editing) {
		// need to make sure that disabled fields don't destroy data
		$data = array(
			'goal'=>$p['goal'],
			'manager' => (checkperm('reassign', $p['id']) ? $_REQUEST['manager'] : $p['manager']),
			'priority'=>(checkperm('prioritize', $p['id']) ? $_REQUEST['priority'] : $p['priority']),
			'phaseid'=>$p['phaseid'],
			'phasefree'=>$p['phasefree'],
			'activity'=>$p['activity'],
			'activitydate'=>$p['activitydate'],
			'comment'=>$p['comment'],
			'commentdate'=>$p['commentdate'],
			'scope'=>array(
				'risk'=>$p['scope']['risk'],
				'mitigation'=>$p['scope']['mitigation']
			)+trait_data('scope', $p),
			'schedule'=>array(
				'risk'=>$p['schedule']['risk'],
				'mitigation'=>$p['schedule']['mitigation']
			)+trait_data('schedule', $p),
			'resource'=>array(
				'risk'=>$p['resource']['risk'],
				'mitigation'=>$p['resource']['mitigation']
			)+trait_data('resource', $p),
			'quality'=>array(
				'risk'=>$p['quality']['risk'],
				'mitigation'=>$p['quality']['mitigation']
			)+trait_data('quality', $p),
			'overall'=>array(
				'risk'=>$p['overall']['risk'],
				'mitigation'=>$p['overall']['mitigation']
			)+trait_data('overall', $p)
		) + $data;
	}

	$id = db_layer::project_update($data, $user->userid());
	$data = db_layer::project_get(array('id'=>$id));
	$created = ($id != $p['id']);
	$changes = report_changes($p, $data);
	$href = link::absolute('!project.php', array('id'=>$id));
	if ($changes) {
		if ($created) $subj = '(Created) '.$data['name'];
		else $subj = '(Updated) '.$data['name'];
		$msg = "Project ".($created ? 'Created' : 'Updated').".  Link to project:\n\n".$href."\n\n".($created ? 'Details' : 'Changes').":\n\n".$changes;
		$userids = db_layer::project_subscribers($id);
		foreach ($userids as $userid)
			notify_user($userid, $subj, $msg);
	}
	if ($created || $p['manager'] != $data['manager']) notify_user($data['manager'], '(Project Assigned) '.$data['name'],
		"A project has been assigned to you.\n\nClick below to view project:\n\n".$href
	);
	$traits = array('scope','schedule', 'resource', 'quality', 'overall');
	$alert = false;
	$risk = array();
	$miti = array();
	foreach ($traits as $t) if ($p[$t]['status'] != 3 && $data[$t]['status'] == 3) { $alert = true; $risk[$t] = $data[$t]['risk']; $miti[$t] = $data[$t]['mitigation']; }
	if ($alert) {
		$msg = "A project has been moved to a Red status:\n\n".$href."\n\nComment:\n".$data['comment']."\n\n";
		foreach ($risk as $i => $r) $msg .= ucfirst($i)." Risk: ".$r."\n".ucfirst($i)." Mitigation: ".$miti[$i]."\n\n";
		$pms = db_layer::project_progmans($id);
		foreach ($pms as $pm) notify_user($pm['userid'], '(Project Alert) '.$data['name'], $msg);
	}
	db_layer::project_publish($id, $user->userid());
	$doc->refresh(0,'project.php',array('id'=>$id));
} else {

	$form = new form($env, 'project');
	$form->prevent_dupes();
	new hidden($form, 'id', $p['id']);

	// Unique ID
	if ($p['id']) {
		$tbox = new textbox($form, 'identify', $p['identify'], 10);
		$tbox->disabled();
		$tbox->setlabel("Unique ID:");

		$form->br();
	}

	// Name
	$tbox = new textbox($form, 'name', $p['name'], 40);
	$tbox->setlabel('Project Name:');
	if ($editing && !checkperm('manageprojects', $p['id'])) $tbox->disabled();
	else $tbox->check_required();

	$form->br();

	// Master Project
	$masters = db_layer::masters_getmany();
	$sel = new select($form, 'master');
	$sel->setlabel('Portfolio:');
	if ($editing && !checkperm('manageprojects', $p['id'])) $sel->disabled();
	$sel->addOption();
	$sel->addOptionsData($masters, 'id', 'name');
	$sel->setSelected($p['master']);
	new contexthelp($form, array('id'=>'masterproject'));

	$form->br();

	// Goal
	$tarea = new textarea($form, 'goal', 30, 5, $p['goal']);
	$tarea->setlabel('Project Goal:');
	if ($editing && !$owner) $tarea->disabled();

	$form->br();

	// Classification
	$types = db_layer::type_getmany();
	$sel = new select($form, 'classification');
	$sel->setlabel('Project Type:');
	$sel->addOption('', '------');
	$sel->addOptionsData($types, 'id', 'name');
	$sel->setSelected($p['classification']);
	$sel->check_required();
	if ($editing && !$owner) $sel->disabled();
	new contexthelp($form, array('id'=>'classification'));

	$form->br();

	// Unit / Department
	function unit_recur($units, $u, $currentid) {
	  $ret = array();
	  foreach ((array) $units as $unit) {
		  if ($unit['manager'] != $u['userid'] && ($u['unitid'] != $unit['id'] || !$u['permissions']['createproject'])) {
				$ret = array_merge($ret,unit_recur($unit['children'], $u, $currentid));
				if ($currentid != $unit['id']) $ret[] = $unit['id'];
		  }
	  }
	  return $ret;
	}

	$u = db_layer::user_get(array('userid'=>doc::getuser()->userid()));
	$sel = new select($form, 'unit');
	$sel->setlabel('Project Level:');
	$units = db_layer::units_gethierarchy();
	$sel->addOption('', '------');
	$sel->addOptionHierarchy($units, 'id', 'name', 'children', '     ');
	$sel->setSelected(($p['unit'] ? $p['unit'] : $u['unitid']));
	if (!checkperm('sysadmin')) $sel->setDisabled(unit_recur($units, $u, $p['unit']));
	$sel->check_required();
	$form->br();

	// Project Manager
	$managers = db_layer::user_managers();
	$sel = new select($form, 'manager');
	$sel->setlabel('Project Lead:');
	$sel->addOption('', '------');
	$sel->addOptionsData($managers, 'userid', 'lastfirst');
	$sel->setSelected($p['manager']);
	if ($editing && !checkperm('reassign', $p['id'])) $sel->disabled();
	else $sel->check_required();

	$form->br();

	// Start Date
	$start = new DateTime($p['start']);
	$tbox = new textbox($form, 'start', $start->format('m/d/Y'), 20);
	$tbox->setlabel('Start Date:');
	$tbox->setid("startdate");
	$tbox->check_date();
	$form->addText(' ');
	$lnk = new link($form, '#', 'cal');
	$lnk->setid("startdatebutton");
	if ($editing && !$owner) $tbox->disabled();
	else $doc->addJS_afterload("Calendar.setup({inputField : 'startdate', ifFormat : '%m/%d/%Y', button : 'startdatebutton'});");
	new contexthelp($form, array('id'=>'startdate'));

	$form->br();

	// Target Date
	$target = new DateTime($p['target']);
	$tbox = new textbox($form, 'target', $target->format('m/d/Y'), 20);
	$tbox->setlabel('Target Date:');
	$tbox->setid("targdate");
	$tbox->check_date();
	$form->addText(' ');
	$lnk = new link($form, '#', 'cal');
	$lnk->setid("targdatebutton");
	if ($editing && !$owner) $tbox->disabled();
	else $doc->addJS_afterload("Calendar.setup({inputField : 'targdate', ifFormat : '%m/%d/%Y', button : 'targdatebutton'});");
	new contexthelp($form, array('id'=>'targetdate'));

	$form->br();

	// Phase
	$sel = new select($form, 'phaseid');
	$sel->setlabel('Phase:');
	$sel->setid('phaseid');
	$sel->addOption('', '------');
	$phases = db_layer::phase_getmany();
	foreach($phases as $ph) {
		if (!$ph['complete'] || $p['phaseid'] == $ph['id']) $sel->addOption($ph['id'], $ph['name']);
	}
	$sel->addOption('custom', 'Custom Phase:');
	if ($p['phasefree']) $p['phaseid'] = 'custom';
	$sel->setSelected($p['phaseid']);
	$sel->check_required();
	$sel->addJS('onchange', 'phasehide(this)');
	$doc->addJS('
function phasehide(sel) {
	var ph = document.getElementById("phasefree");
	if (sel.options[sel.selectedIndex].value == "custom") ph.style.display = "inline";
	else ph.style.display = "none";
}
');
	$doc->addJS_afterload('phasehide(document.getElementById("phaseid"))');
	$tbox = new textbox($form, 'phasefree', $p['phasefree'], 30);
	$tbox->setid('phasefree');
	if ($editing && !$owner) { $sel->disabled(); $tbox->disabled(); }
	new contexthelp($form, array('id'=>'phase'));

	$form->br();

	// Health (aka now - Risk)
	$sel = new select($form, 'overall_status');
	$sel->setlabel('Risk:');
	$sel->setid('health');
	$sel->addOption('', '------');
	$traits = db_layer::traits_lists();
	foreach($traits['status'] as $h) {
		$sel->addOption($h['id'], $h['name']);
	}
	$sel->setSelected($p['overall']['status']);
	$sel->check_required();
	if ($editing && !$owner) $sel->disabled();
	new contexthelp($form, array('id'=>'health'));

	$form->br();

	//Trend (aka now - Timeline)
	$sel = new select($form, 'overall_trend');
	$sel->setlabel('Timeline:');
	$sel->setid('timeline');
	$sel->addOption('', '------');
	foreach($traits['trend'] as $t) {
		$sel->addOption($t['id'], $t['name']);
	}
	$sel->setSelected($p['overall']['trend']);
	if ($editing && !$owner) $sel->disabled();

	$form->br();

	//Status Update
	$tarea = new textarea($form, 'comment', 50, 10, $p['comment']);
	$tarea->setlabel('Status Update:');
	if ($editing && !$owner) $tarea->disabled();
	new contexthelp($form, array('id'=>'comment'));

	$form->br(2);

	//Submit
	new submit($form, 'Save Project');
}

$doc->output();
?>
