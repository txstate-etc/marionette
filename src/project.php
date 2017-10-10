<?php
/**
 * Project Detail Page
 *
 * Shows all the details of a single publish of a project.
 *
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$env = new env($doc);

$p = db_layer::project_get(array('id'=>$_REQUEST['id'], 'latestpublish' => !checkperm('viewcurrent', $_REQUEST['id'])));
$ispublish = $p['publishof'];
if ($ispublish) $toplevel = $p['publishof'];
else $toplevel = $p['id'];
if ($ispublish) $haspublishes = 1;
else $haspublishes = db_layer::project_haspublishes($p['id']);

if (!checkperm('viewcurrent', $p['id']) || !$haspublishes) $latest = $p;
else $latest = db_layer::project_get(array('id'=>$_REQUEST['id'], 'latestpublish'=>1));

$doc->appendTitle('Project Detail');
$doc->appendTitle($p['name']);

$doc->includeCSS('!project.css');

if ($haspublishes && $p['complete'] != 'complete') {
	if ($ispublish && $latest['id'] != $p['id']) {
		$span = new div($env, 'oldpublish');
		$span->addText('You are viewing an older publish of this project.  Click ');
		new link($span, 'project.php', 'here', array('id'=>$latest));
		$span->addText(' for the latest published version.');
	} elseif (checkperm('viewcurrent', $p['id'])) {
		/*
		$span = new div($env, 'oldpublish');
		if ($latest['id'] == $p['id']) {
			$span->addText('You are viewing the published version of this project.  Click ');
			new link($span, 'project.php', 'here', array('id'=>$p['publishof']));
			$span->addText(' for the latest unpublished version.');
		} else {
			$span->addText('You are viewing the unpublished project data. Changes are marked with red asterisks.  Click ');
			new link($span, 'project.php', 'here', array('id'=>$latest['id']));
			$span->addText(' for the published version.');
		}
		*/
	}
}

class red_asterisk extends text {
	public function __construct($parent) {
		text::__construct($parent, "*", 'asterisk');
	}
}

function project_add_data_field($table, $label, $value, $oldvalue, $isdate = false) {
	static $class;
	if ($isdate && strtotime($value)) {
		$newdate = date_from_database($value);
		$olddate = date_from_database($oldvalue);
		$value = $newdate->format('m/d/y');
		$oldvalue = $olddate->format('m/d/y');
	}
	$class = ($class == 'even' ? 'odd' : 'even');
	$row = new row($table, $class);
	$cell = new cell($row, 'col1');
	$cell->addText($label);
	if ($value != $oldvalue) new red_asterisk($cell);
	$cell->addText(':');
	$cell = new cell($row, 'col2');
	$cell->addText($value);
}

$table = new table($env, 'infotable');
if (!$haspublishes) $latest['created'] = 'Never';
if ($p['activitydate'] != '0000-00-00 00:00:00') {
	$dt = new DateTime($p['activitydate']);
	$actpre = $dt->format('(n/j) ');
}
if ($p['commentdate'] != '0000-00-00 00:00:00') {
	$dt = new DateTime($p['commentdate']);
	$compre = $dt->format('(n/j) ');
}
project_add_data_field($table, 'ID', $p['identify'], $latest['identify']);
project_add_data_field($table, 'Project', $p['name'], $latest['name']);
if ($p['master_name'] || $latest['master_name']) project_add_data_field($table, 'Portfolio', $p['master_name'], $latest['master_name']);
project_add_data_field($table, 'Start Date', $p['start'], $latest['start'], true);
project_add_data_field($table, 'Target Date', $p['target'], $latest['target'], true);
project_add_data_field($table, 'Last Publish', $latest['created'], $latest['created'], true);
project_add_data_field($table, 'Project Type', $p['classification_name'], $latest['classification_name']);
project_add_data_field($table, 'Project Level', $p['unit_name'].' ('.$p['unit_abbr'].')', $latest['unit_name'].' ('.$latest['unit_abbr'].')');
// did something special on this one: folks who can ONLY see the published version will still see the CURRENT project manager, for clarity
project_add_data_field($table, 'Project Lead', (checkperm('viewcurrent', $p['id']) || $p['id'] != $latest['id'] ? $p['manager_name'] : $p['current_manager']), $latest['manager_name']);
project_add_data_field($table, 'Phase', $p['phase'], $latest['phase']);
$status_icon = '[i class="status_'.strToLower($p['overall']['status_name']).'"][/i]';
project_add_data_field($table, 'Health', $status_icon.$p['overall']['status_name'], $status_icon.$latest['overall']['status_name']);
$timelineValue = $p['overall']['trend'] == 0 ? 'None' : $p['overall']['trend_name'];
project_add_data_field($table, 'Timeline', $timelineValue, $timelineValue, false);
project_add_data_field($table, 'Status Update', $compre.$p['comment'], $compre.$latest['comment']);

