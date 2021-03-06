<?php
/**
 * A script to run at the beginning of every page load
 * 
 * This loads the various libraries we need and checks to see
 * if the user is logged in.
 * 
 * @package phpmanage
 */

require_once('framework/config.default.php');
require_once('database/dblayer_mysql.php');
require_once('widgets/env.php');
require_once('includes/functions.php');
require_once('includes/sessions.php');
require_once('includes/datetime.php');

db_layer::maintain_db();

$user = new manage_user();
$doc = new doc($user);
$doc->showbuildtime();

if (!$user->userid()) {
	$notLoginPage = preg_match('/login\.php/',$_SERVER['PHP_SELF']) == 0;

	$urlParams = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
	$filteredMatch = preg_match('/filtered\.php/',$_SERVER['PHP_SELF']);
	$notParameterizedFilteredPage = $filteredMatch == 0 || ($filteredMatch != 0 && $urlParams == null);

	if ($notLoginPage && $notParameterizedFilteredPage) {
		$user->store('redirect_after_login', $_SERVER['REQUEST_URI']);
		$doc->refresh(0, 'login.php', array($user->sidname()=>$user->sid()), db_layer::setting('ssl_login'));
		$doc->output();
		exit;
	}
}

?>
