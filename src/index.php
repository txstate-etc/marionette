<?php
/**
 * Master Project List - Index
 * 
 * @package phpmanage
 */

require_once("common.php");
require_once("widgets/projectlist.php");

session_start();

$doc = doc::getdoc();
$user = doc::getuser();

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
$filter = array(
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
);

$_SESSION['currentfilter'] = $filter;

$projects = db_layer::project_getmany($filter);
$foundrows = db_layer::$foundrows;
$lastpage = ceil($foundrows / $perpage);

if ($complete == -1 && checkperm('createnew')) {
	$grp = new linkgroup($env);
	$lnk = new link($grp, 'editproject.php', 'Create a New Project');
}

$lnk = new link($grp, 'csvdownload.php', 'Export CSV');

new project_list($env, array('data'=>$projects, 'sortable'=>true, 'lastpage'=>$lastpage));

$doc->output();
?> 
