<?php
App::uses ( 'Logger', 'Lib' );
class FriendsController extends AppController {
	public $uses = array (
			'Friend',
			'Member',
			'ChatMessage',
			'PushToken',
			'PrivacySetting' 
	);
	public function recursiveRemoval(&$array, $val) {
		if (is_array ( $array )) {
			foreach ( $array as $key => &$arrayElement ) {
				if (is_array ( $arrayElement )) {
					recursiveRemoval ( $arrayElement, $val );
				} else {
					if ($arrayElement == $val) {
						unset ( $array [$key] );
					}
				}
			}
		}
	}
	/**
	 * Add place to bookmarks of this member
	 */
	public function api_GetFriendsTest() {
        $this->_checkVars ( array (
                'idMember' 
        ) );
        
        $idMember = $this->api ['idMember'];
        
        if (isset ( $this->request->data ['action'] ) && $this->request->data ['action'] != null) {
            $action = $this->request->data ['action'];
            $Amici = $this->Friend->findAllFriendsNew( $idMember, $action );
        } else {
            $Amici = $this->Friend->findAllFriendsNew( $idMember );
        }
        
                
       
        //print_r($Amici);
        $xresponse = array ();
        $xami = array ();
        foreach ( $Amici as $key=>$ami ) {
                     
                        
            if ($ami['member1_big'] == $idMember) {
                $ami['friendstatus'] = $ami['status'];
                $ami['friendtype'] = 'Passive';
                if ($ami['status']!='A')
                {
                    $ami['surname'] = mb_substr ( $ami['surname'], 0, 1 ) . '.';
                }
                //$xami[] = $ami;
            } 

            else {
                $ami['friendstatus'] = $ami['status'];
                $ami['friendtype'] = 'Active';
                if ($ami['status']!='A')
                {
                    $ami['surname'] = mb_substr ( $ami['surname'], 0, 1 ) . '.';
                }
                //$xami [] = $ami;
            }
             unset($ami['member1_big']);
             unset($ami['member2_big']);
             unset($ami['status']);
             //print_r('--->'.$xami[0]['photo_updated']);                     
            if (isset ( $ami['photo_updated'] ) && $ami['photo_updated'] > 0) {
                $ami['profile_picture'] = $this->FileUrl->profile_picture ( $ami['big'], $ami['photo_updated'] );
            } else {
                // standard image
                $sexpic = 2;
                if ($ami['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $ami['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
            
            $xami[] = $ami;
                                            
            if ($xami[0]['visibletousers']==0){
            
            $xami[0]['coordinates']=null;
            }
             unset($xami[0]['visibletousers']);
            // CHECK USER NOT DELETED
             $xresponse [] = $xami [0];
            
            unset ( $xami );
        }
        
        print_r($xresponse);
        
        //ordina l'array per nome
        //usort( $xresponse, 'FriendsController::multiFieldSortArray' );
        
        // reset counter A cosa serve ??!?!?
        //$this->Friend->setReadFriendRequest($idMember);
        //unset($xresponse[346]);
        $this->_apiOk ( $xresponse );
    }
    
    public function api_GetFriends() {
    	$this->_checkVars ( array (
                'idMember' 
        ) );
        
        $idMember = $this->api ['idMember'];

        
        if (isset ( $this->request->data ['action'] ) && $this->request->data ['action'] != null) {
            $action = $this->request->data ['action'];
            $Amici = $this->Friend->findAllFriendsNew( $idMember, $action );
        } else {
            $Amici = $this->Friend->findAllFriendsNew( $idMember );
        }
        
        $xresponse = array ();
        $xami = array ();
        foreach ( $Amici as $key=>$ami ) {
                     
                        
            if ($ami['member1_big'] == $idMember) {
                $ami['friendstatus'] = $ami['status'];
                $ami['friendtype'] = 'Passive';
                if ($ami['status']!='A')
                {
                    $ami['surname'] = mb_substr( $ami['surname'], 0, 1 ) . '.';
                }
                //$xami[] = $ami;
            } 

            else {
                $ami['friendstatus'] = $ami['status'];
                $ami['friendtype'] = 'Active';
                if ($ami['status']!='A')
                {
                    $ami['surname'] = mb_substr( $ami['surname'], 0, 1 ) . '.';
                }
                //$xami [] = $ami;
            }
             unset($ami['member1_big']);
             unset($ami['member2_big']);
             unset($ami['status']);
             //print_r('--->'.$xami[0]['photo_updated']);                     
            if (isset ( $ami['photo_updated'] ) && $ami['photo_updated'] > 0) {
                $ami['profile_picture'] = $this->FileUrl->profile_picture ( $ami['big'], $ami['photo_updated'] );
            } else {
                // standard image
                $sexpic = 2;
                if ($ami['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $ami['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
            
            $xami[] = $ami;
                                            
            if ($xami[0]['visibletousers']==0){
            
            $xami[0]['coordinates']=null;
            }
             unset($xami[0]['visibletousers']);
            // CHECK USER NOT DELETED
             $xresponse [] = $xami [0];
            
            unset ( $xami );
        }
        
        //print_r($xresponse);
        
        //ordina l'array per nome
        //usort( $xresponse, 'FriendsController::multiFieldSortArray' );
        
        // reset counter A cosa serve ??!?!?
        $this->Friend->setReadFriendRequest($idMember);
        
        $this->_apiOk ( $xresponse );
    }
    
        
    public function api_GetFriendsOLD() {
		$this->_checkVars ( array (
				'idMember' 
		) );
		
		$idMember = $this->api ['idMember'];
		
		if (isset ( $this->request->data ['action'] ) && $this->request->data ['action'] != null) {
			$action = $this->request->data ['action'];
			$Amici = $this->Friend->FriendsRelated ( $idMember, $action );
		} else {
			$Amici = $this->Friend->findAllFriends ( $idMember );
		}
		
		$xresponse = array ();
		$xami = array ();
		foreach ( $Amici as $ami ) {
			$deleted = false;
			
			if ($ami ["Friend1"] ["big"] == $idMember) {
				$ami ["Friend2"] ["friendstatus"] = $ami ["Friend"] ["status"];
				$ami ["Friend2"] ["friendtype"] = "Passive";
				if ($ami ["Friend"] ["status"]!='A')
				{
					$ami ["Friend2"] ['surname'] = mb_substr ( $ami ["Friend2"] ['surname'], 0, 1 ) . '.';
				}
				$xami [] = $ami ["Friend2"];
			} 

			else {
				$ami ["Friend1"] ["friendstatus"] = $ami ["Friend"] ["status"];
				$ami ["Friend1"] ["friendtype"] = "Active";
				if ($ami ["Friend"] ["status"]!='A')
				{
					$ami ["Friend1"] ['surname'] = mb_substr ( $ami ["Friend1"] ['surname'], 0, 1 ) . '.';
				}
				$xami [] = $ami ["Friend1"];
			}
			
			
			// debug($xami[0]);
			
			if (isset ( $xami [0] ['photo_updated'] ) && $xami [0] ['photo_updated'] > 0) {
				$xami [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $xami [0] ['big'], $xami [0] ['photo_updated'] );
			} else {
				// standard image
				$sexpic = 2;
				if ($xami [0] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$xami [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			
			$TheMem=$this->Member->getMemberByBig($xami [0] ['big']);
			
            $privacySettings=$this->PrivacySetting->getPrivacySettings($xami [0] ['big']);
            
            $privacySettings=$privacySettings[0]['PrivacySetting']['visibletousers'];
            
            if ($privacySettings>0){
            
            $xami [0]['coordinates']=$TheMem['Member']['last_lonlat'];
			} else {
                    $xami [0]['coordinates']=null;
                    }
            // CHECK USER NOT DELETED
			if ($this->Member->isActive ( $xami [0] ['big'] )) {
				$xresponse [] = $xami [0];
			}
			unset ( $xami );
		}
		//ordina l'array per nome
        usort( $xresponse, 'FriendsController::multiFieldSortArray' );
		
		// reset counter
		$this->Friend->setReadFriendRequest($idMember);
		//print_r($xresponse);
		$this->_apiOk ( $xresponse );
	}
    
    
    function getmicrotime(){
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
        }
    
    
    
    public function api_getBoardFriendsNew($MemberID=45723352, $offset=0) {
        //$Amici array di amici rilevati dalla tabella Friends
               
        $db = $this->Friend->getDataSource ();
        
        $time_start_2 = $this->getmicrotime();//sec iniziali
        
        $Amici = $this->Friend->findFriendsNew( $MemberID, $offset );
        
        $time_end_2 = $this->getmicrotime();//sec finali
        $time_2 = $time_end_2 - $time_start_2;//differenza in secondi
        $this->log("TIME (Friend -> getBoardFriendsNew -> findFriendsNew) $time_2 s ");
        //$key->0->dati
        
        // create array of friends and populate with checkins places and last chat messages if any
        
        //print_r($Amici);
        $FriendsID = array();
        
        
        $time_start_3 = $this->getmicrotime();//sec iniziali
        foreach ( $Amici as $key=>$val ) {
            
            //$friendID = big dell'amico
            $friendID = $val[0]["big"] ;
                                   
            $goonPrivacy = true;//membro visibile per default
            
            if (intval($val[0]['checkinsvisibility']) == 0) { //considera i casi 0 e NULL ricondotti al valore 0
                    
                    $goonPrivacy = false;//membro non visibile
                }
                  
            if ($goonPrivacy){
            
                  $FriendsID[] = $friendID; //$FriendsID array big di amici visibili e non bloccati
                }    
        
            }
            
            $time_end_3 = $this->getmicrotime();//sec finali
            $time_3 = $time_end_3 - $time_start_3;//differenza in secondi
            $this->log("TIME (Friend -> getBoardFriendsNew -> foreach Amici) $time_3 s ");
            //print_r($FriendsID);
            
            $time_start_4 = $this->getmicrotime();//sec iniziali
            foreach ($Amici as $key=>$val) {
                //mi aggiusto l'array membri creando come chiave dell'array il big in modo da usare i dati per popolare la var $TheMember
                //senza fare ulteriori query
                
                unset($val[0]['visibletousers']);
                unset($val[0]['fbintegration']);
                unset($val[0]['disconnectplace']);
                unset($val[0]['profilestatus']);
                unset($val[0]['showvisitedplaces']);
                unset($val[0]['sharecontacts']);
                unset($val[0]['notifyprofileviews']);
                unset($val[0]['notifyfriendshiprequests']);
                unset($val[0]['notifychatmessages']);
                unset($val[0]['boardsponsor']);
                unset($val[0]['checkinsvisibility']);
                unset($val[0]['photosvisibility']);
                unset($val[0]['friendsvisibility']);
                            
                $arrayAmici[$val[0]['big']]=$val[0];
                              
                
            }
            $time_end_4 = $this->getmicrotime();//sec finali
            $time_4 = $time_end_4 - $time_start_4;//differenza in secondi
            $this->log("TIME (Friend -> getBoardFriendsNew -> foreach Amici Unset) $time_4 s ");                           
            //print_r($arrayAmici);
           //$this->log("Friends ".serialize($FriendsID));
           
           $time_start_5 = $this->getmicrotime();//sec iniziali
           if (count($FriendsID) > 0) {
             $FriendsID = implode(',',$FriendsID); //$FriendsID viene preparato per query
            
            /*$MySql = 'SELECT checkins.member_big,events.place_big,checkins.created,checkins.big '.
                     'FROM public.checkins,public.events '.
                     'WHERE checkins.event_big = events.big    AND checkins.member_big IN (' . $FriendsID . ') '.
                     'ORDER BY checkins.created DESC '.
                     'LIMIT 50'; */
                     
            //la query qui sopra produceva doppioni generando doppioni in $ThePlace (qualche riga più avanti)         
            $MySql = 'SELECT * '.
                     'FROM ( '.
                        'SELECT DISTINCT ON(events.place_big) place_big, checkins.member_big,checkins.created,checkins.big '.
                        'FROM public.checkins,public.events '.
                        'WHERE checkins.event_big = events.big AND checkins.member_big IN ('. $FriendsID . ') '.
                        //'ORDER BY place_big '.
                        ') AS xxx '.
                     'ORDER BY created DESC '.            
                     'LIMIT 20';
         
            //$this->log("Query ".$MySql);
                       
            // try {
            $result = $db->fetchAll ( $MySql );
            
            //print_r($MySql);
            // die(debug($result));
            
            if (empty ( $result ))
                return array ();
            
            $time_end_5 = $this->getmicrotime();//sec finali
            $time_5 = $time_end_5 - $time_start_5;//differenza in secondi
            $this->log("TIME (Friend -> getBoardFriendsNew -> SELECT * FROM) $time_5 s ");        
                // Transform to a friendlier format
            
            $xresponse = array ();
            $ThePlace = array ();
            $TheMember = array ();
            
            $PlaceModel = ClassRegistry::init ( 'Place' );
            $MemberModel = ClassRegistry::init ( 'Member' );
            
            $time_start_1 = $this->getmicrotime();//sec iniziali
            
            foreach ( $result as $r ) {
                $ThePlace = $PlaceModel->find ( 'first', array (
                        'conditions' => array (
                                'Place.big' => $r [0] ["place_big"] 
                        ) 
                ) );      
                
               //print_r($ThePlace); 
                $r ["Place"] = $ThePlace;
                
                                                           
                                
                $TheMember['Member']=$arrayAmici[$r[0]['member_big']];
                
                //print_r($TheMember);
                
                $r ["Member"] = $TheMember;
                
                
                $r ["Checkinbig"] = $r[0]["big"];
                $r ["Created"] = $r[0]["created"];

                if (count($TheMember)>0)
                {
                    $xresponse [] = $r;
                }
                
                unset ( $TheMember );
            }
          $time_end_1 = $this->getmicrotime();//sec finali
          $time_1 = $time_end_1 - $time_start_1;//differenza in secondi
          $this->log("TIME (Friend -> getBoardFriendsNew -> foreach) $time_1 s ");
        } // IF HAS FRIENDS!!
        //print_r($xresponse);
        $this->_apiOk($xresponse);
    }
    
    
    
    public static function multiFieldSortArray($x, $y) { // ordina per nome
                
            return ($x ['name'] > $y ['name']) ? + 1 : - 1;
    }
    
	public function _add($placeBig) {
	}
	public function admin_edit($id = 0) {
		if ($this->request->is ( 'post' ) || $this->request->is ( 'put' )) {
			
			if ($this->Region->saveAll ( $this->request->data, array (
					'validate' => 'first' 
			) )) {
				$this->Session->setFlash ( __ ( 'Regione salvata' ), 'flash/success' );
				return $this->redirect ( array (
						'action' => 'index' 
				) );
			} else {
				$this->Session->setFlash ( __ ( 'Errore durante il salvataggio della regione' ), 'flash/error' );
			}
		} elseif ($id > 0) {
			
			$this->request->data = $this->Region->getRegionLangs ( $id );
		}
	}
	public function admin_delete($id) {
		$has_places = $this->Region->Place->find ( 'count', array (
				'conditions' => array (
						'Place.region_id' => $id 
				),
				'recursive' => - 1 
		) );
		
		if ($has_places == 0) {
			$this->Region->RegionLang->deleteAll ( array (
					'RegionLang.region_id' => $id 
			) );
			$this->Region->deleteAll ( array (
					'Region.id' => $id 
			) );
			$this->Session->setFlash ( __ ( 'Regione cancellata' ), 'flash/success' );
		} else {
			$this->Session->setFlash ( __ ( 'La regione non può essere eliminata perchè contiene un posto. Cancellare il posto.' ), 'flash/error' );
		}
		return $this->redirect ( array (
				'action' => 'index' 
		) );
	}
	
	/**
	 * Return ids of list of friends
	 */
	public function api_list() {
		unbindAllBut ( $this->Region );
		$cities = $this->Region->find ( 'list', array (
				'fields' => array (
						'Region.id',
						'Region.city' 
				),
				'order' => array (
						'Region.id' => 'asc' 
				),
				'recursive' => - 1 
		) );
		
		// $cities_array = array();
		// fo ($cities as $row) {
		// $cities_array[] = $row;
		// }
		
		$this->_apiOk ( array (
				'cities' => $cities 
		) );
	}
	
	/**
	 * manage friends:
	 * actions
	 * R=request
	 * A=accept
	 * D=decline
	 * X=remove
	 */
	public function api_ManageFriendship() {
		
		
		$this->_checkVars ( array ( 'idMember1', 'idMember2', 'action' ) );
		
		//debug ( $this->api ['idMember1'] );
		$idMember1 = $this->api ['idMember1'];
		$idMember2 = $this->api ['idMember2'];
        
        $memBig = $this->logged['Member']['big']; 
        
		$action = $this->api ['action'];
		/*
		 * $okMember1 = isset($this->api['$idMember1']) && !empty($this->api['$idMember1']); $okMember2 = isset($this->api['$idMember2']) && !empty($this->api['$idMember2']); if (!$okMember1 || !$okMember2 ) { }
		 */
		$WalletModel = ClassRegistry::init('Wallet');
        
		// Check if user is not on partners ignore list
		$isIgnored = $this->ChatMessage->Sender->MemberSetting->isOnIgnoreListDual( $idMember1, $idMember2 );
		if ($isIgnored) {
			$this->_apiEr (__("Non posso inviare il messaggio chat. L'utente è stato bloccato."), true);
            
		}
		
		$SearchState = $action;
		if ($SearchState == 'A' || $SearchState == 'D') {
			$SearchState = 'R';
		}
		if ($SearchState == 'X') {
			$SearchState = 'A';
		}
		$Amici = $this->Friend->FriendsRelationship ( $idMember1, $idMember2, $SearchState );
		
		switch ($action) {
			case "R" :
				
                                
                $memberType=$this->Friend->FriendType($idMember2);
                                              
                $Amici = $this->Friend->FriendsRelationship ( $idMember1, $idMember2, $action );
				
				if (count ( $Amici ) == 0) {
					$Amici2 = $this->Friend->FriendsRelationship ( $idMember1, $idMember2, 'A' );
					if (count ( $Amici2 ) == 0) {
						$Amici3 = $this->Friend->FriendsRelationship ( $idMember1, $idMember2, 'D' );
						if (count ( $Amici3 ) == 0) {
							$this->Friend->set ( array (
									'member1_big' => $idMember1,
									'member2_big' => $idMember2,
									'status' => $action 
							) );
							
							// Model::$validationErrors:
							if ($this->Friend->save ()) {
                                $lastID=$this->Friend->getLastInsertID();
								$response ['test'] = $action;
								
								// push if request
							
								
								$Privacyok = $this->PrivacySetting->getPrivacySettings ( $idMember2 );
								$goonPrivacy = true;
								
						/*		if (count ( $Privacyok ) > 0) {
									if ($Privacyok [0] ['notifyfriendshiprequests'] == 0) {
										$goonPrivacy = false;
									}
								}
								*/ 
								
                                                              
                                if ($goonPrivacy) {
                                    
                                    if ($memberType!=MEMBER_VIP){
                                            //Se il ricevente non è VIP allora riceve la push
									$this->PushToken->sendNotification ( 'Haamble', 'Hai ricevuto una richiesta di amicizia!!', array (
											'partner_big' => $this->logged ['Member'] ['big'],
											'created' => date ( "Y-m-d H:i:s" ),
											'rel_id' =>$idMember2  
									), array (
											$idMember2 
									), 'Friends', 'new' );
							        
                                      
                                    }		
							
								}
							} else {
								$response ['test'] = "not saved";
							}
						}
					}
                    //crediti e rank per amicizia richiesta
                    $WalletModel->addAmount($this->logged['Member']['big'], '2', 'Amicizia richiesta' );
                    $this->Member->rank($this->logged['Member']['big'],2);
                    
				} else {
					$this->_apiEr ( __("Record esistente"));
				}
				
                 if ($memberType == MEMBER_VIP) {
                       
                                $action='A';                                
                                                                   
                                        $this->Friend->set ( 'big', $lastID);
                                        $this->Friend->set ( 'member1_big', $idMember1);
                                        $this->Friend->set ( 'member2_big', $idMember2);
                                        $this->Friend->set ( 'status', $action );
                                        
                                        if (!$this->Friend->save()){
                                                           $this->_apiEr ( __("Record non trovato") );
                                                           }
                                          
                                                 
                                   $this->PushToken->sendNotification ( 'Haamble', 'La Richiesta di amicizia è stata accettata!!', array (
                                                    'partner_big' => $idMember2,
                                                    'created' => date ( "Y-m-d H:i:s" ),
                                                    'rel_id' => $idMember1), array($idMember1), 'Friends', 'accepted' );
                                           
                                                                                                 
                                            
                                        //}
                                                                 
                                     }                                
				break;
			case "A" :
			case "D" :
			case "X" :
				
				if (count ( $Amici ) == 1) {
					
					$Amici ['status'] = $action;
					// debug($Amici[0][0]['big']); //$Amici->field('member1_big'));
					$this->Friend->set ( 'big', $Amici [0] [0] ['big'] );
					$this->Friend->set ( 'member1_big', $Amici [0] [0] ['member1_big'] );
					$this->Friend->set ( 'member2_big', $Amici [0] [0] ['member2_big'] );
					$this->Friend->set ( 'status', $action );
					
					$this->Friend->save ();
				} else {
					$this->_apiEr ( __("Record non trovato") );
				}
				
				// PUSH FOR ACCEPTED FRIENDSHIP
				if ($action == 'A') {
					$Privacyok = $this->PrivacySetting->getPrivacySettings ( $idMember1 );
					$goonPrivacy = true;
				/*	if (count ( $Privacyok ) > 0) {
						if ($Privacyok [0]['PrivacySetting'] ['notifyfriendshiprequests'] == 0) {
							$goonPrivacy = false;
						}
					} */
					if ($goonPrivacy) {
						$this->PushToken->sendNotification ( 'Haamble', 'Una richiesta di amicizia e\' stata accettata!!', array (
								'partner_big' => $this->logged ['Member'] ['big'],
								'created' => date ( "Y-m-d H:i:s" ),
								'rel_id' => $idMember2 
						), array (
								$idMember1 
						), 'Friends', 'accepted' );
						
						//Logger::Info ( 'after sendNotification' );
                        
                        //crediti e rank per amicizia accettata
                        $WalletModel->addAmount($this->logged['Member']['big'], '5', 'Amicizia accettata' );
                        $this->Member->rank($this->logged['Member']['big'],5);
					}
				} else
                {
                    //crediti e rank per amicizia cancellata o negata 
                    $WalletModel->addAmount ($this->logged['Member']['big'], '2', 'Amicizia cancellata/negata' );
                    $this->Member->rank($this->logged['Member']['big'],2); 
                   
                   // $this->Friend->delete()
                    $ok = $this->Friend->RemoveFriend( $idMember1, $idMember2);
                    
                }
				
				break;
			
			default :
				break;
		}
		// $this->_apiOk ( count($Amici) );
		
		$this->_apiOk ( array (
				'action' => $action 
		) );
	}
	
	/**
	 * manage friends:
	 * actions
	 * R=request
	 * A=accept
	 * D=decline
	 * X=remove
	 */
	public function api_ManageFriendshipOLD() {
		
		/*
		 * $this->_checkVars ( array ( '$idMember1', '$idMember2', '$action' ) );
		 */
		debug ( $this->api ['idMember1'] );
		$idMember1 = $this->api ['idMember1'];
		$idMember2 = $this->api ['idMember2'];
		$action = $this->api ['action'];
		/*
		 * $okMember1 = isset($this->api['$idMember1']) && !empty($this->api['$idMember1']); $okMember2 = isset($this->api['$idMember2']) && !empty($this->api['$idMember2']); if (!$okMember1 || !$okMember2 ) { }
		 */
		
		$SearchState = $action;
		if ($SearchState == 'A' || $SearchState == 'D') {
			$SearchState = 'R';
		}
		if ($SearchState == 'X') {
			$SearchState = 'A';
		}
		$Amici = $this->Friend->FriendsRelationship ( $idMember1, $idMember2, $SearchState );
		
		switch ($action) {
			case "R" :
				$Amici = $this->Friend->FriendsRelationshipGeneric ( $idMember1, $idMember2 );
				if (count ( $Amici ) == 0) {
					
					$this->Friend->set ( array (
							'member1_big' => $idMember1,
							'member2_big' => $idMember2,
							'status' => $action 
					) );
					
					// Model::$validationErrors:
					if ($this->Friend->save ()) {
						$response ['test'] = $action;
					} else {
						$response ['test'] = "not saved";
					}
					// debug($response ['test']);
				} else {
					$this->_apiEr ( __("Record esistente") );
				}
				
				break;
			case "A" :
			case "D" :
			case "X" :
				
				if (count ( $Amici ) == 1) {
					
					$Amici ['status'] = $action;
					// debug($Amici[0][0]['big']); //$Amici->field('member1_big'));
					$this->Friend->set ( 'big', $Amici [0] [0] ['big'] );
					$this->Friend->set ( 'member1_big', $Amici [0] [0] ['member1_big'] );
					$this->Friend->set ( 'member2_big', $Amici [0] [0] ['member2_big'] );
					$this->Friend->set ( 'status', $action );
					
					$this->Friend->save ();
				} else {
					$this->_apiEr ( __("Record non trovato") );
				}
				break;
			
			default :
				break;
		}
		// $this->_apiOk ( count($Amici) );
		
		$this->_apiOk ( array (
				'action' => $action 
		) );
	}
	public function api_RequestFriendship() {
		$this->admin_edit ();
		$this->render ( 'admin_edit' );
	}
    
    
    public function api_birthFriends(){
        
        $this->_checkVars ( array (
                'idMember' 
        ) );
        
        $idMember=$this->api['idMember'];
        
        $friends=$this->Friend->findAllFriendsNew($idMember,'A',true);
                
                
        $this->_apiOk($friends);
        
    }
    
}