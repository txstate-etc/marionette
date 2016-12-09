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

if (form::check_error('links')) {
	if ($_REQUEST['href']) {
	  $href = $_REQUEST['href'];
	  if (!preg_match('/^https?:\/\//', $href)) $href = 'http://'.$href;
		db_layer::link_add($_REQUEST['id'], array(
			'href' => $href,
			'title' => $_REQUEST['title']
		));
		$_REQUEST['href'] = '';
		$_REQUEST['title'] = '';
	}

	foreach((array) $_REQUEST['lndelete'] as $lnid) {
		db_layer::link_del($lnid, $_REQUEST['id']);
	}
	foreach((array) $_REQUEST['lnrestore'] as $lnid) {
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
if (!empty($p['links'])) $fs = new fieldset($form, 'Delete Links');
foreach ($p['links'] as $ln) {
	$box = new checkbox($fs, 'lndelete', $ln['id']);
	$box->setlabel($ln['href'].' ('.$ln['title'].')', 'cbox');
	$fs->br();
}

// list deleted links, allow restoration
function compare_rowids($a, $b) { return ($a['id'] == $b['id'] ? 0 : ($a['id'] < $b['id'] ? -1 : 1)); }
$deleted = array_udiff($latest['links'], $p['links'], 'compare_rowids');
if (!empty($deleted)) $fs = new fieldset($form, 'Restore Links');
else $form->br();
foreach ((array) $deleted as $ln) {
	$box = new checkbox($fs, 'lnrestore', $ln['id']);
	$box->setlabel($ln['title'], 'cbox');
	$fs->br();
}

$fs = new fieldset($form, 'Add Link');
$href = new textbox($fs, 'href', '', 45);
$href->setlabel('URL:');
$fs->br();
$title = new textbox($fs, 'title', '', 35);
$title->setlabel('Title:');
$form->br(2);

//Submit
new submit($form, 'Submit Changes / New Link');

$doc->output();
?>
