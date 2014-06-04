<?php
if (!defined('ONLINE_TIMEOUT'))
define('ONLINE_TIMEOUT', '-1 hour');

if (!defined('POLL_MAX_HISTORY'))
define('POLL_MAX_HISTORY', '-30 minutes');

if (!defined('POLL_CHAT_REFRESH'))
define('POLL_CHAT_REFRESH', '-20 seconds');

if (!defined('POLL_MAX_REQUEST_TIME'))
define('POLL_MAX_REQUEST_TIME', 10);	//in seconds

class Chat {

	private $pdo = null;
	private $member_big;
	public $verbose = false;

	private $msg_replace = array(
		'<span class="smile">:)</span>' => array(':)', ':-)'),
		'<span class="smile wink">;)</span>' => array(';)', ';-)'),
		'<span class="smile grin">:D</span>' => array(':D', ':-D'),
		'<span class="smile tongue">:P</span>' => array(':P', ':-P'),
		'<span class="smile kiss">:*</span>' => array(':*', ':-*'),
		'<span class="smile sad">:(</span>' => array(':(', ':-('),
	);

	/**
	 * initialize chat
	 * @param int $member_big     BIG of currently logged in member
	 */
	public function __construct($member_big, $verbose=false) {
		$this->verbose = $verbose;
		$this->member_big = $member_big;

		if (empty($this->member_big)) {
			$this->reply(array('r' => 'denied'), array('error' => 'Access denied, you should also log out the user from frontend'));
		}
	}

	/**
	 * load latest messages
	 * @param  int $to_big BIG of the member to open conversation with
	 * @return JSON
	 */
	public function init($to_big) {

		$msgs = $this->get_messages($to_big, time());
		$this->reply($msgs);

	}

	/**
	 * load old messages in conversation
	 * @param  int $to_big BIG of the member the conversation is with
	 * @param  timestamp $first_timestamp timestamp of oldest loaded message
	 * @return array of messages
	 */
	public function history($to_big, $first_timestamp) {

		$msgs = $this->get_messages($to_big, $first_timestamp);
		$this->reply($msgs);

	}


	/**
	 * send a chat message
	 * @param  integer $to_big BIG of recipient (member)
	 * @param  string  $msg    text of the message
	 * @return JSON
	 */
	public function send($to_big=0, $msg='') {

		if (empty($msg) || $to_big==0 || $this->member_big == $to_big) {
			if (!$this->verbose) {
				$this->reply(array('r' => false));
			} else {
				if ($this->member_big == $to_big) {
					$verbose = array('error' => 'Cannot send message to self');
				} else {
					$verbose = array('error' => 'Empty POST variables "to" or "msg"');
				}
				$this->reply(array('r' => false), $verbose);
			}
		}

		//check ignore list
		{
			$stm = $this->db()->prepare('select "chat_ignore" from "member_settings" where "from_big" = :recipient_big and "to_big" = :current_member_big;');
			$stm->execute(array(
				':current_member_big'	=> $this->member_big,
				':recipient_big'		=> $to_big,
			));
			$ignore_list = $stm->fetch(PDO::FETCH_ASSOC);
			if (isset($ignore_list['chat_ignore']) && $ignore_list['chat_ignore'] == 1) {
				$this->reply(array('r' => false), array('error' => 'Cannot send chat message, you are blocked by the user.'));
			}
		}

		$msg_time = time();

		$rel_id = $this->get_rel_id($to_big);

		if (empty($rel_id)) {
			try {
				$stm = $this->db()->prepare('insert into "member_rels" ("member1_big", "member2_big") values (:from_big, :to_big);');
				$stm->execute(array(
					':from_big'	=> $this->member_big,
					':to_big'	=> $to_big,
				));
				$rel_id = $this->db()->lastInsertId('member_rels_id_sequence');
			} catch(PDOException $e) {
				if ($e->getCode() == 23503) {
					$this->reply(array('r' => 'invalid'), array('msg' => 'Invalid recipient BIG (POST variable "to")'));
				}
			}
		}

		//TODO: from_status, to_status - 0 joined, 1 checked in, 2 no join (what? why bother?) wasnt this status meant to indicate deleted messages?

		$msg = htmlspecialchars($msg);

		$stm = $this->db()->prepare(
			'insert into "chat_messages" ' .
			'("rel_id", "from_big", "to_big", "checkin_big", "text", "from_status", "to_status", "created", "status") values ' .
			'(
				:rel_id,
				:from_big,
				:to_big,
				(select "big" from "checkins" where "member_big" = :from_big and ("checkout" is null or "checkout" > now()) order by "created" desc),
				:msg,
				1,
				1,
				:created,
				1
			)'
		);
		$created = date('c', $msg_time);
		$stm->execute(array(
			':rel_id'	=> $rel_id,
			':from_big'	=> $this->member_big,
			':to_big'	=> $to_big,
			':msg'		=> $msg,
			':created'	=> $created//date('Y-m-d H:i:s', $msg_time),
		));

