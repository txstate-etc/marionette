<?php
/**
 * Output the entire history of a project
 * 
 * This page generates a table view that gives an overview of
 * every published version of this project.
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$env = new env($doc);

$p = db_layer::project_get(array('id'=>$_REQUEST['id']));
$ispublish = $p['publishof'];

$doc->appendTitle('Project History');
$doc->appendTitle($p['name']);

$doc->includeCSS('!project.css');
$doc->includeJS('@prototype.js');
$doc->includeJS('!history.js');

if ($latest = db_layer::project_oldpublish($p['id'])) {
	$span = new div($env, 'oldpublish');
	$span->addText('You are viewing an older publish of this project.  Click ');
	new link($span, 'project.php', 'here', array('id'=>$latest));
	$span->addText(' for the latest published version.');
}

$latest = db_layer::project_get(array('id'=>$_REQUEST['id'], 'latestpublish'=>1));

$table = new table($env, 'infotable');
$pubdate = new DateTime($p['created']);
$table->loaddata(array(
	array('ID:', $latest['identify']),
	array('Project:', $latest['name']),
	array('Type:', $latest['classification_name']),
	array('PM:', $latest['manager_name']),
	array('Priority:', $latest['priority']),
	array('Area:', $latest['unit_name'].' ('.$latest['unit_abbr'].')'),
	array('Goal:', $latest['goal'])
));

if ($p['publishof']) $pubof = $p['publishof'];
else $pubof = $p['id'];
$publishes = db_layer::project_getmany(array('publishof'=>$pubof, 'sort'=>array(array('created', 'DESC'))));

$table = new table($env, 'historytable');
$trow = new row($table, 'trow');
$trow->addCell('Published', 'date');
$trow->addCell('Phase', 'phase');
$trow->addCell('Current Activity', 'activity');
$trow->addCell('Comment', 'comment');
$trow->addCell('Area', 'area');
$trow->addCell('Flexibility', 'flexibility');
$trow->addCell('Status', 'status');
$trow->addCell('Trend', 'trend');
$trow->addCell('Risk', 'risk');
$trow->addCell('Mitigation', 'risk');
foreach ($publishes as $pub) {
	$class = ($class == 'odd' ? 'even' : 'odd');
	$row = new row($table, $class);
	
	// Date
	$cell = new cell($row, 'date');
	$cell->setheight(5);
	$pubdate = new DateTime($pub['created']);
	$cell->addText($pubdate->format('m/d/y'));
	
	// Phase
	$cell = $row->addCell($pub['phase'], 'phase');
	$cell->setheight(5);
	
	// Activity
	$cell = $row->addCell($pub['activity'], 'activity');
	$cell->setheight(5);
	
	// Comment
	$cell = $row->addCell($pub['comment'], 'comment');
	$cell->setheight(5);

	// Attachments (part of comment cell)
	if (!empty($pub['attach'])) {
		$exp = new link($cell, '#', '(multiple attachments)', array(), 'attach_expand');
		$att = new div($cell, '', 'attachments');
		$grp = new linkgroup($att, array('nobound'=>true, 'separator'=>', '));
		foreach ($pub['attach'] as $a) {
			new link($grp, 'attachment.php', $a['filename'], array('id'=>$a['id']), 'attach');
		}
	}
	
	$aspects = array('scope', 'schedule', 'resource', 'quality', 'overall');
	$aspect_names = array('Scope', 'Schedule', 'Resources', 'Quality', 'Overall Health');
	$innerclass = $class;
	foreach ($aspects as $i => $aspect) {
		$t = $pub[$aspect];
		if ($i>0) $row = new row($table, $innerclass);
		$innerclass = ($innerclass == 'odd' ? 'even' : 'odd');
		$row->addCell($aspect_names[$i], 'aspect');
		$row->addCell($t['flexibility_name'], 'flexibility');
		$row->addCell('', 'status'.strToLower($t['status_name']));
		$row->addCell($t['trend_name'], 'trend');
		$row->addCell($t['risk'], 'risk');
		$row->addCell($t['mitigation'], 'mitigation');
	}
}

$grp = new linkgroup($env);
new link($grp, 'project.php', 'Back to Project Page', array('id'=>$p['id']));

$doc->output();
?>
