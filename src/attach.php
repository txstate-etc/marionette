<?php
/**
 * Edit the attachments on a project
 * 
 * This form will handle adding and deleting attachments.
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$env = new env($doc);
$doc->appendTitle('Edit/Upload Attachments');

if (form::check_error('attach')) {
	$file = new file('upload');
	$atts = db_layer::project_attach($_REQUEST['id']);
	foreach ($atts as $att) $names[] = $att['filename'];
	$file->unique_in_array($names);
	if ($file->data()) {
		$info = array(
			'filename' => $file->filename(),
			'data' => $file->data()
		);
		db_layer::attach_add($_REQUEST['id'], $info);
	}
	foreach((array) $_REQUEST['attdelete'] as $attid) {
		db_layer::attach_del($attid, $_REQUEST['id']);
	}
	foreach((array) $_REQUEST['attrestore'] as $attid) {
		db_layer::attach_add($_REQUEST['id'], array('id'=>$attid));
	}
}

$p = db_layer::project_get(array('id'=>$_REQUEST['id']));
$latest = db_layer::project_get(array('id'=>$_REQUEST['id'], 'latestpublish'=>1));

$grp = new linkgroup($env);
new link($grp, 'project.php', 'Return to Project', array('id'=>$p['id']));
$env->br(2);

$form = new form($env, 'attach');
new hidden($form, 'id', $p['id']);

// list existing attachments
if (!empty($p['attach'])) $fs = new fieldset($form, 'Delete Attachments');
foreach ($p['attach'] as $att) {
	$box = new checkbox($fs, 'attdelete', $att['id']);
	$box->setlabel($att['filename'], 'cbox');
	$fs->br();
}

// list deleted attachments, allow restoration
function compare_attach($a, $b) { return ($a['id'] == $b['id'] ? 0 : ($a['id'] < $b['id'] ? -1 : 1)); }
$deleted = array_udiff($latest['attach'], $p['attach'], 'compare_attach');
if (!empty($deleted)) $fs = new fieldset($form, 'Restore Attachments');
else $form->br();
foreach ((array) $deleted as $att) {
	$box = new checkbox($fs, 'attrestore', $att['id']);
	$box->setlabel($att['filename'], 'cbox');
	$fs->br();
}

$fs = new fieldset($form, 'Upload New Document');
$file = new filebrowser($fs, 'upload');
$form->br(2);

//Submit
new submit($form, 'Submit Changes / Files');

$doc->output();
?>
