<?php
/**
 * Edit the links on a project
 * 
 * This form will handle adding and deleting links.
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$env = new env($doc);
$doc->appendTitle('Edit Links');

if (form::check_error('links')) 
{
	if ($_REQUEST['href']) 
	{
	  $href = $_REQUEST['href'];
	  if (!preg_match('/^https?:\/\//', $href)) $href = 'http://'.$href;
		db_layer::link_add($_REQUEST['id'], array(
			'href' => $href,
			'title' => $_REQUEST['title']
		));
		$_REQUEST['href'] = '';
		$_REQUEST['title'] = '';
	}

	if ($_REQUEST['projectcharter'])
	{
		$href = $_REQUEST['projectcharter'];
		if (!preg_match('/^https?:\/\//', $href)) $href = 'http://'.$href;
		db_layer::link_insertupdate($_REQUEST['id'], array(
			'href' => $href,
			'title' => 'Project Charter'
		));
	}

	if ($_REQUEST['issuelog'])
	{
		$href = $_REQUEST['issuelog'];
		if (!preg_match('/^https?:\/\//', $href)) $href = 'http://'.$href;
		db_layer::link_insertupdate($_REQUEST['id'], array(
			'href' => $href,
			'title' => 'Issue Log'
		));
	}

	if ($_REQUEST['livetimeline'])
	{
		$href = $_REQUEST['livetimeline'];
		if (!preg_match('/^https?:\/\//', $href)) $href = 'http://'.$href;
		db_layer::link_insertupdate($_REQUEST['id'], array(
			'href' => $href,
			'title' => 'Live Timeline'
		));
	}

	foreach((array) $_REQUEST['lndelete'] as $lnid) 
	{
		db_layer::link_del($lnid, $_REQUEST['id']);
	}

	foreach((array) $_REQUEST['lnrestore'] as $lnid) 
	{
		db_layer::link_add($_REQUEST['id'], array('id'=>$lnid));
	}
}

$p = db_layer::project_get(array('id'=>$_REQUEST['id']));
$latest = db_layer::project_get(array('id'=>$_REQUEST['id'], 'latestpublish'=>1));

$grp = new linkgroup($env);
new link($grp, 'project.php', 'Return to Project', array('id'=>$p['id']));
$env->br(2);

$form = new form($env, 'links');
new hidden($form, 'id', $p['id']);

// list existing links
if (!empty($p['links'])) $fsExisting = new fieldset($form, 'Delete Links');


// list deleted links, allow restoration
function compare_rowids($a, $b) { return ($a['id'] == $b['id'] ? 0 : ($a['id'] < $b['id'] ? -1 : 1)); }
$deleted = array_udiff($latest['links'], $p['links'], 'compare_rowids');
if (!empty($deleted)) $fsRestore = new fieldset($form, 'Restore Links');
else $form->br();
foreach ((array) $deleted as $ln) {
	$box = new checkbox($fsRestore, 'lnrestore', $ln['id']);
	$box->setlabel($ln['title'], 'cbox');
	$fsRestore->br();
}

$staticlinkfs = new fieldset($form, 'Static Links');

$tbProjectCharter = new textbox($staticlinkfs, 'projectcharter', '', 45);
$tbProjectCharter->setlabel('Project Charter: ');
$staticlinkfs->br(2);

$tbIssueLog = new textbox($staticlinkfs, 'issuelog', '', 45);
$tbIssueLog->setlabel('Issue Log: ');
$staticlinkfs->br(2);

$tbLiveTimeline = new textbox($staticlinkfs, 'livetimeline', '', 45);
$tbLiveTimeline->setlabel('Live Timeline:');
$staticlinkfs->br();

foreach ($p['links'] as $ln) {
	switch($ln['title'])
	{
		case 'Project Charter':
			$tbProjectCharter->setvalue($ln['href']);
			break;

		case 'Issue Log':
			$tbIssueLog->setvalue($ln['href']);
			break;
		
		case 'Live Timeline':
			$tbLiveTimeline->setvalue($ln['href']);
			break;

		default:
			$box = new checkbox($fsExisting, 'lndelete', $ln['id']);
			$box->setlabel($ln['href'].' ('.$ln['title'].')', 'cbox');
			$fsExisting->br();
			break;	
	}	
}


$fs = new fieldset($form, 'Add Link');

$href = new textbox($fs, 'href', '', 45);
$href->setlabel('URL:');
$fs->br();

$title = new textbox($fs, 'title', '', 35);
$title->setlabel('Title:');
$titleLabel = $title->getlabel();
$form->br();

//Submit
new submit($form, 'Submit Changes / New Link');

$doc->output();
?>