		ChatCache::write($to_big.'_last_msg', $msg_time);

		// Send push notifications
		$params = array(
			'relid'		=> $rel_id,
			'membig'	=> $this->member_big,
			'parbig'	=> $to_big,
			'msg'		=> $msg,
			'created'	=> $created,
			'msgid'		=> $this->pdo->lastInsertId('chat_messages_id_sequence')
		);
		$this->post_async('http://' . $_SERVER['SERVER_NAME'] . '/api/chat_messages/send_push', $params);

		$this->reply(array('r' => true), array('success' => 'Message "'.addslashes($msg).'" sent to member BIG '.$to_big.' with timestamp '.$msg_time));

	}

	/**
	 * members in the same chatroom as current member (ie checked in the same place or chatted before)
	 * @return JSON
	 */
	public function list_members() {

		//ignored users
		$stm = $this->db()->prepare('select "to_big" from "member_settings" where "from_big" = :current_member_big and "chat_ignore" = 1');
		$stm->execute(array(
			':current_member_big'	=> $this->member_big,
		));
		$ignored_members_raw = $stm->fetchAll(PDO::FETCH_ASSOC);
		$ignored_members = array(0);
		foreach($ignored_members_raw as $member) {
			$ignored_members[] = $member['to_big'];
		}
		$ignored_members = implode(',', $ignored_members);

		//joined users
		$stm = $this->db()->prepare(
			'select distinct on (m.big) "m"."big", "m"."name"||\' \'||substring("m"."surname" from 1 for 1)||\'.\' as "name", (case when "c"."physical"=1 then \'onsite\' else \'online\' end) as "status"
			from "checkins" "c"
				left join "members" "m" on ("c"."member_big" = "m"."big")
			where ("c"."checkout" is null or "c"."checkout" > now())
				and "c"."event_big" = (select "event_big" from "checkins" where "member_big" = :member_big and ("checkout" is null or "checkout" > now()) order by "created" desc limit 1)
				and ("m"."last_web_activity" > :online_timeout or "m"."last_mobile_activity" > :online_timeout)
				and "m"."big" != :member_big
				and "m"."big" not in ('.$ignored_members.')
			order by m.big, "c"."created" asc'
		);
		$stm->execute(array(
			':member_big'		=> $this->member_big,
			':online_timeout'	=> date('c', strtotime(ONLINE_TIMEOUT)),
		));
		$joined_members = $stm->fetchAll(PDO::FETCH_ASSOC);

		$joined_member_bigs = array(0);
		foreach($joined_members as $member) {
			$joined_member_bigs[] = $member['big'];
		}
		$joined_member_bigs = implode(',', $joined_member_bigs);

		//users we already had conversation with
		$stm = $this->db()->prepare(
			'select distinct on (m.big) "m"."big", "m"."name"||\' \'||substring("m"."surname" from 1 for 1)||\'.\' as "name",
				(case when ("m"."last_web_activity" > :online_timeout or "m"."last_mobile_activity" > :online_timeout) then \'online\' else \'offline\' end) as "status"
			from "member_rels" "r"
				left join "members" "m" on ("m"."big" = (case when "r"."member1_big"=:member_big then "r"."member2_big" else "r"."member1_big" end))
				left join "chat_messages" as "cm" on ("cm"."rel_id" = "r"."id" and (case when "cm"."from_big"=:member_big then "cm"."from_status" else "cm"."to_status" end) = 1)
			where "m"."big" != :member_big
				and (r.member1_big = :member_big OR r.member2_big = :member_big)
				and "m"."big" not in ('.$ignored_members.','.$joined_member_bigs.')
				and "cm"."id" > 0
			order by m.big'
		);
		$stm->execute(array(
			':member_big'		=> $this->member_big,
			':online_timeout'	=> date('c', strtotime(ONLINE_TIMEOUT)),
		));
		$history_members = $stm->fetchAll(PDO::FETCH_ASSOC);

		foreach($joined_members as $key=>$val) {
			$joined_members[$key]['img'] = ProfilePicture::get_resized($val['big'], 28, 28);
		}

		foreach($history_members as $key=>$val) {
			$history_members[$key]['img'] = ProfilePicture::get_resized($val['big'], 28, 28);
		}


		return $this->reply(array(
			'joined' => $joined_members,
			'history' => $history_members,
		));

	}

	/**
	 * Make polling reuqest - client (browser) is waiting for reply from server (new chat messages)
	 * @param  int $last_timestamp timestamp of last request made byt client
	 * @param  $last_list_timestamp timestamp of last listing request made by client
	 * @return
	 */
	public function poll($last_timestamp, $last_list_timestamp) {

		//we do not rely on $last_timestamp sent from the client - if not sent (or sent old value) we will return many messages, potential security problem (dos atack)
		if ($last_timestamp < strtotime(POLL_MAX_HISTORY)) {
			$last_timestamp = strtotime(POLL_MAX_HISTORY);
		}

		$max_time = POLL_MAX_REQUEST_TIME;
		$sleep_time = 2;
		$start = time();

		$counter = 0;

		do {
			$data = array('timestamp' => time());

			$counter++;

			//extend execution time to keep the http request alive longer in case there are no data
			ini_set('max_execution_time', $max_time);

			/*if ($last_list_timestamp < strtotime(POLL_CHAT_REFRESH)) {	//fetch new users
				$users = $this->get_delta_members($last_list_timestamp);
				if (!empty($users)) {
					$data['users'] = $users;
				}
			}*/

			$last_message = ChatCache::read($this->member_big.'_last_msg');
			if ($last_timestamp < $last_message) {	//fetch messages
				$data['msgs'] = $this->get_new_messages($last_timestamp);
			}

			if (count($data) > 1) {	//output data, if there are any other than timestamp
				$this->reply($data);
			} elseif (time() > $start+$max_time) {
				$this->reply(null);	//, array('success' => 'No new messages or other data. Request took '.(time()-$start).' seconds and we checked for new messages '.$counter.' times'));
			}

			sleep($sleep_time);

		} while(true);

	}

	/**
	 * send JSON reply
	 * @param  array  $data    reply messages
	 * @param  array  $verbose reply messages for verbose mode (descriptive!)
	 * @return null   ouputs JSON and exits the script
	 */
	public function reply($data, $verbose=array()) {

		if ($this->verbose && !empty($verbose)) {
			$data['more'] = $verbose;
		}

		echo json_encode($data);
		exit;

	}

	/**
	 * get change in list of members
	 */
	/*private function get_delta_members($last_list_timestamp=null, $members=array()) {

		$member_checkin_event_big = $this->ChatCache->read($this->member_big.'_checkin_event_big');

		foreach($members as $item) {
			if ($this->ChatCache->read($this->logged['Member']['big'].'_last_web_activity') > strtotime(ONLINE_TIMEOUT)) {

			}

		}

	}*/

	/**
	 * load new messages in all conversations
	 * @param  int $to_big BIG of the member the conversation is with
	 * @param  timestamp $last_timestamp timestamp of latest received message
	 * @return array of messages
	 */
	private function get_new_messages($last_timestamp) {

		$stm = $this->db()->prepare(
			'select "m"."id", "m"."text" as "msg", extract(epoch from "m"."created") as "time", "m"."from_big", "from"."name"||\' \'||substring("from"."surname" from 1 for 1)||\'.\' as "name"
			from "chat_messages" "m"
				left join "members" as "from" on "from"."big" = "from_big"
			where "m"."status" = 1
				and "m"."to_big" = :member_big and "m"."to_status" = 1
				and "m"."created" > :last
			order by "m"."created" asc
			limit 100'
		);
		$stm->execute(array(
			':member_big'	=> $this->member_big,
			':last'		=> date('c', $last_timestamp), //'Y-m-d H:i:s'
		));
		$new_raw = $stm->fetchAll(PDO::FETCH_ASSOC);

		$new = array();
		$new_ids = array();
		foreach($new_raw as $msg) {
			$tmp = $msg;
			unset($tmp['from_big']);
			$new[ $msg['from_big'] ][] = $tmp;
			$new_ids[] = $tmp['id'];
		}

		if (!empty($new_ids)) {

			$stm = $this->db()->prepare(
				'update "chat_messages" set "read" = 1 where "id" in (' . implode(',', $new_ids) . ')'
			);
			$stm->execute();

		}

		foreach($new as $key=>$items) {
			foreach($items as $k=>$item) {
				$new[ $key ][ $k ]['msg'] = $this->msg_replace($item['msg']);
			}
		}

		return $new;

	}

	/**
	 * replace text with elements in message (smilies)
	 * @param  string $msg
	 * @return string of message with replaced elements
	 */
	private function msg_replace($msg) {

		$find = array();
		$replace = array();
		foreach($this->msg_replace as $replace_string=>$find_strings) {
			$find = array_merge($find, $find_strings);
			foreach($find_strings as $string) {
				$replace[] = $replace_string;
			}
		};

		$msg = str_replace($find, $replace, $msg);

		return $msg;

	}

	/**
	 * load old messages in conversation
	 * @param  int $to_big BIG of the member the conversation is with
	 * @param  timestamp $first_timestamp timestamp of oldest loaded message
	 * @return array of messages
	 */
	private function get_messages($to_big, $first_timestamp) {

		//TODO: do we mark unread messages? do we mark read messages?

		$rel_id = $this->get_rel_id($to_big);

		$stm = $this->db()->prepare(
			'select "id", "text" as "msg", extract(epoch from "created") as "time", "from_big" = :member_big as "sent"
			from "chat_messages"
			where "rel_id" = :rel_id and "status" = 1
				and ( ("from_big" = :member_big and "from_status" = 1) or ("to_big" = :member_big and "to_status" = 1) )
				and "created" < :first
			order by "created" desc
			limit 10'
		);
		$stm->execute(array(
			':rel_id'		=> $rel_id,
			':member_big'	=> $this->member_big,
			':first'		=> date('c', $first_timestamp), //'Y-m-d H:i:s'
		));
		$messages = $stm->fetchAll(PDO::FETCH_ASSOC);

		foreach($messages as $key=>$item) {
			$messages[ $key ]['msg'] = $this->msg_replace($item['msg']);
		}

		$stm = $this->db()->prepare(
			'update "chat_messages"
			set "read" = 1
			where "rel_id" = :rel_id and "status" = 1
				and ( ("from_big" = :member_big and "from_status" = 1) or ("to_big" = :member_big and "to_status" = 1) )
				and "created" < :first'
		);
		$stm->execute(array(
			':rel_id'		=> $rel_id,
			':member_big'	=> $this->member_big,
			':first'		=> date('c', $first_timestamp), //'Y-m-d H:i:s'
		));

		$this->reply($messages);

	}

	/**
	 * find relation ID of 2 members
	 * @param  int $to_big
	 * @return int relation ID
	 */
	private function get_rel_id($to_big) {

		$stm = $this->db()->prepare(
			'select "id"
			from "member_rels"
			where ("member1_big" = :from_big and "member2_big" = :to_big)
				or ("member2_big" = :from_big and "member1_big" = :to_big)
			limit 1'
		);
		$stm->execute(array(
			':from_big'	=> $this->member_big,
			':to_big'	=> $to_big,
		));
		$rel_id = $stm->fetch(PDO::FETCH_ASSOC);
		return $rel_id['id'];

	}

	/**
	 * Delete conversation with member specified in parameter
	 * @param  int $member_big of the member to delete the conversation with
	 * @return
	 */
	public function delete($member_big) {

		$stm = $this->db()->prepare(
			'update chat_messages
				set to_status = :status_deleted
				where to_big = :current_member_big and from_big = :member_big'
		);
		$stm->execute(array(
			':status_deleted'		=> 255,
			':current_member_big'	=> $this->member_big,
			':member_big'			=> $member_big,
		));

		$stm = $this->db()->prepare(
			'update chat_messages
				set from_status = :status_deleted
				where from_big = :current_member_big and to_big = :member_big'
		);
		$stm->execute(array(
			':status_deleted'		=> 255,
			':current_member_big'	=> $this->member_big,
			':member_big'			=> $member_big,
		));

		$this->reply(array('r' => true), array('message' => 'Converstaion removed'));

	}


	/**
	 * return PDO datasource for database requests (if not created yet, creates it first)
	 * @return PDO data source
	 */
	private function db() {

		if ($this->pdo == null) {
			include_once('../../Config/database.php');
			$config = new DATABASE_CONFIG;
			$this->pdo = new PDO('pgsql:host='.$config->default['host'].
									';dbname='.$config->default['database'],
									$config->default['login'],
									$config->default['password']);
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	//thorw exceptions on SQL errors
			$this->pdo->query("SET timezone TO 'Europe/Rome'");
		}

		return $this->pdo;

	}

	private function post_async($url, $params)
	{
	    foreach ($params as $key => &$val) {
	      if (is_array($val)) $val = implode(',', $val);
	        $post_params[] = $key.'='.urlencode($val);
	    }
	    $post_string = implode('&', $post_params);

	    $parts=parse_url($url);

	    $fp = fsockopen($parts['host'],
	        isset($parts['port'])?$parts['port']:80,
	        $errno, $errstr, 30);

	    $out = "POST ".$parts['path']." HTTP/1.1\r\n";
	    $out.= "Host: ".$parts['host']."\r\n";
	    $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
	    $out.= "Content-Length: ".strlen($post_string)."\r\n";
	    $out.= "Connection: Close\r\n\r\n";
	    if (isset($post_string)) $out.= $post_string;

	    fwrite($fp, $out);
	    fclose($fp);
	}

}

