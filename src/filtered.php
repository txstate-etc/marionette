<?php
/**
 * Filtered Project List
 *
 * This page is designed to allow the user to filter and sort their project list
 * however they like.
 * 
 * @package phpmanage
 */

require_once("common.php");
require_once("widgets/projectlist.php");

$doc = doc::getdoc();
$user = doc::getuser();

$env = new env($doc);
$doc->appendTitle('Custom Filtered');
$doc->includeJS('!scriptaculous/prototype.js');
$doc->includeJS('!scriptaculous/scriptaculous.js');
$doc->includeJS('!filters.js');
$doc->includeCSS('!filters.css');
$doc->includeCSS('!jscal/calendar.css');
$doc->includeJS('!jscal/calendar.js');
$doc->includeJS('!jscal/calendar-en.js');
$doc->includeJS('!jscal/calendar-setup.js');
$doc->includeJS('!dateformat.js');

function add_list_data($data, $field, $valuecol, $labelcol) {
	$js .= "phpmanage_list_data['".$field."'] = [\n";
	$lines = array();
	foreach ($data as $d) {
		$lines[] = "['".addslashes($d[$valuecol])."','".addslashes($d[$labelcol])."']";
	}
	$js .= implode(",\n", $lines) . "]";
	doc::getdoc()->addJS($js);
}

$traits = array('scope', 'schedule', 'resource', 'quality', 'overall');

if (form::check_error('filters')) {
	$data = array();
	$types = array(
		'created'=>'date',
		'target'=>'date',
		'start'=>'date',
		'modified'=>'date',
		'priority'=>'pri',
		'manager'=>'list',
		'name'=>'search',
		'master'=>'list',
		'unit'=>'list',
		'classification'=>'list',
		'phaseid'=>'list',
		'activity'=>'search',
		'goal'=>'search',
		'anyhealth' => 'list'
	);
	foreach ($traits as $tr) $types[$tr] = 'list';
	for ($i = 1; $i < 1000; $i++) {
		if ($_REQUEST['field'.$i] && $_REQUEST['val'.$i] != '') {
			$data[] = array('type'=>$types[$_REQUEST['field'.$i]], 'field'=>$_REQUEST['field'.$i], 'control' => $_REQUEST['control'.$i], 'val' => $_REQUEST['val'.$i]);
		}
	}
	db_layer::filter_create($user->userid(), $data);
}

// elicit filter options from the user
$div = new div($env, 'filtertoggle');
$lnk = new link($div, '#', 'Edit Custom Filters');
$form = new form($env, 'filters');
$form->setid('filterarea');
$filts = new div($form, 'filtersonly'); 

// create the template for a filter
$div = new div($filts, '', 'singlefilter');
$slct = new select($div, 'field1', 'field');
$slct->addOption('', '-- Choose Filter --');
$slct->addOptionGroup('Users');
$slct->addOption('manager', 'Project Manager', FALSE, 'list');
$slct->addOptionGroup('Dates');
$slct->addOption('start', 'Start Date', FALSE, 'date');
$slct->addOption('target', 'Target Date', FALSE, 'date');
$slct->addOption('created', 'Created Date', FALSE, 'date');
$slct->addOption('modified', 'Last Modified', FALSE, 'date');
$slct->addOptionGroup('Project Health');
foreach ($traits as $tr)
	$slct->addOption($tr, ucfirst($tr).' Health', FALSE, 'list');
$slct->addOption('anyhealth', 'Any Health', FALSE, 'list');
$slct->addOptionGroup('Other Filters');
$slct->addOption('master', 'Master Project', FALSE, 'list');
$slct->addOption('priority', 'Priority', FALSE, 'pri');
$slct->addOption('name', 'Project Title', FALSE, 'search');
$slct->addOption('phaseid', 'Project Phase', FALSE, 'list');
$slct->addOption('unit', 'Area', FALSE, 'list');
$slct->addOption('classification', 'Project Type', FALSE, 'list');
$slct->addOption('activity', 'Current Activity', FALSE, 'search');
$slct->addOption('goal', 'Goal', FALSE, 'search');
$slct->setid('field1');

$lnk = new link($form, '#', 'Add another filter');
$lnk->setid('addfilterbutton');

$sbt = new submit($form, 'Apply Filters');


// javascript will need the proper path to the calendar icon
global $cfg;
$doc->addJS("phpmanage_cal_path = '".endslash($cfg['image_root'])."calendar.gif';");

// make some data available to the javascript
$doc->addJS("phpmanage_list_data = new Array();");
// create list of managers for javascript to use
add_list_data(db_layer::user_managers(), 'manager', 'userid', 'lastfirst');
// create a list of phases for javascript
$phases = db_layer::phase_getmany();
$phases[] = array('id'=>0, 'name'=>'Custom Phase');
add_list_data($phases, 'phaseid', 'id', 'name');
// create lists for all the trait statuses
$lists = db_layer::traits_lists();
foreach ($traits as $tr)
	add_list_data($lists['status'], $tr, 'id', 'name');
add_list_data($lists['status'], 'anyhealth', 'id', 'name');
add_list_data(db_layer::masters_getmany(), 'master', 'id', 'name');
add_list_data(db_layer::type_getmany(), 'classification', 'id', 'name');
add_list_data(db_layer::units_getmany(), 'unit', 'id', 'name');


$filtdata = db_layer::filter_currentdata($user->userid());
foreach ($filtdata as $f) {
	$jsarr[] = "{field: '".addslashes($f['field'])."', control: '".addslashes($f['control'])."', val: '".addslashes($f['val'])."'}";
}
if (!empty($jsarr)) {
	$doc->addJS("phpmanage_preloads = [\n".implode(",\n", $jsarr)."];");
}

// grab filtered list of projects
$perpage = db_layer::setting('pl_perpage');
$projects = db_layer::project_getmany((array) $sortopt + array(
	'latestpublish' => !checkperm('viewcurrent'), 
	'manager_show_current'=>$user->userid(),
	'perpage' => $perpage,
	'page' => $_REQUEST['pl_page'],
	'filterid'=>db_layer::filter_current($user->userid())
));
$foundrows = db_layer::$foundrows;
$lastpage = ceil($foundrows / $perpage);

new project_list($env, array('data'=>$projects, 'sortable'=>false, 'lastpage'=>$lastpage));

$doc->output();
?>
