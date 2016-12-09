<?php
$content = file_get_contents($_GET['url']);
foreach ($http_response_header as $head) {
	header($head);
}
echo $content;
?>