if (!defined('WWW_ROOT'))

define('WWW_ROOT', dirname(__FILE__).'/../../');

if (!defined('FILES_UPLOAD_PATH'))
define('FILES_UPLOAD_PATH', WWW_ROOT . 'files/');

if (!defined('RESIZED_IMAGES_PATH'))
define('RESIZED_IMAGES_PATH', WWW_ROOT . 'resized_images/');

class ProfilePicture {

	public static function get_resized($big, $width=0, $height=0, $enlarge=false, $crop=true) {

		$path = FILES_UPLOAD_PATH . 'members/' . $big . '.jpg';
		if (!is_file($path)) {
			$path = FILES_UPLOAD_PATH . 'members/dummy_avatar_profile.jpg';
		}

		$width = round($width);
		$height = round($height);

		if (!is_file($path)) {
			return false;
		}

		$pathinfo = pathinfo($path);

		//path to the file
		{
			// if (strpos($pathinfo['dirname'], IMAGES) !== false)  {
			// 	$dir = str_replace(IMAGES, RESIZED_IMAGES_PATH, $pathinfo['dirname']);
			// } else {
				$dir = str_replace(FILES_UPLOAD_PATH, RESIZED_IMAGES_PATH, $pathinfo['dirname']);
			// }
			$new_filename = $pathinfo['filename'] . '_' . $width . 'x' . $height . '.' . $pathinfo['extension'];
			$new_path = $dir . '/' . $new_filename;
		}

		if (!is_file($new_path) || filemtime($new_path) < filemtime($path)) {	//file doenst exist - resize and create it

			$new_path = self::_resize($new_path, $path, $width, $height, $enlarge, $crop);

		}	//if (!is_file($new_path))

		//web URL to the resized image
		$new_path = preg_replace('~^(.*)/app/webroot/chat/lib/../../~', '/', $new_path);
		return $new_path;

	}

