<?php

class MemberRel extends AppModel {
	
	public $belongsTo = array(
		'Sender' => array(
			'className' => 'Member',
			'foreignKey' => 'member1_big'
		),
		'Recipient' => array(
			'className' => 'Member',
			'foreignKey' => 'member2_big'
		),
	);
	
	public $hasMany = array(
		'ChatMessage' => array(
			'className' => 'ChatMessage',
			'foreignKey' => 'rel_id',
//			'order' => 'ChatMessage.created DESC',
//			'fields' => 'ChatMessage.created',
		),
	);
	
	public function findRelationships($memberOne, $memberTwo = null)
	{
		$type = 'all';
		$params = array(
			'conditions' => array(
				'OR' => array(
					'MemberRel.member1_big' => $memberOne,
					'MemberRel.member2_big' => $memberOne,
				),
			),
		);
		if (!empty($memberTwo))
		{
				$params = array(
					'conditions' => array(
						'OR' => array(
							array(
								'MemberRel.member1_big' => $memberOne,
								'MemberRel.member2_big' => $memberTwo,
							),
							array(
								'MemberRel.member1_big' => $memberTwo,
								'MemberRel.member2_big' => $memberOne,
							),
						),
					),
				);
				$type = 'first';
		}
		
		$result = $this->find($type, $params);
		return $result;
	}
	
