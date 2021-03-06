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
			'Event',
            'MemberSetting' 
	); // load these models
	
	/**
	 * get board content for logged user
	 */
	public function api_GetBoardContent2() {
		

        //$this->log ( "------------you are in api_GetBoardContent--------" );
        $this->_checkVars(array(),array('offset'));
        
        $offset = isset($this->api['offset']) ? $this->api['offset'] : 0;
        
        $MyPlaces = array ();
        $MyPlaces = $this->Place->getBoardPlaces ( $this->logged ['Member'] ['big'],$offset );
        
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
        $MyFriends = $this->Friend->getBoardFriends ( $this->logged ['Member'] ['big'], $offset );
        
        //$this->log("Myfriends ".serialize($MyFriends));
        /*
         * $this->log("------------MyFriends------------"); $this->log($MyFriends); $this->log("------------Fine MyFriends-------");
         */
        if (is_array ( $MyFriends )) {
            
            foreach ( $MyFriends as $key => $val ) {
                
                // ADD MEMBER PHOTO
                // debug( $val ['Member']['Member'] ['photo_updated'] );
                if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] ) and $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] > 0 AND $MyFriends [$key]['Member']['PrivacySetting']['photosvisibility'] > 0) {
                    $MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $val ['Member'] ['Member'] ['big'], $val ['Member'] ['Member'] ['photo_updated'] );
                } else {
                    $sexpic = 2;
                    if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['sex'] ) and $MyFriends [$key] ['Member'] ['Member'] ['sex'] == 'f') {
                        $sexpic = 3;
                    }
                    
                    $MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
                }
                //print_r($MyFriends);
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
        $MySugFriends = $this->BoardContacts ( $this->logged ['Member'] ['big']);
        //print_r($MySugFriends);
        /*
         * $this->log("------------MySugFriends------------"); $this->log($MySugFriends); $this->log("------------Fine MySugFriends-------");
         */
        
        foreach ( $MySugFriends as $key => &$val ) {
            
            // ADD MEMBER PHOTO
            // debug( $val ['Member']['Member'] ['photo_updated'] );
            $privacySetting=$this->PrivacySetting->getPrivacySetting($MySugFriends[$key]['Member']['big']);
            $photosVisibility=$privacySetting['photosvisibility'];
            
            if ($MySugFriends [$key] ['Member'] ['photo_updated'] > 0 AND $photosVisibility > 0) {
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
        $MySugAffinity = $this->Member->getAffinityMembers ( $this->logged ['Member'] ['big'],$offset );

            foreach ( $MySugAffinity as $key2 => &$val2 ) {
        /*   */ 
        $privacySetting=$this->PrivacySetting->getPrivacySetting($MySugAffinity[$key2]['Member']['big']);
            $photosVisibility=$privacySetting['photosvisibility'];
            // ADD MEMBER PHOTO
            // debug($MySugAffinity [$key] [0]['sex']);
            if ($MySugAffinity [$key2] [0] ['photo_updated'] > 0 AND $photosVisibility > 0) {
                $MySugAffinity [$key2] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
            } else {
                $sexpic = 2;
                if ($MySugAffinity [$key2] [0] ['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $MySugAffinity [$key2] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
            
            
            $MySugAffinity [$key2] [0] ['surname']=substr($MySugAffinity [$key2] [0] ['surname'],0,1).'.';
        
        }
        
        // debug($MySugAffinity);
        
        // recovery members members order by checkins
        
        $MyAds = array ();
        $MyAds = $this->Advert->getBoardAds ( $this->logged ['Member'] ['big'] );
        //print_r($MyAds);
        
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
        
        
        foreach ( $MySugAffinity as $key => $val ) {
            
            if ($nume < count ( $MySugAffinity )) {
                // $MySugAffinity[$i]["BoardType"]= "AffinityMember";
                
                // RIMETTER $MyBoardAff[]['Member'] = $val[0]; //[0];
            }
        }
        
        $MyBoardAff [] = array (
                $MySugAffinity 
        );
         */
        // $bms[] = array(
        // 'Bookmark' => $val['Bookmark'],
        
        for($i = 0; $i <= 25; $i ++) {
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
            
            if ($i < count ( $MySugFriends ) and $i < 10) {
                $MySugFriends [$i] ["BoardType"] = "SuggestedMember";
                $MyBoard [] = $MySugFriends [$i];
            }
            
            /*
             * if ($i < count ( $MySugAffinity ) ) { $MySugAffinity [$i] ["BoardType"] = "AffinityMember"; $MyBoard [] = $MySugAffinity [$i]; }
             */
        }
        
        $MyBoardAff= $MySugAffinity;
        //$MyBoardAff 
        $MySugAffinity["BoardType"] = "AffinityMembers";
        
        // TOLTO PER CRASH    
        $MyBoard [] = $MySugAffinity;
        
        
        // $MyBoardAff;
        // array(
        // 'AffinityMembers' => $MyBoardAff
        // );
        // $MyBoard ["AffinityMembers"] = $MyBoardAff[0];
        
        $this->_apiOk ( $MyBoard );
    }

	function getmicrotime(){
        list($usec, $sec) = explode(" ",microtime());
        return ((float)$usec + (float)$sec);
        }
        
        
    public function api_GetBoardContent() {
        $time_start = $this->getmicrotime();//sec iniziali
        //$this->log ( "------------you are in api_GetBoardContent--------" );
    //	debug(strcmp('2', IOS_APP_VERSION)>=0);
    //	debug(strcmp('1.0', ANDROID_APP_VERSION)>=0);
        $this->_checkVars(array(),array('offset'));
        
        $offset = isset($this->api['offset']) ? $this->api['offset'] : 0;
        
        $MyPlaces = array ();
        $time_start_1 = $this->getmicrotime();//sec iniziali
        $MyPlaces = $this->Place->getBoardPlaces ( $this->logged ['Member'] ['big'],$offset );
        
        $utenteTime=$this->logged ['Member'] ['big'];
                
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
            $MyPlaces [$key] ['CountOfComments'] = $this->Comment->getCommentsCount ( $MyPlaces [$key] [0] ['checkinbig'], 0);
            $MyPlaces [$key] ['CountOfLikes'] = $this->Comment->getLikesCount ( $MyPlaces [$key] [0] ['checkinbig'], 0 );
            
            $MyPlaces [$key] ['ILike'] = $xlike;
        }
          $time_end_1 = $this->getmicrotime();//sec finali
          $time_1 = $time_end_1 - $time_start_1;//differenza in secondi
          $this->log("TIME (getBoardPlaces -> $utenteTime) $time_1 s ");  
        // recovery friends order by checkins
        
        $MyFriends = array ();
        $time_start_2 = $this->getmicrotime();//sec iniziali
        $MyFriends = $this->Friend->getBoardFriends ( $this->logged ['Member'] ['big'], $offset );
        
        //$this->log("Myfriends ".serialize($MyFriends));
        /*
         * $this->log("------------MyFriends------------"); $this->log($MyFriends); $this->log("------------Fine MyFriends-------");
         */
        if (is_array ( $MyFriends )) {
            
            foreach ( $MyFriends as $key => $val ) {
                
            	unset ( $MyFriends [$key] ['Member'] ['MemberSetting']  );
            	unset ( $MyFriends [$key] ['Member'] ['Checkin']);
            	unset ( $MyFriends [$key] ['Member'] ['Photo']);
            	unset ( $MyFriends [$key] ['Member'] ['Signalation']);
            	unset ( $MyFriends [$key] ['Member']['Member'] ['password']  );
                // ADD MEMBER PHOTO
                // debug( $val ['Member']['Member'] ['photo_updated'] );
                if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] ) and $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] > 0 AND $MyFriends [$key]['Member']['PrivacySetting']['photosvisibility'] > 0) {
                    $MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $val ['Member'] ['Member'] ['big'], $val ['Member'] ['Member'] ['photo_updated'] );
                } else {
                    $sexpic = 2;
                    if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['sex'] ) and $MyFriends [$key] ['Member'] ['Member'] ['sex'] == 'f') {
                        $sexpic = 3;
                    }
                    
                    $MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
                }
                //print_r($MyFriends);
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
          $time_end_2 = $this->getmicrotime();//sec finali
          $time_2 = $time_end_2 - $time_start_2;//differenza in secondi
          $this->log("TIME (getBoardFriends -> $utenteTime) $time_2 s ");
        // recovery suggested friends order by ?
        
        $MySugFriends = array ();
        $time_start_3 = $this->getmicrotime();//sec iniziali
        $MySugFriends = $this->BoardContacts ( $this->logged ['Member'] ['big']);
        //print_r($MySugFriends);
        /*
         * $this->log("------------MySugFriends------------"); $this->log($MySugFriends); $this->log("------------Fine MySugFriends-------");
         */
        foreach ( $MySugFriends as $key => $vals ) {
            
            // ADD MEMBER PHOTO
            // debug( $val ['Member']['Member'] ['photo_updated'] );
//            $privacySetting2=$this->PrivacySetting->getPrivacySetting();
 //           $photosVisibility=$privacySetting2['photosvisibility'];
            
            $privacySettings = $this->PrivacySetting->getPrivacySettings ( $vals['Member']['big'] );
            $privacySettings = $privacySettings[0]['PrivacySetting'];
            
            $photosVisibility=$privacySettings['photosvisibility'];
             //2[0]['PrivacySetting']);
            
            if ($MySugFriends [$key] ['Member'] ['photo_updated'] > 0 AND $photosVisibility > 0) {
                $MySugFriends [$key] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $vals ['Member'] ['big'], $vals ['Member'] ['photo_updated'] );
            } else {
                $sexpic = 2;
                if ($MySugFriends [$key] ['Member'] ['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $MySugFriends [$key] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
        }
          $time_end_3 = $this->getmicrotime();//sec finali
          $time_3 = $time_end_3 - $time_start_3;//differenza in secondi
          $this->log("TIME (BoardContacts -> $utenteTime) $time_3 s ");
        // die(debug($MySugFriends));
        
        $MySugAffinity = array ();
        $time_start_4 = $this->getmicrotime();//sec iniziali
        $MySugAffinity = $this->Member->getAffinityMembersNew( $this->logged ['Member'] ['big'],$offset );
        
        foreach($MySugAffinity as $key=>$val){
            
            
            $MySugAffinity2[]=$MySugAffinity[$key][0];
            
            
        }
        
        //print_r($MySugAffinity2);
        
            foreach ( $MySugAffinity2 as $key2 => $val2 ) {
         
             // removed , picture is always visible....
             //$privacySetting=$this->PrivacySetting->getPrivacySetting($MySugAffinity2[$key2]['Member']['big']);
            //$photosVisibility=$privacySetting['photosvisibility'];
            // ADD MEMBER PHOTO
          //   debug($val2['sex'] );
            if ($val2  ['photo_updated'] > 0 /*AND $photosVisibility > 0*/) {
                $MySugAffinity2 [$key2]  ['profile_picture'] = $this->FileUrl->profile_picture ( $val2['big'], $val2['photo_updated'] );
            } else {
                $sexpic = 2;
                if ($val2 ['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $MySugAffinity2 [$key2] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
            
            
            $MySugAffinity2 [$key2] ['surname']=substr($MySugAffinity2 [$key2]['surname'],0,1).'.';
//        debug( $MySugAffinity2 [$key2]['surname']);
            $MySAff[0][]=$MySugAffinity2[$key2];
        }
        
        // debug($MySugAffinity);
        
        // recovery members members order by checkins
          $time_end_4 = $this->getmicrotime();//sec finali
          $time_4 = $time_end_4 - $time_start_4;//differenza in secondi
          $this->log("TIME (getAffinityMembers -> $utenteTime) $time_4 s ");
        $MyAds = array ();
        $MyAds = $this->Advert->getBoardAds ( $this->logged ['Member'] ['big'] );
        //print_r($MyAds);
        
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
        
        
        foreach ( $MySugAffinity as $key => $val ) {
            
            if ($nume < count ( $MySugAffinity )) {
                // $MySugAffinity[$i]["BoardType"]= "AffinityMember";
                
                // RIMETTER $MyBoardAff[]['Member'] = $val[0]; //[0];
            }
        }
        
        $MyBoardAff [] = array (
                $MySugAffinity 
        );
         */
        // $bms[] = array(
        // 'Bookmark' => $val['Bookmark'],
        if ( count($MyPlaces)>0 || count($MyFriends)>0 )
        {
        for($i = 0; $i <= 25; $i ++) {
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
            
            if ($i < count ( $MySugFriends ) and $i < 10) {
                $MySugFriends [$i] ["BoardType"] = "SuggestedMember";
                $MyBoard [] = $MySugFriends [$i];
            }
            
            /*
             * if ($i < count ( $MySugAffinity ) ) { $MySugAffinity [$i] ["BoardType"] = "AffinityMember"; $MyBoard [] = $MySugAffinity [$i]; }
             */
        }
        
        $MyBoardAff= $MySugAffinity;
        //$MyBoardAff 
        $MySugAffinity["BoardType"] = "AffinityMembers";
        
        $MySAff["BoardType"]="AffinityMembers";
        
        //print_r($MySAff);
        //da togliere per crash
        //$MyBoard [] = $MySugAffinity;
        
        $MyBoard[]=$MySAff;
        
        
        } // almeno un elemento o risposta vuota
        
        //print_r($MyBoard);       
        
        // $MyBoardAff;
        // array(
        // 'AffinityMembers' => $MyBoardAff
        // );
        // $MyBoard ["AffinityMembers"] = $MyBoardAff[0];
        
        $this->_apiOk ( $MyBoard );
        $time_end = $this->getmicrotime();//sec finali
        $time = $time_end - $time_start;//differenza in secondi
        $this->log("TIME (TOTgetBoardContent -> $utenteTime) $time s");  

    }
    
    
    
     public function api_GetBoardContentNew() {
        $time_start = $this->getmicrotime();//sec iniziali
        //$this->log ( "------------you are in api_GetBoardContent--------" );
    //    debug(strcmp('2', IOS_APP_VERSION)>=0);
    //    debug(strcmp('1.0', ANDROID_APP_VERSION)>=0);
        $this->_checkVars(array(),array('offset'));
        
        $offset = isset($this->api['offset']) ? $this->api['offset'] : 0;
        
        $MyPlaces = array ();
        $time_start_1 = $this->getmicrotime();//sec iniziali
        $MyPlaces = $this->Place->getBoardPlaces ( $this->logged ['Member'] ['big'],$offset );
        
        $utenteTime=$this->logged ['Member'] ['big'];
                
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
            $MyPlaces [$key] ['CountOfComments'] = $this->Comment->getCommentsCount ( $MyPlaces [$key] [0] ['checkinbig'], 0);
            $MyPlaces [$key] ['CountOfLikes'] = $this->Comment->getLikesCount ( $MyPlaces [$key] [0] ['checkinbig'], 0 );
            
            $MyPlaces [$key] ['ILike'] = $xlike;
        }
          $time_end_1 = $this->getmicrotime();//sec finali
          $time_1 = $time_end_1 - $time_start_1;//differenza in secondi
          $this->log("TIME (GetBoardContentNew -> getBoardPlaces -> $utenteTime) $time_1 s ");  
        // recovery friends order by checkins
        
        $MyFriends = array ();
        $time_start_2 = $this->getmicrotime();//sec iniziali
        $MyFriends = $this->Friend->getBoardFriendsNew ( $this->logged ['Member'] ['big'], $offset );
        
        //$this->log("Myfriends ".serialize($MyFriends));
        /*
         * $this->log("------------MyFriends------------"); $this->log($MyFriends); $this->log("------------Fine MyFriends-------");
         */
        if (is_array ( $MyFriends )) {
            
            foreach ( $MyFriends as $key => $val ) {
                
                unset ( $MyFriends [$key] ['Member'] ['MemberSetting']  );
                unset ( $MyFriends [$key] ['Member'] ['Checkin']);
                unset ( $MyFriends [$key] ['Member'] ['Photo']);
                unset ( $MyFriends [$key] ['Member'] ['Signalation']);
                unset ( $MyFriends [$key] ['Member']['Member'] ['password']  );
                // ADD MEMBER PHOTO
                // debug( $val ['Member']['Member'] ['photo_updated'] );
                if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] ) and $MyFriends [$key] ['Member'] ['Member'] ['photo_updated'] > 0 AND $MyFriends [$key]['Member']['PrivacySetting']['photosvisibility'] > 0) {
                    $MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $val ['Member'] ['Member'] ['big'], $val ['Member'] ['Member'] ['photo_updated'] );
                } else {
                    $sexpic = 2;
                    if (isset ( $MyFriends [$key] ['Member'] ['Member'] ['sex'] ) and $MyFriends [$key] ['Member'] ['Member'] ['sex'] == 'f') {
                        $sexpic = 3;
                    }
                    
                    $MyFriends [$key] ['Member'] ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
                }
                //print_r($MyFriends);
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
          $time_end_2 = $this->getmicrotime();//sec finali
          $time_2 = $time_end_2 - $time_start_2;//differenza in secondi
          $this->log("TIME (GetBoardContentNew -> getBoardFriends -> $utenteTime) $time_2 s ");
        // recovery suggested friends order by ?
        
        $MySugFriends = array ();
        $time_start_3 = $this->getmicrotime();//sec iniziali
        $MySugFriends = $this->BoardContactsNew ( $this->logged ['Member'] ['big']);
        //print_r($MySugFriends);
        /*
         * $this->log("------------MySugFriends------------"); $this->log($MySugFriends); $this->log("------------Fine MySugFriends-------");
         */
        foreach ( $MySugFriends as $key => $val ) {
            
            // ADD MEMBER PHOTO
            // debug( $val ['Member']['Member'] ['photo_updated'] );
            // $privacySetting2=$this->PrivacySetting->getPrivacySetting();
            // $photosVisibility=$privacySetting2['photosvisibility'];
            
            //**$privacySettings = $this->PrivacySetting->getPrivacySettings ( $val['big'] );
            //**$privacySettings = $privacySettings[0]['PrivacySetting'];
            
            //**$photosVisibility=$privacySettings['photosvisibility'];
             //2[0]['PrivacySetting']);
            
            /* ***
            
            if ($MySugFriends[$key]['photo_updated'] > 0 AND $photosVisibility > 0) {
                $MySugFriends[$key]['profile_picture'] = $this->FileUrl->profile_picture( $val['big'], $val['photo_updated'] );
            } else {
                $sexpic = 2;
                if ($MySugFriends[$key]['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $MySugFriends[$key]['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }  ***    */
        
                           
               if ($MySugFriends[$key]['photo_updated'] > 0 AND $MySugFriends[$key]['photosvisibility'] > 0) {
                $MySugFriends[$key]['profile_picture'] = $this->FileUrl->profile_picture( $val['big'], $val['photo_updated'] );
            } else {
                $sexpic = 2;
                if ($MySugFriends[$key]['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $MySugFriends[$key]['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }              
              
        }
            
        
          $time_end_3 = $this->getmicrotime();//sec finali
          $time_3 = $time_end_3 - $time_start_3;//differenza in secondi
          $this->log("TIME (GetBoardContentNew -> BoardContactsNew -> $utenteTime) $time_3 s ");
        // die(debug($MySugFriends));
        
        $MySugAffinity = array ();
        $time_start_4 = $this->getmicrotime();//sec iniziali
        $MySugAffinity = $this->Member->getAffinityMembersNew( $this->logged ['Member'] ['big'],$offset );
        
        foreach($MySugAffinity as $key=>$val){
            
            
            $MySugAffinity2[]=$MySugAffinity[$key][0];
            
            
        }
        
        //print_r($MySugAffinity2);
        
            foreach ( $MySugAffinity2 as $key2 => $val2 ) {
         
             // removed , picture is always visible....
             //$privacySetting=$this->PrivacySetting->getPrivacySetting($MySugAffinity2[$key2]['Member']['big']);
            //$photosVisibility=$privacySetting['photosvisibility'];
            // ADD MEMBER PHOTO
          //   debug($val2['sex'] );
            if ($val2  ['photo_updated'] > 0 /*AND $photosVisibility > 0*/) {
                $MySugAffinity2 [$key2]  ['profile_picture'] = $this->FileUrl->profile_picture ( $val2['big'], $val2['photo_updated'] );
            } else {
                $sexpic = 2;
                if ($val2 ['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $MySugAffinity2 [$key2] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
            
            
            $MySugAffinity2 [$key2] ['surname']=substr($MySugAffinity2 [$key2]['surname'],0,1).'.';
//        debug( $MySugAffinity2 [$key2]['surname']);
            $MySAff[0][]=$MySugAffinity2[$key2];
        }
        
        // debug($MySugAffinity);
        
        // recovery members members order by checkins
          $time_end_4 = $this->getmicrotime();//sec finali
          $time_4 = $time_end_4 - $time_start_4;//differenza in secondi
          $this->log("TIME (GetBoardContentNew -> getAffinityMembersNew -> $utenteTime) $time_4 s ");
        $MyAds = array ();
        $MyAds = $this->Advert->getBoardAds ( $this->logged ['Member'] ['big'] );
        //print_r($MyAds);
        
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
        
        
        foreach ( $MySugAffinity as $key => $val ) {
            
            if ($nume < count ( $MySugAffinity )) {
                // $MySugAffinity[$i]["BoardType"]= "AffinityMember";
                
                // RIMETTER $MyBoardAff[]['Member'] = $val[0]; //[0];
            }
        }
        
        $MyBoardAff [] = array (
                $MySugAffinity 
        );
         */
        // $bms[] = array(
        // 'Bookmark' => $val['Bookmark'],
        if ( count($MyPlaces)>0 || count($MyFriends)>0 )
        {
        for($i = 0; $i <= 25; $i ++) {
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
            
            if ($i < count ( $MySugFriends ) and $i < 10) {
                $MySugFriends [$i] ["BoardType"] = "SuggestedMember";
                $MyBoard [] = $MySugFriends [$i];
            }
            
            /*
             * if ($i < count ( $MySugAffinity ) ) { $MySugAffinity [$i] ["BoardType"] = "AffinityMember"; $MyBoard [] = $MySugAffinity [$i]; }
             */
        }
        
        $MyBoardAff= $MySugAffinity;
        //$MyBoardAff 
        $MySugAffinity["BoardType"] = "AffinityMembers";
        
        $MySAff["BoardType"]="AffinityMembers";
        
        //print_r($MySAff);
        //da togliere per crash
        //$MyBoard [] = $MySugAffinity;
        
        $MyBoard[]=$MySAff;
        
        
        } // almeno un elemento o risposta vuota
        
        //print_r($MyBoard);       
        
        // $MyBoardAff;
        // array(
        // 'AffinityMembers' => $MyBoardAff
        // );
        // $MyBoard ["AffinityMembers"] = $MyBoardAff[0];
        
        $this->_apiOk ( $MyBoard );
        $time_end = $this->getmicrotime();//sec finali
        $time = $time_end - $time_start;//differenza in secondi
        $this->log("TIME (GetBoardContentNew -> TOTgetBoardContent -> $utenteTime) $time s");  

    }
    
    
    
    
    
	/**
	 * get board content for logged user
	 */
	public function api_GetDiaryContent() {
		
		// TORNARE FOTO,POSTI, AMICIZIE
		$this->_checkVars ( array (), array ('big','offset') );
		
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
        
		$offset = isset($this->api['offset']) ? $this->api['offset'] : 0;
		
        $MyBig = $this->api['big'];
		
		// TEST PLACES!!!
		$MyPlaces = array ();
        
         //Da qui ricavo i PrivacySetting dell'utente 
        $privacySettings = $this->PrivacySetting->getPrivacySettings ( $MyBig );
        $privacySettings = $privacySettings[0]['PrivacySetting'];
        
        //print_r($privacySettings);
        //Se checkinsvisibility=0 allora non mostra i posti quindi � inutile estrarli dal db
        //if ($privacySettings['checkinsvisibility'] > 0)
       
           
        if ($MyBig!=$this->logged['Member']['big']) {//Accesso ai Places di un membro.
                   
        
        switch ($privacySettings['checkinsvisibility']){
            
            
            case 0 : //visibile a nessuno
                    $MyPlaces = array();
                    break;
            
            case 1 : // visibile a tutti
                    $MyPlaces = $this->Place->getBoardPlaces($MyBig, $offset);
                                    
                    break;
            
            case 2 : //visibile solo ad amici. Verificare che non sia amico poi bloccato
                     //perch� il blocco non tocca lo status di amico                    
                     $amico=$this->Friend->FriendsRelationship($this->logged['Member']['big'],$MyBig,'A');
                     $bloccato=$this->MemberSetting->isOnIgnoreListDual($this->logged['Member']['big'],$MyBig);
                     
                    if (count($amico)>0 AND !$bloccato){//sono amici non bloccati quindi ok visualizzazione Places
                        
                        $MyPlaces = $this->Place->getBoardPlaces($MyBig, $offset);
                                   
                    } else {//non sono amici oppure lo erano e ora sono bloccati quindi no visualizzazione Places
                        
                            $MyPlaces = array();
                        
                    }
        } 
            }
                else {                               
                   
		              $MyPlaces = $this->Place->getBoardPlaces( $MyBig, $offset);
		           }
		//$this->log("------------MyPlaces------------"); 
        //$this->log($MyPlaces); 
        //$this->log("------------Fine MyPlaces-------");
		      
               
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
							'Comment.member_big' => $MyBig,
							'Comment.likeit' => 1,
							// 'Comment.place_big' => $MyPlaces [$key] [0]['checkinbig']
							'Comment.checkin_big' => $MyPlaces [$key] [0] ['checkinbig']
							// 'Comment.place_big'
					)
			) );
				
			// TODO: ARRIVATO QUI !!!! LIKE COUNT
			$MyPlaces [$key] ['CountOfComments'] = $this->Comment->getCommentsCount ( $MyPlaces [$key] [0] ['checkinbig'], 0 );
			$MyPlaces [$key] ['CountOfLikes'] = $this->Comment->getLikesCount ( $MyPlaces [$key] [0] ['checkinbig'], 0 );
				
			$MyPlaces [$key] ['ILike'] = $xlike;
		}
	
		
		/* PARTE COMMENTATA PERCHE' NON UTILIZZATA DALL'APP
        			
		$MyCheckins = array ();
		$Checkins = array ();
		
		$allx = true;
        
        if ($MyBig!=$this->logged['Member']['big']) {//se voglio accedere ai checkins di un membro
        
               
                     
        switch ($privacySettings['checkinsvisibility']){
            
            
            case 0 : //visibile a nessuno
                    $MyCheckins=array();
                    break;
            
            case 1 : // visibile a tutti
                    $MyCheckins = $this->Checkin->getNearbyCheckinsMember ( $MyBig, $allx, $offset );
                                    
                    break;
            
            case 2 : //visibile solo ad amici
                    
                     $amico=$this->Friend->FriendsRelationship($this->logged['Member']['big'],$MyBig,'A');
                    
                    if (count($amico)>0){//sono amici quindi ok visualizzazione checkins
                        
                        $MyCheckins = $this->Checkin->getNearbyCheckinsMember ( $MyBig, $allx, $offset );
                                   
                    } else {//non sono amici quindi no visualizzazione checkins
                        
                            $MyCheckins=array();
                        
                    }
        }                                        
            
       } else {//I miei Checkins          
                 $MyCheckins = $this->Checkin->getNearbyCheckinsMember ( $MyBig, $allx, $offset );
                 }
        	
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
        
        */
        
               
        if ($MyBig!=$this->logged['Member']['big']) {//Accesso alle foto del diario di un membro
        
               
        //$PrivacyFoto = $this->PrivacySetting->getPrivacySettings ( $MyBig );
        //$PrivacyFoto = $PrivacyFoto[0]['PrivacySetting']['photosvisibility'];
        
                
        switch ($privacySettings['photosvisibility']){
            
            
            case 0 : //foto visibili a nessuno 
                    $MyPhotos=array();
                    break;
            
            case 1 : //foto visibili a tutti
                    $MyPhotos = $this->Photo->getMemberPhotos( $MyBig, $offset );
                    break;                    
            
            case 2 ://foto visibili solo amici
                    
                    $amico=$this->Friend->FriendsRelationship($this->logged['Member']['big'],$MyBig,'A');
                    $bloccato=$this->MemberSetting->isOnIgnoreListDual($this->logged['Member']['big'],$MyBig);
                     
                    if (count($amico)>0 AND !$bloccato){//sono amici e non bloccati quindi ok foto
                        
                        $MyPhotos = $this->Photo->getMemberPhotos( $MyBig, $offset );
                                   
                    } else {//non sono amici o lo erano ma ora sono bloccati quindi niente foto
                        
                            $MyPhotos=array();
                        
                    }
                                      
                     
        }                                        
            
       } else {//Accesso alle foto del mio diario           
		         $MyPhotos = $this->Photo->getMemberPhotos( $MyBig, $offset );
                 }
        
        
		$MyFriends = array ();
        
        
        if ($MyBig!=$this->logged['Member']['big']) {//se voglio accedere al diario di un membro
              
          switch ($privacySettings['friendsvisibility']){//Visualizzazione delle amicizie strette presenti sul diario di un membro 
            
            
            case 0 : //Ha stretto amicizia con.... (visibili a nessuno) 
                    $Amici=array();
                    break;
            
            case 1 : //Ha stretto amicizia con... (visibili a tutti)
                    $Amici = $this->Friend->GetDiaryFriends( $MyBig, $offset );
                    break;                    
            
            case 2 : //Ha stretto amicizia con... (visibili solo agli amici)
                    
                    $amico=$this->Friend->FriendsRelationship($this->logged['Member']['big'],$MyBig,'A');
                    $bloccato=$this->MemberSetting->isOnIgnoreListDual($this->logged['Member']['big'],$MyBig);
                     
                    if (count($amico)>0 AND !$bloccato){//sono amici e non bloccati quindi KO 
                        
                       $Amici = $this->Friend->GetDiaryFriends( $MyBig, $offset );
                                   
                    } else {//non sono amici o lo erano ma ora sono bloccati quindi KO
                        
                            $Amici=array();
                        
                    }
                                      
                     
        }                      
                
          // $Amici = $this->Friend->GetDiaryFriends( $MyBig, $offset );
        
            
               foreach ($Amici as $key=>$val){
                //Non visualizza sul diario altrui i membri bloccati da chi visualizza           
                           
                      if (!$this->MemberSetting->isOnIgnoreListDual($this->logged['Member']['big'],$val[0]['big'])){                  
                            $Amici_clean[]=$val;
                            
                           }
               
                }                                
            $Amici=$Amici_clean;    
        
          //vedo se sono amici        
          $amico=$this->Friend->FriendsRelationship($this->logged['Member']['big'],$MyBig,'A');
            
           if (count($amico)==0){//logged e MyBig NON sono amici quindi privacy cognome sugli amici di MyBig
                        
                        
                        foreach ($Amici as $key=>$val){
                            
                            $cognome=$val[0]['surname'];
                            
                            $Amici[$key][0]['surname']=strtoupper($cognome[0].".");
                            
                            
                            /*
                            Inoltre aggiungi foto se l'amico di MyBig � mio amico e photosvisibility=1,
                            oppure photosvisibility=2
                            */
                                              
              $PrivacyFotoAmico = $this->PrivacySetting->getPrivacySettings ( $val[0]['big'] );
              $photosVisibility = $PrivacyFotoAmico[0]['PrivacySetting']['photosvisibility'];
              $amicoLogged=$this->Friend->FriendsRelationship($this->logged['Member']['big'],$val[0]['big'],'A');                  
              $Amici[$key][0]['photosvisibility']=$photosVisibility;
                          
            if (($photosVisibility==2 AND count($amicoLogged)>0) OR $photosVisibility==1) {
                
                   if (isset($val[0]['photo_updated']) AND $val[0]['photo_updated'] > 0 ) {
                     
                     $Amici[$key][0]['profile_picture'] = $this->FileUrl->profile_picture ( $val[0]['big'], $val[0]['photo_updated']);
           } 
            else 
                  { 
                    $sexpic = 2;
                    if (isset($val[0]['sex']) AND $val[0]['sex'] == 'f') {
                                    $sexpic = 3;
                                }
                
                $Amici[$key][0]['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
                    
                    
            } else {
                    $sexpic = 2;
                    if (isset ( $val[0]['sex'] ) AND $val[0]['sex'] == 'f') {
                                    $sexpic = 3;
                                }
                
                $Amici[$key][0]['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
                                
            }
           
           }                         
                   
        } else {//logged e MyBig sono amici quindi per ogni amico di MyBig visualizzo la foto del profilo se presente
                   
                     foreach ($Amici as $key=>$val){
                          
                      $amico=$this->Friend->FriendsRelationship($val[0]['big'],$this->logged['Member']['big'],'A');
                      
                      if ($amico==0){//Se gli amici sul diario di un mio amico non sono anche amici miei allora mi vengono visualizzati con privacy cognome
                          
                            $cognome=$val[0]['surname'];
                            
                            $Amici[$key][0]['surname']=strtoupper($cognome{0}.".");
                                               
                      }
                      
                         
                    if (isset($val[0]['photo_updated']) AND $val[0]['photo_updated'] > 0 ) {
                                      
                     $Amici[$key][0]['profile_picture'] = $this->FileUrl->profile_picture ( $val[0]['big'], $val[0]['photo_updated']);
           } 
            else 
                  { 
                    $sexpic = 2;
                    if (isset($val[0]['sex']) AND $val[0]['sex'] == 'f') {
                                    $sexpic = 3;
                                }
                
                $Amici[$key][0]['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }   
            
            
            }  
        }  
        }
    
            else {//accesso al mio diario quindi non necessaria privacy
              
		            $Amici = $this->Friend->GetDiaryFriends( $MyBig, $offset );
                    
                    foreach ($Amici as $key=>$val){
                        
                       if (isset($val[0]['photo_updated']) AND $val[0]['photo_updated']>0 )
                       {
                          $Amici[$key][0]['profile_picture'] = $this->FileUrl->profile_picture ($val[0]['big'], $val[0]['photo_updated'] );
            } else {
                $sexpic = 2;
                if (isset ( $val[0]['sex']) AND $val[0]['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $Amici[$key][0]['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }      
                } 
                       
                    }
         //print_r($Amici);                             
		if (is_array ( $Amici )) { // previene il warning Invalid argument supplied for foreach()
			foreach ( $Amici as $ami ) {
				// add only if privacy ok
				
                $friendID=($ami["Friend1"]["big"] == $MyBig) ? $ami["Friend2"]["big"] : $ami["Friend1"]["big"];
                                             
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
			
			if ($i < count ( $MyPlaces )) {
				$MyPlaces [$i] ["BoardType"] = "Place";
				$MyBoard [] = $MyPlaces [$i];
			}
			/* COMMENTATO PERCHE' NON USATO NELL'APP
            if ($i < count ( $Checkins )) {
				$Checkins [$i] ["BoardType"] = "Checkins";
				$MyBoard [] = $Checkins [$i];
			}
            */
            
            
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
         $time_start = $this->getmicrotime();//sec iniziali
		$MyPlaces = array ();
        //prende i dati del member loggato dalla tabella Members
        $IPmember = ($this->Member->getMemberByBig ( $this->logged ['Member'] ['big'] ));
        //Riduce profondit� array. Imember contiene tutti i dati del member
        $Imember = $IPmember ['Member'];
        $utenteTime=$this->logged ['Member'] ['big'];
        //Memorizza in $coords l'ultima posizione in tabella 
        $coords = $Imember ['last_lonlat'];
        $time_start_1 = $this->getmicrotime();//sec iniziali
        //Prende i Places ordinati per distanza crescente da coords
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
		  $time_end_1 = $this->getmicrotime();//sec finali
          $time_1 = $time_end_1 - $time_start_1;//differenza in secondi
          $this->log("TIME (getRadarPlaces -> $utenteTime) $time_1 s ");
		$MyFriends = array ();
		$MyFriendsClean = array ();
		//$MyFriends = $this->Member->getRadarMembers ( $this->logged ['Member'] ['big'] );
		$serviceList=explode(',',ID_RADAR_VISIBILITY_PRODUCTS);
        $time_start_2 = $this->getmicrotime();//sec iniziali
        $MyFriends = $this->Member->getRadarPrivacyMembers ( $this->logged ['Member'] ['big'],$serviceList,true);
        
		foreach ( $MyFriends as $key => $val ) {
			
			// ADD MEMBER PHOTO
            //flag privacy photovisibility
			$photoVisibility=$val[0]['photosvisibility'];
            
			if (isset ( $MyFriends [$key] [0] ['photo_updated'] ) AND $MyFriends [$key] [0] ['photo_updated'] > 0 AND $photoVisibility > 0) {
				$MyFriends [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if (isset ( $MyFriends [$key] [0] ['sex'] ) and $MyFriends [$key] [0] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$MyFriends [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			
			// if not friend NO surname please
			$IsFriend=$this->Friend->FriendsRelationship($MyFriends [$key] [0]['big'],$this->logged ['Member'] ['big'],'A');
			if (count($IsFriend)<1)
			{			
			
			$MyFriends [$key] [0]['surname']=substr($MyFriends [$key] [0]['surname'],0,1).'.';
			}
			
			//QUI   !!!  $isIgnored = $this->ChatMessage->Sender->MemberSetting->isOnIgnoreList ( $partnerBig, $memBig );
		//	if ($isIgnored) {
			$isIgnored=$this->MemberSetting->isOnIgnoreListDual($this->logged ['Member'] ['big'],$val[0]['big']);
			$Privacyok = $this->PrivacySetting->getPrivacySettings ( $MyFriends [$key] [0]['big'] );
		//	$this->log("------------BOARDS CONTROLLER------------");
		//	$this->log($Privacyok [0]['PrivacySetting'] ['visibletousers']);
		//	$this->log($Privacyok [0] ['visibletousers']);
		//	$this->log($MyFriends [$key] [0]['big']);
		//	$this->log($MyFriends [$key] [0]['name'].' '.$MyFriends [$key] [0]['surname'] );
				
				
			$goonPrivacy = true;
			if (count ( $Privacyok ) > 0) {
				if ($Privacyok [0]['PrivacySetting'] ['visibletousers'] == 0 OR $isIgnored) {
					$goonPrivacy = false;
				}
			}
		//	$this->log($goonPrivacy );
		//	$this->log("------------FINE------------");
				
				
			if ($goonPrivacy) {
			$MyFriendsClean[] = $MyFriends [$key];
			}
		}
		$MyFriends=$MyFriendsClean;
		// die(debug($MySugFriends));
		$time_end_2 = $this->getmicrotime();//sec finali
          $time_2 = $time_end_2 - $time_start_2;//differenza in secondi
          $this->log("TIME (Radar -> MyFriends -> $utenteTime) $time_1 s ");
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
        
        $time_end = $this->getmicrotime();//sec finali
        $time = $time_end - $time_start;//differenza in secondi
        $this->log("TIME (getRadarContent) $time s");  
	}
	
	
    public function api_GetRadarContent_OLD() {
         $time_start = $this->getmicrotime();//sec iniziali
        $MyPlaces = array ();
        //prende i dati del member loggato dalla tabella Members
        $IPmember = ($this->Member->getMemberByBig ( $this->logged ['Member'] ['big'] ));
        //Riduce profondit� array. Imember contiene tutti i dati del member
        $Imember = $IPmember ['Member'];
        $utenteTime=$this->logged ['Member'] ['big'];
        //Memorizza in $coords l'ultima posizione in tabella 
        $coords = $Imember ['last_lonlat'];
        $time_start_1 = $this->getmicrotime();//sec iniziali
        //Prende i Places ordinati per distanza crescente da coords
        $MyPlaces = $this->Place->getRadarPlaces ( $coords );
        
        print_r($MyPlaces);
        
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
          $time_end_1 = $this->getmicrotime();//sec finali
          $time_1 = $time_end_1 - $time_start_1;//differenza in secondi
          $this->log("TIME (getRadarPlaces -> $utenteTime) $time_1 s ");
        $MyFriends = array ();
        $MyFriendsClean = array ();
        //$MyFriends = $this->Member->getRadarMembers ( $this->logged ['Member'] ['big'] );
        $serviceList=explode(',',ID_RADAR_VISIBILITY_PRODUCTS);
        $time_start_2 = $this->getmicrotime();//sec iniziali
        $MyFriends = $this->Member->getRadarPrivacyMembers ( $this->logged ['Member'] ['big'],$serviceList,true);
        
        foreach ( $MyFriends as $key => $val ) {
            
            // ADD MEMBER PHOTO
            //flag privacy photovisibility
            $photoVisibility=$val[0]['photosvisibility'];
            
            if (isset ( $MyFriends [$key] [0] ['photo_updated'] ) AND $MyFriends [$key] [0] ['photo_updated'] > 0 AND $photoVisibility > 0) {
                $MyFriends [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
            } else {
                $sexpic = 2;
                if (isset ( $MyFriends [$key] [0] ['sex'] ) and $MyFriends [$key] [0] ['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $MyFriends [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
            
            // if not friend NO surname please
            $IsFriend=$this->Friend->FriendsRelationship($MyFriends [$key] [0]['big'],$this->logged ['Member'] ['big'],'A');
            if (count($IsFriend)<1)
            {            
            
            $MyFriends [$key] [0]['surname']=substr($MyFriends [$key] [0]['surname'],0,1).'.';
            }
            
            //QUI   !!!  $isIgnored = $this->ChatMessage->Sender->MemberSetting->isOnIgnoreList ( $partnerBig, $memBig );
        //    if ($isIgnored) {
            $isIgnored=$this->MemberSetting->isOnIgnoreListDual($this->logged ['Member'] ['big'],$val[0]['big']);
            $Privacyok = $this->PrivacySetting->getPrivacySettings ( $MyFriends [$key] [0]['big'] );
        //    $this->log("------------BOARDS CONTROLLER------------");
        //    $this->log($Privacyok [0]['PrivacySetting'] ['visibletousers']);
        //    $this->log($Privacyok [0] ['visibletousers']);
        //    $this->log($MyFriends [$key] [0]['big']);
        //    $this->log($MyFriends [$key] [0]['name'].' '.$MyFriends [$key] [0]['surname'] );
                
                
            $goonPrivacy = true;
            if (count ( $Privacyok ) > 0) {
                if ($Privacyok [0]['PrivacySetting'] ['visibletousers'] == 0 OR $isIgnored) {
                    $goonPrivacy = false;
                }
            }
        //    $this->log($goonPrivacy );
        //    $this->log("------------FINE------------");
                
                
            if ($goonPrivacy) {
            $MyFriendsClean[] = $MyFriends [$key];
            }
        }
        $MyFriends=$MyFriendsClean;
        // die(debug($MySugFriends));
        $time_end_2 = $this->getmicrotime();//sec finali
          $time_2 = $time_end_2 - $time_start_2;//differenza in secondi
          $this->log("TIME (Radar -> MyFriends -> $utenteTime) $time_1 s ");
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
        
        $time_end = $this->getmicrotime();//sec finali
        $time = $time_end - $time_start;//differenza in secondi
        $this->log("TIME (getRadarContent) $time s");  
    }
    
    
    
    
    
    public function api_GetRadarContent_NEW() {
         $time_start = $this->getmicrotime();//sec iniziali
        $MyPlaces = array ();
        //prende i dati del member loggato dalla tabella Members
        $IPmember = ($this->Member->getMemberByBig ( $this->logged ['Member'] ['big'] ));
        //Riduce profondit� array. Imember contiene tutti i dati del member
        $Imember = $IPmember ['Member'];
        $utenteTime=$this->logged ['Member'] ['big'];
        //Memorizza in $coords l'ultima posizione in tabella 
        $coords = $Imember ['last_lonlat'];
        $time_start_1 = $this->getmicrotime();//sec iniziali
        //Prende i Places ordinati per distanza crescente da coords
        $MyPlaces = $this->Place->getRadarPlacesBoost( $coords ); //prende i 15 place pi� vicini a $coords
        
        print_r($MyPlaces);
         
         
        foreach ( $MyPlaces as $key => $val ) {
            // debug($val[0] );
            if (isset ( $val ['DefaultPhoto'] ) && $val ['DefaultPhoto'] ['big'] > 0) { // add URLs to default photos
                                            
               $MyPlaces [$key] [0] ['photo'] = $this->FileUrl->place_photo($val[0]['big'],$val['DefaultPhoto']['gallery_big'],$val['DefaultPhoto']['big'],$val['DefaultPhoto'] ['original_ext']);
               
            } else {
                
                $MyPlaces [$key] [0] ['photo'] = $this->FileUrl->default_place_photo ( $val[0]['category_id'] );
            }
                      
            $xeve = $this->Event->getDefault ( $MyPlaces [$key] [0] ['big'] );
           
            $MyPlaces [$key] [0] ['CheckinsCount'] = $this->Checkin->getJoinsandCheckinsCountFor ( $xeve ['Event'] ['big'] );
        }
         
        // recovery friends order by checkins
          $time_end_1 = $this->getmicrotime();//sec finali
          $time_1 = $time_end_1 - $time_start_1;//differenza in secondi
          $this->log("TIME (getRadarPlaces -> $utenteTime) $time_1 s ");
        $MyFriends = array ();
        $MyFriendsClean = array ();
        //$MyFriends = $this->Member->getRadarMembers ( $this->logged ['Member'] ['big'] );
        $serviceList=explode(',',ID_RADAR_VISIBILITY_PRODUCTS);
        $time_start_2 = $this->getmicrotime();//sec iniziali
        
        //getRadarPrivacyMembers si mangia 10secondi di esecuzione!!!!!!
        $MyFriends = $this->Member->getRadarPrivacyMembers ( $this->logged ['Member'] ['big'],$serviceList,true,$coords);
        
        
        
        
        foreach ( $MyFriends as $key => $val ) {
            
            // ADD MEMBER PHOTO
            //flag privacy photovisibility
            $photoVisibility=$val[0]['photosvisibility'];
            
            if (isset ( $MyFriends [$key] [0] ['photo_updated'] ) AND $MyFriends [$key] [0] ['photo_updated'] > 0 AND $photoVisibility > 0) {
                $MyFriends [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
            } else {
                $sexpic = 2;
                if (isset ( $MyFriends [$key] [0] ['sex'] ) and $MyFriends [$key] [0] ['sex'] == 'f') {
                    $sexpic = 3;
                }
                
                $MyFriends [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
            
            // if not friend NO surname please
            $IsFriend=$this->Friend->FriendsRelationship($MyFriends [$key] [0]['big'],$this->logged ['Member'] ['big'],'A');
            if (count($IsFriend)<1)
            {            
            
            $MyFriends [$key] [0]['surname']=substr($MyFriends [$key] [0]['surname'],0,1).'.';
            }
            
            //QUI   !!!  $isIgnored = $this->ChatMessage->Sender->MemberSetting->isOnIgnoreList ( $partnerBig, $memBig );
        //    if ($isIgnored) {
            $isIgnored=$this->MemberSetting->isOnIgnoreListDual($this->logged ['Member'] ['big'],$val[0]['big']);
            $Privacyok = $this->PrivacySetting->getPrivacySettings ( $MyFriends [$key] [0]['big'] );
        //    $this->log("------------BOARDS CONTROLLER------------");
        //    $this->log($Privacyok [0]['PrivacySetting'] ['visibletousers']);
        //    $this->log($Privacyok [0] ['visibletousers']);
        //    $this->log($MyFriends [$key] [0]['big']);
        //    $this->log($MyFriends [$key] [0]['name'].' '.$MyFriends [$key] [0]['surname'] );
                
                
            $goonPrivacy = true;
            if (count ( $Privacyok ) > 0) {
                if ($Privacyok [0]['PrivacySetting'] ['visibletousers'] == 0 OR $isIgnored) {
                    $goonPrivacy = false;
                }
            }
        //    $this->log($goonPrivacy );
        //    $this->log("------------FINE------------");
                
                
            if ($goonPrivacy) {
            $MyFriendsClean[] = $MyFriends [$key];
            }
        }
               
        
        $MyFriends=$MyFriendsClean;
        // die(debug($MySugFriends));
        $time_end_2 = $this->getmicrotime();//sec finali
          $time_2 = $time_end_2 - $time_start_2;//differenza in secondi
          $this->log("TIME (Radar -> MyFriends -> $utenteTime) $time_1 s ");
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
        
        $time_end = $this->getmicrotime();//sec finali
        $time = $time_end - $time_start;//differenza in secondi
        $this->log("TIME (getRadarContent) $time s");  
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
				'user_msg' => __('Profile update succesfull') 
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
			
			// se non c'� lo inserisco
			
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
		//$this->log ( "------------you are in mergeArr--------" );
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
	public function api_CheckContactsprofileDATOGLIERE() {
		//$this->log ( "------------you are in api_CheckContactsprofile--------" );
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
		
		/*$this->log ( "------------BOARDS CONTROLLER-----------" );
		$this->log ( "------------PhoneContacts---BIG " . $ContactBIG );
		$this->log ( "------------Chunks " . $numChunks );
		$this->log ( "-----------------------------------------" );
		$this->log ( serialize ( $PhoneContacts ) );
		$this->log ( "-----------------------------------------" );
		  */    
        
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
                $val['phone_number']=(strlen($val['phone_number'])<32) ? $val['phone_number']: substr($val['phone_number'],0,32);
                
				$paramsCont ["conditions"] [] = array (
						'Contact.phone' => $val ['phone_number'] 
				);
			}
			;
			if (isset ( $val ['mail_address'] )) {
                
            $val['mail_address']=(strlen($val['mail_address'])<50) ? $val['mail_address']: substr($val['mail_address'],0,50);   
				$paramsCont ["conditions"] [] = array (
						'Contact.email' => $val ['mail_address'] 
				);
			}
			;
			
			$contactCount = $this->Contact->find ( 'count', $paramsCont );
			
			/*$this->log ( "------------BOARDS CONTROLLER-----------" );
			$this->log ( "------------contactCount---" . $contactCount );
			$this->log ( "------------Fine contactCount-----------" );
			*/
			// se non c'� lo inserisco
			
			if ($contactCount == 0) {
				
                
                $tox_chars=array('.',',',' ','(',')');
                
                $pattern='/[()]+|[a-zA-Z]+|[.]+|[ ]+|[#*]+[0-9]+[#*]+|[\\/]+[0-9]+|[-]+|[#*]$/';
                  
                $val['phone_number']=preg_replace($pattern,'',str_replace($tox_chars,'',$val['phone_number']));
                                
				$Contacts ['member_big'] = $ContactBIG;
				if (isset ( $val ['mail_address'] )) {
					$Contacts ['email'] =$val['mail_address'];
				}
				if (isset ( $val ['phone_number'] )) {
					$Contacts ['phone'] = (strlen($val['phone_number'])<32) ? $val['phone_number']: substr($val['phone_number'],0,32);
				}
				$Contacts ['name'] = (strlen($val['internal_name'])<300) ? $val['internal_name']: substr($val['internal_name'],0,300);
				$this->Contact->set ( $Contacts );
				
				$logSaveStatus = $this->Contact->save ();
				
				/*$this->log ( "------------BOARDS CONTROLLER-----------" );
				$this->log ( "------------Contacts------" . $Contacts );
				$this->log ( "------------logSaveStatus----" . $logSaveStatus );
				$this->log ( "------------Fine Contacts e logSave---------" );
                */
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
		
		/*$this->log ( "------------BOARDS CONTROLLER-----------" );
		$this->log ( "------------membersMails----------------" );
		$this->log ( addslashes ( serialize ( $membersMails ) ) );
		$this->log ( "------------Fine membersMails-----------" );
		
		$this->log ( "------------BOARDS CONTROLLER-----------" );
		$this->log ( "------------membersPhones----------------" );
		$this->log ( addslashes ( serialize ( $membersPhones ) ) );
		$this->log ( "------------Fine membersPhones-----------" );
		*/
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
		//$this->log ( "------------you are in multipleShortQueries--------" );
		$maxElem = $maxPerQuery;
		$smallMembersMails = $membersMails;
		$totalDataByEmails = array();
		$start = 0;
		$stop = 0;
		$mv = 0;
		
		$smallMembersMails = array_slice ( $membersMails, $start, $maxElem );
		
		while ( count ( $smallMembersMails ) > 0 ) {
			
			//print_r($smallMembersMails);
			$params = array (
					'conditions' => array (
							array (
									'Member.email' => $smallMembersMails 
							) 
					),
					'recursive' => -1,
					'fields' => array (
							'Member.big',
                            //'Privacy.member_big',
                            //'Privacy.photosvisibility',
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
                ),
                   'joins' => array(array('table' => 'privacy_settings',
                                          'alias' => 'Privacy',
                                          'type' => 'left',
                                          'conditions' => array('Member.big=Privacy.member_big')
                                            ))  
			);
			
			$dataByEmails = $this->Member->find ( 'all', $params );
            //print_r($dataByEmails);
            //print("----------------");
			$totalDataByEmails = $this->mergeArr ( $dataByEmails, $totalDataByEmails );
			//print_r($totalDataByEmails);
            //print("****************");
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
                            //'Privacy.member_big',
                            //'Privacy.photosvisibility',
							'Member.name',
							'Member.middle_name',
							'Member.surname',
							'Member.photo_updated',
							'Member.sex',
							'Member.phone',
							'Member.birth_date',
							'Member.address_town',
							'Member.address_country' 
					),
                    'joins' => array(array('table' => 'privacy_settings',
                                          'alias' => 'Privacy',
                                          'type' => 'left',
                                          'conditions' => array('Member.big=Privacy.member_big')
                                            ))   
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
		//print_r($dataByEmails);
        //print_r($dataByPhones);
		//$this->log ( "------------multipleShortQueries (data)--" );
		//$this->log ( serialize ( $data ) );
		//$this->log ( "-----------------------------------------" );
		
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
		
        //$this->_checkVars ( array (), array ('offset'));
		$MySugFriends = array ();
		$MySugFriends = $this->BoardContacts( $this->logged ['Member'] ['big']);
        
		$this->_apiOk ( $MySugFriends );
	}
    
    public function api_BoardContactsNew() {
        
        //$this->_checkVars ( array (), array ('offset'));
        $MySugFriends = array ();
        $MySugFriends = $this->BoardContactsNew( $this->logged ['Member'] ['big']);
        
        $this->_apiOk ( $MySugFriends );
    }
    
	public function BoardContacts($ContactBIG) {
		//$this->log ( "------------you are in BoardContacts--------" );
		$membersMails = array ();
		$membersPhones = array ();
//		$ContactBIG = $this->api ['member_big'];
		$PhoneContacts = array ();
		
		// array_merge
		// delete all existing contacts
		           
        $SugContacts = $this->Contact->find ( 'all', array (
                'conditions' =>array('Contact.member_big' => $ContactBIG),
                'order'=>array('Contact.name ASC')
                 ) 
               );       
               
        //print_r($SugContacts);
        //$SugContacts = $this->Contact->find ( 'all', array (
		//		'Contact.member_big' => $ContactBIG 
		//) );
		
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
       
        $index = array($ContactBIG);//previene che l'utente sia consigliato a se stesso nel caso avesse i propri
                                    //numeri in rubrica
                                    
        //Questo foreach elimina i doppioni che si verificano nel caso in cui
        //la ricerca contatti vada a buon fine per email e numero di telefono
        foreach ($data as $key=>$val){
            
            if (!in_array($val['Member']['big'],$index)){
                
                $index[]=$val['Member']['big'];
                
                $dataUnique[]=$val;
                
            }
                      
        }
        
        $data=$dataUnique; 
		$dbo = $this->Member->getDatasource ();
		$logs = $dbo->getLog ();
		$lastLog = end ( $logs ['log'] );
		$AppoMem = array ();
		
        //print_r($data);
		foreach ( $data as $key => &$mem ) {
			
			// check if any friendship exists yet
            //first param is logged member big and the second param is member friend
			$AlreadyFr = $this->Friend->recommendedFriend($ContactBIG,$mem['Member']['big']);
			
			if ($AlreadyFr) {//se true allora l'amico pu� essere consigliato
				
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
				$mem ['Member']['surname']=substr($mem['Member']['surname'],0,1).'.';
				$AppoMem [] = $mem;
			}
		}
		
        //ordina l'array per nome
        usort( $AppoMem, 'BoardsController::multiFieldSortArray' );
        
        //print_r($AppoMem);
		 
        /*
		 *
		 */
		//$this->log ( "------------BoardsController--------------" );
		//$this->log ( "------------var AppoMem--------------" );
		//$this->log ( serialize ( $AppoMem ) );
		//$this->log ( "-----------------------------------------" );
		return $AppoMem;
	}
    
     public function BoardContactsNew($ContactBIG) {
        //$this->log ( "------------you are in BoardContacts--------" );
        $db = $this->Member->getDatasource ();
        $membersMails = array ();
        $membersPhones = array ();
        //$ContactBIG = $this->api ['member_big'];
        $PhoneContacts = array ();
              
        $time_start_1 = $this->getmicrotime();//sec iniziali
        $query="SELECT m.big,m.name,m.middle_name,m.surname,m.photo_updated,m.sex,m.phone,m.birth_date,m.address_town,m.address_country,ps.visibletousers,".
               "ps.photosvisibility ". 
               "FROM members m ".
               "JOIN privacy_settings ps ON m.big=ps.member_big ".
               "WHERE ( ".
                        "m.email IN (".
                                   "SELECT email ".
                                   "FROM contacts ".
                                   "WHERE member_big=$ContactBIG AND email IS NOT NULL) ".
                        "OR ".
                        
                        "m.phone IN ( ".
                                   "SELECT phone ".
                                   "FROM contacts ".
                                   "WHERE member_big=$ContactBIG AND phone IS NOT NULL) ".
                                   ") ".
               //solo membri attivi
               "AND m.status<255 ".
               //solo membri che non sono amici o hanno richiesto amicizia
               "AND m.big NOT IN ( ".
                                                    "SELECT member1_big as friend ".
                                                    "FROM friends ".
                                                    "WHERE member2_big=$ContactBIG ".
                                                    "UNION ".
                                                    "SELECT member2_big as friend ".
                                                    "FROM friends ".
                                                    "WHERE member1_big=$ContactBIG ) ".
               //solo membri non bloccati
               "AND m.big NOT IN ( ".
                                    "SELECT from_big as blocked ".
                                    "FROM member_settings ".
                                    "WHERE to_big=$ContactBIG AND chat_ignore=1 ".
                                    "UNION ".
                                    "SELECT to_big as blocked ".
                                    "FROM member_settings ".
                                    "WHERE from_big=$ContactBIG AND chat_ignore=1) ".
               //solo membri visibili a tutti
               "AND visibletousers=1 ".
               "ORDER BY m.name LIMIT 500";
      
          
          $Suggested=$db->fetchAll($query);
          
          $time_end_1 = $this->getmicrotime();//sec finali
          $time_1 = $time_end_1 - $time_start_1;//differenza in secondi
          $this->log("TIME (BoardContactsNew Query -> $utenteTime) $time_1 s ");  
          
          $time_start_2 = $this->getmicrotime();//sec iniziali
          
          foreach ($Suggested as $key=>$val){
             
             $data[]=$val[0];
                   
         }   
         
          $time_end_2 = $this->getmicrotime();//sec finali
          $time_2 = $time_end_2 - $time_start_2;//differenza in secondi
          $this->log("TIME (BoardContactsNew Adatta Output -> $utenteTime) $time_2 s ");
                           
        $AppoMem = array ();
        
        //print_r($data);
        
        $time_start_3 = $this->getmicrotime();//sec iniziali
        foreach ( $data as $key => &$mem ) {
            
                    
             if ($mem['photo_updated'] > 0) {
                                  $mem['profile_picture'] = $this->FileUrl->profile_picture ( $mem['big'], $mem['photo_updated'] );
                                    } else {
                                            // standard image
                                            $sexpic = 2;
                                            if ($mem['sex'] == 'f') {
                                                    $sexpic = 3;
                                                    }
                    
                                            $mem['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
                                            }
                $mem['surname']=substr($mem['surname'],0,1).'.';
                $AppoMem []['Member'] = $mem;
            }
        
        
          $time_end_3 = $this->getmicrotime();//sec finali
          $time_3 = $time_end_3 - $time_start_3;//differenza in secondi
          $this->log("TIME (BoardContactsNew Attacca Foto -> $utenteTime) $time_3 s ");
              
        return $AppoMem;
    }
    
    
     public static function multiFieldSortArray($x, $y) { // ordina per nome
                
            return ($x ['Member']['name'] > $y ['Member']['name']) ? + 1 : - 1;
    }
}