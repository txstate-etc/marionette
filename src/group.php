<?php
/**
 * Edit an individual group.
 * 
 * Groups are not yet implemented in phpmanage, but may appear
 * in a future release.
 * 
 * @package phpmanage
 */

require_once("common.php");

$doc = doc::getdoc();

$env = new env($doc);
$doc->appendTitle('Edit Group');
$doc->includeCSS('!group.css');


$doc->output();
?>
