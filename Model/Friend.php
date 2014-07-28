<?php
class Friend extends AppModel {
	public $primaryKey = 'big';
	public $belongsTo = array (
			'Friend1' => array (
					'className' => 'Member',
					'foreignKey' => 'member1_big' 
			),
			'Friend2' => array (
					'className' => 'Member',
					'foreignKey' => 'member2_big' 
			) 
	);
	public function getBoardFriends($MemberID) {
		$Amici = $this->findFriends ( $MemberID );
		
		// create array of friends and populate with checkins places and last chat messages if any
		$PrivacySettingModel = ClassRegistry::init ( 'PrivacySetting' );
		
		$FriendsID = "(";
		foreach ( $Amici as $ami ) {
			// add only if privacy ok
			if ($ami ["Friend1"] ["big"] == $MemberID) {
				$friendID = $ami ["Friend2"] ["big"] ;
			} 

			else {
				$friendID = $ami ["Friend1"] ["big"] ;
			}
			$Privacyok = $PrivacySettingModel->getPrivacySettings ( $friendID );
			$goonPrivacy = true;
			if (count ( $Privacyok ) > 0) {
				if ($Privacyok [0] ['checkinsvisibility'] == 0) {
					$goonPrivacy = false;
				}
			}
			if ($goonPrivacy) {
				$FriendsID .= $friendID . ',';
			}
		}
		
		if (strlen ( $FriendsID ) > 1) {
			$FriendsID = substr ( $FriendsID, 0, - 1 ) . ")";
			
			$MySql = 'SELECT
		checkins.member_big,
		events.place_big,
	    checkins.created,
		checkins.big
		FROM
		public.checkins,
		public.events
		WHERE
		checkins.event_big = events.big
		AND
  checkins.member_big IN ' . $FriendsID . ' ORDER BY
  checkins.created DESC LIMIT 50';
			
			$db = $this->getDataSource ();
			
			// try {
			$result = $db->fetchAll ( $MySql );
			
			// die(debug($result));
			
			if (empty ( $result ))
				return array ();
				
				// Transform to a friendlier format
			
			$xresponse = array ();
			
			$ThePlace = array ();
			$TheMember = array ();
			
			$PlaceModel = ClassRegistry::init ( 'Place' );
			// App::import('Place','PlaceModel');
			// $PlaceModel = new PlaceModel();
			
			// App::import('MemberModel','Member');
			$MemberModel = ClassRegistry::init ( 'Member' );
			// = new MemberModel();
			
			foreach ( $result as $r ) {
				$ThePlace = $PlaceModel->find ( 'first', array (
						'conditions' => array (
								'Place.big' => $r [0] ["place_big"] 
						) 
				) );
				
				// die(debug($key));
				// die(debug($r[0]["place_big"]));
				$r ["Place"] = $ThePlace;
				
				unset ( $TheMember );
				$TheMember = $MemberModel->find ( 'first', array (
						'conditions' => array (
								'Member.big' => $r [0] ["member_big"] ,
								'Member.status !=' => DELETED
						) 
				) );
				
				// die(debug($key));
				// die(debug($r[0]["place_big"]));
				$r ["Member"] = $TheMember;
				unset ( $TheMember );
				
				$r ["Checkinbig"] = $r [0] ["big"];
				
				$xresponse [] = $r;
			}
		} // IF HAS FRIENDS!!
		
		return $xresponse;
	}
	
