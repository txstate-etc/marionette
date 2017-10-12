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
require_once("csvHelper.php");


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
$doc->includeJS("!FileSaver.js");

function construct_query_parms($rules) {
	$parmString = '';
	foreach ($rules as $r) {
		$parmString .= sprintf('%s|%s|%s', $r["field"], $r['control'], $r['val']);
		$parmString .= '&';
	}

	return trim($parmString, '&');
}

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
		'manager'=>'list',
		'name'=>'search',
		'master'=>'list',
		'unit'=>'list',
		'classification'=>'list',
		'phaseid'=>'list',
		'comment'=>'search',
		'goal'=>'search',
		'overall' => 'list'
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
$lnk = new link($env, '#', 'Export CSV');
$lnk->addJS('onclick', "exportFile();");
$lnk = new link($div, '#', 'Edit Custom Filters');
$form = new form($env, 'filters');
$form->setid('filterarea');
$filts = new div($form, 'filtersonly');

// create the template for a filter
$div = new div($filts, '', 'singlefilter');
$slct = new select($div, 'field1', 'field');
$slct->addOption('', '-- Choose Filter --');
$slct->addOptionGroup('Users');
$slct->addOption('manager', 'Project Lead', FALSE, 'list');
$slct->addOptionGroup('Dates');
$slct->addOption('start', 'Start Date', FALSE, 'date');
$slct->addOption('target', 'Target Date', FALSE, 'date');
$slct->addOption('created', 'Created Date', FALSE, 'date');
$slct->addOption('modified', 'Last Modified', FALSE, 'date');
$slct->addOptionGroup('Other Filters');
$slct->addOption('master', 'Portfolio', FALSE, 'list');
$slct->addOption('name', 'Project Title', FALSE, 'search');
$slct->addOption('phaseid', 'Project Phase', FALSE, 'list');
$slct->addOption('unit', 'Level', FALSE, 'list');
$slct->addOption('classification', 'Project Type', FALSE, 'list');
$slct->addOption('goal', 'Goal', FALSE, 'search');
$slct->addOption('overall', 'Health', FALSE, 'list');
$slct->addOption('comment', 'Project Status', FALSE, 'search');
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


// try to set $filtdata by parsing the URL query string
$filtdata = null;
$url = $_SERVER['REQUEST_URI'];
$urlParams = parse_url($url, PHP_URL_QUERY);

$use_query_parms = False;
if (!is_null($urlParams))
{
	$filtdata = array();

	$urlParams = urldecode($urlParams);

	$filterArray = explode('&', $urlParams);
	foreach ($filterArray as $filter)
	{
		$filterComponents = explode('|', $filter);
		if (count($filterComponents) != 3)
		{
			$filtdata = null;
			break;
		}

		$filter_field = $filterComponents[0];
		$filter_control = $filterComponents[1];
		$filter_val = $filterComponents[2];
		

		
		$filter_type = 'DEFAULT';
		switch(strtoupper($filter_control))
		{
			case 'EQUAL':
			case 'NOTEQUAL':
				$filter_type = 'list';
				break;

			case 'GT':
			case 'LT':
				$filter_type = 'date';
				break;

			case 'MAYCONT':
			case 'MUSTCONT':
			case 'NOTCONT':
				$filter_type = 'search';
				break;
		}
		

		$filtdata[] = array('type' => $filter_type, 
			'field' => $filter_field, 
			'control' => $filter_control, 
			'val' => $filter_val);
	}
	$use_query_parms = True;
}

if (is_null($filtdata))
{
	$filtdata = db_layer::filter_currentdata($user->userid());
	$use_query_parms = False;

	//If the user came to this page via a submit (aka - clicking the Apply Filters button)
	//Then redirect to the parameterized URL...

	if(!is_null($_POST["pwo_submit"]))
	{
		$URL = 'filtered.php';
		$qparms = construct_query_parms($filtdata);
		if (strlen($qparms) != 0)
		{
			$URL = $URL . '?' . $qparms;
		}

		$doc->addJS("document.location.href='{$URL}");
		echo '<META HTTP-EQUIV="refresh" content="0;URL=' . $URL . '">';
	}
}

//...Otherwise, continue loading the filtered.php page as normal using the user's DB filters
foreach ($filtdata as $f) {
	$jsarr[] = "{field: '".addslashes($f['field'])."', control: '".addslashes($f['control'])."', val: '".addslashes($f['val'])."'}";
}
if (!empty($jsarr)) {
	$doc->addJS("phpmanage_preloads = [\n".implode(",\n", $jsarr)."];");
}

// grab filtered list of projects
$perpage = db_layer::setting('pl_perpage');

$project_parms = (array) $sortopt + array(
'latestpublish' => !checkperm('viewcurrent'),
'manager_show_current'=>$user->userid(),
'perpage' => $perpage,
'page' => $_REQUEST['pl_page']);

if ($use_query_parms)
{
	$project_parms['filter_rules'] = $filtdata;
}
else
{
	$project_parms['filterid'] = db_layer::filter_current($user->userid());
}

// figure out how to sort
if (!$_REQUEST['sort']) {
	// no sort requested, let's check their session for a saved pref
	if ($user->grab('sort')) {
		$_REQUEST['sort'] = $user->grab('sort');
		$_REQUEST['desc'] = $user->grab('desc');
	} else {
		$_REQUEST['sort'] = 'target'; // default sort
	}
} else {
	// sort requested, let's save it as a session preference
	$user->store('sort', $_REQUEST['sort']);
	$user->store('desc', $_REQUEST['desc']);
}

$project_parms['sort'] = array(
		array($_REQUEST['sort'], ($_REQUEST['desc'] ? 'DESC' : 'ASC')),
		array('target', 'ASC')
	);

$projects = db_layer::project_getmany($project_parms);

$foundrows = db_layer::$foundrows;
$lastpage = ceil($foundrows / $perpage);

$projectCsvString = csvHelper::createCsv($projects);

new project_list($env, array('data'=>$projects, 'sortable'=>true, 'lastpage'=>$lastpage));

$doc->output();

?>
<script type="text/javascript">
	function exportFile() {
		var myText = <?php echo json_encode($projectCsvString); ?>;
		saveTextAs(myText, "FilteredMarionetteExport.csv");
	}
</script>	 
