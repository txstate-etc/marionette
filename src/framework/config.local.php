<?php
/** DATABASE DEFINITIONS **/

$dbref = "0";
$cfg['db'][$dbref]['server'] = getenv('DB_PORT_3306_TCP_ADDR');
$cfg['db'][$dbref]['username'] = getenv('DB_ENV_MYSQL_USER');
$cfg['db'][$dbref]['password'] = getenv('DB_ENV_MYSQL_PASSWORD');
$cfg['db'][$dbref]['dbname'] = getenv('DB_ENV_MYSQL_DATABASE');
$cfg['db'][$dbref]['extension'] = "mysqli";
$cfg['db'][$dbref]['compress'] = FALSE;

/** PATH INFORMATION **/

$cfg['server_path'] = "/var/www/html"

?>
