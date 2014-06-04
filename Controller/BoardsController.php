<?php
class BoardsController extends AppController {
	public $uses = array('Place', 'Operator','Member','Friend','Advert','Photo','Comment');//load these models
	
	
	
	/**
	 * get board content for logged user 
	 */
	public function api_GetBoardContent() {
	       
		$MyPlaces=array();
		$MyPlaces=$this->Place->getBoardPlaces($this->logged['Member']['big']);

		foreach ( $MyPlaces as $key => $val ) {
		
			
			
			if (isset ( $val['Place']['Place']['default_photo_big'] ) && $val['Place']['Place']['default_photo_big'] > 0) { // add URLs to default photos
				
				$DefPic=$this->Photo->find(
						'first', array(
								'conditions' => array(
										'Photo.big' => $val['Place']['Place']['default_photo_big'] 
								),
								'recursive'=>-1));
				
			$MyPlaces [$key] ['Place']['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place']['Place'] ['big'],$DefPic['Photo']['gallery_big'] , $DefPic['Photo'] ['big'],$DefPic['Photo'] ['original_ext'] );
				
		/*	TODO: put again conditrions for updated
		 * 	if (isset ( $val ['Place']['Place']['DefaultPhoto'] ['status'] ) && $val ['Place']['Place']['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos
					
					
					$MyPlaces [$key] ['Place']['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place']['Place'] ['big'], $val ['Place']['Place']['Gallery'] [0] ['big'], $val['Place'] ['Place']['DefaultPhoto'] ['big'],$val['Place'] ['Place'] ['DefaultPhoto'] ['original_ext'] );
				} else {
					$MyPlaces [$key]['Place'] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place']['Place'] ['category_id'] );
				}*/
			} else {
				
				$MyPlaces [$key]['Place'] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val['Place'] ['Place'] ['category_id'] );
			}
		
