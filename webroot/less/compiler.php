<?php
	header('Content-type: text/css');
	include('lessc.inc.php');
	$less = new lessc;
	echo $less->compileFile("styles.less");
?>