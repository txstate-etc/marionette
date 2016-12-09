<?php
/**
 * Send the binary data of an attachment to the browser
 * 
 * Sends gif/jpg/jpeg/png as inline, everything else as downloaded
 * attachment.
 * 
 * @package phpmanage
 */

require_once("common.php");
$att = db_layer::attach_get($_REQUEST['id']);
$ext = file_extension($att['filename']);

if ($ext == 'gif') {
	$type = 'image/gif';
	$disp = 'inline';
} elseif ($ext == 'jpg' || $ext == 'jpeg') {
	$type = 'image/jpeg';
	$disp = 'inline';
} elseif ($ext == 'png') {
	$type = 'image/png';
	$disp = 'inline';
} else {
	$type = 'application/octet-stream';
	$disp = 'attachment';
}
header('Content-type: '.$type);
header('Content-Disposition: '.$disp.'; filename="'.addslashes($att['filename']).'"');
echo $att['data'];
?>