<?php

class ChatMessage extends AppModel {
	
	public $belongsTo = array(
		'Checkin',
		'Sender' => array(
			'className' => 'Member',
			'foreignKey' => 'from_big'
		),
		'Recipient' => array(
			'className' => 'Member',
			'foreignKey' => 'to_big'
		),
		'MemberRel' => array(
			'foreignKey' => 'rel_id'
		),
	);
	
	public function findConversations($memberOne, $partnerBig, $olderThan = null, $newerThan = null, $offset = 0, $refresh = false)
	{
//		die(debug($memberOne)); 
		
		if ($refresh == false && $offset == 0)
		{
			// Get message parties
			$pars = array(
				'conditions' => array(
					'OR' => array(
						array(
							'MemberRel.member1_big' => $memberOne,
							'MemberRel.member2_big' => $partnerBig,
						),
						array(
							'MemberRel.member2_big' => $memberOne,
							'MemberRel.member1_big' => $partnerBig,
						),
					),
				),
				'fields' => array(
					'Sender.name',
					'Sender.middle_name',
					'Sender.surname',
					'Sender.photo_updated',
					'Recipient.name',
					'Recipient.middle_name',
					'Recipient.surname',
					'Recipient.photo_updated',
					'Sender.big',
					'Recipient.big',
				),
				'recursive' => 0
			);
			try {
				$parties = $this->MemberRel->find('first', $pars);
//				debug($parties);
			}
			catch (Exception $e)
			{
//				debug($e);
				CakeLog::error($e);
			}
	
			if (empty($parties))
			{
				// Get message parties
				$pars = array(
					'conditions' => array(
						'Sender.big' => $partnerBig 
						
					),
					'fields' => array(
						'Sender.name',
						'Sender.middle_name',
						'Sender.surname',
						'Sender.photo_updated',
						'Sender.big',
					),
					'recursive' => 0
				);
				try {
					$sender = $this->Sender->find('first', $pars);
//					debug($sender);
				}
				catch (Exception $e)
				{
//					debug($e);
					CakeLog::error($e);
				}

				$result = array();
				if (!empty($sender))
				{
					$result = $sender;
				}
				
				// Recipient
				$pars = array(
					'conditions' => array(
						'Recipient.big' => $memberOne 
						
					),
					'fields' => array(
						'Recipient.name',
						'Recipient.middle_name',
						'Recipient.surname',
						'Recipient.photo_updated',
						'Recipient.big',
					),
					'recursive' => 0
				);
				try {
					$recipient = $this->Recipient->find('first', $pars);
//					debug($recipient);
				}
				catch (Exception $e)
				{
//					debug($e);
					CakeLog::error($e);
				}
				
				if (!empty($recipient))
				{
					$result = array_merge($result, $recipient);
				}
//				debug($result);
				return array('members' => $result);
			}
			
		
		}
			
		// Get messages
		$params = array(
			'conditions' => array(
				'OR' => array(
					array(
						'MemberRel.member1_big' => $memberOne,
						'MemberRel.member2_big' => $partnerBig,
					),
					array(
						'MemberRel.member2_big' => $memberOne,
						'MemberRel.member1_big' => $partnerBig,
					),
				),
				'OR ' => array(
					array(
						'ChatMessage.from_big' => $memberOne,
						'ChatMessage.from_status' => 1,
					),
					array(
						'ChatMessage.to_big' => $memberOne,
						'ChatMessage.to_status' => 1,
					),
				),
			),
			'fields' => array(
				'Sender.big',
				'Recipient.big',
				'ChatMessage.created',
				'ChatMessage.text',
				'ChatMessage.id',
				'ChatMessage.read'
			),
			'order' => array(
				'ChatMessage.created' => 'desc'
			),
			'limit' => API_CHAT_PER_PAGE,
		);
		
		if (!empty($olderThan))
		{
			$params['conditions'] = array_merge($params['conditions'], array('ChatMessage.created <=' => $olderThan ));
		}
		
		if (!empty($newerThan))
		{
			$params['conditions'] = array_merge($params['conditions'], array('ChatMessage.created >' => $newerThan ));
		}

		if (!empty($offset))
		{
			$params['offset'] = $offset * API_CHAT_PER_PAGE;
		}
		
		try {
			$chatmsgs = $this->find('all', $params);
		}
		catch (Exception $e)
		{
//			debug($e);
			CakeLog::error($e);
		}
		// Data post porcessing
		foreach ($chatmsgs as &$res)
		{
			$res['sender_big'] = $res['Sender']['big'];
			unset($res['Sender']); 
			$res['recipient_big'] = $res['Recipient']['big'];
			unset($res['Recipient']); 
			$res['msg_text'] = $res['ChatMessage']['text'];
			$res['msg_created'] = $res['ChatMessage']['created'];
			$res['msg_id'] = $res['ChatMessage']['id'];
			$res['read'] = $res['ChatMessage']['read'];
			unset($res['ChatMessage']); 
		}
		
		// Build result
		$result = array();
		if ($refresh == false && $offset == 0)
		{
			$result['members'] = $parties;
		}
		$result['chat_messages']= array_reverse($chatmsgs);
		
		// Get total count of messages
		if ($offset == 0)
		{
			unset($params['limit']);
			unset($params['offset']);
			$count = $this->find('count', $params);
			$result['msg_count'] = $count;
		}
		
		return $result;
	}
	