		//TODO: ARRIVATO QUI !!!! LIKE COUNT 
			$MyPlaces [$key]['CountOfComments'] = $this->Comment->getCommentsCount ($val['Checkinbig']  );
			$MyPlaces [$key] ['CountOfLikes'] = $this->Comment->getLikesCount ( $val ['Place']['Place'] ['big'],$DefPic['Photo']['gallery_big'] , $DefPic['Photo'] ['big'],$DefPic['Photo'] ['original_ext'] );
	//		die(debug($MyPlaces));
		
		}
		
		
		// recovery friends  order by checkins

		$MyFriends=array();
		$MyFriends=$this->Friend->getBoardFriends($this->logged['Member']['big']);
		foreach ( $MyFriends as $key => $val ) {
			
			// ADD MEMBER PHOTO
	//	debug( $val ['Member']['Member'] ['photo_updated'] );
			if ($MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] > 0) {
			$MyFriends [$key] ['Member'] ['Member']['profile_picture'] = $this->FileUrl->profile_picture ( $val ['Member']['Member'] ['big'], $val ['Member']['Member'] ['photo_updated'] );
			}	else {
			$sexpic=2;
			if($MyFriends [$key] ['Member'] ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
		
		$MyFriends [$key] ['Member'] ['Member']['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
				
		}
		// ADD PLACE PHOTO
		$DefPic=$this->Photo->find(
				'first', array(
						'conditions' => array(
								'Photo.big' => $val['Place']['Place']['default_photo_big']
						),
						'recursive'=>-1));
		
//		debug( $DefPic );
		
		$MyFriends [$key] ['Place']['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place']['Place'] ['big'],$DefPic['Photo']['gallery_big'] , $DefPic['Photo'] ['big'],$DefPic['Photo'] ['original_ext'] );
		
		}
	//	debug($MyFriends);
		
		//recovery members  members order by checkins
		
		$MyAds=array();
		$MyAds=$this->Advert->getBoardAds($this->logged['Member']['big']);
		
		// recovery ads
		
	
		// compose a board
		
		$MyBoard=array();
		
		
		for ($i = 0; $i <= 5; $i++) {
			if ($i<count($MyPlaces)) {
				$MyPlaces[$i]["BoardType"] = "Place";
				$MyBoard[]=$MyPlaces[$i];
			}
			if ($i<count($MyFriends)) {
				$MyFriends[$i]["BoardType"] = "Friend";
				$MyBoard[]=$MyFriends[$i];
			}
			if ($i<count($MyAds)) {
				$MyAds[$i]["BoardType"] = "Ad";
				$MyBoard[]=$MyAds[$i];
			}
			
		}
			
		
		$this->_apiOk ( $MyBoard );
	}
	
	/**
	 * update existing member profile
	 */
	public function api_edit() {
		
		// update existing member
		$member = $this->_save ( $this->logged ['Member'] ['big'] );
		
		if (! $member) {
			$this->_apiEr ( __ ( 'There was an error while saving your profile data' ), true );
		}
		
		$response = array (
				'user_msg' => 'Profile update succesfull' 
		);
		
		try {
			$this->_api_photo_upload ( $member ['big'] );
		} catch ( UploadException $e ) {
			$response ['user_msg'] .= $e->getMessage ();
		}
		
		$this->_apiOk ( $response );
	}
	
	// TODO: move this function to model, makes more sense there? or not, if we need to call component
	private function _api_photo_upload() {
		$msg = ', however there was an error uploading your profile picture';
		
		if (! isset ( $this->api ['photo'] )) {
			return false;
		}
		
		if (! isset ( $_FILES [$this->api ['photo']] )) {
			return false;
		}
		
		try {
			$uploaded = $this->_upload ( $_FILES [$this->api ['photo']], $this->Member->id, true );
		} catch ( UploadException $e ) {
			throw new UploadException ( __ ( $msg ) . ': ' . $e->getMessage () );
		}
		
		if ($uploaded) {
			$this->Member->save ( array (
					'Member' => array (
							'photo_updated' => DboSource::expression ( 'now()' ) 
					) 
			) );
		} else {
			throw new UploadException ( __ ( $msg ) );
		}
		
		return true;
	}
	
	// TODO: move this function to model, makes more sense there?
	private function _save($big = 0) {
		if ($big == 0) { // new member
			
			$required_fields = array (
					'email' => 'email',
					'password' => 'password',
					'name' => 'name',
					'surname' => 'surname' 
			);
			$optional_fields = array ();
		} else {
			
			$required_fields = array ();
			$optional_fields = array (
					'password' => 'password',
					'name' => 'name',
					'surname' => 'surname' 
			);
		}
		
		$optional_fields += array (
				'photo' => 'photo',
				'middle_name' => 'middle_name',
				'lang' => 'language',
				'birth_date' => 'birth_date',
				'sex' => 'sex',
				'phone' => 'phone',
				'birth_place' => 'birth_place',
				'address_street' => 'street',
				'address_street_no' => 'street_no',
				'address_town' => 'city',
				'address_province' => 'province',
				'address_region' => 'region',
				'address_country' => 'state',
				'address_zip' => 'zip' 
		);
		$all_fields = array_merge ( $required_fields, $optional_fields );
		
		$this->_checkVars ( $required_fields, $optional_fields );
		
		$member = array ();
		foreach ( $all_fields as $column => $field ) {
			if (isset ( $this->api [$field] )) {
				$member [$column] = trim ( $this->api [$field] );
			}
		}
		
		// TODO: check field format? check in Member model?
		
		$member ['type'] = MEMBER_MEMBER;
		$member ['status'] = ACTIVE;
		
		if ($big > 0) { // editing
			$member ['big'] = $big;
		}
		
		$this->Member->set ( $member );
		$this->Member->save ();
		
		if (! empty ( $this->Member->validationErrors )) { // we have errors while saving the data
			$this->_apiEr ( __ ( 'Please fill in all required fields' ), true, false, array (
					'fields' => $this->Member->validationErrors 
			) );
		}
		
		$member ['big'] = $this->Member->id;
		
		return $member;
	}
	
	/**
	 * view member profile
	 * TODO: at the moment this is method is still incomplete
	 */
	public function api_profile() {
		$this->_checkVars ( array (), array (
				'big' 
		) );
		
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$params = array (
				'conditions' => array (
						'Member.big' => $this->api ['big'] 
				),
				'recursive' => - 1 
		);
		
		$data = $this->Member->find ( 'first', $params );
		
		unset ( $data ['Member'] ['password'] );
		unset ( $data ['Member'] ['salt'] );
		unset ( $data ['Member'] ['created'] );
		unset ( $data ['Member'] ['updated'] );
		unset ( $data ['Member'] ['last_mobile_activity'] );
		unset ( $data ['Member'] ['last_web_activity'] );
		unset ( $data ['Member'] ['status'] );
		unset ( $data ['Member'] ['type'] );
		
		if ($data ['Member'] ['photo_updated'] > 0) {
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
		}
		else {
			$sexpic=2;
			if($data ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
		
			$data ['Member']['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
				
		}
		$this->_apiOk ( $data );
	}
	public function api_CheckContactsprofileOld() {
		$InputData = $this->api; // request->input ( 'json_decode', true );
		                         
		// debug($this->request);7
		$membersMails = array ();
		$membersPhones = array ();
		$ContactBIG = $this->api ['member_big']; // $InputData ['member_big'];
		$XCo2 = json_decode ( $this->api ['contacts'], true );
		foreach ( $XCo2 as $val ) {
			$Contacts = array ();
			// parte inserimento nel db...
			// se non esiste
			$paramsCont = array (
					'conditions' => array (
							'Contact.name' => $val ['internal_name'] 
					// 'Contact.phone' => $val ['phone_number'],
					// 'Contact.email' => $val ['mail_address']
										) 
			);
			if (isset ( $val ['phone_number'] )) {
				$paramsCont ["conditions"] [] = array (
						'Contact.phone' => $val ['phone_number'] 
				);
			}
			;
			if (isset ( $val ['mail_address'] )) {
				$paramsCont ["conditions"] [] = array (
						'Contact.email' => $val ['mail_address'] 
				);
			}
			;
			debug ( $paramsCont );
			
			$contactCount = $this->Contact->find ( 'count', $paramsCont );
			
			debug ( $contactCount );
			
			// se non c'è lo inserisco
			
			if ($contactCount == 0) {
				
				$Contacts ['member_big'] = $ContactBIG;
				if (isset ( $val ['mail_address'] )) {
					$Contacts ['email'] = $val ['mail_address'];
				}
				if (isset ( $val ['phone_number'] )) {
					$Contacts ['phone'] = $val ['phone_number'];
				}
				$Contacts ['name'] = $val ['internal_name'];
				$this->Contact->set ( $Contacts );
				$this->Contact->save ();
			}
			;
			unset ( $Contacts );
			unset ( $this->Contact->id );
			
			// preparazione per ricerca
			if (isset ( $val ['mail_address'] )) {
				$membersMails [] = $val ['mail_address'];
			}
			;
			if (isset ( $val ['phone_number'] )) {
				$membersPhones [] = $val ['phone_number'];
			}
			;
		}
		
		// query
		$params = array (
				'conditions' => array (
						"OR" => array (
								
								array (
										'Member.email' => $membersMails 
								),
								array (
										'Member.phone' => $membersPhones 
								) 
						) 
				),
				'recursive' => - 1,
				
				'fields' => array (
						'Member.big',
						'Member.name',
						'Member.middle_name',
						'Member.surname',
						'Member.photo_updated',
						'Member.sex',
						'Member.phone',
						'Member.birth_date',
						'Member.address_town',
						'Member.address_country' 
				) 
		);
		
		$data = $this->Member->find ( 'all', $params );
		
		// debug ( $data ); // ['contact']['internal_name']);
		// $test=$this->viewVars[0]; //array(("pippo"),("aaa"));
		$this->_apiOk ( $data );
		
		// $this->_apiOk($data['member_big']);
		/*
		 * $this->_checkVars(array(), array('big')); if (!isset($this->api['big'])) { $this->api['big'] = $this->logged['Member']['big']; } $params = array( 'conditions' => array( 'Member.big' => $this->api['big'] ), 'recursive' => -1 ); $data = $this->Member->find('first', $params); unset($data['Member']['password']); unset($data['Member']['salt']); unset($data['Member']['created']); unset($data['Member']['updated']); unset($data['Member']['last_mobile_activity']); unset($data['Member']['last_web_activity']); unset($data['Member']['status']); unset($data['Member']['type']); if ($data['Member']['photo_updated'] > 0) { $data['Member']['profile_picture'] = $this->FileUrl->profile_picture($data['Member']['big'], $data['Member']['photo_updated']); } $this->_apiOk($data);
		 */
	}
	public function api_CheckContactsprofile() {
		$InputData = $this->api; // request->input ( 'json_decode', true );
		                         
		// debug($this->request);7
		$membersMails = array ();
		$membersPhones = array ();
		$ContactBIG = $this->api ['member_big']; // $InputData ['member_big'];
		$PhoneContacts = array ();
		
		$numChunks = $this->api ['chunksCount'];
		for($i = 1; $i <= $numChunks; $i ++) {
			
			$PhoneContacts = $this->api ['contacts' . $i];
		}
		
		// $XCo2 = json_decode($this->api ['contacts'],true);
		foreach ( $PhoneContacts as $val ) {
			$Contacts = array ();
			// parte inserimento nel db...
			// se non esiste
			$paramsCont = array (
					'conditions' => array (
							'Contact.name' => $val ['internal_name'] 
					// 'Contact.phone' => $val ['phone_number'],
					// 'Contact.email' => $val ['mail_address']
										) 
			);
			if (isset ( $val ['phone_number'] )) {
				$paramsCont ["conditions"] [] = array (
						'Contact.phone' => $val ['phone_number'] 
				);
			}
			;
			if (isset ( $val ['mail_address'] )) {
				$paramsCont ["conditions"] [] = array (
						'Contact.email' => $val ['mail_address'] 
				);
			}
			;
			debug ( $paramsCont );
			
			$contactCount = $this->Contact->find ( 'count', $paramsCont );
			
			debug ( $contactCount );
			
			// se non c'è lo inserisco
			
			if ($contactCount == 0) {
				
				$Contacts ['member_big'] = $ContactBIG;
				if (isset ( $val ['mail_address'] )) {
					$Contacts ['email'] = $val ['mail_address'];
				}
				if (isset ( $val ['phone_number'] )) {
					$Contacts ['phone'] = $val ['phone_number'];
				}
				$Contacts ['name'] = $val ['internal_name'];
				$this->Contact->set ( $Contacts );
				$this->Contact->save ();
			}
			;
			unset ( $Contacts );
			unset ( $this->Contact->id );
			
			// preparazione per ricerca
			if (isset ( $val ['mail_address'] )) {
				$membersMails [] = $val ['mail_address'];
			}
			;
			if (isset ( $val ['phone_number'] )) {
				$membersPhones [] = $val ['phone_number'];
			}
			;
		}
		
		// query
		$params = array (
				'conditions' => array (
						"OR" => array (
								
								array (
										'Member.email' => $membersMails 
								),
								array (
										'Member.phone' => $membersPhones 
								) 
						) 
				),
				'recursive' => - 1,
				'fields' => array (
						'Member.big',
						'Member.name',
						'Member.middle_name',
						'Member.surname',
						'Member.photo_updated',
						'Member.sex',
						'Member.phone',
						'Member.birth_date',
						'Member.address_town',
						'Member.address_country' 
				) 
		);
		
		$data = $this->Member->find ( 'all', $params );
		
		// debug ( $data ); // ['contact']['internal_name']);
		// $test=$this->viewVars[0]; //array(("pippo"),("aaa"));
		$this->_apiOk ( $data );
		
		// $this->_apiOk($data['member_big']);
		/*
		 * $this->_checkVars(array(), array('big')); if (!isset($this->api['big'])) { $this->api['big'] = $this->logged['Member']['big']; } $params = array( 'conditions' => array( 'Member.big' => $this->api['big'] ), 'recursive' => -1 ); $data = $this->Member->find('first', $params); unset($data['Member']['password']); unset($data['Member']['salt']); unset($data['Member']['created']); unset($data['Member']['updated']); unset($data['Member']['last_mobile_activity']); unset($data['Member']['last_web_activity']); unset($data['Member']['status']); unset($data['Member']['type']); if ($data['Member']['photo_updated'] > 0) { $data['Member']['profile_picture'] = $this->FileUrl->profile_picture($data['Member']['big'], $data['Member']['photo_updated']); } $this->_apiOk($data);
		 */
	}
	
	/**
	 * View public member profile
	 */
	public function api_public() {
		$this->_checkVars ( array (
				'user_big' 
		) );
		
		$memBig = $this->api ['user_big'];
		
		// Get member data
		unbindAllBut ( $this->Member );
		$params = array (
				'conditions' => array (
						'Member.big' => $this->api ['user_big'] 
				),
				'fields' => array (
						'Member.big',
						'Member.name',
						'Member.middle_name',
						'Member.surname',
						'Member.photo_updated',
						'Member.sex',
						'Member.birth_date',
						'Member.address_town',
						'Member.address_country' 
				) 
		);
		
		$data = $this->Member->find ( 'first', $params );
		
		// Get checkin or join
		$checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
		if (! empty ( $checkin ) && $checkin ['Event'] ['type'] == 2 && $checkin ['Event'] ['status'] == 0) {
			$params = array (
					'conditions' => array (
							'Place.big' => $checkin ['Event'] ['place_big'] 
					),
					'fields' => array (
							'Place.name',
							'Place.default_photo_big',
							'Place.category_id' 
					),
					'recursive' => - 1 
			);
			$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
			$data ['Member'] ['place_big'] = $checkin ['Event'] ['place_big'];
			$data ['Member'] ['event_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
			$data ['Member'] ['place_category_id'] = $place ['Place'] ['category_id'];
		} elseif (! empty ( $checkin )) {
			$params = array (
					'conditions' => array (
							'Place.big' => $checkin ['Event'] ['place_big'] 
					),
					'fields' => array (
							'Place.name',
							'Place.default_photo_big',
							'Place.category_id' 
					),
					'recursive' => - 1 
			);
			$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
			
			$data ['Member'] ['event_big'] = $checkin ['Event'] ['big'];
			$data ['Member'] ['event_name'] = $checkin ['Event'] ['name'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
			$data ['Member'] ['place_category_id'] = $place ['Place'] ['category_id'];
		}
		
		// Get checkins count
		$checkinsCount = $this->Member->Checkin->getCheckinsCountForMember ( $memBig );
		$data ['Member'] ['checkins_count'] = intval ( $checkinsCount );
		
		// Photos processing
		if (isset ( $data ['Member'] ['photo_updated'] ) && $data ['Member'] ['photo_updated'] > 0) {
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
		} else {
			$sexpic=2;
			if($data ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$data ['Member']['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			
		}
		unset ( $data ['Member'] ['photo_updated'] );
		
		// Get uploaded photos
		$params = array (
				'conditions' => array (
						'Photo.member_big' => $memBig 
				),
				'fields' => array (
						'Photo.big',
						'Photo.original_ext',
						'Gallery.*' 
				),
				'joins' => array (
						array (
								'table' => 'galleries',
								'alias' => 'Gallery',
								'type' => 'LEFT',
								'conditions' => array (
										'Photo.gallery_big = Gallery.big' 
								) 
						) 
				),
				'recursive' => - 1 
		);
		
		$photos = $this->Member->Photo->find ( 'all', $params );
		$photosCount = $this->Member->Photo->find ( 'count', $params );
		
		$photos = $this->_addMemberPhotoUrls ( $photos );
		$data ['Uploaded'] = $photos;
		$data ['Member'] ['photos_count'] = $photosCount;
		
		$this->Util->transform_name ( $data );
		$this->_apiOk ( $data );
	}
	public function public_profile() {
		$this->_sidebarPlaces (); // places for right sidebar
		                          // debug($this->request);
		$memBig = isset ( $this->request ['pass'] [0] ) ? $this->request ['pass'] [0] : false;
		$showEvents = isset ( $this->request ['pass'] [1] ) && $this->request ['pass'] [1] == 'events' ? TRUE : FALSE;
		
		if (empty ( $memBig )) {
			$memBig = $this->logged ['Member'] ['big'];
		}
		
		$this->set ( 'memBig', $memBig );
		
		// Get member data
		unbindAllBut ( $this->Member );
		$params = array (
				'conditions' => array (
						'Member.big' => $memBig 
				),
				'fields' => array (
						'Member.big',
						'Member.name',
						'Member.middle_name',
						'Member.surname',
						'Member.photo_updated' 
				) 
		);
		
		$member = $this->Member->find ( 'first', $params );
		$this->set ( 'member', $member );
		
		if (! $member) {
			$this->Session->setFlash ( __ ( 'The user does not exist' ), 'flash/error' );
			return $this->redirect ( '/' );
		}
		
		// Get checkin or join
		$checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
		$this->set ( 'checkin', $checkin );
		
		// Get place details
		$params = array (
				'conditions' => array (
						'Place.big' => $checkin ['Event'] ['place_big'] 
				),
				'fields' => array (
						'Place.big',
						'Place.name',
						'Place.slug' 
				),
				'recursive' => - 1 
		);
		$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
		$this->set ( 'place', $place );
		
		// debug($checkin);
		if (! empty ( $checkin ) && $checkin ['Event'] ['type'] == 2 && $checkin ['Event'] ['status'] == 0) {
			$data ['Member'] ['place_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['place_slug'] = $place ['Place'] ['slug'];
			$data ['Member'] ['place_big'] = $place ['Place'] ['big'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
		} elseif (! empty ( $checkin )) {
			$data ['Member'] ['place_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['place_slug'] = $place ['Place'] ['slug'];
			$data ['Member'] ['place_big'] = $place ['Place'] ['big'];
			$data ['Member'] ['event_name'] = $checkin ['Event'] ['name'];
			$data ['Member'] ['event_slug'] = $checkin ['Event'] ['slug'];
			$data ['Member'] ['event_big'] = $checkin ['Event'] ['big'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
		}
		
		$this->set ( 'is_ignored', $this->Member->MemberSetting->isOnIgnoreList ( $this->logged ['Member'] ['big'], $member ['Member'] ['big'] ) );
	}
	public function my_profile() {
		$this->logged = $this->Member->findByBig ( $this->Auth->user ( 'big' ) ); // don't understand why it's not already filled
		$this->_sidebarPlaces (); // places for right sidebar
		                          // debug($this->request);
		$memBig = $this->logged ['Member'] ['big'];
		$showEvents = isset ( $this->request ['pass'] [1] ) && $this->request ['pass'] [1] == 'events' ? TRUE : FALSE;
		
		$this->set ( 'memBig', $memBig );
		
		// Get member data
		$member = $this->logged;
		$this->set ( 'member', $member );
		
		if (! $member) {
			$this->Session->setFlash ( __ ( 'The user does not exist' ), 'flash/error' );
			return $this->redirect ( '/' );
		}
		
		// Get checkin or join
		$checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
		$this->set ( 'checkin', $checkin );
		
		// Get place details
		$params = array (
				'conditions' => array (
						'Place.big' => $checkin ['Event'] ['place_big'] 
				),
				'fields' => array (
						'Place.big',
						'Place.name',
						'Place.slug' 
				),
				'recursive' => - 1 
		);
		$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
		$this->set ( 'place', $place );
		
		// debug($checkin);
		if (! empty ( $checkin ) && $checkin ['Event'] ['type'] == 2 && $checkin ['Event'] ['status'] == 0) {
			$data ['Member'] ['place_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['place_slug'] = $place ['Place'] ['slug'];
			$data ['Member'] ['place_big'] = $place ['Place'] ['big'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
		} elseif (! empty ( $checkin )) {
			$data ['Member'] ['place_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['place_slug'] = $place ['Place'] ['slug'];
			$data ['Member'] ['place_big'] = $place ['Place'] ['big'];
			$data ['Member'] ['event_name'] = $checkin ['Event'] ['name'];
			$data ['Member'] ['event_slug'] = $checkin ['Event'] ['slug'];
			$data ['Member'] ['event_big'] = $checkin ['Event'] ['big'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
		}
	}
	public function events($memBig) {
		$events = $this->Member->Checkin->Event->getAttendedEventsForMember ( $memBig );
		$this->set ( 'events', $events );
	}
	public function places($memBig) {
		$places = $this->Member->Checkin->Event->getAttendedEventsForMember ( $memBig );
		$this->set ( 'places', $places );
	}
	public function gallery($memBig = 0) {
		$photos = $this->Member->Photo->getMemberPhotos ( $memBig );
		$this->set ( 'photos', $photos );
		$this->set ( 'memberBig', $memBig );
		
		$this->set ( 'loggedBig', $this->logged ['Member'] ['big'] );
	}
}