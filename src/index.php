<?php
/**
 * Master Project List - Index
 * 
 * @package phpmanage
 */

require_once("common.php");
require_once("widgets/projectlist.php");

$doc = doc::getdoc();
$user = doc::getuser();
$doc->includeJS("!FileSaver.js");

$env = new env($doc);
$doc->appendTitle('Projects');

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

// show only complete projects?
$complete = ($_REQUEST['complete'] ? 1 : -1);

// grab all our projects
$perpage = db_layer::setting('pl_perpage');
$projects = db_layer::project_getmany(array(
	'latestpublish' => !checkperm('viewcurrent'), 
	'manager_show_current'=>$user->userid(),
	'complete' => $complete,
	'mine' => ($_REQUEST['mine'] ? $user->userid() : 0),
	'perpage' => $perpage,
	'page' => $_REQUEST['pl_page'],
	'sort'=>array(
		array($_REQUEST['sort'], ($_REQUEST['desc'] ? 'DESC' : 'ASC')),
		array('target', 'ASC')
	)
));
$foundrows = db_layer::$foundrows;
$lastpage = ceil($foundrows / $perpage);

if ($complete == -1 && checkperm('createnew')) {
	$grp = new linkgroup($env);
	$lnk = new link($grp, 'editproject.php', 'Create a New Project');
}

$lnk = new link($grp, '#', 'Export CSV');
$lnk->addJS('onclick', "exportFile();");

$projectCsvString = "Target, Project, Portfolio, Level, Type, Phase, Lead, Modified, Health, Timeline\n";
foreach ($projects as $proj) {
	$projectCsvString .= "\"" . $proj['target'] . "\",";
	$projectCsvString .= "\"" . htmlspecialchars($proj['name']) . "\",";
	$projectCsvString .= "\"" . $proj['master_name'] . "\",";
	$projectCsvString .= "\"" . $proj['unit_abbr'] . "\",";
	$projectCsvString .= "\"" . $proj['classification_name'] . "\",";
	$projectCsvString .= "\"" . $proj['phase'] . "\",";
	$projectCsvString .= "\"" . $proj['current_manager'] . "\",";
	$projectCsvString .= "\"" . $proj['modified'] . "\",";
	$projectCsvString .= "\"" . $proj['overall']['status_name'] . "\",";
	$projectCsvString .= "\"" . $proj['overall']['trend_name'] . "\"\r\n";
}

new project_list($env, array('data'=>$projects, 'sortable'=>true, 'lastpage'=>$lastpage));

$doc->output();
?>

<script type="text/javascript">
	function exportFile() {
		var myText = <?php echo json_encode($projectCsvString); ?>;
		saveTextAs(myText, "MarionetteExport.csv");
	}
</script>	 