	/*
	 * public $hasMany = array( 'ChatMessage' => array( 'className' => 'ChatMessage', 'foreignKey' => 'rel_id', //			'order' => 'ChatMessage.created DESC', //			'fields' => 'ChatMessage.created', ), );
	 */
	public function FriendsAllRelationship($memberOne, $memberTwo) {
		$type = 'all';
		$params = array (
				'conditions' => array (
						'AND' => array (
								'OR' => array (
										'Friend.member1_big' => $memberOne,
										'Friend.member2_big' => $memberOne 
								),
								'OR' => array (
										'Friend.member1_big' => $memberTwo,
										'Friend.member2_big' => $memberTwo 
								) 
						) 
				) 
		);
		
		$result = $this->find ( $type, $params );
		return $result;
	}
	public function FriendsRelationship($memberOne, $memberTwo, $relation) {
		$db = $this->getDataSource ();
		
		$MySql = 'select * from  friends where (member1_big=' . $memberOne . ' OR member2_big=' . $memberOne . ') AND (member1_big=' . $memberTwo . ' OR member2_big=' . $memberTwo . ') AND status=\'' . $relation . '\'';
		// try {
		$result = $db->fetchAll ( $MySql );
		
		return $result;
	}
	public function FriendsRelationshipGeneric($memberOne, $memberTwo) {
		$relation = 'R';
		$type = 'all';
		$params = array (
				'conditions' => array (
						'AND' => array (
								'OR' => array (
										array (
												'Friend.member1_big' => $memberOne 
										),
										array (
												'Friend.member2_big' => $memberOne 
										) 
								),
								'OR' => array (
										array (
												'Friend.member1_big' => $memberTwo 
										),
										array (
												'Friend.member2_big' => $memberTwo 
										) 
								) 
						) 
				) 
		);
		
		$db = $this->getDataSource ();
		
		$MySql = 'select * from  friends where (member1_big=' . $memberOne . ' OR member2_big=' . $memberOne . ') AND (member1_big=' . $memberTwo . ' OR member2_big=' . $memberTwo . ')';
		// try {
		$result = $db->fetchAll ( $MySql );
		
		// $result = $this->find ( $type, $params );
		
		return $result;
	}
	public function FriendsRelated($memberOne, $relation) {
		$type = 'all';
		$params = array (
				'conditions' => array (
						'AND' => array (
								'OR' => array (
										'Friend.member1_big' => $memberOne,
										'Friend.member2_big' => $memberOne 
								),
								array (
										'Friend.status' => $relation 
								) 
						) 
				),
				'fields' => array (
						'Friend.member1_big',
						'Friend.member2_big',
						'Friend.status',
						'Friend1.big',
						'Friend1.name',
						'Friend1.middle_name',
						'Friend1.surname',
						'Friend1.photo_updated',
						'Friend1.sex',
						'Friend1.birth_date',
						'Friend1.address_town',
						'Friend1.address_country',
						'Friend1.photo_updated',
						'Friend2.big',
						'Friend2.name',
						'Friend2.middle_name',
						'Friend2.surname',
						'Friend2.photo_updated',
						'Friend2.sex',
						'Friend2.birth_date',
						'Friend2.address_town',
						'Friend2.address_country',
						'Friend2.photo_updated' 
				)
				 
		);
		
		$result = $this->find ( $type, $params );
		return $result;
	}
	public function findAllFriends($memberBig) {
		$type = 'all';
		$params = array (
				'conditions' => array (
						'AND' => array (
								'OR' => array (
										'Friend.member1_big' => $memberBig,
										'Friend.member2_big' => $memberBig 
								) 
						) 
				),
				'fields' => array (
						'Friend.member1_big',
						'Friend.member2_big',
						'Friend.status',
						'Friend1.big',
						'Friend1.name',
						'Friend1.middle_name',
						'Friend1.surname',
						'Friend1.photo_updated',
						'Friend1.sex',
						'Friend1.birth_date',
						'Friend1.address_town',
						'Friend1.address_country',
						'Friend1.photo_updated',
						'Friend2.big',
						'Friend2.name',
						'Friend2.middle_name',
						'Friend2.surname',
						'Friend2.photo_updated',
						'Friend2.sex',
						'Friend2.birth_date',
						'Friend2.address_town',
						'Friend2.address_country',
						'Friend2.photo_updated' 
				)
				 
		)
		;
		
		$result = $this->find ( $type, $params );
		return $result;
	}
	
	// return accepted friens
	public function findFriends($memberBig) {
		$type = 'all';
		$params = array (
				'conditions' => array (
						'AND' => array (
								'OR' => array (
										'Friend.member1_big' => $memberBig,
										'Friend.member2_big' => $memberBig 
								),
								array (
										'Friend.status' => 'A' 
								) 
						) 
				),
				'order' => array('Friend.created DESC')
		);
		
		$result = $this->find ( $type, $params );
		return $result;
	}
}