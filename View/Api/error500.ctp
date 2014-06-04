<?php
/*
$data = array(
	'status' => 0,
	'error' => array(
		'message' => 'Internal server error',
		'type' => 'internal_error',
	)
);*/


//debug($error);

$type = 'internal_error';

if (is_object($error)) {
	if ($error instanceof FacebookApiException) {
		$type = 'facebook_api_error';
	}
}

if (is_object($error)) {
	if ($error instanceof TwitterApiException) {
		$type = 'twitter_api_error';
	}
}

$data = array(
	'status' => 0,
	'error' => array(
		'message' => $error->getMessage(),
		'type' => $type,
		'filename' => str_replace(APP, 'app'.DS, $error->getFile()) . ':' . $error->getLine(),
	)
);

if (Configure::read('debug') > 0 ) {
	$data['error']['more'] = strip_tags( $this->element('exception_stack_trace') );
}
include_once('json.ctp');