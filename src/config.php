<?php
/**
 * Set various configuration variables
 *
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();
$env = new env($doc);
$doc->appendTitle('System Config');

if (form::check_error('config')) {
	db_layer::setting_set('ldap_server', $_REQUEST['ldap_server']);
	db_layer::setting_set('ldap_port', $_REQUEST['ldap_port']);
	db_layer::setting_set('ldap_userstring', $_REQUEST['ldap_userstring']);
	db_layer::setting_set('use_cas', $_REQUEST['cas_mode'] > 0);
	db_layer::setting_set('force_cas', $_REQUEST['cas_mode'] == 2);
	db_layer::setting_set('cas_server', $_REQUEST['cas_server']);
	db_layer::setting_set('cas_port', $_REQUEST['cas_port']);
	db_layer::setting_set('cas_path', '/'.$_REQUEST['cas_path']);
	db_layer::setting_set('default_manager', $_REQUEST['default_manager']);
	db_layer::setting_set('pl_perpage', $_REQUEST['pl_perpage']);
	db_layer::setting_set('test_server', $_REQUEST['test_server']);
	db_layer::setting_set('people_search_url', $_REQUEST['people_search_url']);
	$env->addText('Settings have been saved.  Taking you back to config page.');
	$doc->refresh(2, 'config.php');
} else {
	$settings = db_layer::setting();
	$form = new form($env, 'config');

	/** LDAP SETTINGS **/
	$fs = new fieldset($form, 'LDAP Login Settings');
	// Server
	$serv = new textbox($fs, 'ldap_server', $settings['ldap_server'], 40);
	$serv->setlabel('LDAP Server:');
	// Port
	$fs->addText(':');
	$port = new textbox($fs, 'ldap_port', $settings['ldap_port'], 4);
	$port->setlabel('LDAP Port:');
	$port->hidelabel();
	// User String Pattern
	$fs->br();
	$patt = new textbox($fs, 'ldap_userstring', $settings['ldap_userstring'], 55);
	$patt->setlabel('Userstring Pattern:');
	$patt->tooltip('Specify the Distinguished Name to check against LDAP, use %netid% to stand in for the individual\'s username.');

	/** CAS SETTINGS **/
	$fs = new fieldset($form, 'CAS Single Sign-on Settings');
	// use_cas and force_cas
	$mode = new select($fs, 'cas_mode');
	$mode->setlabel('CAS Mode:');
	$mode->addOption(0, 'No CAS', !$settings['use_cas']);
	$mode->addOption(1, 'Respect CAS sessions but do not log in to CAS', $settings['use_cas'] && !$settings['force_cas']);
	$mode->addOption(2, 'Use CAS for all login activity', $settings['use_cas'] && $settings['force_cas']);
	$fs->br();
	// CAS server
	$serv = new textbox($fs, 'cas_server', $settings['cas_server'], 40);
	$serv->setlabel('CAS Server:');
	// CAS port
	$fs->addText(':');
	$port = new textbox($fs, 'cas_port', $settings['cas_port'], 4);
	$port->setlabel('CAS Port:');
	$port->hidelabel();
	// CAS path
	$fs->addText('/');
	$path = new textbox($fs, 'cas_path', substr($settings['cas_path'], 1), 10);
	$path->setlabel('CAS Path');
	$path->hidelabel();

	/** OTHER SETTINGS **/
	$fs = new fieldset($form, 'Other Settings');
	$slct = new select($fs, 'default_manager');
	$slct->setlabel('Default Project Manager:');
	$managers = db_layer::user_managers();
	$slct->addOption();
	$slct->addOptionsData($managers, 'userid', 'lastfirst');
	$slct->setSelected(db_layer::setting('default_manager'));
	$fs->clear();
	// Projects per page
	$tbox = new textbox($fs, 'pl_perpage', db_layer::setting('pl_perpage'), 4);
	$tbox->setlabel('Projects per Page:');
	$fs->clear();
	// Test Server Checkbox
	$cbox = new checkbox($fs, 'test_server', 1, db_layer::setting('test_server'));
	$cbox->setlabel('Test Server:', 'normal');
	$fs->clear();
	// People Search server
	$serv = new textbox($fs, 'people_search_url', $settings['people_search_url'], 40);
	$serv->setlabel('People Search Service:');

	/** SUBMIT **/
	$form->br();
	$sbt = new submit($form, 'Save Settings');
	$sbt->setlabel('  ');
}

$doc->useprettycode();
$doc->output();

?>
