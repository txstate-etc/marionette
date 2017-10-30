<?php
require_once("common.php");
session_start();
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="MarionetteExport.csv"');

$doc = doc::getdoc();
$user = doc::getuser();

$fout = fopen('php://output','w');
$filters = $_SESSION['currentfilter'];

fwrite($fout, db_layer::project_getcsv($filters));
fclose($fout);
?>