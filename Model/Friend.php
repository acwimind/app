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
	public function getBoardFriends($MemberID, $offset=0) {
		//$Amici array di amici rilevati dalla tabella Friends
        $Amici = $this->findFriends ( $MemberID, $offset );
		
		// create array of friends and populate with checkins places and last chat messages if any
		$PrivacySettingModel = ClassRegistry::init ( 'PrivacySetting' );
		$MemberSettingModel = ClassRegistry::init('MemberSetting');
		
		$FriendsID = array();
		foreach ( $Amici as $ami ) {
			// add only if privacy ok
			if ($ami ["Friend1"] ["big"] == $MemberID) {
				$friendID = $ami ["Friend2"] ["big"] ;
			} 

			else {
				$friendID = $ami ["Friend1"] ["big"] ;
			}
            //$friendID = big dell'amico 
			$Privacyok = $PrivacySettingModel->getPrivacySettings ( $friendID );
            $Privacyok=$Privacyok[0];
                        
			$goonPrivacy = true;//membro visibile per default
			if (count ( $Privacyok ) > 0) {
				if ($Privacyok ['PrivacySetting']['checkinsvisibility'] == 0) {
                    
					$goonPrivacy = false;//membro non visibile
				}
			}
        
        if ($goonPrivacy){
            
		$memBlocked=$MemberSettingModel->isOnIgnoreListDual($ami['Friend1']['big'],$ami['Friend2']['big']);
    
            if (!$memBlocked) {
                
                			$FriendsID[] = $friendID; //$FriendsID array big di amici visibili
                }	
		}
    } 
		//$this->log("Friends ".serialize($FriendsID));
		if (count($FriendsID) > 0) {
			$FriendsID = implode(',',$FriendsID); //$FriendsID viene preparato per query
			/*$MySql = 'SELECT checkins.member_big,events.place_big,checkins.created,checkins.big '.
                     'FROM public.checkins,public.events '.
		             'WHERE checkins.event_big = events.big	AND checkins.member_big IN (' . $FriendsID . ') '.
                     'ORDER BY checkins.created DESC '.
                     'LIMIT 50'; */
                     
            //la query qui sopra produceva doppioni generando doppioni in $ThePlace (qualche riga più avanti)         
            $MySql = 'SELECT * '.
                     'FROM ( '.
                        'SELECT DISTINCT ON(events.place_big) place_big, checkins.member_big,checkins.created,checkins.big '.
                        'FROM public.checkins,public.events '.
                        'WHERE checkins.event_big = events.big AND checkins.member_big IN ('. $FriendsID . ') '.
                        'ORDER BY place_big '.
                        ') AS xxx '.
                     'ORDER BY created DESC '.            
                     'LIMIT 1';
         
			//$this->log("Query ".$MySql);
			$db = $this->getDataSource ();
			
			// try {
			$result = $db->fetchAll ( $MySql );
			//$this->log("result ".print_r($result));
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
				
                //print_r($ThePlace);
				//unset ( $TheMember );
				$TheMember = $MemberModel->find ( 'first', array (
						'conditions' => array (
								'Member.big' => $r [0] ["member_big"] ,
								'Member.status !=' => DELETED
						)
                         
				) );
				//print_r($TheMember);
				// die(debug($key));
				// die(debug($r[0]["place_big"]));
				$r ["Member"] = $TheMember;
				
				
				$r ["Checkinbig"] = $r [0] ["big"];

				if (count($TheMember)>0)
				{
				$xresponse [] = $r;
				}
				
				unset ( $TheMember );
			}
		} // IF HAS FRIENDS!!
		//print_r($xresponse);
		return $xresponse;
	}
	
	
	public function getDiaryFriends($MemberID,$offset=0) {
		$Amici = $this->findFriends ( $MemberID, $offset );
	
		// create array of friends and populate with checkins places and last chat messages if any
		$PrivacySettingModel = ClassRegistry::init ( 'PrivacySetting' );
	    $MemberSettingModel = ClassRegistry::init('MemberSetting');
		
        $FriendsID = array();
        
		foreach ( $Amici as $ami ) {
			// add only if privacy ok
			if ($ami ["Friend1"] ["big"] == $MemberID) {
				$friendID = $ami ["Friend2"] ["big"] ;
			}
	
			else {
				$friendID = $ami ["Friend1"] ["big"] ;
			}
			$Privacyok = $PrivacySettingModel->getPrivacySettings ( $friendID );
            $Privacyok=$Privacyok[0];
            
            //$this->log($ami['Friend1']['big']."---".$ami['Friend2']['big']);
            
			$goonPrivacy = true;//visibile per default
			if (count ( $Privacyok ) > 0) {
				if ($Privacyok ['PrivacySetting'] ['checkinsvisibility'] == 0) {
					$goonPrivacy = false;//non visibile
				}
			}
          
          if ($goonPrivacy){
            
          $memBlocked=$MemberSettingModel->isOnIgnoreListDual($ami['Friend1']['big'],$ami['Friend2']['big']);
			
            if (!$memBlocked) {
                
                         $FriendsID[] = $friendID; //$FriendsID array big di amici visibili
       
			    }
		    }
       }
    
        //$this->log("FriendsID ".$FriendsID);    
		
        if (count( $FriendsID ) > 0) {
			$FriendsID = implode(',',$FriendsID);
				
			$MySql = 'SELECT * '.
		             'FROM public.members '.
		             'WHERE members.big IN (' . $FriendsID . ') '. 
                     'LIMIT 50';
			//todo:	ORDER BY RANK??
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
								'Member.big' => $r [0] ["member_big"] ,
								'Member.status !=' => DELETED
						)
				) );
	
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
	 * public $hasMany = array( 'ChatMessage' => array( 'className' => 'ChatMessage', 'foreignKey' => 'rel_id', //			'order' => 'ChatMessage.created DESC', //			'fields' => 'ChatMessage.created', ), );
	 */
	public function FriendsAllRelationship($memberOne, $memberTwo) {
        $MemberSettingModel = ClassRegistry::init('MemberSetting');
        $MemberModel = ClassRegistry::init('Member');
        
        $type = 'all';
        $params = array (
                'conditions' => array (
                        'AND' => array (
                                'OR' => array (
                                        'Friend.member1_big' => $memberOne,
                                        'Friend.member2_big' => $memberOne 
                                ),array(
                                'OR' => array (
                                        'Friend.member1_big' => $memberTwo,
                                        'Friend.member2_big' => $memberTwo 
                                )) 
                        ) 
                ) 
        );
    //    die(debug($params));
        $result = $this->find ( $type, $params );
        
        foreach($result as $key=>$val){
            
       $memBlocked=$MemberSettingModel->isOnIgnoreListDual($val['Friend']['member1_big'],$val['Friend']['member2_big']);
       //non considera i membri cancellati. Quindi se uno dei due membri risulta cancellato non viene presa la relazione
       $mem1_Active=$MemberModel->isActive($val['Friend']['member1_big']);
       $mem2_Active=$MemberModel->isActive($val['Friend']['member2_big']);
       
       if (!$memBlocked AND $mem1_Active AND $mem2_Active){//se non bloccato metti in output
            $cleanResult[]=$result[$key];
            }
        }        
           
        return $cleanResult;
    }
	public function FriendsRelationship($memberOne, $memberTwo, $relation) {
        
        $MemberSettingModel = ClassRegistry::init('MemberSetting');
		$db = $this->getDataSource ();
		
		$MySql = 'select * from  friends where (member1_big=' . $memberOne . ' OR member2_big=' . $memberOne . ') AND (member1_big=' . $memberTwo . ' OR member2_big=' . $memberTwo . ') AND status=\'' . $relation . '\' order by created desc';
		// try {
		$result = $db->fetchAll ( $MySql );
        
      foreach($result as $key=>$val){
              
       $memBlocked=$MemberSettingModel->isOnIgnoreListDual($val[0]['member1_big'],$val[0]['member2_big']);
            if (!$memBlocked){//se non bloccato metti in output
            $cleanResult[]=$result[$key];
            }
        }        
		        
		return $cleanResult;
	}
	public function FriendsRelationshipGeneric($memberOne, $memberTwo) {
        $MemberSettingModel = ClassRegistry::init('MemberSetting');
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
		foreach($result as $key=>$val){
              
       $memBlocked=$MemberSettingModel->isOnIgnoreListDual($val[0]['member1_big'],$val[0]['member2_big']);
            if (!$memBlocked){//se non bloccato metti in output
            $cleanResult[]=$result[$key];
            }
        }        
		return $cleanResult;
	}
	public function FriendsRelated($memberOne, $relation) {
		
        $MemberSettingModel = ClassRegistry::init('MemberSetting');
        
        
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
		
		$result = $this->find ( 'all', $params );
        
        foreach($result as $key=>$val){
              
       $memBlocked=$MemberSettingModel->isOnIgnoreListDual($val['Friend']['member1_big'],$val['Friend']['member2_big']);
            if (!$memBlocked){//se non bloccato metti in output
            $cleanResult[]=$result[$key];
            }
        }
        
		return $cleanResult;
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
	public function findFriends($memberBig,$offset=0) {
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
                'limit' => 10,
                'offset' => $offset,
                'order' => array('Friend.big ASC') 
		);
		
		$result = $this->find ( $type, $params );
		return $result;
	}
    
    
    public function countFriendRequest($memBig){
        
        //$status='R';
//               
//        $counter = $this->find('count', array(
//            'conditions' => array(
//                'read' => 0, 'status' => $status, 'member2_big' => $memBig
//            )));
//        
        
         $db = $this->getDataSource();
         $sql = 'SELECT COUNT(*) AS request FROM friends WHERE read=0 AND status=\'R\''.' AND member2_big='.$memBig;
         $result=$db->fetchAll($sql);
                       
        return $result;
        
    }
    
    
    public function setReadFriendRequest($memBig){
        
         $db = $this->getDataSource();
         $sql = 'UPDATE friends SET read=1 WHERE read=0 AND status=\'R\''.' AND member2_big='.$memBig;
         
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
         
    
}