	/**
	 * Mark all chat messages from selected conversations as deleted
	 * @param string $relIds Member_rel (conversation) id/s in format id,id2,id3,...,idn
	 */
	public function removeConversations($relId, $memberBig)
	{
		// error handling for invalid format (i.e. other than id,id2,id3,...,idn)
		if (!is_numeric($relId))
		{
			$relIds = explode(',', $relId);
			foreach ($relIds as $key=>$rlId)
			{
				if (!is_numeric($rlId))
					unset($relIds[$key]);
			}
			$relId = implode(',', $relIds);
		} 
		
		$db = $this->getDataSource();
		$sql = 'UPDATE chat_messages SET from_status = ' . DELETED . ' WHERE rel_id IN (' . $relId . ') AND from_big = '. intval($memberBig);
		try {
			$db->fetchAll($sql);
		}
		catch (Exception $e)
		{
			debug($e);
			return false;
		}


		$db = $this->getDataSource();
		$sql = 'UPDATE chat_messages SET to_status = ' . DELETED . ' WHERE rel_id IN (' . $relId . ') AND to_big = '. intval($memberBig);
		try {
			$db->fetchAll($sql);
		}
		catch (Exception $e)
		{
			debug($e);
			return false;
		}

		return true;

	}
	
	public function getMessagesForSignalation($memBig, $flgBig)
	{
		$params = array(
			'conditions' => array(
				'OR' => array(
					array(
						'ChatMessage.from_big' => $memBig,
						'ChatMessage.to_big' => $flgBig,
					),
					array(
						'ChatMessage.from_big' => $flgBig,
						'ChatMessage.to_big' => $memBig,
					),
				),
			),
			'fields' => array(
				'Sender.big',
				'Recipient.big',
				'ChatMessage.created',
				'ChatMessage.text',
			),
			'recursive' => 0,
			'order' => array(
				'ChatMessage.created' => 'desc'
			),
			'limit' => FLAG_MSG_LIMIT
		);
		
		return $this->find('all', $params);
	}
	
	public function markAsRead($memBig, $partnerBig)
	{
		return $this->updateAll(
		    array(
		    	'ChatMessage.read' => 1
		    ),
		    array(
		    	'ChatMessage.read ' => 0,
		    	'ChatMessage.to_big ' => $memBig,
		    	'ChatMessage.from_big ' => $partnerBig,
		    )
		);
	}
	
	public function getUnreadMsgCount($partnerBig)
	{
		$params = array(
			'conditions' => array(
				'ChatMessage.to_big' => $partnerBig,
				'ChatMessage.read' => 0,
			)
		);
		
		return $this->find('count', $params);
	}
	
}