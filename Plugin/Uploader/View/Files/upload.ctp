<?php

if (!isset($result)) {
	
	$result = array(
		'success' => false,
		'error' => 'Unknown internal error',
	);
	
}

echo json_encode($result);