function compare_rowids($a, $b) { return ($a['id'] == $b['id'] ? 0 : ($a['id'] < $b['id'] ? -1 : 1)); }

// Links
$rowFirst = new row($table);
$rowFirst->addCell('Links:', 'col1');


// Create specific rows for the Static Link Fields and keep track of them in variables
$row_projectCharter = new row($table);
$row_projectCharter -> addCell("   " . 'Project Charter:', 'col1');
$cell_projectCharter = $row_projectCharter->addCell('None', 'col2');

$row_issueLog = new row($table);
$row_issueLog -> addCell("   " . 'Issue Log:', 'col1');
$cell_issueLog = $row_issueLog->addCell('None', 'col2');

$row_liveTimeline = new row($table);
$row_liveTimeline -> addCell("   " . 'Live Timeline:', 'col1');
$cell_liveTimeline = $row_liveTimeline->addCell('None', 'col2');

$row = new row($table);
$row->addCell("   " . 'Other:', 'col1');
$cell = $row->addCell('', 'col2');

$linksgrp = new linkgroup($cell, array('right'=>' ] '));
$grp = new linkgroup($cell, array('nobound'=>TRUE, 'separator'=>"\n"));
foreach ($latest['links'] as $ln) {
	$wasthere[$ln['id']] = TRUE;
}
foreach ($p['links'] as $ln) {
	$info = link::parse_href($ln['href']);

	switch($ln['title'])
	{
		case 'Project Charter':
			$cell_projectCharter->clearChildren();
			$lnk = new link($cell_projectCharter, $info['file'], $ln['title'], $info['vars']);
			$lnk->target();
			break;

		case 'Issue Log':
			$cell_issueLog->clearChildren();
			$lnk = new link($cell_issueLog, $info['file'], $ln['title'], $info['vars']);
			$lnk->target();
			break;

		case 'Live Timeline':
			$cell_liveTimeline->clearChildren();
			$lnk = new link($cell_liveTimeline, $info['file'], $ln['title'], $info['vars']);
			$lnk->target();
			break;

		default:
			$lnk = new link($grp, $info['file'], $ln['title'], $info['vars']);
			$lnk->target();
			if ($info['hash']) $lnk->sethash($info['hash']);
			if (!$wasthere[$ln['id']]) new red_asterisk($lnk);
			break;
	}

	
	
}
// were there any links on the last publish that have now disappeared?
$deleted = array_udiff($latest['links'], $p['links'], 'compare_rowids');
foreach ((array) $deleted as $ln) {
	$info = link::parse_href($ln['href']);
	$lnk = new link($grp, $info['file'], $ln['title'], $info['vars'], 'attach_deleted');
	$lnk->target();
	if ($info['hash']) $lnk->sethash($info['hash']);
	new red_asterisk($lnk);
}

// Attachments
$row = new row($table);
$row->addCell(" ");
$row = new row($table);
$row->addCell('*** Attachments: ', 'col1');
$cell = new cell($row);
$attachgrp = new linkgroup($cell, array('right'=>' ] '));
$grp = new linkgroup($cell, array('nobound'=>TRUE, 'separator'=>', '));
foreach ($latest['attach'] as $att) {
	$wasthere[$att['id']] = TRUE;
}
foreach ($p['attach'] as $att) {
	$lnk = new link($grp, 'attachment.php', $att['filename'], array('id'=>$att['id']));
	if (!$wasthere[$att['id']]) new red_asterisk($lnk);
}
// were there any attachments on the last publish that have now disappeared?
$deleted = array_udiff($latest['attach'], $p['attach'], 'compare_rowids');
foreach ((array) $deleted as $att) {
	$lnk = new link($grp, 'attachment.php', $att['filename'], array('id'=>$att['id']), 'attach_deleted');
	new red_asterisk($lnk);
}

$rowCaption = new row($table);
$rowCaption->addCell(" ");
$rowCaption = new row($table);
$rowCaption->addCell('*** = This field is deprecated and will be removed in a future Marionette release. Use the Links section instead.');