	public function findConversations($memberBig, $offset = 0, $fromChat = false)
	{
		
		// Get ignored users
		$params = array(
			'conditions' => array(
				'MemberSetting.from_big' => $memberBig,
				'MemberSetting.chat_ignore' => 1,
			),
			'fields' => array(
				'MemberSetting.to_big'
			),
			'recursive' => -1
		);
		$ignored = $this->Sender->MemberSetting->find('list', $params);
		$ign = implode(',', $ignored);
		
//		$params = array(
//			'conditions' => array(
//				'ChatMessage.status !=' => DELETED,
//				'NOT' => array(
//					'OR' => array(
//						'MemberRel.member1_big' => $ignored,
//						'MemberRel.member2_big' => $ignored,
//					)
//				),
//				'OR' => array(
//					'MemberRel.member1_big' => $memberBig,
//					'MemberRel.member2_big' => $memberBig,
//				),
//				'OR' => array(
//					array(
//						'ChatMessage.from_big' => $memberBig,
//						'ChatMessage.from_status !=' => DELETED,
//					),
//					array(
//						'ChatMessage.to_big' => $memberBig,
//						'ChatMessage.to_status !=' => DELETED,
//					),
//				),
//			),
//			'fields' => array(
//				'Sender.big',
//				'Sender.name',
//				'Sender.middle_name',
//				'Sender.surname',
//				'Sender.photo_updated',
//				'Recipient.big',
//				'Recipient.name',
//				'Recipient.middle_name',
//				'Recipient.surname',
//				'Recipient.photo_updated',
//				'MemberRel.id',
//				'ChatMessage.created'
//			),
//			'joins' => array(
//				 array(
//				 	'table' => 'chat_messages',
//		    	    'alias' => 'ChatMessage',
//			        'type' => 'INNER',
//			        'conditions' => array(
//				            'ChatMessage.rel_id = MemberRel.id',
//				            '"ChatMessage"."created" = (SELECT MAX(created) FROM chat_messages WHERE rel_id = "MemberRel"."id")',
//			        )
//			    ),
//			),
//			'group' => array(
//				'Sender.big',
//				'Recipient.big',
//				'MemberRel.id',
//				'ChatMessage.created'
//			),
//			'order' => array('ChatMessage.created DESC'),
//			'recursive' => 0,
//		);
//		
//		if ($fromChat)
//		{
//			$params['limit'] = API_PER_PAGE;
//		}
//		
//		if (!empty($offset))
//		{
//			$params['offset'] = $offset * API_PER_PAGE;
//		}
//		
//		$result = array('conversations' => null);
//		try {
//			$res = $this->find('all', $params);
//			$result['conversations'] = $res;
//		}
//		catch (Exception $e)
//		{
//			debug($e);
//		}
//
//		if ($offset == 0 && $fromChat)
//		{
//			unset($params['limit']);
//			unset($params['offset']);
//			$count = $this->find('count', $params);
//			$result['conversations_count'] = intval($count);
//		}





		$db = $this->getDataSource();
		$query = "SELECT \"Sender\".\"big\" AS \"Sender__big\", \"Sender\".\"name\" AS \"Sender__name\",".
                 "\"Sender\".\"middle_name\" AS \"Sender__middle_name\",\"Sender\".\"surname\" AS \"Sender__surname\",".
                 "\"Sender\".\"photo_updated\" AS \"Sender__photo_updated\",\"Recipient\".\"big\" AS \"Recipient__big\",".
                 "\"Recipient\".\"name\" AS \"Recipient__name\", \"Recipient\".\"middle_name\" AS \"Recipient__middle_name\",".
                 "\"Recipient\".\"surname\" AS \"Recipient__surname\",".
                 "\"Recipient\".\"photo_updated\" AS \"Recipient__photo_updated\",\"MemberRel\".\"id\" AS \"MemberRel__id\",".
                 "\"ChatMessage\".\"created\" AS \"ChatMessage__created\",\"ChatMessage\".\"text\" AS \"ChatMessage__text\",".
                 "(\"ChatMessage\".\"from_big\" = ". $memberBig .") AS \"ChatMessage__self\",".
                 "\"Sender\".\"chat_status\" AS \"Sender__chat_status\",".
                 "\"Recipient\".\"chat_status\" AS \"Recipient__chat_status\",\"Sender\".\"sex\" AS \"Sender__sex\",".
                 "\"Recipient\".\"sex\" AS \"Recipient__sex\",".
                 "\"Sender\".\"status\" AS \"Sender__status\", \"Recipient\".\"status\" AS \"Recipient__status\" ".
                 "FROM \"member_rels\" AS \"MemberRel\" ". 
			     "INNER JOIN \"chat_messages\" AS \"ChatMessage\" ON (\"ChatMessage\".\"rel_id\" = \"MemberRel\".\"id\" ". 
				 "AND \"ChatMessage\".\"created\" = (SELECT MAX(created) FROM chat_messages ".
                 "WHERE rel_id = \"MemberRel\".\"id\")) ". 
			     "LEFT JOIN (SELECT members.big,members.sex, members.name, members.middle_name, members.surname,".
                 "members.status,".
                 "members.photo_updated,(CASE WHEN checkins.physical = 1 THEN 2	WHEN checkins.physical = 0 THEN 1 ".
                 "WHEN members.last_web_activity > NOW() - interval '". ONLINE_TIMEOUT . " hour' ".
                 "OR members.last_mobile_activity > NOW() - interval '" . ONLINE_TIMEOUT . " hour' THEN 1 ELSE 0 END) ".
                 "AS chat_status ".
				 "FROM members ".
				 "LEFT JOIN checkins ON (members.big = checkins.member_big) ". 
				 "AND checkins.created = (SELECT MAX(created) FROM checkins WHERE member_big = members.big) ".
				 "AND (checkins.checkout IS NULL OR checkins.checkout > NOW())) AS \"Sender\" ON ".
                 "(\"MemberRel\".\"member1_big\" = \"Sender\".\"big\") ". 
			     "LEFT JOIN (SELECT members.big,members.sex, members.name, members.middle_name, members.surname,".
                 "members.status,".
                 "members.photo_updated,(CASE WHEN checkins.physical = 1 THEN 2	WHEN checkins.physical = 0 THEN 1 ".
                 "WHEN members.last_web_activity > NOW() - interval '" . ONLINE_TIMEOUT . " hour' OR ".
                 "members.last_mobile_activity > NOW() - interval '" . ONLINE_TIMEOUT . " hour' THEN 1 ".
                 "ELSE 0 END) AS chat_status ".
				 "FROM members ".
                 "LEFT JOIN checkins ON (members.big = checkins.member_big) ".
                 "AND checkins.created = (SELECT MAX(created) FROM checkins WHERE member_big = members.big) ".
                 "AND (checkins.checkout IS NULL OR checkins.checkout > NOW())) AS \"Recipient\" ON ".
                 "(\"MemberRel\".\"member2_big\" = \"Recipient\".\"big\") ". 
			     "WHERE \"Sender\".\"status\"<255 AND \"Recipient\".\"status\"<255 AND \"ChatMessage\".\"status\" != 255 " . 
			     (!empty($ign) ? " AND NOT (\"MemberRel\".\"member1_big\" IN (" . $ign . ") OR ".
                 "\"MemberRel\".\"member2_big\" IN (" . $ign . ")) " : "" ) .
                 " AND ((\"ChatMessage\".\"from_big\" = " . $memberBig . "  AND  \"ChatMessage\".\"from_status\" != 255) ".
                 "OR (\"ChatMessage\".\"to_big\" = ". $memberBig . "  AND  \"ChatMessage\".\"to_status\" != 255)) ".
                 "ORDER BY \"ChatMessage\".\"created\" DESC ";
                 
                    
		/* Sostituito con una count al risultato
        $countQuery = "SELECT COUNT(*) ".
                      "FROM \"member_rels\" AS \"MemberRel\" ".
                      "INNER JOIN \"chat_messages\" AS \"ChatMessage\" ON (\"ChatMessage\".\"rel_id\" = \"MemberRel\".\"id\" ".
                      "AND \"ChatMessage\".\"created\" = (SELECT MAX(created) FROM chat_messages ".
                      "WHERE rel_id = \"MemberRel\".\"id\")) ".
                      "WHERE \"ChatMessage\".\"status\" != 255 " . 
			          (!empty($ign) ? " AND NOT (\"MemberRel\".\"member1_big\" IN (" . $ign . ") OR ".
                      "\"MemberRel\".\"member2_big\" IN (" . $ign . ")) " : "" ) .
                      " AND ((\"ChatMessage\".\"from_big\" = ". $memberBig . "  AND  \"ChatMessage\".\"from_status\" != 255) ".
                      "OR (\"ChatMessage\".\"to_big\" = ". $memberBig . "  AND \"ChatMessage\".\"to_status\" != 255))";
			*/			
		$countQueryNotRead = "SELECT COUNT(*) ".
			                 "FROM \"member_rels\" AS \"MemberRel\" ".
                             "INNER JOIN \"chat_messages\" AS \"ChatMessage\" ON ".
                             "(\"ChatMessage\".\"rel_id\" = \"MemberRel\".\"id\" AND \"ChatMessage\".\"created\" = ".
                             "(SELECT MAX(created) FROM chat_messages WHERE rel_id = \"MemberRel\".\"id\")) ".
                             "WHERE \"ChatMessage\".\"status\" != 255 ".
					         (!empty($ign) ? " AND NOT (\"MemberRel\".\"member1_big\" IN (" . $ign . ") OR ".
                             "\"MemberRel\".\"member2_big\" IN (" . $ign . ")) " : "" ) .
                             " AND ((\"ChatMessage\".\"read\" = 0 AND \"ChatMessage\".\"from_big\" = " .
                              $memberBig . "  AND  \"ChatMessage\".\"from_status\" != 255) OR ".
                              "(\"ChatMessage\".\"to_big\" = " . $memberBig . "  AND \"ChatMessage\".\"to_status\" != 255))";
		
		$this->log("query_member_rel findconversation ".$query);
        $this->log("query count member_rel findoconversation ".$countQueryNotRead);        
		
        if ($fromChat)
		{
			$query .= 'LIMIT ' . API_PER_PAGE . ' ';
		}
		
		if (!empty($offset))
		{
			$query .= 'OFFSET ' . $offset * API_PER_PAGE . ' ';
		}
			
		$result = array('conversations' => null);
		try {
			$res = $db->fetchAll($query);
            $conversationCount=count($res);
            $result['conversations'] = $res;
		}
		catch (Exception $e)
		{
//			debug($e);
			CakeLog::error($e);
		}		
		
		// Return count
		if ($offset == 0 && $fromChat)
		{
			try 
			{
				$count = $db->fetchAll($countQuery);
			}
			catch (Exception $e)
			{
//				debug($e);
				CakeLog::error($e);
			}
			
			$result['conversations_count'] = $conversationCount; //intval($count[0][0]['count']);
            			
			// Added not read
			try
			{
				$countNR = $db->fetchAll($countQueryNotRead);
			}
			catch (Exception $e)
			{
				//				debug($e);
				CakeLog::error($e);
			}
				
			$result['conversations_count_not_read'] = intval($countNR[0][0]['count']);
		}
		
		return $result;
	}
		
}