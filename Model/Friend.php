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
	public function getBoardFriends($MemberID, $offset = 0) {
		// $Amici array di amici rilevati dalla tabella Friends
		$Amici = $this->findFriends ( $MemberID, $offset );
		
		// create array of friends and populate with checkins places and last chat messages if any
		$PrivacySettingModel = ClassRegistry::init ( 'PrivacySetting' );
		$MemberSettingModel = ClassRegistry::init ( 'MemberSetting' );
		
		$FriendsID = array ();
		foreach ( $Amici as $ami ) {
			// add only if privacy ok
			if ($ami ["Friend1"] ["big"] == $MemberID) {
				$friendID = $ami ["Friend2"] ["big"];
			} 

			else {
				$friendID = $ami ["Friend1"] ["big"];
			}
			// $friendID = big dell'amico
			$Privacyok = $PrivacySettingModel->getPrivacySettings ( $friendID );
			$Privacyok = $Privacyok [0];
			
			$goonPrivacy = true; // membro visibile per default
			if (count ( $Privacyok ) > 0) {
				if ($Privacyok ['PrivacySetting'] ['checkinsvisibility'] == 0) {
					
					$goonPrivacy = false; // membro non visibile
				}
			}
			
			if ($goonPrivacy) {
				
				$memBlocked = $MemberSettingModel->isOnIgnoreListDual ( $ami ['Friend1'] ['big'], $ami ['Friend2'] ['big'] );
				
				if (! $memBlocked) {
					
					$FriendsID [] = $friendID; // $FriendsID array big di amici visibili
				}
			}
		}
		// $this->log("Friends ".serialize($FriendsID));
		if (count ( $FriendsID ) > 0) {
			$FriendsID = implode ( ',', $FriendsID ); // $FriendsID viene preparato per query
			/*
			 * $MySql = 'SELECT checkins.member_big,events.place_big,checkins.created,checkins.big '.
			 * 'FROM public.checkins,public.events '.
			 * 'WHERE checkins.event_big = events.big AND checkins.member_big IN (' . $FriendsID . ') '.
			 * 'ORDER BY checkins.created DESC '.
			 * 'LIMIT 50';
			 */
			
			// la query qui sopra produceva doppioni generando doppioni in $ThePlace (qualche riga più avanti)
			$MySql = 'SELECT * ' . 'FROM ( ' . 'SELECT DISTINCT ON(events.place_big) place_big, checkins.member_big,checkins.created,checkins.big ' . 'FROM public.checkins,public.events ' . 'WHERE checkins.event_big = events.big AND checkins.member_big IN (' . $FriendsID . ') ' . 
			// 'ORDER BY place_big '.
			') AS xxx ' . 'ORDER BY created DESC ' . 'LIMIT 20';
			
			// $this->log("Query ".$MySql);
			$db = $this->getDataSource ();
			
			// try {
			$result = $db->fetchAll ( $MySql );
			// $this->log("result ".print_r($result));
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
				
				// print_r($ThePlace);
				// unset ( $TheMember );
				$TheMember = $MemberModel->find ( 'first', array (
						'conditions' => array (
								'Member.big' => $r [0] ["member_big"],
								'Member.status !=' => DELETED 
						),
						'fields' => array (
								'Member.big',
								'Member.name',
								'Member.middle_name',
								'Member.surname',
								'Member.photo_updated',
								'Member.birth_date',
								'Member.sex' 
						) 
				)
				 );
				
				// print_r($TheMember);
				// print"#################################################";
				// die(debug($key));
				// die(debug($r[0]["place_big"]));
				$r ["Member"] = $TheMember;
				
				$r ["Checkinbig"] = $r [0] ["big"];
				$r ["Created"] = $r [0] ["created"];
				
				if (count ( $TheMember ) > 0) {
					$xresponse [] = $r;
				}
				
				unset ( $TheMember );
			}
		} // IF HAS FRIENDS!!
		  // print_r($xresponse);
		return $xresponse;
	}
	public function getBoardFriendsNew($MemberID, $offset = 0) {
		// $Amici array di amici rilevati dalla tabella Friends
		$db = $this->getDataSource ();
		
		$time_start_2 = $this->getmicrotime (); // sec iniziali
		
		$Amici = $this->findFriendsNew ( $MemberID, $offset );
		
		$time_end_2 = $this->getmicrotime (); // sec finali
		$time_2 = $time_end_2 - $time_start_2; // differenza in secondi
		$this->log ( "TIME (Friend -> getBoardFriendsNew -> findFriendsNew) $time_2 s " );
		// $key->0->dati
		
		// create array of friends and populate with checkins places and last chat messages if any
		
		// print_r($Amici);
		$FriendsID = array ();
		
		$time_start_3 = $this->getmicrotime (); // sec iniziali
		foreach ( $Amici as $key => $val ) {
			
			// $friendID = big dell'amico
			$friendID = $val [0] ["big"];
			
			$goonPrivacy = true; // membro visibile per default
			
			if (intval ( $val [0] ['checkinsvisibility'] ) == 0) { // considera i casi 0 e NULL ricondotti al valore 0
				
				$goonPrivacy = false; // membro non visibile
			}
			
			if ($goonPrivacy) {
				
				$FriendsID [] = $friendID; // $FriendsID array big di amici visibili e non bloccati
			}
		}
		
		$time_end_3 = $this->getmicrotime (); // sec finali
		$time_3 = $time_end_3 - $time_start_3; // differenza in secondi
		$this->log ( "TIME (Friend -> getBoardFriendsNew -> foreach Amici) $time_3 s " );
		// print_r($FriendsID);
		
		$time_start_4 = $this->getmicrotime (); // sec iniziali
		foreach ( $Amici as $key => $val ) {
			// mi aggiusto l'array membri creando come chiave dell'array il big in modo da usare i dati per popolare la var $TheMember
			// senza fare ulteriori query
			
			unset ( $val [0] ['visibletousers'] );
			unset ( $val [0] ['fbintegration'] );
			unset ( $val [0] ['disconnectplace'] );
			unset ( $val [0] ['profilestatus'] );
			unset ( $val [0] ['showvisitedplaces'] );
			unset ( $val [0] ['sharecontacts'] );
			unset ( $val [0] ['notifyprofileviews'] );
			unset ( $val [0] ['notifyfriendshiprequests'] );
			unset ( $val [0] ['notifychatmessages'] );
			unset ( $val [0] ['boardsponsor'] );
			unset ( $val [0] ['checkinsvisibility'] );
			// unset($val[0]['photosvisibility']);
			unset ( $val [0] ['friendsvisibility'] );
			
			$arrayAmici [$val [0] ['big']] = $val [0];
		}
		$time_end_4 = $this->getmicrotime (); // sec finali
		$time_4 = $time_end_4 - $time_start_4; // differenza in secondi
		$this->log ( "TIME (Friend -> getBoardFriendsNew -> foreach Amici Unset) $time_4 s " );
		// print_r($arrayAmici);
		// $this->log("Friends ".serialize($FriendsID));
		
		$time_start_5 = $this->getmicrotime (); // sec iniziali
		if (count ( $FriendsID ) > 0) {
			$FriendsID = implode ( ',', $FriendsID ); // $FriendsID viene preparato per query
			
			/*
			 * $MySql = 'SELECT checkins.member_big,events.place_big,checkins.created,checkins.big '.
			 * 'FROM public.checkins,public.events '.
			 * 'WHERE checkins.event_big = events.big AND checkins.member_big IN (' . $FriendsID . ') '.
			 * 'ORDER BY checkins.created DESC '.
			 * 'LIMIT 50';
			 */
			
			// la query qui sopra produceva doppioni generando doppioni in $ThePlace (qualche riga più avanti)
			$MySql = 'SELECT * ' . 'FROM ( ' . 'SELECT DISTINCT ON(events.place_big) place_big, checkins.member_big,checkins.created,checkins.big ' . 'FROM public.checkins,public.events ' . 'WHERE checkins.event_big = events.big AND checkins.member_big IN (' . $FriendsID . ') ' . 
			// 'ORDER BY place_big '.
			') AS xxx ' . 'ORDER BY created DESC ' . 'LIMIT 20';
			
			// $this->log("Query ".$MySql);
			
			// try {
			$result = $db->fetchAll ( $MySql );
			
			// print_r($MySql);
			// die(debug($result));
			
			if (empty ( $result ))
				return array ();
			
			$time_end_5 = $this->getmicrotime (); // sec finali
			$time_5 = $time_end_5 - $time_start_5; // differenza in secondi
			$this->log ( "TIME (Friend -> getBoardFriendsNew -> SELECT * FROM) $time_5 s " );
			// Transform to a friendlier format
			
			$xresponse = array ();
			$ThePlace = array ();
			$TheMember = array ();
			
			$PlaceModel = ClassRegistry::init ( 'Place' );
			$MemberModel = ClassRegistry::init ( 'Member' );
			
			$time_start_1 = $this->getmicrotime (); // sec iniziali
			
			foreach ( $result as $r ) {
				$ThePlace = $PlaceModel->find ( 'first', array (
						'conditions' => array (
								'Place.big' => $r [0] ["place_big"] 
						) 
				) );
				
				// print_r($ThePlace);
				$r ["Place"] = $ThePlace;
				
				$TheMember ['Member'] = $arrayAmici [$r [0] ['member_big']];
				
				// print_r($TheMember);
				
				$r ["Member"] = $TheMember;
				
				$r ["Checkinbig"] = $r [0] ["big"];
				$r ["Created"] = $r [0] ["created"];
				
				if (count ( $TheMember ) > 0) {
					$xresponse [] = $r;
				}
				
				unset ( $TheMember );
			}
			$time_end_1 = $this->getmicrotime (); // sec finali
			$time_1 = $time_end_1 - $time_start_1; // differenza in secondi
			$this->log ( "TIME (Friend -> getBoardFriendsNew -> foreach) $time_1 s " );
		} // IF HAS FRIENDS!!
		  // print_r($xresponse);
		return $xresponse;
	}
	function getmicrotime() {
		list ( $usec, $sec ) = explode ( " ", microtime () );
		return (( float ) $usec + ( float ) $sec);
	}
	public function getDiaryFriends($MemberID, $offset = 0) {
		$Amici = $this->findFriends ( $MemberID, $offset );
		
		// create array of friends and populate with checkins places and last chat messages if any
		$PrivacySettingModel = ClassRegistry::init ( 'PrivacySetting' );
		$MemberSettingModel = ClassRegistry::init ( 'MemberSetting' );
		
		$FriendsID = array ();
		
		foreach ( $Amici as $ami ) {
			// add only if privacy ok
			if ($ami ["Friend1"] ["big"] == $MemberID) {
				$friendID = $ami ["Friend2"] ["big"];
			} 

			else {
				$friendID = $ami ["Friend1"] ["big"];
			}
			$Privacyok = $PrivacySettingModel->getPrivacySettings ( $friendID );
			$Privacyok = $Privacyok [0];
			
			// $this->log($ami['Friend1']['big']."---".$ami['Friend2']['big']);
			
			$goonPrivacy = true; // visibile per default
			if (count ( $Privacyok ) > 0) {
				if ($Privacyok ['PrivacySetting'] ['checkinsvisibility'] == 0) {
					$goonPrivacy = false; // non visibile
				}
			}
			
			if ($goonPrivacy) {
				
				$memBlocked = $MemberSettingModel->isOnIgnoreListDual ( $ami ['Friend1'] ['big'], $ami ['Friend2'] ['big'] );
				
				if (! $memBlocked) {
					
					$FriendsID [] = $friendID; // $FriendsID array big di amici visibili
				}
			}
		}
		
		// $this->log("FriendsID ".$FriendsID);
		
		if (count ( $FriendsID ) > 0) {
			$FriendsID = implode ( ',', $FriendsID );
			
			$MySql = ' SELECT   members.big,  members.fb_id,  members.name,  members.middle_name,  members.surname,  members.photo_updated,  members.birth_date,  members.sex,  members.last_lonlat ' . 'FROM public.members ' . 'WHERE members.big IN (' . $FriendsID . ') ' . 'LIMIT 50';
			// todo: ORDER BY RANK??
			// checkins.created DESC
			$db = $this->getDataSource ();
			
			// try {
			$result = $db->fetchAll ( $MySql );
			
			// die(debug($result));
			
			if (empty ( $result ))
				return array ();
				
				// Transform to a friendlier format
			
			$xresponse = array ();
			
			$TheMember = array ();
			
			// App::import('MemberModel','Member');
			$MemberModel = ClassRegistry::init ( 'Member' );
			
			foreach ( $result as $r ) {
				
				unset ( $TheMember );
				$TheMember = $MemberModel->find ( 'first', array (
						'conditions' => array (
								'Member.big' => $r [0] ["big"],
								'Member.status !=' => DELETED 
						),
						'fields' => array (
								'Member.big',
								'Member.name',
								'Member.middle_name',
								'Member.surname',
								'Member.photo_updated',
								'Member.birth_date',
								'Member.sex',
								'Member.type',
								'Member.created' 
						)
						 
				) );
				

				$TheMember ['Member']['isvip']=false;
				$TheMember ['Member']['ishot']=false;
				$TheMember ['Member']['isnew']=false;
				
				$TheMember ['Member']['isvip'] = ($TheMember['Member'] ['type'] == MEMBER_VIP);
				
				$db = $MemberModel->getDataSource ();
				
				$serviceList = explode ( ',', ID_RADAR_VISIBILITY_PRODUCTS );
				$query = 'SELECT count(*) FROM wallets WHERE member1_big=' . $TheMember['Member'] ['big'] . ' AND expirationdate>NOW() AND product_id IN (' . ID_RADAR_VISIBILITY_PRODUCTS . ')';
				
				try {
					$mwal = $db->fetchAll ( $query );
					$TheMember ['Member']['ishot'] = (count ( $mwal ) > 0);
				} catch ( Exception $e ) {
					
					$this->_apiEr ( $e );
				}
				
				$now = new DateTime ();
				$olddate = date ( 'm/d/Y h:i:s a', time () );
				date_sub ( $now, date_interval_create_from_date_string ( '5 days' ) );
				$TheMember ['Member']['isnew'] = ($TheMember['Member'] ['created']) > $now;
				
				// die(debug($key));
				// die(debug($r[0]["place_big"]));
				$r ["Member"] = $TheMember;
				unset ( $TheMember );
				
				$xresponse [] = $r;
			}
		} // IF HAS FRIENDS!!
		
		return $xresponse;
	}
	
	/*
	 * public $hasMany = array( 'ChatMessage' => array( 'className' => 'ChatMessage', 'foreignKey' => 'rel_id', // 'order' => 'ChatMessage.created DESC', // 'fields' => 'ChatMessage.created', ), );
	 */
	public function FriendsAllRelationship($memberOne, $memberTwo) {
		$MemberSettingModel = ClassRegistry::init ( 'MemberSetting' );
		$MemberModel = ClassRegistry::init ( 'Member' );
		$cleanResult = array ();
		
		$type = 'all';
		$params = array (
				'conditions' => array (
						'AND' => array (
								'OR' => array (
										'Friend.member1_big' => $memberOne,
										'Friend.member2_big' => $memberOne 
								),
								array (
										'OR' => array (
												'Friend.member1_big' => $memberTwo,
												'Friend.member2_big' => $memberTwo 
										) 
								) 
						) 
				) 
		);
		// die(debug($params));
		$result = $this->find ( $type, $params );
		
		foreach ( $result as $key => $val ) {
			
			$memBlocked = $MemberSettingModel->isOnIgnoreListDual ( $val ['Friend'] ['member1_big'], $val ['Friend'] ['member2_big'] );
			// non considera i membri cancellati. Quindi se uno dei due membri risulta cancellato non viene presa la relazione
			$mem1_Active = $MemberModel->isActive ( $val ['Friend'] ['member1_big'] );
			$mem2_Active = $MemberModel->isActive ( $val ['Friend'] ['member2_big'] );
			
			if (! $memBlocked and $mem1_Active and $mem2_Active) { // se non bloccato metti in output
				$cleanResult [] = $result [$key];
			}
		}
		
		return $cleanResult;
	}
	public function FriendsRelationship($memberOne, $memberTwo, $relation) {
		$MemberSettingModel = ClassRegistry::init ( 'MemberSetting' );
		$db = $this->getDataSource ();
		$cleanResult = array ();
		
		$MySql = "SELECT * FROM friends " . "WHERE (member1_big=$memberOne OR member2_big=$memberOne) " . "AND (member1_big=$memberTwo OR member2_big=$memberTwo) AND status='$relation' " . "ORDER BY created DESC";
		// try {
		$result = $db->fetchAll ( $MySql );
		
		if (count ( $result [0] ) > 0) {
			foreach ( $result as $key => $val ) {
				
				$memBlocked = $MemberSettingModel->isOnIgnoreListDual ( $val [0] ['member1_big'], $val [0] ['member2_big'] );
				if (! $memBlocked) { // se non bloccato metti in output
					$cleanResult [] = $result [$key];
				}
			}
		}
		return $cleanResult;
	}
	public function recommendedFriend($memBig, $memTwo) {
		/*
		 * param :
		 * $memBig = logged member big
		 * $memTwo = member big da verificare se amico
		 * Verifica se un membro può essere raccomandato ad un dato memberBig
		 */
		$PrivacySettingModel = ClassRegistry::init ( 'PrivacySetting' );
		$MemberSettingModel = ClassRegistry::init ( 'MemberSetting' );
		$MemberModel = ClassRegistry::init ( 'Member' );
		
		$type = 'first';
		$params = array (
				'conditions' => array (
						'AND' => array (
								'OR' => array (
										'Friend.member1_big' => $memBig,
										'Friend.member2_big' => $memBig 
								),
								array (
										'OR' => array (
												'Friend.member1_big' => $memTwo,
												'Friend.member2_big' => $memTwo 
										) 
								) 
						) 
				) 
		);
		// verifico se è già amico o lo è stato A,R,D,X
		$result = $this->find ( $type, $params );
		
		if (count ( $result ) > 0) {
			// è già amico quindi non consigliare
			$status = false;
		} else { // non è amico quindi verifico se non bloccato e attivo
		  
			// verifica se bloccato
			$memBlocked = $MemberSettingModel->isOnIgnoreListDual ( $memBig, $memTwo );
			
			// verifica se il member è attivo (< 255)
			$memActive = $MemberModel->isActive ( $memTwo );
			
			$Privacyok = $PrivacySettingModel->getPrivacySettings ( $memTwo );
			$memPrivacy = $Privacyok [0] ['PrivacySetting'] ['visibletousers'];
			
			switch ($memPrivacy) {
				
				case 0 : // visibile a nessuno
					$visible = false;
					break;
				
				case 1 : // visibile a tutti
					$visible = true;
					break;
			}
			
			if (! $memBlocked and $memActive and $visible) {
				// se non bloccato, attivo e visibletouser=1 allora può essere consigliato a tutti
				// se visibletousers=2 può essere consigliato solo agli amici
				
				$status = true;
			} else
				// è bloccato o non attivo quindi non può essere consigliato
				$status = false;
		}
		
		return $status;
	}
	public function FriendsRelationshipGeneric($memberOne, $memberTwo) {
		$MemberSettingModel = ClassRegistry::init ( 'MemberSetting' );
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
		foreach ( $result as $key => $val ) {
			
			$memBlocked = $MemberSettingModel->isOnIgnoreListDual ( $val [0] ['member1_big'], $val [0] ['member2_big'] );
			if (! $memBlocked) { // se non bloccato metti in output
				$cleanResult [] = $result [$key];
			}
		}
		return $cleanResult;
	}
	public function FriendsRelated($memberOne, $relation) {
		$MemberSettingModel = ClassRegistry::init ( 'MemberSetting' );
		
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
		)
		;
		
		$result = $this->find ( 'all', $params );
		
		foreach ( $result as $key => $val ) {
			
			$memBlocked = $MemberSettingModel->isOnIgnoreListDual ( $val ['Friend'] ['member1_big'], $val ['Friend'] ['member2_big'] );
			if (! $memBlocked) { // se non bloccato metti in output
				$cleanResult [] = $result [$key];
			}
		}
		
		return $cleanResult;
	}
	public function findAllFriendsNew($memberBig, $action = null, $birth = false) {
		$db = $this->getDataSource ();
		$actionFilter = '';
		
        if ($birth==true){
            
            $birthCondition=" AND date_part('month',birth_date)=date_part('month',Now()) AND date_part('day',birth_date)=date_part('day',Now()) ";
            
        } else {
                $birthCondition="";
        }
        
		if ($action != null) {
			
			$actionFilter = " AND f.status='$action' ";
		}
		
		$fields = "f.member1_big,f.member2_big,f.status,m.big,m.name,m.middle_name,m.surname,m.photo_updated," . 
                  "m.sex,m.birth_date,m.address_town,m.address_country,cast(last_lonlat as text) AS coordinates,ps.visibletousers" . 
                  ",(m.type=4) as isvip,(m.created>(now() - interval '5 days'))  as isnew," . 
                  " date_part('month',birth_date)=date_part('month',Now()) AND date_part('day',birth_date)=date_part('day',Now()) AS Birth, ".
                  "(SELECT COUNT(*) FROM wallets WHERE member1_big=m.big AND expirationdate>NOW() AND product_id IN (" . ID_RADAR_VISIBILITY_PRODUCTS . "))>0 AS ishot ";
		
		$query = "SELECT $fields " . 
                 "FROM friends f " . 
                 "JOIN members m ON f.member2_big=m.big " . 
                 "LEFT JOIN privacy_settings ps ON m.big=ps.member_big " . 
                 "WHERE f.member1_big=$memberBig $actionFilter $birthCondition AND m.status<255 AND f.member2_big NOT IN ( " . 
                                "SELECT to_big " . "FROM member_settings " . 
                                "WHERE from_big=$memberBig AND chat_ignore=1 " . 
                                "UNION " . 
                                "SELECT from_big " . 
                                "FROM member_settings " . 
                                "WHERE to_big=$memberBig AND chat_ignore=1 " . 
                                ") " . 
                 "UNION " . 
                 "SELECT $fields " . 
                 "FROM friends f " . 
                 "JOIN members m ON f.member1_big=m.big " . 
                 "LEFT JOIN privacy_settings ps ON m.big=ps.member_big " . 
                 "WHERE f.member2_big=$memberBig $actionFilter $birthCondition AND m.status<255 AND f.member1_big NOT IN ( " . 
                                "SELECT to_big " . 
                                "FROM member_settings " . 
                                "WHERE from_big=$memberBig AND chat_ignore=1 " . 
                                "UNION " . "SELECT from_big " . 
                                "FROM member_settings " . 
                                "WHERE to_big=$memberBig AND chat_ignore=1 " . 
                                ")";
		
		$externalQuery = "SELECT * FROM ( $query ) AS foo ORDER BY name,surname";
		
		// print($externalQuery);
		
		$result = $db->fetchAll ( $externalQuery );
		
		foreach ( $result as $key => $val ) {
			
			$cleanResult [$key] = $val [0];
		}
		
		return $cleanResult;
	}
	public function findAllFriends($memberBig) {
		$MemberSettingModel = ClassRegistry::init ( 'MemberSetting' );
		
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
		
		foreach ( $result as $key => $val ) {
			
			$memBlocked = $MemberSettingModel->isOnIgnoreListDual ( $val ['Friend'] ['member1_big'], $val ['Friend'] ['member2_big'] );
			if (! $memBlocked) { // se non bloccato metti in output
				$cleanResult [] = $result [$key];
			}
		}
		
		return $cleanResult;
	}
	public function findAllFriendsWithBad($memberBig) {
		$db = $this->getDataSource ();
		
		$query = "SELECT member1_big AS myfriend " . "FROM friends " . "WHERE member2_big=$memberBig " . "UNION " . "SELECT member2_big AS myfriend " . "FROM friends " . "WHERE member1_big=$memberBig";
		
		$result = $db->fetchAll ( $query );
		
		$badfriends = $this->badFriends ( $memberBig );
		
		foreach ( $result as $key => $val ) {
			
			$cleanResult [] = $val [0] ['myfriend'];
		}
		
		foreach ( $badfriends as $key => $val ) {
			
			$cleanResult [] = $val;
		}
		
		return $cleanResult;
	}
	public function badFriends($membig) {
		// restituisce i membri della tabella memberSettings con chat_ignore=1
		$ignore = array ();
		
		$db = $this->getDataSource ();
		
		$query = "SELECT from_big AS ignore " . "FROM member_settings " . "WHERE to_big=$membig AND chat_ignore=1 " . "UNION " . "SELECT to_big AS ignore " . "FROM member_settings " . "WHERE from_big=$membig AND chat_ignore=1";
		
		try {
			$result = $db->fetchAll ( $query );
		} catch ( Exception $e ) {
			debug ( $e );
			return false;
		}
		
		if (count ( $result ) > 0) {
			
			foreach ( $result as $key => $val ) {
				
				$ignore [] = $val [0] ['ignore'];
			}
		}
		
		return $ignore;
	}
	public function FriendType($membig) {
		// restituisce il tipo di membro campo type. Se Vip allora type=4. Se member normale allora type=1
		$db = $this->getDataSource ();
		
		$query = "SELECT * " . "FROM members " . "WHERE big=$membig AND status<255";
		
		try {
			$result = $db->fetchAll ( $query );
		} catch ( Exception $e ) {
			debug ( $e );
			return false;
		}
		
		if (count ( $result ) < 1) {
			
			$this->_apiEr ( __ ( "Utente Inesistente" ) );
		} else {
			
			$type = $result [0] [0] ['type'];
		}
		// print_r($result);
		return $type;
	}
	
	// return accepted friens
	public function findFriendsNew($memberBig, $offset = 0) {
		$db = $this->getDataSource ();
		$offset = $offset * API_CHAT_PER_PAGE;
		
		/*
		 * Estrae i dati anagrafici e di privacy di tutti gli amici di $memberBig
		 * che NON sono bloccati ($badfriend) o disattivati(255)
		 */
		$query = "WITH amici AS (SELECT member1_big AS friend " . "FROM friends " . "WHERE member2_big=$memberBig AND status='A' " . "UNION " . "SELECT member2_big AS friend " . "FROM friends " . "WHERE member1_big=$memberBig AND status='A' " . "ORDER BY friend ASC ) " . 

		"SELECT m.big,m.name,m.middle_name,m.surname,m.photo_updated,m.birth_date,m.sex,ps.visibletousers,ps.fbintegration," . '(m.type=4) as isvip,(m.created>(now() - interval \'5 days\'))  as isnew,' . '(SELECT COUNT(*) FROM wallets WHERE member1_big=m.big AND expirationdate>NOW() AND product_id IN (' . ID_RADAR_VISIBILITY_PRODUCTS . '))>0 AS ishot, ' . "ps.disconnectplace,ps.profilestatus,ps.showvisitedplaces,ps.sharecontacts,ps.notifyprofileviews,ps.notifyfriendshiprequests," . "ps.notifychatmessages,ps.boardsponsor,ps.checkinsvisibility,ps.photosvisibility,ps.friendsvisibility " . "FROM amici " . "JOIN members m ON friend=m.big " . "LEFT JOIN privacy_settings ps ON ps.member_big=m.big " . "WHERE m.status<255 AND m.big NOT IN (SELECT from_big as badfriend " . "FROM member_settings " . "WHERE to_big=$memberBig AND chat_ignore=1 " . "UNION " . "SELECT to_big as badfriend " . "FROM member_settings " . "WHERE from_big=$memberBig AND chat_ignore=1 )" . 

		"LIMIT " . API_CHAT_PER_PAGE . " " . "OFFSET $offset";
		
		$result = $db->fetchAll ( $query );
		return $result;
	}
	
	// return accepted friens
	public function findFriends($memberBig, $offset = 0) {
		$offset = $offset * API_CHAT_PER_PAGE;
		
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
				'limit' => API_CHAT_PER_PAGE,
				'offset' => $offset,
				'order' => array (
						'Friend.big ASC' 
				) 
		);
		
		$result = $this->find ( $type, $params );
		return $result;
	}
	public function countFriendRequest($memBig) {
		
		// $status='R';
		//
		// $counter = $this->find('count', array(
		// 'conditions' => array(
		// 'read' => 0, 'status' => $status, 'member2_big' => $memBig
		// )));
		//
		$db = $this->getDataSource ();
		$sql = 'SELECT COUNT(*) AS request FROM friends WHERE read=0 AND status=\'R\'' . ' AND member2_big=' . $memBig;
		$result = $db->fetchAll ( $sql );
		
		return $result;
	}
	public function setReadFriendRequest($memBig) {
		$db = $this->getDataSource ();
		$sql = 'UPDATE friends SET read=1 WHERE read=0 AND status=\'R\'' . ' AND member2_big=' . $memBig;
		
		try {
			$db->fetchAll ( $sql );
		} catch ( Exception $e ) {
			debug ( $e );
			return false;
		}
		
		return true;
	}
	public function RemoveFriend($memBig1, $memBig2) {
		$db = $this->getDataSource ();
		$sql = 'DELETE FROM friends WHERE ( member1_big=' . $memBig1 . ' AND member2_big=' . $memBig2 . ') OR ( member2_big=' . $memBig1 . ' AND member1_big=' . $memBig2 . ')';
		
		try {
			$db->fetchAll ( $sql );
		} catch ( Exception $e ) {
			debug ( $e );
			return false;
		}
		
		return true;
	}
}