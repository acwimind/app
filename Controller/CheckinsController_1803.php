<?php
class CheckinsController extends AppController {
	public $uses = array (
			'Member',
			'Checkin',
			'Friend',
			'Wallet' ,
			'PrivacySetting'
	);
    
    var $components = array('MailchimpApi','Mandrill');
	
	/**
	 * Return list of people available to chat
	 */
	public function api_people() {
		$eventBig = $this->Checkin->getCheckedinEventFor ( $this->logged ['Member'] ['big'] );
		if (empty ( $eventBig )) {
		$this->_apiEr ( __('Errore. Nessun checkin trovato.'), __('Non hai fatto join o check-in in qualche posto.') );
		}
		
		$result = $this->Checkin->getMemberAvailableToChat ( $eventBig, $this->logged ['Member'] ['big'] );
		
		foreach ( $result as $key => $val ) {
			if ($val ['Member'] ['photo_updated'] > 0) {
				$result [$key] ['Member'] ['photo'] = $this->FileUrl->profile_picture ( $val ['Member'] ['big'], $val ['Member'] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($val ['Member'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$result [$key] ['Member'] ['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			unset ( $result [$key] ['Member'] ['photo_updated'] );
			
			// ADDED key for frindship
			$xfriend = $this->Friend->FriendsAllRelationship ( $this->logged ['Member'] ['big'], $result [$key] ['Member'] ['big'] );
			$xisFriend = 0;
			$xstatus = 'NO';
			if (count ( $xfriend ) > 0) {
				$xisFriend = 1;
				$result [$key] ['Member'] ['friendstatus'] = $xfriend [0] ['Friend'] ['status'];
				$xstatus = $xfriend [0] ['Friend'] ['status'];
			}
			
			if ($xstatus != 'A') {
				$result [$key] ['Member'] ['surname'] = mb_substr ( $result [$key] ['Member'] ['surname'], 0, 1 ) . '.';
			}
			
			$result [$key] ['Member'] ['isFriend'] = $xisFriend;
			
			// ['Member']['isFriend']=count ( $Amici );
		}
		// $this->Util->transform_name($result);
		$this->_apiOk ( array (
				'members' => $result 
		) );
	}
	
	/**
	 * Legacy method name - the same functionality as /checkins/in
	 */
	public function api_try() {
		$this->api_in ();
	}
	
	/**
	 * Checkin or join an event
	 * Member can checkin/join based on event_big or place_big
	 * In case of checkin/join using place_big, we will use default event of the place (or create one)
	 */
	public function api_in() {
		$this->_checkVars ( array (
				'physical'  // 1 = check in (member has physically visited the place),
		           // 0 = join (joined event without visiting it, for example from website)
	)	, array (
				'event_big', // BIG of the event
				'place_big', // BIG of the place
				'lon', // GPS position (longitude), only required on checkin (physical presence at event)
				'lat'  // GPS position (latitude), only required on checkin (physical presence at event)
				 ));
		
		$physical = intval ( $this->api ['physical'] );
		$placeBig = isset ( $this->api ['place_big'] ) && ! empty ( $this->api ['place_big'] ) ? $this->api ['place_big'] : null;
		$eventBig = isset ( $this->api ['event_big'] ) && ! empty ( $this->api ['event_big'] ) ? $this->api ['event_big'] : null;
		
		CakeLog::info ( 'Called checkin/in with data: Physical = ' . $physical . ' PlcBig = ' . $placeBig . ' EvntBig = ' . $eventBig );
		
		// event BIG not specified - checkin via place BIG, find (or create) default event
		if ($eventBig == null) {
			
			if ($placeBig == null) {
				
				CakeLog::error ( 'Error occured. Missing place_big and event_big.' );
				
				$this->_apiEr ( __('La seguente variabile è stata omessa: place_big and/or event_big') );
			}
			
			$event = $this->Checkin->Event->Place->getCurrentEvent ( $placeBig ); // get place current event
			$eventBig = $event ['Event'] ['big'];
		}
		
		// checkin data
		$checkin = array (
				'member_big' => $this->logged ['Member'] ['big'],
				'event_big' => $eventBig,
				'physical' => $physical,
				'type' => 1,
				'created' => date ( 'Y-m-d H:i:s' ) 
		);
		
		// check in (physical presence at event)
		if ($physical) {
			
			$lon = isset ( $this->api ['lon'] ) ? $this->api ['lon'] : '';
			$lat = isset ( $this->api ['lat'] ) ? $this->api ['lat'] : '';
			
			CakeLog::info ( 'Called checkin/in with lon lat: Lon = ' . $lon . ' Lat = ' . $lat );
			
			if (empty ( $lon ) || empty ( $lat )) { // GPS position is required on checkin
				$this->_apiEr ( __('La seguente variabile è stata omessa: lon and/or lat') );
			}
			
			$coords = '(' . $lon . ',' . $lat . ')';
			
			// Match coords against regular expression
			if (! preg_match ( '/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords )) {
				
				CakeLog::error ( 'Error occured. Invalid coords.' );
				
				$this->_apiEr ( __('Le coordinate non sono valide: lon and/or lat') );
			}
			
			$checkin ['lonlat'] = $coords;
		}
		
		CakeLog::info ( 'Before check if user is already checked in' );
		
		// Check if user is already checked in
		if ($this->Checkin->hasJoinedOrCheckedIn ( $this->logged ['Member'] ['big'], $eventBig, $physical )) {
			
			CakeLog::error ( 'Already checked in or joined' );
			
			$this->_apiEr ( __('Hai già fatto ') . ($physical ? __('check-in') : __('join')) . __(' in questo evento.'), true );
		}
		
		// save checkin
		$this->Checkin->set ( $checkin );
        
        //Verifica se primo checkin
        if ($this->firstCheckin($this->logged ['Member'] ['big'])) {
            
             $mandrillResult=$this->mandrill_PrimoJoinReminder($this->api['email']);
             $this->Wallet->addAmount($this->logged ['Member'] ['big'], '50', 'Primo Join' );
        }
        
		try {
			$result = $this->Checkin->save ();
            
            if ($result) {
                            $this->Member->rank($this->logged ['Member'] ['big'],1); //rank +1 se checkin
                            $this->Wallet->addAmount($this->logged ['Member'] ['big'], '1', 'checkin' ); //credito +1 se checkin
                            }
		} catch ( Exception $e ) {
			
			CakeLog::error ( 'Error occured. Checkin failed. Probably non-existent event.' );
			
			$this->_apiEr ( __('Errore. Check-in fallito. Probabile evento inesistente.') );
		}
		
		CakeLog::info ( 'Before checkout. CheckinId = ' . $this->Checkin->id . ' MemBig = ' . $this->logged ['Member'] ['big'] );
		
		// check out all other check-ins / joins
		$this->Checkin->out ( $this->logged ['Member'] ['big'], $this->Checkin->id );
		
		CakeLog::info ( 'After checkout.' );
		
		if (empty ( $result )) {
			
			CakeLog::error ( 'Error occured. Check in procedure failed.' );
			
			$this->_apiEr ( __('Errore. Check-in fallito.'), __("Siamo spiacenti ma si è verificato un problema durante il check-in.") );
		} else {
			
			CakeLog::info ( ' ------------------------ Checkin end -----------------------' );
			
			// $this->ChatCache->write($this->logged['Member']['big'].'_checkin_event_big', $result['Checkin']['big']);
			// $this->ChatCache->append($result['Checkin']['big'].'_members', ','.$this->logged['Member']['big']);
			
			$this->_apiOk ( array (
					'Checkin' => array (
							'big' => $result ['Checkin'] ['big'],
							'physical' => $result ['Checkin'] ['physical'],
							'created' => $result ['Checkin'] ['created'] 
					),
					'Event' => array (
							'big' => $result ['Checkin'] ['event_big'] 
					) 
			) );
		}
	}
	
    
    
    public function mandrill_PrimoJoinReminder($email){
    //REMINDER                   
       
       $message = array('message'=>array(
                                            'subject' => "Complimenti hai fatto il tuo primo Join",
                                            'from_email' => 'haamble@haamble.com',
                                            'to' => array(array('email' => "$email", 
                                                                'name' => ""))));
                        
                        

       $template_name = array('template_name'=>'PrimoJoin_reminder');

       
       $template_content = array('template_content'=>array(array(
                                                                    'name' => 'main',
                                                                    'content' => ''
                                                                    )
                                                          )      
                                );
                                
       $params=array_merge($template_name,$template_content,$message);                                
              
       //risposta non usata per verificare failure
       $this->Mandrill->messagesSend_template($params);
           
       
   } 
          
    
    
    
	/**
	 * Checkout from an event
	 * We checkout from all events, because member cannot be checked at more than 1 events at a time
	 */
	public function api_out() {
		
		// check out all other check-ins / joins
		$checkedOut = $this->Checkin->out ( $this->logged ['Member'] ['big'] );
		
         if ($checkedOut) $this->Member->rank($this->logged ['Member'] ['big'],1);
        
		$this->_apiOk ( array (
				'checkout' => $checkedOut 
		) );
	}
	public function api_autocheckout() {
		$this->_checkVars ( array (
				'lon',
				'lat' 
		) );
		
		$memBig = $this->logged ['Member'] ['big'];
		$lon = isset ( $this->api ['lon'] ) ? $this->api ['lon'] : null;
		$lat = isset ( $this->api ['lat'] ) ? $this->api ['lat'] : null;
		$coords = '(' . $lon . ',' . $lat . ')';
		
		// Match coords against regular expression
		$crdsMatch = preg_match ( '/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords );
		if ($crdsMatch == FALSE) {
			$this->_apiEr ( __('Le coordinate non sono valide: lon and/or lat') );
		}
		
		$checkinData = $this->Checkin->getCheckedinEventFor ( $memBig, true );
		// debug($checkinData);
		if (empty ( $checkinData ) || (isset ( $checkinData ) && $checkinData ['Checkin'] ['physical'] != 1)) {
			$this->_apiEr ( __('Nessun check-in valido trovato'), null, null, array (
					'error_code' => '501' 
			) );
		}
		$checkin = $checkinData ['Checkin'];
		// debug($checkin);
		$isInRadius = $this->Checkin->Event->Place->isUserNearby ( $checkin ['event_big'], $coords );
		// debug($isInRadius);
		
		$msg = '';
		if ($isInRadius == FALSE && empty ( $checkin ['checkout'] )) {
			$acodate = date ( 'Y-m-d H:i', strtotime ( '+ ' . AUTOCHECKOUT_LIMIT . ' minutes' ) );
			$checkin ['checkout'] = $acodate;
			$msg .= 'Out of radius. Automatic checkout set to ' . $acodate . '.';
		} elseif ($isInRadius && ! empty ( $checkin ['checkout'] )) {
			$checkin ['checkout'] = null;
			$msg .= 'Inside radius. Automatic checkout set to null.';
		} else {
			$msg .= 'Current autocheckout value is set to ' . (! empty ( $checkin ['checkout'] ) ? $checkin ['checkout'] : 'null') . '.';
		}
		
		$this->Checkin->set ( $checkin );
		$this->Checkin->save ();
		
		$this->_apiOk ( array (
				'detail_msg' => $msg,
				'checkout_dt' => $checkin ['checkout'],
				'in_radius' => $isInRadius ? 1 : 0 
		) );
	}
	public function api_last_visited() {
		$this->_checkVars ( array (
				'user_big' 
		), array (
				'offset' 
		) );
		
		// Variables
		$user_big = $this->api ['user_big'];
		$offset = isset ( $this->api ['offset'] ) ? $this->api ['offset'] : 0;
		
		// find latest checkins
		$event_bigs = $this->Checkin->find ( 'list', array (
				'conditions' => array (
						'Checkin.member_big' => $user_big,
						'Checkin.physical ' => 1,
						'OR' => array (
								'Checkin.checkout !=' => null,
								'Checkin.checkout <' => 'NOW()' 
						) 
				),
				'fields' => array (
						'Checkin.event_big',
						'Checkin.event_big' 
				), // 'MAX(Checkin.created) as "Checkin__created"'),
				'order' => array (
						'MAX(Checkin.created)' => 'desc' 
				),
				'group' => array (
						'Checkin.event_big' 
				),
				'recursive' => - 1 
		) );
		
		// events for latest checkins
		unbindAllBut ( $this->Checkin->Event, array (
				'Place',
				'DefaultPhoto',
				'Gallery' 
		) );
		$checkins = $this->Checkin->Event->find ( 'all', array (
				'conditions' => array (
						'Event.big' => $event_bigs,
						'Place.status !=' => DELETED 
				),
				'fields' => array (
						'Event.big',
						'Event.name',
						'("Event"."type"=2 and "Event"."status"=0) as "Event__hidden"',
						'Event.rating_avg',
						'Place.big',
						'Place.name',
						'DefaultPhoto.big',
						'DefaultPhoto.original_ext' 
				),
				'limit' => API_PER_PAGE,
				'offset' => $offset * API_PER_PAGE 
		) );
		
		// Get places
		$place_bigs = array ();
		foreach ( array_merge ( $checkins ) as $checkin ) {
			$place_bigs [] = $checkin ['Place'] ['big'];
		}
		unbindAllBut ( $this->Checkin->Event->Place, array (
				'DefaultPhoto',
				'Gallery',
				'Region' 
		) );
		$places = $this->Checkin->Event->Place->find ( 'all', array (
				'conditions' => array (
						'Place.big' => $place_bigs 
				),
				'fields' => array (
						'Place.big',
						'Place.name',
						'Place.category_id',
						'Place.rating_avg',
						'Place.address_street',
						'Place.address_street_no',
						'DefaultPhoto.big',
						'DefaultPhoto.original_ext',
						'"Region"."city" AS "Place__city"' 
				) 
		) );
		$places = $this->_addPlacePhotoUrls ( $places );
		$places = Set::combine ( $places, '{n}.Place.big', '{n}.Place' );
		
		$checkins = $this->_addEventPhotoUrls ( $checkins );
		
		// add places with photos to last checkin events
		foreach ( $checkins as $key => $checkin ) {
			$checkins [$key] ['Place'] = $places [$checkin ['Place'] ['big']];
		}
		
		$result = array (
				'events' => $checkins 
		);
		
		// Count
		if ($offset == 0) {
			$pars = array (
					'conditions' => array (
							'Checkin.member_big' => $user_big,
							'Checkin.physical' => 1,
							'OR' => array (
									'Checkin.checkout !=' => null,
									'Checkin.checkout <' => 'NOW()' 
							) 
					),
					'group' => array (
							'Checkin.event_big' 
					) 
			);
			
			$count = $this->Checkin->find ( 'count', $pars );
			
			$result ['events_count'] = $count;
		}
		
		$this->_apiOk ( $result );
	}
	
	/**
	 * check in via frontend website
	 */
	public function in($eventBig = 0, $placeBig = 0) {
		
		// event BIG not specified - checkin via place BIG, find (or create) default event
		if ($eventBig == 0) {
			
			if ($placeBig == 0) {
				$this->Session->setFlash ( __ ( 'Non è stato specificato alcun posto o evento per il join.' ), 'flash/error' );
				$this->redirect ( '/' );
			}
			
			$event = $this->Checkin->Event->Place->getCurrentEvent ( $placeBig ); // get place current event
			$eventBig = $event ['Event'] ['big'];
		}
		$this->logged = $this->Member->findByBig ( $this->Auth->user ( 'big' ) ); // don't understand why it's not already filled
		
		$this->Checkin->set ( array (
				'member_big' => $this->logged ['Member'] ['big'],
				'event_big' => $eventBig,
				'physical' => 0,
				'type' => 1,
				'created' => date ( 'Y-m-d H:i:s' ) 
		) );
		try {
			$result = $this->Checkin->save ();
		} catch ( Exception $e ) {
			$this->Session->setFlash ( __ ( 'Join fallito. Evento o posto inesistente.' ), 'flash/error' );
			// $this->redirect('/');
		}
		
		$this->Checkin->out ( $this->logged ['Member'] ['big'], $this->Checkin->id );
		
		$this->Checkin->Event->recursive = - 1;
		$event = $this->Checkin->Event->findByBig ( $eventBig );
		
		if ($event ['Event'] ['type'] != EVENT_TYPE_DEFAULT) {
			
			$this->Session->setFlash ( __ ( 'Hai fatto join ad un evento %s', $event ['Event'] ['name'] ), 'flash/success' );
		} else {
			
			$this->Checkin->Event->Place->recursive = - 1;
			$place = $this->Checkin->Event->Place->findByBig ( $event ['Event'] ['place_big'] );
			
			$this->Session->setFlash ( __ ( 'Hai fatto join ad un evento nel posto %s', $place ['Place'] ['name'] ), 'flash/success' );
			// return $this->redirect(array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']));
		}
		
		return $this->redirect ( array (
				'controller' => 'events',
				'action' => 'detail',
				$eventBig,
				$event ['Event'] ['slug'] 
		) );
	}
	
	/**
	 * check out via frontend website
	 */
	public function out() {
		
		// check out all other check-ins / joins
		$checkedOut = $this->Checkin->out ( $this->logged ['Member'] ['big'] );
		
		if ($checkedOut) {
			$this->Session->setFlash ( __ ( 'Check out avvenuto con successo' ), 'flash/success' );
		} else {
			$this->Session->setFlash ( __ ( 'Al momento non hai un check-in' ), 'flash/error' );
		}
		
		$this->redirect ( '/' ); // array('controller' => '', 'action' => ''));
	}
	
	/**
	 * Get nearby checkedin members
	 * We checkout from all events, because member cannot be checked at more than 1 events at a time
	 */
	public function api_nearbyOLD() {
		$this->_checkVars ( array (
				'lon',
				'lat' 
		) );
		$memBig = $this->logged ['Member'] ['big'];
		$lon = $this->api ['lon'];
		$lat = $this->api ['lat'];
		$coords = '(' . $lon . ',' . $lat . ')';
		
		// Match coords against regular expression ('41.873114', '12.510547')
		$crdsMatch = preg_match ( '/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords );
		if ($crdsMatch == FALSE) {
			$this->_apiEr ( __('Le coordinate non sono valide: lon and/or lat') );
		}
		
		$all_nearby = $this->Checkin->getNearbyCheckins ( $coords, $memBig );
		
		$xresponse = array ();
		$xami = array ();
		// print_r($all_nearby);
		foreach ( $all_nearby as $ami ) {
			
			// die(debug($ami));
			
			$xami [] = $ami;
			
			// print_r($ami);
			
			$params = array (
					'conditions' => array (
							'Member.big' => $ami [0] ['member_big'] 
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
					),
					'recursive' => - 1 
			);
			
			$data = $this->Member->find ( 'first', $params );
			
			if (isset ( $data ['Member'] ['photo_updated'] ) && $data ['Member'] ['photo_updated'] > 0) {
				$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($data ['Member'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$data ['Member'] ['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			
			// ADDED key for frindship
			$xfriend = $this->Friend->FriendsAllRelationship ( $this->logged ['Member'] ['big'], $data ['Member'] ['big'] );
			$xisFriend = 0;
			$xstatus = 'NO';
			if (count ( $xfriend ) > 0) {
				$xisFriend = 1;
				$data ['Member'] ['friendstatus'] = $xfriend [0] ['Friend'] ['status'];
				$xstatus = $xfriend [0] ['Friend'] ['status'];
			}
			
			if ($xstatus != 'A') {
				$data ['Member'] ['surname'] = mb_substr ( $data ['Member'] ['surname'], 0, 1 ) . '.';
			}
			
			$data ['Member'] ['isFriend'] = $xisFriend;
			
			$xami [0] ['Member'] = $data ['Member'];
			
			$xresponse [] = $xami [0];
			// debug($xresponse);
		}
		// print_r("---".$xresponse);
		$this->_apiOk ( $xresponse );
	}
	public function api_nearby() {
		$this->_checkVars ( array (
				
		), array ('lon',
				'lat' ,
				'sex',
				'age',
				'distance',
				'category' 
		) );
		$memBig = $this->logged ['Member'] ['big'];
	
		

		$coords = '(40.6300568,16.2894573999997)';
		$lon = '16.2894573999999';
		$lat='40.6300568';
		
		
		/*$coords = '(16.2894573999997,40.6300568)';
		 $lon = '40.6300568';
		$lat='16.2894573999999';
		*/
		
		if  (isset($this->api['lon']))
		{
			$lon = isset($this->api['lon']) ? $this->api['lon'] : null;
			$lat = isset($this->api['lat']) ? $this->api['lat'] : null;
			$coords = '(' . $lon . ',' . $lat . ')';
		}
		else
		{  // try
		$params = array (
				'conditions' => array (
						'Member.big' => $this->logged['Member']['big']
				),
				'fields' => array (
						'big',
						'last_lonlat',
						'updated'
				),
				'recursive' => - 1
		);
			
		try {
			$datapos = $this->Member->find ( 'first', $params );
		
		
		} catch ( Exception $e ) {
			$this->_apiEr ( __("Errore") );
		}
			
		if (count($datapos)>0)
		{
			//	debug($datapos['Member']['last_lonlat']);
			if ($datapos['Member']['last_lonlat']!=null)
			{
				$coords = $datapos['Member']['last_lonlat'];
				$xcoords  = str_replace("(", "", $coords);
				$xcoords  = str_replace(")", "", $xcoords);
				$lecoords=split(',',$xcoords);
				$lon=$lecoords[0];
				$lat=$lecoords[1];
				//	debug('a');
			}
		}
			
		}
		
		$sex = isset ( $this->api ['sex'] ) ? $this->api ['sex'] : null; // values are m or f
		$age = isset ( $this->api ['age'] ) ? $this->api ['age'] : null; // values are 0:<25; 1:25-35; 2:35-45; 3:45-55; 4: >55
		$distance = isset ( $this->api ['distance'] ) ? $this->api ['distance'] : null; // values are number=km or over for >100km
		$category = isset ( $this->api ['category'] ) ? $this->api ['category'] : null; // values are id in categories table
		
		$optParams ['sex'] = $sex;
		$optParams ['age'] = $age;
		$optParams ['distance'] = $distance;
		$optParams ['category'] = $category;
		
		// print_r($optParams);
		// Match coords against regular expression ('41.873114', '12.510547')
		$crdsMatch = preg_match ( '/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords );
		if ($crdsMatch == FALSE) {
			$this->_apiEr ( __('Le coordinate non sono valide: lon and/or lat') );
		}
		
		$all_nearby = $this->Checkin->getNearbyCheckinsNew ( $coords, $optParams, $memBig );
		// print_r($all_nearby);
		$xresponse = array ();
		$xami = array ();
		// print_r($all_nearby);
		foreach ( $all_nearby as $ami ) {
			
			$xami [] = $ami;
			
			$params = array (
					'conditions' => array (
							'Member.big' => $ami [0] ['member_big'] 
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
					),
					'recursive' => - 1 
			);
			
			$data = $this->Member->find ( 'first', $params );
			
			if (isset ( $data ['Member'] ['photo_updated'] ) && $data ['Member'] ['photo_updated'] > 0) {
				$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($data ['Member'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$data ['Member'] ['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			// ADDED key for frindship
			$xfriend = $this->Friend->FriendsAllRelationship ( $this->logged ['Member'] ['big'], $data ['Member'] ['big'] );
			$xisFriend = 0;
			$xstatus = 'NO';
			if (count ( $xfriend ) > 0) {
				$xisFriend = 1;
				$data ['Member'] ['friendstatus'] = $xfriend [0] ['Friend'] ['status'];
				$xstatus = $xfriend [0] ['Friend'] ['status'];
			}
			
			if ($xstatus != 'A') {
				$data ['Member'] ['surname'] = mb_substr ( $data ['Member'] ['surname'], 0, 1 ) . '.';
			}
			
			$data ['Member'] ['isFriend'] = $xisFriend;
			
			$xami [0] ['Member'] = $data ['Member'];
			
			$xresponse [] = $xami [0];
			// debug($xresponse);
		}
		// print_r("---".$xresponse);
		$this->_apiOk ( $xresponse );
	}
	public function api_nearbyPeopleOLD() {
		$this->_checkVars ( array (
				'lon',
				'lat' 
		), array (
				'offset' 
		) );
		$memBig = $this->logged ['Member'] ['big'];
		$lon = $this->api ['lon'];
		$lat = $this->api ['lat'];
		$coords = '(' . $lon . ',' . $lat . ')';
		$offset = isset ( $this->api ['offset'] ) ? $this->api ['offset'] * API_MAP_LIMIT : 0;
		
		// Match coords against regular expression ('41.873114', '12.510547')
		$crdsMatch = preg_match ( '/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords );
		if ($crdsMatch == FALSE) {
			$this->_apiEr ( __('Le coordinate non sono valide: lon and/or lat') );
		}
		
		$all_nearby = $this->Checkin->getNearbyPeople ( $coords, $memBig, $offset );
		
		// print_r($all_nearby);
		$xresponse = array ();
		$xami = array ();
		
		foreach ( $all_nearby as $key => &$val ) {
			
			// SECONDS!!
			if (! isset ( $val [0] ['updated'] ) or $val [0] ['updated'] < (date ( "Y-m-d H:i:s" ) - 86400)) {
				// REMOVE
			} else 

			{
				$privacy = true;
				if (! $privacy) {
					// not il list
				} else {
					// COMPLETE DATA AND ADD TO REQUEST!!
					// FIND CHECKIN AND PLACE
					
					// add photo
					
					if ($val [0] ['photo_updated'] > 0) {
						$val [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
					} else {
						// standard image
						$sexpic = 2;
						if ($val [0] ['sex'] == 'f') {
							$sexpic = 3;
						}
						$val [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
					}
					
					// Id prodotti top visibility
					$serviceList = explode ( ",", ID_VISIBILITY_PRODUCTS );
					$activeService = $this->Wallet->hasActiveService ( $serviceList, $val [0] ['big'] );
					$val [0] ['position_bonus'] = $activeService;
					
					$aa = array ();
					
					$val [0] ['Checkin'] = $this->Checkin->getNearbyCheckinsMember ( $val [0] ['big'] );
					
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
					/*
					 * if (isset ( $val[0]['Place'] ['DefaultPhotobig'] ) && $val[0]['Place'] ['DefaultPhotobig'] > 0) { // add URLs to default photos if (isset ( $val[0]['Place'] ['DefaultPhotostatus'] ) && $val ['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos $data [$key] ['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place'] ['big'], $val ['Gallery'] [0] ['big'], $val ['DefaultPhoto'] ['big'], $val ['DefaultPhoto'] ['original_ext'] ); } else { $data [$key] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['category_id'] ); } } else { $data [$key] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['category_id'] ); }
					 */
					
					// if ($activeService>0) {//mette in cima il member con servizi di posizionamento a pagamento e ancora attivi
					// array_unshift($xresponse,$val[0]);
					// }
					// else
					$xresponse [] = $val [0];
				}
			}
		}
		
		usort ( $xresponse, 'CheckinsController::multiFieldSortArray' );
		
		$this->_apiOk ( $xresponse );
	}
	public function api_nearbyPeople() {
		$this->_checkVars ( array (	
		), array ('lon',
				'lat',
				'sex',
				'age',
				'distance',
				'category',
				'offset',
                'name',
                'onlyfriends' 
		) );
        $this->log("----------------api_nearbyPeople-------------");
		$this->log("Variabili ".serialize($this->api));
		$memBig = $this->logged ['Member'] ['big'];
	//	$lon = $this->api ['lon'];
	//	$lat = $this->api ['lat'];
//		$coords = '(' . $lon . ',' . $lat . ')';
		//$offset = isset ( $this->api ['offset'] ) ? $this->api ['offset'] * API_MAP_LIMIT : 0;
		
		
		$coords = '(40.6300568,16.2894573999997)'; 
		$lon = '16.2894573999999';
		$lat='40.6300568';
		
		
		
		if  (isset($this->api['lon']))
		{
			$lon = isset($this->api['lon']) ? $this->api['lon'] : null;
			$lat = isset($this->api['lat']) ? $this->api['lat'] : null;
			$coords = '(' . $lon . ',' . $lat . ')';
		}
		else
		{  // try
		$params = array (
				'conditions' => array (
						'Member.big' => $this->logged['Member']['big']
				),
				'fields' => array (
						'big',
						'last_lonlat',
						'updated'
				),
				'recursive' => - 1
		);
		 
		try {
			$datapos = $this->Member->find ( 'first', $params );
			 
		
		} catch ( Exception $e ) {
			$this->_apiEr ( __("Errore") );
		}
		 
		if (count($datapos)>0)
		{
			//	debug($datapos['Member']['last_lonlat']);
			if ($datapos['Member']['last_lonlat']!=null)
			{
				$coords = $datapos['Member']['last_lonlat'];
				$xcoords  = str_replace("(", "", $coords);
				$xcoords  = str_replace(")", "", $xcoords);
				$lecoords=split(',',$xcoords);
				$lon=$lecoords[1];
				$lat=$lecoords[0];
				//	debug('a');
			}
		}
		 
		}
		
		$offset = isset( $this->api ['offset'] ) ? $this->api ['offset'] : 0;
		$onlyfriends = isset ($this->api['onlyfriends']) ? $this->api['onlyfriends'] : null;
		$sex = isset ( $this->api ['sex'] ) ? $this->api ['sex'] : null; // values are m or f
		$age = isset ( $this->api ['age'] ) ? $this->api ['age'] : null; // values are 0:<25; 1:25-35; 2:35-45; 3:45-55; 4: >55
		$distance = isset ( $this->api ['distance'] ) ? $this->api ['distance'] : null; // values are number=km or over for >100km
		$category = isset ( $this->api ['category'] ) ? $this->api ['category'] : null; // values are id in categories table
		$name = isset ( $this->api ['name'] ) ? strtolower($this->api ['name']) : null;
        
		$optParams ['sex'] = $sex;
		$optParams ['age'] = $age;
		$optParams ['distance'] = $distance;
		$optParams ['category'] = $category;
        $optParams ['name'] = $name;
        $optParams ['onlyfriends'] = $onlyfriends;
		
		// Match coords against regular expression ('41.873114', '12.510547')
		$crdsMatch = preg_match ( '/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords );
		if ($crdsMatch == FALSE) {
			$this->_apiEr ( __('Le coordinate non sono valide: lon and/or lat') );
		}
		$this->log("Var Age ".$age);
		$all_nearby = $this->Checkin->getNearbyPeopleNew ( $coords, $optParams, $memBig, $offset );
		$this->log("parametri filtri ".serialize($optParams));
		//print_r($all_nearby);
		$xresponse = array ();
		$xami = array ();
		
		foreach ( $all_nearby as $key => &$val ) {
						
					// COMPLETE DATA AND ADD TO REQUEST!!
					// FIND CHECKIN AND PLACE
					
					// add photo
					
					if ($val [0] ['photo_updated'] > 0) {
						$val [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
					} else {
						// standard image
						$sexpic = 2;
						if ($val [0] ['sex'] == 'f') {
							$sexpic = 3;
						}
						$val [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
					}
					
					// Id prodotti top visibility
					$serviceList = explode ( ",", ID_VISIBILITY_PRODUCTS );
					$activeService = $this->Wallet->hasActiveService ( $serviceList, $val [0] ['big'] );
					$val [0] ['position_bonus'] = $activeService;
					
					$aa = array ();
					
					$val [0] ['Checkin'] = $this->Checkin->getNearbyCheckinsMember ( $val [0] ['big'], false );
					
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
					
					
					// ADDED key for frindship
					$xfriend = $this->Friend->FriendsAllRelationship ( $this->logged ['Member'] ['big'], $val [0] ['big'] );
					$xisFriend = 0;
					$xstatus = 'NO';
					if (count ( $xfriend ) > 0) {
						$xisFriend = 1;
						$val [0] ['friendstatus'] = $xfriend [0] ['Friend'] ['status'];
						$xstatus = $xfriend [0] ['Friend'] ['status'];
					}
                    
					$removeMember=false;	
					
                    if ($xstatus != 'A') {
                                              
                        $surname=strtolower($val[0]['surname']);
                        
                        if ($name!=null AND strpos($surname,$name)!==false){
                                        $removeMember=true;
                                        
                                        }
                        $val[0]['surname'] = substr ($val[0]['surname'], 0, 1 ).'.'; 
                        } 
									
                    //$val [0]  ['surname'] = substr ( $val [0]  ['surname'], 0, 1 ) . '.';
                    
				$val [0]  ['isFriend'] = $xisFriend;
					
                                        
					/*
					 * if (isset ( $val[0]['Place'] ['DefaultPhotobig'] ) && $val[0]['Place'] ['DefaultPhotobig'] > 0) { // add URLs to default photos if (isset ( $val[0]['Place'] ['DefaultPhotostatus'] ) && $val ['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos $data [$key] ['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place'] ['big'], $val ['Gallery'] [0] ['big'], $val ['DefaultPhoto'] ['big'], $val ['DefaultPhoto'] ['original_ext'] ); } else { $data [$key] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['category_id'] ); } } else { $data [$key] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['category_id'] ); }
					 */
					
					// if ($activeService>0) {//mette in cima il member con servizi di posizionamento a pagamento e ancora attivi
					// array_unshift($xresponse,$val[0]);
					// }
					// else
					if (!$removeMember)
                    $xresponse [] = $val [0];
				
			
		}
		
		usort ( $xresponse, 'CheckinsController::multiFieldSortArray' );
		
		$this->_apiOk ( $xresponse );
	}
    
    
    public function firstCheckin($memberBig){
          //verifica il numero dei checkins fatti dall'utente
          //utile per verificare il primo checkins       
          //return TRUE se primo checkins  
          
          $checkins = $this->Checkin->find('count', array(
                                            'conditions' => array('Checkin.member_big' => $memberBig)
                                                ));
          
          if ($checkins==0) return true; else
                        return false;        
          
        
    }
    
    
    
	public static function multiFieldSortArray($x, $y) { // sort an array by position_bonus DESC and distance ASC
		if ($x ['position_bonus'] == $y ['position_bonus']) {
			
			return ($x ['distance'] < $y ['distance']) ? - 1 : + 1;
		} else
			
			return ($x ['position_bonus'] > $y ['position_bonus']) ? - 1 : + 1;
	}
}


