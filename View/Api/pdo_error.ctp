<?php

$type = 'database_error';

$data = array(
	'status' => 0,
	'error' => array(
		'message' => $error->getMessage(),
		'user_message' => 'Internal application error (SQL)',
		'db_query' => $error->queryString,
		'type' => $type,
		'filename' => str_replace(APP, 'app'.DS, $error->getFile()) . ':' . $error->getLine(),
	)
);

if (Configure::read('debug') > 0 ) {
	$data['error']['more'] = $this->element('exception_stack_trace');
}

$debug = 1;
include_once('json.ctp');