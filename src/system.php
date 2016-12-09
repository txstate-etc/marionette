<?php
/**
 * Edit the various lists used for popup menus
 * 
 * This page is intended for administrators to be able
 * to edit the lists of things like project types; mostly
 * static but may change over time.
 * 
 * @package phpmanage
 */

require_once("common.php");
require_once("widgets/traitmanage.php");

$doc = doc::getdoc();
$env = new env($doc);
$env = new div($env, 'invisdiv');
$doc->appendTitle('System List Management');
$doc->includeCSS('!system.css');
$doc->includeJS('!scriptaculous/prototype.js');
$doc->includeJS('!scriptaculous/scriptaculous.js');

if (form::check_error('area')) {
	function split_abbr($raw) {
		if (preg_match('/(.*?) \((.*?)\)$/', $raw, $match))
			return array('name'=>$match[1], 'abbrev'=>$match[2]);
		else return array('name'=>$raw);
	}
	foreach ($_REQUEST as $key => $val) {
		if ($val && preg_match('/^area(\d+)/', $key, $match)) {
			$aid = $match[1];
			$info = split_abbr($val) + array(
				'id' => $aid,
				'manager' => $_REQUEST['manarea'.$aid]
			);
			db_layer::unit_update($info);
		}
	}
	if ($_REQUEST['areanew']) {
		$info = split_abbr($_REQUEST['areanew']) + array(
			'manager' => $_REQUEST['manareanew']
		);
		db_layer::unit_update($info);
	}
	$doc->refresh(0, 'system.php');
	$doc->output();
	exit;
}

// Deal with Flexibility/Status/Trends
$traits = db_layer::traits_lists();
new traitmanage($env, array('data'=>$traits['flexibility'], 'legend'=>'Flexibility', 
							'submit'=>'Save Flexibility', 'type'=>'flexibility'));
new traitmanage($env, array('data'=>$traits['status'], 'legend'=>'Status', 
							'submit'=>'Save Status', 'type'=>'status'));

new clear($env);

new traitmanage($env, array('data'=>$traits['trend'], 'legend'=>'Trend', 
							'submit'=>'Save Trend', 'type'=>'trend'));
$phases = db_layer::phase_getmany();
new traitmanage($env, array(
		'data'=>$phases, 
		'legend'=>'Phase',
		'submit'=> 'Save Project Phase', 
		'type' => 'phases',
		'protect' => array('complete'=>array('pending','complete'))
));

new clear($env);

$types = db_layer::type_getmany();
new traitmanage($env, array('data'=>$types, 'legend'=>'Project Types',
							'submit'=> 'Save Project Type', 'type' => 'classification'));
$masters = db_layer::masters_getmany();
new traitmanage($env, array('data'=>$masters, 'legend'=>'Master Projects',
							'submit'=> 'Save Master Project', 'type' => 'masters'));

new clear($env);

// Working Groups, Units, Areas, whatever
global $cfg;
$doc->addJS("phpmanage_img_root = '".$cfg['image_root']."';");
$doc->includeJS('!system.js');

$units = db_layer::units_gethierarchy();
$form = new form($env, 'area', 'areaform');
$fs = new fieldset($form, 'Unit/Area');
$fs->addText('(drag to organize, double-click to edit)', 'instruction');

// Helper function so we can make this recur
function area_recur($units, $parent) {
	$doc = doc::getdoc();
	if (!is_array($units) || empty($units)) return;
	$list = new htmllist($parent);
	foreach ($units as $u) {
		// line above each item, drag onto this to change ordering
		$above = new listitem($list, 'above');
		$above->setid('above'.$u['id']);
		
		// the item itself
		$li = new listitem($list);
		$span = new span($li, 'area'.$u['id'], 'area_item');
		$span->addText($u['name'].' ('.$u['abbrev'].')', 'area_text');
		if ($u['manager']) {
			$manspan = new span($span, '', 'area_manager');
			$manspan->addText(' (');
			$manspan->addText($u['manager_name'], 'match_text');
			$manspan->addText(')');
		}

		// recur
		area_recur($u['children'], $li);
	}
}

area_recur($units, $fs);

$list = new htmllist($fs);
$item = new listitem($list);
$tbox = new textbox($item, 'areanew', '', 45);
$mans = db_layer::user_progmans();
$slct = new select($item, 'manareanew', 'areamanslct');
$slct->setid('manareanew');
$slct->addOption();
$slct->addOptionsData($mans, 'userid', 'fullname');

$div = new div($fs, '', 'submitdiv');
$trash = new span($div, 'areatrash', 'trash');
new submit($div, 'Save Units/Areas');

$doc->output();
?>