	private static function _resize($new_path, $path, $width, $height, $enlarge=false, $crop=false) {

		$new_pathinfo = pathinfo($new_path);
		if (!is_dir($new_pathinfo['dirname'])) {
			if (!mkdir($new_pathinfo['dirname'], 0777, true)) {
				return false;
			}
		}

		if ($crop==false) {
			$original_size = getimagesize($path);
			if ($original_size[1] < $height) {
				$height = 0;
			} elseif ($original_size[0] < $width) {
				$width = 0;
			}
		}

		if (!isset($original_size)) {
			$original_size = getimagesize($path);
		}

		if (($width == 0 || $original_size[0] == $width) && ($height == 0 || $original_size[1] == $height)) {
			return $path;
		}

		switch($original_size['mime']) {
			case 'image/jpeg':
				$create_function = 'imagecreatefromjpeg';
				$save_function = 'imagejpeg';
				$compression_level = 95;
				break;
			case 'image/png':
				$create_function = 'imagecreatefrompng';
				$save_function = 'imagepng';
				$compression_level = 9;
				break;
			case 'image/gif':
				$create_function = 'imagecreatefromgif';
				$save_function = 'imagegif';
				break;
			default:
				return false;
				break;
		}

		if ($height > 0 && $width > 0) {

			$crop_width = $width < $original_size[0] ? $width : $original_size[0];
			$crop_height = $height < $original_size[1] ? $height : $original_size[1];

			$height = $crop_width * ($original_size[1] / $original_size[0]);
			if ($width < $crop_width || $height < $crop_height) {
				$width = $crop_height * ($original_size[0] / $original_size[1]);
				$height = $crop_height;
			}

			$cropped_img = imagecreatetruecolor($crop_width, $crop_height);

		} elseif ($height == 0) {

			$height = $width * ($original_size[1] / $original_size[0]);

		} elseif ($width == 0) {

			$width = $height * ($original_size[0] / $original_size[1]);

		} else {

			$width = $original_size[0];
			$height = $original_size[1];

		}

		$width = round($width);
		$height = round($height);

		if (($width > $original_size[0] || $height > $original_size[1]) && !$enlarge) {	//do we enlarge pictures?
			return $path;
		}

		$img = $create_function($path);
		$resized_img = imagecreatetruecolor($width, $height);

		if ($create_function == 'imagecreatefrompng') {	//PNG transparency
			imagealphablending($img, true);
			imagealphablending($resized_img, false);
			imagesavealpha($resized_img, true);
			if (isset($cropped_img)) {
				imagealphablending($cropped_img, false);
				imagesavealpha($cropped_img, true);
			}
		}

		//imagecopyresized($resized_img, $img, 0, 0, 0, 0, $width, $height, $original_size[0], $original_size[1]);
		imagecopyresampled($resized_img, $img, 0, 0, 0, 0, $width, $height, $original_size[0], $original_size[1]);

		if (isset($cropped_img)) {
			imagecopyresampled($cropped_img, $resized_img, 0, 0, floor(($width-$crop_width)/2), floor(($height-$crop_height)/2), $crop_width, $crop_height, $crop_width, $crop_height);
			$save_img = $cropped_img;
		} else {
			$save_img = $resized_img;
		}

		if (isset($compression_level)) {
			$save_function($save_img, $new_path, $compression_level);
		} else {
			$save_function($save_img, $new_path);
		}

		return $new_path;

	}

}
