<?php
App::uses ( 'Logger', 'Lib' );
class ChatMessagesController extends AppController {
	var $uses = array (
			'ChatMessage',
			'PushToken',
			'Member',
			'Friend' ,
			'PrivacySetting',
			'MemberSetting'
	);
	public function index() {
	}
	
	/**
	 * Return stored chat messages
	 */
	public function api_receive() {
		$this->_checkVars ( array (
				'partner_big' 
		), array (
				'older_than',
				'newer_than',
				'offset',
				'refresh' 
		) );
		
		$memBig = $this->logged ['Member'] ['big'];
		$partnerBig = $this->api ['partner_big'];
		$olderThan = (isset ( $this->api ['older_than'] )) ? $this->api ['older_than'] : null;
		$newerThan = (isset ( $this->api ['newer_than'] )) ? $this->api ['newer_than'] : null;
		$offset = (isset ( $this->api ['offset'] )) ? $this->api ['offset'] : 0;
		$refresh = (isset ( $this->api ['refresh'] ) && $this->api ['refresh'] == 'true') ? true : false;
		
		$result = $this->ChatMessage->findConversations ( $memBig, $partnerBig, $olderThan, $newerThan, $offset, $refresh );
		
		if (empty ( $result )) {
			$result = array (
					'msg' => 'The conversation has not started yet.' 
			);
		} elseif (! empty ( $result ['members'] )) {
			// Add photos
			$members = $result ['members'];
			// Sender
			if ($members ['Sender'] ['photo_updated'] > 0) {
				$members ['Sender'] ['photo'] = $this->FileUrl->profile_picture ( $members ['Sender'] ['big'], $members ['Sender'] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($members ['Sender'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$members ['Sender'] ['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			unset ( $members ['Sender'] ['photo_updated'] );
			
			// Recipient
			if (isset ( $members ['Recipient'] )) {
				if ($members ['Recipient'] ['photo_updated'] > 0) {
					$members ['Recipient'] ['photo'] = $this->FileUrl->profile_picture ( $members ['Recipient'] ['big'], $members ['Recipient'] ['photo_updated'] );
				} else {
					$sexpic = 2;
					if ($members ['Recipient'] ['sex'] == 'f') {
						$sexpic = 3;
					}
					
					$members ['Recipient'] ['photo'] = $this->FileUrl->profile_picture ( $sexpic );
				}
				unset ( $members ['Recipient'] ['photo_updated'] );
			}
			$result ['members'] = $members;
		}
		
		// message image
		
		// Put file url if any
		$chatmsgs = $result ['chat_messages'];
	//	debug($result ['chat_messages']);
		foreach ( $result ['chat_messages'] as $key =>&$res ) {
			if ($res ['photo_updated'] > 0) {
				Logger::Info('in foto2'.$res ['photo_updated']);
				$res ['photo'] = $this->FileUrl->chatmsg_picture ( $res ['msg_id'] );
		//		Logger::Info('in foto3'.$res ['photo']);
			}
		}
		// Mark mesaages as read
		if (! empty ( $result ['chat_messages'] )) {
			$updated = $this->ChatMessage->markAsRead ( $memBig, $partnerBig );
			if (! $updated)
				CakeLog::warning ( 'Messages not marked as read. Membig ' . $memBig . ' Partner big ' . $partnerBig );
		}
		$this->Util->transform_name ( $result );
		$this->_apiOk ( $result );
	}
	public function api_conversations() {
		$memBig = $this->logged ['Member'] ['big'];
		$offset = isset ( $this->api ['offset'] ) ? $this->api ['offset'] : 0;
		
		$result = $this->ChatMessage->MemberRel->findConversations ( $memBig, $offset, true );
		$conversations = $result ['conversations'];
		// debug($result);
		foreach ( $conversations as &$val ) {

		//	if (! $this->MemberSetting->isOnIgnoreList($val ['Sender'] ['big'],$val ['Recipient'] ['big']))
		//	{
				
		//		debug('not in');
		//	}
		//	else
		//	{
		//		debug('in');
		//	}
			
			
			
			
			// Sender
			if ($val ['Sender'] ['photo_updated'] > 0) {
				$val ['Sender'] ['photo'] = $this->FileUrl->profile_picture ( $val ['Sender'] ['big'], $val ['Sender'] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($val ['Sender'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$val ['Sender'] ['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			unset ( $val ['Sender'] ['photo_updated'] );
			// Recipient
			if ($val ['Recipient'] ['photo_updated'] > 0) {
				$val ['Recipient'] ['photo'] = $this->FileUrl->profile_picture ( $val ['Recipient'] ['big'], $val ['Recipient'] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($val ['Recipient'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$val ['Recipient'] ['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			unset ( $val ['Recipient'] ['photo_updated'] );
			
			$memBig = $this->logged ['Member'] ['big'];
			// $newerThan = (isset($this->api['newer_than'])) ? date('Y-m-d H:i:s', $this->api['newer_than']) : null;
			// debug($val['MemberRel']['id']);
			$params = array (
					
					'conditions' => array (
							'ChatMessage.to_big' => $memBig,
							'ChatMessage.rel_id' => $val ['MemberRel'] ['id'],
							'ChatMessage.read ' => 0,
							'ChatMessage.status != ' => 255 
					) 
			);
			
			$resultNR = $this->ChatMessage->find ( 'count', $params );
			// debug($resultNR);
			$val ['CountMessagesNotRead'] = $resultNR;
		}
		$result ['conversations'] = $conversations;
		
		if (empty ( $result )) {
			$this->_apiEr ( 'Error occured. No conversations found.', 'You haven\'t chat with anyone yet.' );
		} else {
			$this->Util->transform_name ( $result );
			$this->_apiOk ( $result );
		}
	}
	public function api_remove() {
		$this->_checkVars ( array (
				'member_big',
				'rel_id' 
		) );
		
		$memBig = $this->api ['member_big'];
		$relId = $this->api ['rel_id'];
		
		$result = $this->ChatMessage->removeConversations ( $relId, $memBig );
		if ($result !== false) {
			$this->_apiOk ();
		} else {
			$this->_apiEr ( 'Error occured. Conversation not removed.' );
		}
	}
	public function api_send() {
		
		/*
		 * Needed values: rel_id - based on member_big and partner_big find a member_rel record. If does not exists, create it from_big - the sender of the message,member_big to_big - recipient of the message, partner_big checkin_big - (optional) text - message/text - text of the message from_status - sender status (joined/checkedin) based on current checkin of member field physical to_status - recipient status (joined/checkedin) based on current checkin of member field physical created - now() status - 1 Push notifications will be part of this call. $pollo=$this->api['photo']; Logger::Info($this->api[$pollo]);
		 */
		$this->_checkVars ( array (
				'partner_big',
				'text' 
		), array (
				'rel_id',
				'newer_than',
				'photo' 
		) );
		
		$memBig = $this->logged ['Member'] ['big'];
		$partnerBig = $this->api ['partner_big'];
		$text = $this->api ['text'];
		$relId = null;
		$checkinBig = null;
		$xfoto = null;
		
		// $fromStatus = CHAT_NO_JOIN;
		// $toStatus = CHAT_NO_JOIN;
		
		$newerThan = (isset ( $this->api ['newer_than'] )) ? $this->api ['newer_than'] : null;
		
		/*
		 * Check if user is not in partners ignore list Find checkins -> because of status and checkin big Find ,potentially create member_rel If users are not checked in at the same place, they have to have a memberRel record (chat started based on previous conversation) Save to DB
		 */
		
		// Check if user is not on partners ignore list
		$isIgnored = $this->ChatMessage->Sender->MemberSetting->isOnIgnoreList ( $partnerBig, $memBig );
		if ($isIgnored) {
			$this->_apiEr ( 'Cannot send chat message. User is blocked by the second party.', false, false, array (
					'error_code' => '510' 
			) );
		}
		
		// Find valid checkin for member and partner
		$memCheckin = $this->ChatMessage->Checkin->getCheckedinEventFor ( $memBig, TRUE );
		$partnerCheckin = $this->ChatMessage->Checkin->getCheckedinEventFor ( $partnerBig, TRUE );
		if ($memCheckin ['Checkin'] ['event_big'] == $partnerCheckin ['Checkin'] ['event_big'] && $memCheckin != false) {
			// If they are on the same event use checkin data
			// $fromStatus = $memCheckin['Checkin']['physical'];
			// $toStatus = $partnerCheckin['Checkin']['physical'];
			$checkinBig = $memCheckin ['Checkin'] ['big'];
		}
		
		// Find relationship
		$memRel = $this->ChatMessage->MemberRel->findRelationships ( $memBig, $partnerBig );
		
		$frieRel = $this->Friend->FriendsRelationship ( $memBig, $partnerBig, 'A' );
		
		if (empty ( $checkinBig ) && empty ( $memRel ) && empty ( $frieRel )) {
			$this->_apiEr ( 'Error occured. Users are not on the same event and no relationship found.' );
		} elseif (empty ( $memRel )) {
			// Create a new one
			$relationship = array (
					'member1_big' => $memBig,
					'member2_big' => $partnerBig 
			);
			$this->ChatMessage->MemberRel->set ( $relationship );
			try {
				$memRel = $this->ChatMessage->MemberRel->save ();
				$relId = $memRel ['MemberRel'] ['id'];
			} catch ( Exception $e ) {
				$this->_apiEr ( 'Error occured. Relationship not created.' );
			}
		} else {
			$relId = $memRel ['MemberRel'] ['id'];
		}
		
		// Create chat message record
		$message = array (
				'rel_id' => $relId,
				'from_big' => $memBig,
				'to_big' => $partnerBig,
				'checkin_big' => $checkinBig,
				'text' => $text,
				'from_status' => 1, // from status = 1 (not deleted)
				'to_status' => 1, // tp status = 1 (not deleted)
				'created' => 'NOW()',
				'status' => 1 
		// 'photo' => $hasphoto,
				);
		
		// $this->Model->getLastInsertId();
		
		$this->ChatMessage->set ( $message );
		$msgId = null;
		$chatMsg = null;
		try {
			$res = $this->ChatMessage->save ();
			$result = ($res) ? true : false;
			$msgId = $res ['ChatMessage'] ['id'];
			$pars = array (
					'conditions' => array (
							'ChatMessage.id' => $msgId 
					),
					'fields' => array (
							'ChatMessage.id',
							'ChatMessage.rel_id',
							'ChatMessage.created' 
					),
					'recursive' => - 1 
			);
			$chatMsg = $this->ChatMessage->find ( 'first', $pars );
			// Crack for image save!!
			 
			if (isset ( $this->api ['photo'] )) {
				
				$myphoto = $_FILES [$this->api ['photo']];
				try {
					$exts = array (
							'jpg',
							'jpeg',
							'png' 
					);
					foreach ( $exts as $ext ) {
						$path = CHATS_UPLOAD_PATH . $msgId . '.' . $ext;
						if (is_file ( $path )) {
							unlink ( $path );
							break;
						}
					}
					$extension = pathinfo ( $myphoto ['name'], PATHINFO_EXTENSION );
					
					try {
						$uploaded = $this->Upload->directUpload ( $_FILES [$this->api ['photo']], 						// data from form (temporary filenames, token)
						CHATS_UPLOAD_PATH . $msgId . '.' . $extension )						// path
						;
					} catch ( UploadException $e ) {
						Logger::Info ( 'QUIERRER!!' . $e->getMessage () );
					}
					
					// filename $this->_upload ( $_FILES [$this->api ['photo']], $this->Member->id, true );
					// Logger::Info('photo up'. $uploaded);
				} catch ( UploadException $e ) {
					Logger::Info ( 'photo er' . $e->getMessage () );
					throw new UploadException ( __ ( $msg ) . ': ' . $e->getMessage () );
				}
				
				if ($uploaded) {
					$this->ChatMessage->save ( array (
							'ChatMessage' => array (
									'photo_updated' => DboSource::expression ( 'now()' ) 
							) 
					) );
				} else {
					throw new UploadException ( __ ( $msg ) );
				}
			}
		} catch ( Exception $e ) {
			$this->_apiEr ( 'Error occured. Message not created.' );
		}
		
		$this->ChatCache->write ( $partnerBig . '_last_msg', strtotime ( $chatMsg ['ChatMessage'] ['created'] ) );
		
		// Determine number of unread messages
		$unreadCount = $this->ChatMessage->getUnreadMsgCount ( $partnerBig );
		// debug($unreadCount);
		
		// Send push notifications
		$Privacyok=$this->PrivacySetting->getPrivacySettings($idMember2);
		$goonPrivacy=true;
		if (count($Privacyok)>0)
		{
			if ($Privacyok[0]['notifychatmessages']==0)
			{
				$goonPrivacy=false;
			}
		}
		if 	($goonPrivacy)
		{
		$strLen = 50;
		$name = $this->logged ['Member'] ['name'] . (! empty ( $this->logged ['Member'] ['middle_name'] ) ? ' ' . $this->logged ['Member'] ['middle_name'] . ' ' : ' ') . $this->logged ['Member'] ['surname'];
		$msg = (strlen ( $text ) > $strLen + 4) ? substr ( $text, 0, $strLen ) . ($text [$strLen + 1] == ' ' ? ' ...' : '...') : $text;
		$this->PushToken->sendNotification ( $name, $msg, array (
				'partner_big' => $memBig,
				'created' => $chatMsg ['ChatMessage'] ['created'],
				'rel_id' => $chatMsg ['ChatMessage'] ['rel_id'],
				'msg_id' => $chatMsg ['ChatMessage'] ['id'],
				// 'timestamp' => time(),
				'unread' => $unreadCount 
		), array (
				$partnerBig 
		), 'chat', 'new' );
		
		}
		// return chat messages like in the receive call with refresh enabled
		$newMsgs = $this->ChatMessage->findConversations ( $memBig, $partnerBig, null, $newerThan, 0, true );
		
		// Mark mesaages as read
		if (! empty ( $newMsgs ['chat_messages'] )) {
			$updated = $this->ChatMessage->markAsRead ( $memBig, $partnerBig );
			if (! $updated)
				CakeLog::warning ( 'Messages not marked as read. Membig ' . $memBig . ' Partner big ' . $partnerBig );
		}
		
		if ($result !== false) {
			$this->Util->transform_name ( $chatMsg );
			$this->Util->transform_name ( $newMsgs );
			$this->_apiOk ( $chatMsg );
			$this->_apiOk ( $newMsgs );
		} else {
			$this->_apiEr ( 'Error occured. Message not sent.' );
		}
	}
	public function api_badges() {
		// $this->_checkVars(array('newer_than'));
		$memBig = $this->logged ['Member'] ['big'];
		// $newerThan = (isset($this->api['newer_than'])) ? date('Y-m-d H:i:s', $this->api['newer_than']) : null;
		
		$params = array (
				'fields' => array (
						// '(CASE WHEN from_big = ' . $memBig . ' THEN to_big ELSE from_big END ) AS ChatMessage__user_big',
						'COUNT(ChatMessage.id) AS "ChatMessage__msg_count"',
						'ChatMessage.from_big' 
				),
				'conditions' => array (
						// 'OR' => array(
						// 'ChatMessage.from_big' => $memBig,
						'ChatMessage.to_big' => $memBig,
						// ),
						// 'ChatMessage.created >=' => $newerThan,
						'ChatMessage.read ' => 0,
						'ChatMessage.status <' => 255 
				),
				'group' => array (
						'ChatMessage.from_big' 
				// 'ChatMessage.to_big',
								) 
		);
		
		$result = $this->ChatMessage->find ( 'all', $params );
		
		$this->_apiOk ( $result );
	}
	public function api_testpn() {
		$this->PushToken->sendNotification ( 'Test', 'This is a test', array (
				'partner_big' => '23',
				'created' => '2013-09-24 17:00',
				'rel_id' => '1',
				'msg_id' => '2',
				'timestamp' => time () 
		), array (
				'23' 
		), 'chat', 'new' );
		
		$this->_apiOk ( 'OK' );
	}
	public function api_send_push() {
		// CakeLog::debug('Params: ');
		// foreach ($this->request->data as $key => $par)
		// {
		// if (is_array($par))
		// {
		// CakeLog::debug($key . ' = ' . implode(',', $par));
		// }
		// else
		// {
		// CakeLog::debug($key . ' = ' . $par);
		// }
		// }
		$msg = $this->request->data ['msg'];
		$memBig = $this->request->data ['membig'];
		$member = $this->Member->getMemberByBig ( $memBig );
		if (! empty ( $member )) {
			$name = $member ['Member'] ['name'] . (! empty ( $member ['Member'] ['middle_name'] ) ? ' ' . mb_substr ( $member ['Member'] ['middle_name'], 0, 1 ) . '. ' : ' ') . mb_substr ( $member ['Member'] ['surname'], 0, 1 ) . '.';
		}
		$created = $this->request->data ['created'];
		$relId = $this->request->data ['relid'];
		$msgId = $this->request->data ['msgid'];
		$partnerBig = $this->request->data ['parbig'];
		$unreadCount = $this->ChatMessage->getUnreadMsgCount ( $partnerBig );
		
		$this->PushToken->sendNotification ( $name, $msg, array (
				'partner_big' => $memBig,
				'created' => $created,
				'rel_id' => $relId,
				'msg_id' => $msgId,
				'unread' => $unreadCount 
		), array (
				$partnerBig 
		), 'chat', 'new' );
	}
}