// Project Links (sidebar)
if ($ispublish) $histid = $p['publishof'];
else $histid = $p['id'];
$sidebar = new div($env, '', 'sidebar');
$grp = new htmllist($sidebar, 'sidebarlist');

if ($haspublishes) new link($grp, 'history.php', 'Project History', array('id'=>$histid));
if (checkperm('editcurrent', $p['id']) && (!$ispublish || $p['id'] == $latest['id'])) new link($grp, 'editproject.php', 'Edit Project', array('id'=>$histid));

// add a form submission for publishing the project
// should not be a link as that would be a violation of the rule
// against state change on GET
/*
if (!$p['publishof'] && checkperm('publish', $p['id']) && $p['complete'] != 'complete') {
	if (form::check_error('publish', 10)) {
		db_layer::project_publish($p['id'], $user->userid());
		$doc->refresh(0, 'project.php', array('id'=>$p['id']));
	}
	$form = new form($env, 'publish', 'bottombutton');
	$form->setid('publish');
	new hidden($form, 'id', $p['id']);
	$hid = new hidden($form, 'pwo_submit', 'Submit');

	$lnk = new link($grp, '#', 'Publish Project');
	$lnk->addJS('onclick', 'document.forms.publish.submit(); return false;');
}
*/

if (checkperm('editcurrent', $p['id']) && !$ispublish && $p['complete'] != 'complete') {
	if (checkperm('owner', $p['id'])) $rowFirst->addCell(new link($linksgrp, 'links.php', '[ edit ]', array('id'=>$histid)), 'col2');

	// Removed the Edit button, since the Attachments functionality has now been deprecated
	// This will allow users to access existing Attachments for the time being, but prevent them
	// from editing or adding
	//if (checkperm('owner', $p['id'])) new link($attachgrp, 'attach.php', 'edit', array('id'=>$histid));

	if (checkperm('manageprojects', $p['id']))
		new link($grp, 'deleteproject.php', 'Delete Project', array('id'=>$histid));
	if (!checkperm('completeproject', $p['id']) && $p['complete']!='complete')
		new link ($grp, 'completeproject.php', 'Complete Project', array('id'=>$histid));
}
if (checkperm('completeproject', $histid) && $p['complete']!='complete')
	new link ($grp, 'completeproject.php', 'Complete Project', array('id'=>$histid));

// Project Goal
$goal = new div($sidebar, '', 'goal');
$goal->addText("Project Goal:", 'goaltitle');
if ($p['goal'] != $latest['goal']) new red_asterisk($goal);
$goal->addText("\n".$p['goal']);

// Comment Section
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$comments = db_layer::comments_getall($p['id'], $page, 10);
$lastpage = ceil(db_layer::$foundrows / 10);
if (form::check_error('comment')) {
	if ($_REQUEST['pwo_submit'] == 'Preview') {
		$now = new DateTime();
		array_unshift($comments, array('comment'=>$_REQUEST['newcomment'], 'author'=>'You (preview)', 'created'=>$now->format('YmdHis')));
	} else {
		db_layer::comments_add($p['id'], $_REQUEST['newcomment'], $user->userid());
		$doc->refresh(0, '', array('id'=>$p['id']));
		$doc->output();
		exit;
	}
}

if (!empty($comments)) {
	if ($lastpage > 1) {
		$grp = new linkgroup($env);
		for ($i = 1; $i <= $lastpage; $i++) {
			if ($i == $page) { $grp->addText('[b]'.$i.'[/b]'); }
			else { new link($grp, '', $i, array('page'=>$i)+doc::create_mimic()); }
		}
		$env->br(2);
	}
	foreach ($comments as $c) {
		$div = new div($env, '', 'commentdiv');
		$author = new div($div, '', 'commentauthor');
		$author->addText($c['author']);
		$ts = new timestamp('db', $c['created']);
		$stamp = new div($div, '', 'commentstamp');
		$stamp->addText(relative_date($ts));
		$comment = new div($div, '', 'commenttext');
		$comment->addBBCode($c['comment']);
	}
}

if (checkperm('addcomment')) {
	$div = new div($env, '', 'commentdiv');
	$form = new form($div, 'comment');
	new hidden($form, 'id', $p['id']);
	$fs = new fieldset($form, 'Add to the Discussion');
	$fs->addclass('commentfs');
	$tarea = new textarea($fs, 'newcomment', 60, 5);
	$fs->br();
	$subt = new submit($fs, 'Preview');
	$subt = new submit($fs);
}

$doc->output();
?>
