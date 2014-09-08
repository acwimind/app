<?php
class BoardsController extends AppController {
	public $uses = array (
			'Place',
			'Operator',
			'Member',
			'Friend',
			'Advert',
			'Photo',
			'Comment',
			'Contact',
			'Checkin',
			'PrivacySetting',
			'Event' 
	); // load these models
	
	/**
	 * get board content for logged user
	 */
	public function api_GetBoardContent() {
		$this->log ( "------------you are in api_GetBoardContent--------" );
		$MyPlaces = array ();
		$MyPlaces = $this->Place->getBoardPlaces ( $this->logged ['Member'] ['big'] );
		
		/*
		 * $this->log("------------MyPlaces------------"); $this->log($MyPlaces); $this->log("------------Fine MyPlaces-------");
		 */
		
		foreach ( $MyPlaces as $key => $val ) {
			
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['region_id'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['external_id'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['external_source'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['slug'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['opening_hours'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['news'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['photo_updated'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['status'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['created'] );
			unset ( $MyPlaces [$key] ['Place'] ['Place'] ['updated'] );
		}
		
		foreach ( $MyPlaces as $key => $val ) {
			
			if (isset ( $val ['Place'] ['Place'] ['default_photo_big'] ) && $val ['Place'] ['Place'] ['default_photo_big'] > 0) { // add URLs to default photos
				
				$DefPic = $this->Photo->find ( 'first', array (
						'conditions' => array (
								'Photo.big' => $val ['Place'] ['Place'] ['default_photo_big'] 
						),
						'recursive' => - 1 
				) );
				
				$MyPlaces [$key] ['Place'] ['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place'] ['Place'] ['big'], $DefPic ['Photo'] ['gallery_big'], $DefPic ['Photo'] ['big'], $DefPic ['Photo'] ['original_ext'] );
				
				/*
				 * TODO: put again conditrions for updated if (isset ( $val ['Place']['Place']['DefaultPhoto'] ['status'] ) && $val ['Place']['Place']['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos $MyPlaces [$key] ['Place']['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place']['Place'] ['big'], $val ['Place']['Place']['Gallery'] [0] ['big'], $val['Place'] ['Place']['DefaultPhoto'] ['big'],$val['Place'] ['Place'] ['DefaultPhoto'] ['original_ext'] ); } else { $MyPlaces [$key]['Place'] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place']['Place'] ['category_id'] ); }
				 */
			} else {
				
				$MyPlaces [$key] ['Place'] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['Place'] ['category_id'] );
			}
			// check if i liked it
			$xlike = 0;
			$xlike = $this->Comment->find ( 'count', array (
					'conditions' => array (
							'Comment.member_big' => $this->logged ['Member'] ['big'],
							'Comment.likeit' => 1,
							// 'Comment.place_big' => $MyPlaces [$key] [0]['checkinbig']
							'Comment.checkin_big' => $MyPlaces [$key] [0] ['checkinbig'] 
					// 'Comment.place_big'
										) 
			) );
			
			// TODO: ARRIVATO QUI !!!! LIKE COUNT
			$MyPlaces [$key] ['CountOfComments'] = $this->Comment->getCommentsCount ( $MyPlaces [$key] [0] ['checkinbig'], 1 );
			$MyPlaces [$key] ['CountOfLikes'] = $this->Comment->getLikesCount ( $MyPlaces [$key] [0] ['checkinbig'], 1 );
			
			$MyPlaces [$key] ['ILike'] = $xlike;
		}
		
		// recovery friends order by checkins
		
		$MyFriends = array ();
		$MyFriends = $this->Friend->getBoardFriends ( $this->logged ['Member'] ['big'] );
		
		/*
		 * $this->log("------------MyFriends------------"); $this->log($MyFriends); $this->log("------------Fine MyFriends-------");
		 */
		if (is_array ( $MyFriends )) {
			
			foreach ( $MyFriends as $key => $val ) {
				
				// ADD MEMBER PHOTO
				// debug( $val ['Member']['Member'] ['photo_updated'] );
				if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] ) and $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] > 0) {
					$MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $val ['Member'] ['Member'] ['big'], $val ['Member'] ['Member'] ['photo_updated'] );
				} else {
					$sexpic = 2;
					if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['sex'] ) and $MyFriends [$key] ['Member'] ['Member'] ['sex'] == 'f') {
						$sexpic = 3;
					}
					
					$MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
				}
				
				// debug( $val ['Place']['Place'] ['big']);
				// ADD PLACE PHOTO
				$params = array (
						'conditions' => array (
								'Place.big' => $val ['Place'] ['Place'] ['big'] 
						) 
				// recursive => 0
								);
				
				$places = $this->Place->find ( 'first', $params );
				
				$places = $this->_addPlacePhotoUrls ( $places );
				
				// debug($places);
				
				$MyFriends [$key] ['Place'] ['Place'] ['photo'] = $places ['Place'] ['photo'];
				
				/*
				 * $DefPic = $this->Photo->find ( 'first', array ( 'conditions' => array ( 'Photo.big' => $val ['Place'] ['Place'] ['default_photo_big'] ), 'recursive' => - 1 ) ); // die(debug( $DefPic) ); $appho = ""; if (count ( $DefPic ) > 0) { $appho = $this->FileUrl->place_photo ( $val ['Place'] ['Place'] ['big'], $DefPic ['Photo'] ['gallery_big'], $DefPic ['Photo'] ['big'], $DefPic ['Photo'] ['original_ext'] ); } $MyFriends [$key] ['Place'] ['Place'] ['photo'] = $appho;
				 */
				
				// check if i liked it
				$xlike = 0;
				$xlike = $this->Comment->find ( 'count', array (
						'conditions' => array (
								'Comment.member_big' => $this->logged ['Member'] ['big'],
								'Comment.likeit' => 1,
								'Comment.checkin_big' => $MyFriends [$key] ['Checkinbig'] 
						) 
				) );
				
				$MyFriends [$key] ['CountOfComments'] = $this->Comment->getCommentsCount ( $MyFriends [$key] ['Checkinbig'], 0 );
				
				$MyFriends [$key] ['CountOfLikes'] = $this->Comment->getLikesCount ( $MyFriends [$key] ['Checkinbig'], 0 );
				
				$MyFriends [$key] ['ILike'] = $xlike;
			}
		}
		// recovery suggested friends order by ?
		
		$MySugFriends = array ();
		$MySugFriends = $this->BoardContacts ( $this->logged ['Member'] ['big'] );
		
		/*
		 * $this->log("------------MySugFriends------------"); $this->log($MySugFriends); $this->log("------------Fine MySugFriends-------");
		 */
		
		foreach ( $MySugFriends as $key => &$val ) {
			
			// ADD MEMBER PHOTO
			// debug( $val ['Member']['Member'] ['photo_updated'] );
			if ($MySugFriends [$key] ['Member'] ['photo_updated'] > 0) {
				$MySugFriends [$key] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $val ['Member'] ['big'], $val ['Member'] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($MySugFriends [$key] ['Member'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$MySugFriends [$key] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			}
		}
		
		// die(debug($MySugFriends));
		
		$MySugAffinity = array ();
		$MySugAffinity = $this->Member->getAffinityMembers ( $this->logged ['Member'] ['big'] );
		
		/*
		 * $this->log("------------MySugAffinity------------"); $this->log($MySugAffinity); $this->log("------------Fine MySugAffinity-------");
		 */
		
		foreach ( $MySugAffinity as $key => &$val ) {
			
			// ADD MEMBER PHOTO
			// debug($MySugAffinity [$key] [0]['sex']);
			if ($MySugAffinity [$key] [0] ['photo_updated'] > 0) {
				$MySugAffinity [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($MySugAffinity [$key] [0] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$MySugAffinity [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			}
		}
		
		// debug($MySugAffinity);
		
		// recovery members members order by checkins
		
		$MyAds = array ();
		$MyAds = $this->Advert->getBoardAds ( $this->logged ['Member'] ['big'] );
		
		foreach ( $MyAds as $key => $val ) {
			
			unset ( $MyAds [$key] ['Advert'] ['photo_ext'] );
			unset ( $MyAds [$key] ['Advert'] ['status'] );
			unset ( $MyAds [$key] ['Advert'] ['photo_updated'] );
		}
		
		// recovery ads
		
		// compose a board
		
		$MyBoard = array ();
		$MyBoardAff = array ();
		
		$nume = 0;
		
		/*
		 * $this->log("------------MyAds------------"); $this->log($MyAds); $this->log("------------Fine MyAds-------");
		 */
		
		foreach ( $MySugAffinity as $key => $val ) {
			
			if ($nume < count ( $MySugAffinity )) {
				// $MySugAffinity[$i]["BoardType"]= "AffinityMember";
				
				// RIMETTER $MyBoardAff[]['Member'] = $val[0]; //[0];
			}
		}
		
		$MyBoardAff [] = array (
				$MySugAffinity 
		);
		
		// $bms[] = array(
		// 'Bookmark' => $val['Bookmark'],
		
		for($i = 0; $i <= 5; $i ++) {
			if ($i < count ( $MyPlaces )) {
				$MyPlaces [$i] ["BoardType"] = "Place";
				$MyBoard [] = $MyPlaces [$i];
			}
			if ($i < count ( $MyFriends )) {
				$MyFriends [$i] ["BoardType"] = "Friend";
				$MyBoard [] = $MyFriends [$i];
			}
			if ($i < count ( $MyAds )) {
				$MyAds [$i] ["BoardType"] = "Ad";
				$MyBoard [] = $MyAds [$i];
			}
			
			if ($i < count ( $MySugFriends ) and $i < 3) {
				$MySugFriends [$i] ["BoardType"] = "SuggestedMember";
				$MyBoard [] = $MySugFriends [$i];
			}
			
			/*
			 * if ($i < count ( $MySugAffinity ) ) { $MySugAffinity [$i] ["BoardType"] = "AffinityMember"; $MyBoard [] = $MySugAffinity [$i]; }
			 */
		}
		
		$MyBoardAff ["BoardType"] = "AffinityMembers";
		$MyBoard [] = $MyBoardAff;
		// $MyBoardAff;
		// array(
		// 'AffinityMembers' => $MyBoardAff
		// );
		// $MyBoard ["AffinityMembers"] = $MyBoardAff[0];
		
		$this->_apiOk ( $MyBoard );
	}
	
	/**
	 * get board content for logged user
	 */
	public function api_GetDiaryContent() {
		
		// TORNARE FOTO,POSTI, AMICIZIE
		$this->_checkVars ( array (), array (
				'big' 
		) );
		
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$MyBig = $this->api ['big'];
		
		$MyCheckins = array ();
		$Checkins = array ();
		
		$allx = true;
		$MyCheckins = $this->Checkin->getNearbyCheckinsMember ( $MyBig, $allx );
		
		foreach ( $MyCheckins as $key => $val ) {
			
			if (isset ( $val [0] ['Checkin'] [0] ['Place'] ['DefaultPhoto'] ['big'] ) && $val [0] ['Checkin'] [0] ['DefaultPhoto'] ['big'] > 0) { // add URLs to default photos
				if (isset ( $val [0] ['Checkin'] [0] ['DefaultPhoto'] ['status'] ) && $val [0] ['Checkin'] [0] ['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos
					$val [0] ['Checkin'] [0] ['Place'] ['photo'] = $this->FileUrl->place_photo ( $val [0] ['Checkin'] [0] ['Place'] ['big'], $val [0] ['Checkin'] [0] ['Gallery'] [0] ['big'], $val [0] ['Checkin'] [0] ['DefaultPhoto'] ['big'], $val [0] ['Checkin'] [0] ['DefaultPhoto'] ['original_ext'] );
				} else {
					$val [0] ['Checkin'] [0] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val [0] ['Checkin'] [0] ['Place'] ['category_id'] );
				}
			} else {
				
				$val [0] ['Checkin'] [0] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val [0] ['Checkin'] [0] ['Place'] ['category_id'] );
			}
			
			unset ( $val [0] ['Checkin'] [0] ['DefaultPhoto'] );
			unset ( $val [0] ['Checkin'] [0] ['Gallery'] );
			
			$Privacyok = $this->PrivacySetting->getPrivacySettings ( $MyBig );
			
			$goonPrivacy = true;
			if (count ( $Privacyok ) > 0) {
				// PARTE PRIVACY TO DO
				if ($Privacyok [0] ['PrivacySetting'] ['showvisitedplaces'] == 0) {
					$goonPrivacy = false;
				}
			}
			if ($goonPrivacy) {
				// !! aggiungere agli amici
				$Checkins [] = $MyCheckins;
			}
		}
		
		$MyPhotos = $this->Photo->getMemberPhotos ( $MyBig );
		debug ( $MyPhotos );
		$MyFriends = array ();
		$Amici = $this->Friend->GetDiaryFriends ( $MyBig );
		
		if (is_array ( $Amici )) { // previene il warning Invalid argument supplied for foreach()
			foreach ( $Amici as $ami ) {
				// add only if privacy ok
				if ($ami ["Friend1"] ["big"] == $MyBig) {
					$friendID = $ami ["Friend2"] ["big"];
				} 

				else {
					$friendID = $ami ["Friend1"] ["big"];
				}
				$Privacyok = $this->PrivacySetting->getPrivacySettings ( $friendID );
				$goonPrivacy = true;
				if (count ( $Privacyok ) > 0) {
					// PARTE PRIVACY TO DO
					if ($Privacyok [0] ['PrivacySetting'] ['visibletousers'] == 0) {
						$goonPrivacy = false;
					}
				}
				if ($goonPrivacy) {
					// !! aggiungere agli amici
					$MyFriends [] = $ami;
				}
			}
		}
		// recovery friends order by checkins
		
		// recovery members members order by checkins
		
		$MyAds = array ();
		$MyAds = $this->Advert->getBoardAds ( $this->logged ['Member'] ['big'] );
		
		// recovery ads
		
		// compose a board
		
		$MyBoard = array ();
		
		for($i = 0; $i <= 5; $i ++) {
			if ($i < count ( $MyPhotos )) {
				$MyPhotos [$i] ["BoardType"] = "Photo";
				$MyPhotos [$i] ["Photo"] ["url"] = $this->FileUrl->event_photo ( $MyPhotos [$i] ['Event'] ['big'], $MyPhotos [$i] ["Photo"] ['gallery_big'], $MyPhotos [$i] ["Photo"] ['big'], $MyPhotos [$i] ["Photo"] ['original_ext'] );
				$MyBoard [] = $MyPhotos [$i];
			}
			if ($i < count ( $MyFriends )) {
				$MyFriends [$i] ["BoardType"] = "Friends";
				$MyBoard [] = $MyFriends [$i];
			}
			if ($i < count ( $Checkins )) {
				$Checkins [$i] ["BoardType"] = "Places";
				$MyBoard [] = $Checkins [$i];
			}
		/*	if (count ( $Checkins ) > 0 || count ( $MyPhotos ) > 0 || count ( $MyFriends ) > 0) {
				// put ads only if there is other contents!!
				if ($i < count ( $MyAds )) {
					$MyAds [$i] ["BoardType"] = "Ad";
					$MyBoard [] = $MyAds [$i];
				}
			}
		*/	
		}
		
		$this->_apiOk ( $MyBoard );
	}
	
	/**
	 * get radar content for logged user
	 */
	public function api_GetRadarContentOLD() {
		$MyPlaces = array ();
		
		$IPmember = ($this->Member->getMemberByBig ( $this->logged ['Member'] ['big'] ));
		$Imember = $IPmember ['Member'];
		$coords = $Imember ['last_lonlat'];
		debug ( $coords );
		// $MyPlaces = $this->Place->getRadardPlaces ($coords );
		
		$plar = $this->Place->getRadarPlaces ( $coords );
		
		foreach ( $plar as $key => $val ) {
			
			if (isset ( $val ['Place'] ['Place'] ['default_photo_big'] ) && $val ['Place'] ['Place'] ['default_photo_big'] > 0) { // add URLs to default photos
				
				$DefPic = $this->Photo->find ( 'first', array (
						'conditions' => array (
								'Photo.big' => $val ['Place'] ['Place'] ['default_photo_big'] 
						),
						'recursive' => - 1 
				) );
				
				$MyPlaces [$key] ['Place'] ['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place'] ['Place'] ['big'], $DefPic ['Photo'] ['gallery_big'], $DefPic ['Photo'] ['big'], $DefPic ['Photo'] ['original_ext'] );
				
				/*
				 * TODO: put again conditrions for updated if (isset ( $val ['Place']['Place']['DefaultPhoto'] ['status'] ) && $val ['Place']['Place']['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos $MyPlaces [$key] ['Place']['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place']['Place'] ['big'], $val ['Place']['Place']['Gallery'] [0] ['big'], $val['Place'] ['Place']['DefaultPhoto'] ['big'],$val['Place'] ['Place'] ['DefaultPhoto'] ['original_ext'] ); } else { $MyPlaces [$key]['Place'] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place']['Place'] ['category_id'] ); }
				 */
			} else {
				
				$MyPlaces [$key] ['Place'] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['Place'] ['category_id'] );
			}
			// check if i liked it
			$xlike = 0;
			$xlike = $this->Comment->find ( 'count', array (
					'conditions' => array (
							'Comment.member_big' => $this->logged ['Member'] ['big'],
							'Comment.likeit' => 1,
							// 'Comment.place_big' => $MyPlaces [$key] [0]['checkinbig']
							'Comment.checkin_big' => $MyPlaces [$key] [0] ['checkinbig'] 
					// 'Comment.place_big'
										) 
			) );
			
			// TODO: ARRIVATO QUI !!!! LIKE COUNT
			// $MyPlaces [$key] ['CountOfComments'] = $this->Comment->getCommentsCount ( $MyPlaces [$key] [0]['checkinbig'],1 );
			// $MyPlaces [$key] ['CountOfLikes'] = $this->Comment->getLikesCount ( $MyPlaces [$key] [0]['checkinbig'], 1 );
			
			// $MyPlaces [$key] ['ILike'] = $xlike;
		}
		
		// recovery friends order by checkins
		
		$MyFriends = array ();
		$MyFriends = $this->Member->getRadarMembers ( $this->logged ['Member'] ['big'] );
		
		foreach ( $MyFriends as $key => $val ) {
			
			// ADD MEMBER PHOTO
			// debug( $val ['Member']['Member'] ['photo_updated'] );
			if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] ) and $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] > 0) {
				$MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $val ['Member'] ['Member'] ['big'], $val ['Member'] ['Member'] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['sex'] ) and $MyFriends [$key] ['Member'] ['Member'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			
			// ADD PLACE PHOTO
			$DefPic = $this->Photo->find ( 'first', array (
					'conditions' => array (
							'Photo.big' => $val ['Place'] ['Place'] ['default_photo_big'] 
					),
					'recursive' => - 1 
			) );
			
			// die(debug( $DefPic) );
			$appho = "";
			if (count ( $DefPic ) > 0) {
				$appho = $this->FileUrl->place_photo ( $val ['Place'] ['Place'] ['big'], $DefPic ['Photo'] ['gallery_big'], $DefPic ['Photo'] ['big'], $DefPic ['Photo'] ['original_ext'] );
			}
			$MyFriends [$key] ['Place'] ['Place'] ['photo'] = $appho;
			/*
			 * // check if i liked it $xlike = 0; $xlike = $this->Comment->find ( 'count', array ( 'conditions' => array ( 'Comment.member_big' => $this->logged ['Member'] ['big'], 'Comment.likeit' => 1, 'Comment.checkin_big' => $MyFriends [$key] ['Checkinbig'] ) ) ); $MyFriends [$key] ['CountOfComments'] = $this->Comment->getCommentsCount ( $MyFriends [$key] ['Checkinbig'] ,0 ); $MyFriends [$key] ['CountOfLikes'] = $this->Comment->getLikesCount ( $MyFriends [$key] ['Checkinbig'] , 0 ); $MyFriends [$key]['ILike'] = $xlike;
			 */
		}
		
		// die(debug($MySugFriends));
		
		$MyBoard = array ();
		
		for($i = 0; $i <= 10; $i ++) {
			if ($i < count ( $MyPlaces )) {
				$MyPlaces [$i] ["BoardType"] = "Place";
				$MyBoard [] = $MyPlaces [$i];
			}
			if ($i < count ( $MyFriends )) {
				$MyFriends [$i] ["BoardType"] = "Member";
				$MyBoard [] = $MyFriends [$i];
			}
		}
		
		$this->_apiOk ( $MyBoard );
	}
	
	/**
	 * get radar content for logged user
	 */
	public function api_GetRadarContent() {
		$MyPlaces = array ();
		
		$IPmember = ($this->Member->getMemberByBig ( $this->logged ['Member'] ['big'] ));
		$Imember = $IPmember ['Member'];
		$coords = $Imember ['last_lonlat'];
		
		$MyPlaces = $this->Place->getRadarPlaces ( $coords );
		
		foreach ( $MyPlaces as $key => $val ) {
			// debug($val[0] );
			if (isset ( $val ['DefaultPhoto'] ) && $val ['DefaultPhoto'] ['big'] > 0) { // add URLs to default photos
				
				$DefPic = $this->Photo->find ( 'first', array (
						'conditions' => array (
								'Photo.big' => $val ['DefaultPhoto'] ['big'] 
						),
						'recursive' => - 1 
				) );
				
				$MyPlaces [$key] [0] ['photo'] = $this->FileUrl->place_photo ( $val [0] ['big'], $DefPic ['Photo'] ['gallery_big'], $DefPic ['Photo'] ['big'], $DefPic ['Photo'] ['original_ext'] );
				
				/*
				 * TODO: put again conditrions for updated if (isset ( $val ['Place']['Place']['DefaultPhoto'] ['status'] ) && $val ['Place']['Place']['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos $MyPlaces [$key] ['Place']['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place']['Place'] ['big'], $val ['Place']['Place']['Gallery'] [0] ['big'], $val['Place'] ['Place']['DefaultPhoto'] ['big'],$val['Place'] ['Place'] ['DefaultPhoto'] ['original_ext'] ); } else { $MyPlaces [$key]['Place'] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place']['Place'] ['category_id'] ); }
				 */
			} else {
				
				$MyPlaces [$key] [0] ['photo'] = $this->FileUrl->default_place_photo ( $val [0] ['category_id'] );
			}
			// check if i liked it
			
			// ADD CHECKINCONUT FOR PLACE
			
			$xeve = $this->Event->getDefault ( $MyPlaces [$key] [0] ['big'] );
			// $appollo=$this->Checkin->getJoinsCountFor($xeve['Event']['big']);
			// debug ($appollo);
			$MyPlaces [$key] [0] ['CheckinsCount'] = $this->Checkin->getJoinsandCheckinsCountFor ( $xeve ['Event'] ['big'] );
		}
		
		// recovery friends order by checkins
		
		$MyFriends = array ();
		$MyFriends = $this->Member->getRadarMembers ( $this->logged ['Member'] ['big'] );
		
		foreach ( $MyFriends as $key => $val ) {
			
			// ADD MEMBER PHOTO
			
			if (isset ( $MyFriends [$key] [0] ['photo_updated'] ) and $MyFriends [$key] [0] ['photo_updated'] > 0) {
				$MyFriends [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if (isset ( $MyFriends [$key] [0] ['sex'] ) and $MyFriends [$key] [0] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$MyFriends [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			}
		}
		
		// die(debug($MySugFriends));
		
		$MyBoard = array ();
		
		for($i = 0; $i <= 10; $i ++) {
			if ($i < count ( $MyPlaces )) {
				$MyPlaces [$i] [0] ["BoardType"] = "Place";
				$MyBoard [] = $MyPlaces [$i] [0];
			}
			if ($i < count ( $MyFriends )) {
				$MyFriends [$i] [0] ["BoardType"] = "Member";
				$MyBoard [] = $MyFriends [$i] [0];
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
		} else {
			$sexpic = 2;
			if ($data ['Member'] ['sex'] == 'f') {
				$sexpic = 3;
			}
			
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
		}
		$this->_apiOk ( $data );
	}
	public function api_CheckContactsprofileOLD() {
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
			// debug ( $paramsCont );
			
			$contactCount = $this->Contact->find ( 'count', $paramsCont );
			
			// debug ( $contactCount );
			
			// se non c'è lo inserisco
			
			if ($contactCount == 0) {
				
				$Contacts ['member_big'] = $ContactBIG;
				
				if (isset ( $val ['mail_address'] )) {
					$Contacts ['email'] = $val ['mail_address'];
				}
				if (isset ( $val ['phone_number'] )) {
					$Contacts ['phone'] = $val ['phone_number'];
				}
				$Contacts ['name'] = str_replace ( "'", " ", $val ['internal_name'] );
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
	public function mergeArr($a, $b) { // unisce due array del tipo [n]->[Member]->array
		$this->log ( "------------you are in mergeArr--------" );
		$data = array ();
		
		if (count ( $a ) > 0) {
			foreach ( $a as $k => $v ) {
				
				$data [] = $v;
			}
		}
		if (count ( $b ) > 0) {
			foreach ( $b as $k => $v ) {
				
				$data [] = $v;
			}
		}
		
		return $data;
	}
	public function api_CheckContactsprofile() {
		$this->log ( "------------you are in api_CheckContactsprofile--------" );
		$InputData = $this->api; // request->input ( 'json_decode', true );
		                         
		// debug($this->request);
		$membersMails = array ();
		$membersPhones = array ();
		$ContactBIG = $this->api ['member_big']; // $InputData ['member_big'];
		$PhoneContacts = array ();
		
		$numChunks = $this->api ['chunksCount'];
		for($i = 1; $i <= $numChunks; $i ++) {
			
			$PhoneContacts = $this->api ['contacts' . $i];
		}
		
		$this->log ( "------------BOARDS CONTROLLER-----------" );
		$this->log ( "------------PhoneContacts---BIG " . $ContactBIG );
		$this->log ( "------------Chunks " . $numChunks );
		$this->log ( "-----------------------------------------" );
		$this->log ( serialize ( $PhoneContacts ) );
		$this->log ( "-----------------------------------------" );
		
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
			
			$contactCount = $this->Contact->find ( 'count', $paramsCont );
			
			$this->log ( "------------BOARDS CONTROLLER-----------" );
			$this->log ( "------------contactCount---" . $contactCount );
			$this->log ( "------------Fine contactCount-----------" );
			
			// se non c'è lo inserisco
			
			if ($contactCount == 0) {
				
				$Contacts ['member_big'] = $ContactBIG;
				if (isset ( $val ['mail_address'] )) {
					$Contacts ['email'] = $val ['mail_address'];
				}
				if (isset ( $val ['phone_number'] )) {
					$Contacts ['phone'] = $val ['phone_number'];
				}
				$Contacts ['name'] = str_replace ( "'", " ", $val ['internal_name'] );
				$this->Contact->set ( $Contacts );
				
				$logSaveStatus = $this->Contact->save ();
				
				$this->log ( "------------BOARDS CONTROLLER-----------" );
				$this->log ( "------------Contacts------" . $Contacts );
				$this->log ( "------------logSaveStatus----" . $logSaveStatus );
				$this->log ( "------------Fine Contacts e logSave---------" );
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
		
		$this->log ( "------------BOARDS CONTROLLER-----------" );
		$this->log ( "------------membersMails----------------" );
		$this->log ( addslashes ( serialize ( $membersMails ) ) );
		$this->log ( "------------Fine membersMails-----------" );
		
		$this->log ( "------------BOARDS CONTROLLER-----------" );
		$this->log ( "------------membersPhones----------------" );
		$this->log ( addslashes ( serialize ( $membersPhones ) ) );
		$this->log ( "------------Fine membersPhones-----------" );
		
		// $membersMails=array('ciaccia@wimind.itqq','qwe@qweqwe.qwe','peter.krauspe@stradiware.sk','paulavesho@gmail.com','r.tomassetti@gmail.com','nome35@live.it','nome43@live.it');
		// $membersPhones=array('3338938102','123456','3339997727');
		
		/*
		 * $this->log("------------BOARDS CONTROLLER------------"); $this->log("------------Archivio email---------------"); $this->log(serialize($membersMails)); $this->log("-----------------------------------------"); $this->log("------------Archivio Phones--------------"); $this->log(serialize($membersPhones)); $this->log("-----------------------------------------");
		 */
		
		$data = $this->multipleShortQueries ( $membersMails, $membersPhones, 50 );
		
		$this->_apiOk ( $data );
	}
	public function multipleShortQueries($membersMails, $membersPhones, $maxPerQuery) {
		// verifica se nei contatti della rubrica del telefono ci sono membri haamble
		$this->log ( "------------you are in multipleShortQueries--------" );
		$maxElem = $maxPerQuery;
		$smallMembersMails = $membersMails;
		$totalDataByEmails = array ();
		$start = 0;
		$stop = 0;
		$mv = 0;
		
		$smallMembersMails = array_slice ( $membersMails, $start, $maxElem );
		
		while ( count ( $smallMembersMails ) > 0 ) {
			
			// print_r($smallMembersMails);
			$params = array (
					'conditions' => array (
							array (
									'Member.email' => $smallMembersMails 
							) 
					)
					,
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
                /*'fields' => array (
                        'Member.big',
                        'Member.name',
                        'Member.email',
                        'Member.middle_name',
                        'Member.surname',
                        //'Member.photo_updated',
                        'Member.sex',
                        'Member.phone',
                        'Member.birth_place',
                        'Member.birth_date',
                        'Member.address_street_no',
                        'Member.address_street',
                        'Member.address_province',
                        'Member.address_region',
                        'Member.address_zip',
                        'Member.address_town',
                        'Member.address_country',
                        'Member.lang',
                        'Member.last_lonlat'*/
                ) 
			);
			
			$dataByEmails = $this->Member->find ( 'all', $params );
			$totalDataByEmails = $this->mergeArr ( $dataByEmails, $totalDataByEmails );
			
			$mv += 1;
			$stop = $maxElem;
			$start = 0 + $stop * $mv;
			
			$smallMembersMails = array_slice ( $membersMails, $start, $stop );
		}
		
		$dataByEmails = $totalDataByEmails;
		
		$start = 0;
		$stop = 0;
		$mv = 0;
		$smallMembersPhones = $membersPhones;
		$totalDataByPhones = array ();
		
		$smallMembersPhones = array_slice ( $membersPhones, $start, $maxElem );
		
		while ( count ( $smallMembersPhones ) > 0 ) {
			
			$params = array (
					'conditions' => array (
							array (
									'Member.phone' => $smallMembersPhones 
							) 
					)
					,
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
			
			$dataByPhones = $this->Member->find ( 'all', $params );
			$totalDataByPhones = $this->mergeArr ( $dataByPhones, $totalDataByPhones );
			
			$mv += 1;
			$stop = $maxElem;
			$start = 0 + $stop * $mv;
			
			$smallMembersPhones = array_slice ( $membersPhones, $start, $stop );
		}
		
		$dataByPhones = $totalDataByPhones;
		
		$data = $this->mergeArr ( $dataByEmails, $dataByPhones );
		
		$this->log ( "------------multipleShortQueries (data)--" );
		$this->log ( serialize ( $data ) );
		$this->log ( "-----------------------------------------" );
		
		return $data;
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
			$sexpic = 2;
			if ($data ['Member'] ['sex'] == 'f') {
				$sexpic = 3;
			}
			
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
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
	public function api_BoardContacts() {
		$this->log ( "------------you are in api_BoardContacts--------" );
		$MySugFriends = array ();
		$MySugFriends = $this->BoardContacts ( $this->logged ['Member'] ['big'] );
		$this->_apiOk ( $MySugFriends );
	}
	public function BoardContacts($ContactBIG) {
		$this->log ( "------------you are in BoardContacts--------" );
		$membersMails = array ();
		$membersPhones = array ();
		$ContactBIG = $this->api ['member_big'];
		$PhoneContacts = array ();
		
		// array_merge
		// delete all existing contacts
		$SugContacts = $this->Contact->find ( 'all', array (
				'Contact.member_big' => $ContactBIG 
		) );
		
		/*
		 * $this->log("------------BOARDS CONTROLLER4-----------"); $this->log("------------Archivio SugContacts---------"); $this->log(serialize($SugContacts)); $this->log("-----------------------------------------");
		 */
		
		foreach ( $SugContacts as $key => $val ) {
			$Contacts = array ();
			// preparazione per ricerca
			if (isset ( $val ['Contact'] ['email'] )) {
				$membersMails [] = $val ['Contact'] ['email'];
			}
			;
			if (isset ( $val ['Contact'] ['phone'] )) {
				$membersPhones [] = $val ['Contact'] ['phone'];
			}
			;
		}
		
		// TODO: find a better way
		// fast fix for empties
		if (count ( $membersMails ) == 0)
			$membersMails [] = 'nomail';
		
		if (count ( $membersPhones ) == 0)
			$membersPhones [] = 'nophone';
			
			/*
		 * $this->log("------------BOARDS CONTROLLER2------------"); $this->log("BIG ".$ContactBIG); $this->log("------------Archivio email---------------"); $this->log(serialize($membersMails)); $this->log("-----------------------------------------"); $this->log("------------Archivio Phones--------------"); $this->log(serialize($membersPhones)); $this->log("-----------------------------------------");
		 */
			
		// query
			// $params = array (
			// 'conditions' => array (
			// "OR" => array (
			//
			// array (
			// 'Member.email' => $membersMails
			// ),
			// array (
			// 'Member.phone' => $membersPhones
			// )
			// )
			// ),
			// 'recursive' => - 1,
			//
			// 'fields' => array (
			// 'Member.big',
			// 'Member.name',
			// 'Member.middle_name',
			// 'Member.surname',
			// 'Member.photo_updated',
			// 'Member.sex',
			// 'Member.phone',
			// 'Member.birth_date',
			// 'Member.address_town',
			// 'Member.address_country'
			// )
			// );
			//
			// $data = $this->Member->find ( 'all', $params );
		
		$membersMails = array_unique ( $membersMails );
		$membersPhones = array_unique ( $membersPhones );
		
		$data = $this->multipleShortQueries ( $membersMails, $membersPhones, 50 );
		$dbo = $this->Member->getDatasource ();
		$logs = $dbo->getLog ();
		$lastLog = end ( $logs ['log'] );
		$AppoMem = array ();
		
		foreach ( $data as $key => &$mem ) {
			
			// check if any friendship exists yet
			$AlreadyFr = $this->Friend->FriendsAllRelationship ( $ContactBIG, $mem ['Member'] ['big'] );
			
			if (count ( $AlreadyFr ) == 0) {
				
				if ($mem ['Member'] ['photo_updated'] > 0) {
					$mem ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $mem ['Member'] ['big'], $mem ['Member'] ['photo_updated'] );
				} else {
					// standard image
					$sexpic = 2;
					if ($mem ['Member'] ['sex'] == 'f') {
						$sexpic = 3;
					}
					
					$mem ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
				}
				$AppoMem [] = $mem;
			}
		}
		
		/*
		 *
		 */
		$this->log ( "------------BoardsController--------------" );
		$this->log ( "------------var AppoMem--------------" );
		$this->log ( serialize ( $AppoMem ) );
		$this->log ( "-----------------------------------------" );
		return $AppoMem;
	}
}