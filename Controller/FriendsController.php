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
	public function api_GetFriends() {
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
				$xami [] = $ami ["Friend2"];
			} 

			else {
				$ami ["Friend1"] ["friendstatus"] = $ami ["Friend"] ["status"];
				$ami ["Friend1"] ["friendtype"] = "Active";
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
			
			// CHECK USER NOT DELETED
			if ($this->Member->isActive ( $xami [0] ['big'] )) {
				debug ( "trovato" . $xami [0] ['big'] );
				$xresponse [] = $xami [0];
			}
			unset ( $xami );
		}
		
		$this->_apiOk ( $xresponse );
	}
	public function _add($placeBig) {
	}
	public function admin_edit($id = 0) {
		if ($this->request->is ( 'post' ) || $this->request->is ( 'put' )) {
			
			if ($this->Region->saveAll ( $this->request->data, array (
					'validate' => 'first' 
			) )) {
				$this->Session->setFlash ( __ ( 'Region saved' ), 'flash/success' );
				return $this->redirect ( array (
						'action' => 'index' 
				) );
			} else {
				$this->Session->setFlash ( __ ( 'Error while saving region' ), 'flash/error' );
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
			$this->Session->setFlash ( __ ( 'Region deleted' ), 'flash/success' );
		} else {
			$this->Session->setFlash ( __ ( 'Unable to delete region, because it contains places. Please delete places first.' ), 'flash/error' );
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
		
		// Check if user is not on partners ignore list
		$isIgnored = $this->ChatMessage->Sender->MemberSetting->isOnIgnoreList ( $idMember1, $idMember2 );
		if ($isIgnored) {
			$this->_apiEr ( 'Cannot send chat message. User is blocked by the second party.', false, false, array (
					'error_code' => '510' 
			) );
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
								$response ['test'] = $action;
								
								// push if request
								Logger::Info ( 'before sendNotification' );
								
								$Privacyok = $this->PrivacySetting->getPrivacySettings ( $idMember2 );
								$goonPrivacy = true;
								if (count ( $Privacyok ) > 0) {
									if ($Privacyok [0] ['notifyfriendshiprequests'] == 0) {
										$goonPrivacy = false;
									}
								}
								if ($goonPrivacy) {
									$this->PushToken->sendNotification ( 'Haamble', 'Hai ricevuto una richiesta di amicizia!!', array (
											'partner_big' => $this->logged ['Member'] ['big'],
											'created' => date ( "Y-m-d H:i:s" ),
											'rel_id' => 1,
											'msg_id' => 1,
											'unread' => 1 
									), array (
											$idMember2 
									), 'Friends', 'new' );
									
									Logger::Info ( 'afetr sendNotification' );
								}
							} else {
								$response ['test'] = "not saved";
							}
						}
					}
				} else {
					$this->_apiEr ( "Record already found" );
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
					$this->_apiEr ( "Record not found" );
				}
				
				// PUSH FOR ACCEPTED FRIENDSHIP
				if ($action == 'A') {
					$Privacyok = $this->PrivacySetting->getPrivacySettings ( $idMember1 );
					$goonPrivacy = true;
					if (count ( $Privacyok ) > 0) {
						if ($Privacyok [0] ['notifyfriendshiprequests'] == 0) {
							$goonPrivacy = false;
						}
					}
					if ($goonPrivacy) {
						$this->PushToken->sendNotification ( 'Haamble', 'Una richiesta di amicizia è stata accettata!!', array (
								'partner_big' => $this->logged ['Member'] ['big'],
								'created' => date ( "Y-m-d H:i:s" ),
								'rel_id' => 1,
								'msg_id' => 1,
								'unread' => 1 
						), array (
								$idMember1 
						), 'Friends', 'new' );
						
						Logger::Info ( 'afetr sendNotification' );
					}
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
					$this->_apiEr ( "Record already found" );
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
					$this->_apiEr ( "Record not found" );
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
}