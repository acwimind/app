<?php

error_reporting(E_ALL);

session_name('CAKEPHP');
@session_start();
$member_big = isset($_SESSION['Auth']['User']['big']) ? $_SESSION['Auth']['User']['big'] : null;
@session_write_close();

set_time_limit(60);
date_default_timezone_set('Europe/Rome');

include_once('./lib/cache.php');
include_once('./lib/chat.php');

// if (function_exists('apc_fetch')) {
	
// 	Cache::init('Apc', array(
// 		'prefix' => 'haamble_chat_',
// 	));

// } else {
	
 	ChatCache::init('File', array(
 		'path' => '../../tmp/cache/persistent/',
 		'prefix' => 'haamble_chat_',
 	));

// }


$query = isset($_GET['q']) ? $_GET['q'] : null;


//initialize chat class
$chat = new Chat($member_big, true);

switch($query) {

	//initialize chat session, load old messages
	case 'init':

		$to = isset($_POST['to']) ? $_POST['to'] : (isset($_GET['to']) ? $_GET['to'] : null);

		$chat->init($to);

		break;

	//old chat messages
	case 'history':

		$to = isset($_POST['to']) ? $_POST['to'] : (isset($_GET['to']) ? $_GET['to'] : null);
		$first = isset($_POST['first']) ? $_POST['first'] : (isset($_GET['first']) ? $_GET['first'] : time());

		$chat->history($to, $first);

		break;

	//send a new message to chat
	case 'send':

		$to = isset($_POST['to']) ? $_POST['to'] : (isset($_GET['to']) ? $_GET['to'] : null);
		$msg = isset($_POST['msg']) ? $_POST['msg'] : (isset($_GET['msg']) ? $_GET['msg'] : null);

		$chat->send($to, $msg);

		break;

	//list members in current members chatroom
	case 'list':

		$chat->list_members();

		break;

	//polling request - javascript is waiting for php script to return some data
	case 'poll':
		
		$last = isset($_POST['last']) ? $_POST['last'] : (isset($_GET['last']) ? $_GET['last'] : null);
		$last_list = isset($_POST['last_list']) ? $_POST['last_list'] : (isset($_GET['last_list']) ? $_GET['last_list'] : null);

		$chat->poll($last, $last_list);

		break;

	//delete conversation with specified member
	case 'delete':
		
		$member_big = isset($_POST['big']) ? $_POST['big'] : (isset($_GET['big']) ? $_GET['big'] : null);

		$chat->delete($member_big);

		break;

	default:

		$chat->reply(array(), array('error' => 'Specify GET variable "q" - possible values: init, history, send, list, poll, delete'));

		break;
}

