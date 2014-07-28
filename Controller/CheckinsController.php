<?php

class CheckinsController extends AppController {

	public $uses = array (
			'Member',
			'Checkin',
			'Friend'
			
	);
	
	
	/**
	 * Return list of people available to chat
	 */
	public function api_people() {


		$eventBig = $this->Checkin->getCheckedinEventFor($this->logged['Member']['big']);
		if (empty($eventBig)) {
			$this->_apiEr('Error occured. No valid checkin found.', 'You have not joined or checked in anywhere.');
		}

		$result = $this->Checkin->getMemberAvailableToChat($eventBig, $this->logged['Member']['big']);

		foreach($result as $key=>$val) {
			if ($val['Member']['photo_updated'] > 0) {
				$result[$key]['Member']['photo'] = $this->FileUrl->profile_picture($val['Member']['big'], $val['Member']['photo_updated']);
			}else {
			$sexpic=2;
			if($val ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$result[$key] ['Member']['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			
		}
			unset($result[$key]['Member']['photo_updated']);
			
			// ADDED key for frindship
			$Amici = $this->Friend->FriendsRelationship ( $this->logged['Member']['big'], $result[$key]['Member']['big'], 'A' );
			$result[$key]['Member']['isFriend']=count ( $Amici );
			
		}
		$this->Util->transform_name($result);
		$this->_apiOk(array('members' => $result));

	}

	/**
	 * Legacy method name - the same functionality as /checkins/in
	 */
	public function api_try() {
		$this->api_in();
	}

	/**
	 * Checkin or join an event
	 * Member can checkin/join based on event_big or place_big
	 * In case of checkin/join using place_big, we will use default event of the place (or create one)
	 */
	public function api_in() {

		$this->_checkVars(
			array(
				'physical'		//1 = check in (member has physically visited the place),
								//0 = join (joined event without visiting it, for example from website)
			),
			array(
				'event_big',	//BIG of the event
				'place_big',	//BIG of the place
				'lon', 	//GPS position (longitude), only required on checkin (physical presence at event)
				'lat'	//GPS position (latitude), only required on checkin (physical presence at event)
			)
		);

		$physical = intval($this->api['physical']);
		$placeBig = isset($this->api['place_big']) && !empty($this->api['place_big']) ? $this->api['place_big'] : null;
		$eventBig = isset($this->api['event_big']) && !empty($this->api['event_big']) ? $this->api['event_big'] : null;

		CakeLog::info('Called checkin/in with data: Physical = ' . $physical . ' PlcBig = ' . $placeBig . ' EvntBig = ' . $eventBig);

		//event BIG not specified - checkin via place BIG, find (or create) default event
		if ($eventBig == null) {

			if ($placeBig == null) {

				CakeLog::error('Error occured. Missing place_big and event_big.');

				$this->_apiEr('The following required API variables are missing: place_big and/or event_big');
			}

			$event = $this->Checkin->Event->Place->getCurrentEvent($placeBig);	//get place current event
			$eventBig = $event['Event']['big'];

		}

		//checkin data
		$checkin = array(
			'member_big'	=> $this->logged['Member']['big'],
			'event_big'		=> $eventBig,
			'physical'		=> $physical,
			'type'			=> 1,
			'created'		=> date('Y-m-d H:i:s'),
		);

		//check in (physical presence at event)
		if ($physical) {

			$lon = isset($this->api['lon']) ? $this->api['lon'] : '';
			$lat = isset($this->api['lat']) ? $this->api['lat'] : '';

			CakeLog::info('Called checkin/in with lon lat: Lon = ' . $lon . ' Lat = ' . $lat);

			if (empty($lon) || empty($lat)) {	//GPS position is required on checkin
				$this->_apiEr('The following required API variables are missing: lon and/or lat');
			}

			$coords = '(' . $lon . ',' . $lat . ')';

			// Match coords against regular expression
			if (!preg_match('/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords)) {

				CakeLog::error('Error occured. Invalid coords.');

				$this->_apiEr('The following API variables are invalid: lon and/or lat');
			}

			$checkin['lonlat'] = $coords;

		}

		CakeLog::info('Before check if user is already checked in');

		// Check if user is already checked in
		if ($this->Checkin->hasJoinedOrCheckedIn($this->logged['Member']['big'], $eventBig, $physical)) {

			CakeLog::error('Already checked in or joined');

			$this->_apiEr('You already ' . ($physical ? 'checked in at' : 'joined') . ' this event.', true);
		}

		//save checkin
		$this->Checkin->set($checkin);
		try {
			$result = $this->Checkin->save();
		}
		catch (Exception $e) {

			CakeLog::error('Error occured. Checkin failed. Probably non-existent event.');

			$this->_apiEr('Error occured. Checkin failed. Probably non-existent event.');
		}

		CakeLog::info('Before checkout. CheckinId = ' . $this->Checkin->id . ' MemBig = ' . $this->logged['Member']['big']);

		//check out all other check-ins / joins
		$this->Checkin->out($this->logged['Member']['big'], $this->Checkin->id);

		CakeLog::info('After checkout.');

		if (empty($result)) {

			CakeLog::error('Error occured. Check in procedure failed.');

			$this->_apiEr('Error occured. Check in procedure failed.', 'We\'re sorry, but a problem occured during checkin.');
		} else {

			CakeLog::info(' ------------------------ Checkin end -----------------------');

			//$this->ChatCache->write($this->logged['Member']['big'].'_checkin_event_big', $result['Checkin']['big']);
			//$this->ChatCache->append($result['Checkin']['big'].'_members', ','.$this->logged['Member']['big']);

			$this->_apiOk(array(
				'Checkin' => array(
					'big' 		=> $result['Checkin']['big'],
					'physical' 	=> $result['Checkin']['physical'],
					'created' 	=> $result['Checkin']['created'],
				),
				'Event' => array(
					'big' => $result['Checkin']['event_big'],
				),
			));
		}

	}

	/**
	 * Checkout from an event
	 * We checkout from all events, because member cannot be checked at more than 1 events at a time
	 */
	public function api_out() {

		//check out all other check-ins / joins
		$checkedOut = $this->Checkin->out($this->logged['Member']['big']);

		$this->_apiOk(array('checkout' => $checkedOut));

	}

	public function api_autocheckout() {

		$this->_checkVars(array('lon', 'lat'));

		$memBig = $this->logged['Member']['big'];
		$lon = isset($this->api['lon']) ? $this->api['lon'] : null;
		$lat = isset($this->api['lat']) ? $this->api['lat'] : null;
		$coords = '(' . $lon . ',' . $lat . ')';

		// Match coords against regular expression
		$crdsMatch = preg_match('/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords);
		if ($crdsMatch == FALSE) {
			$this->_apiEr('The following API variables are invalid: lon and/or lat');
		}

		$checkinData = $this->Checkin->getCheckedinEventFor($memBig, true);
//		debug($checkinData);
		if (empty($checkinData) || (isset($checkinData) && $checkinData['Checkin']['physical'] != 1)) {
			$this->_apiEr('No valid checkin found', null, null, array('error_code' => '501'));
		}
		$checkin = $checkinData['Checkin'];
//		debug($checkin);
		$isInRadius = $this->Checkin->Event->Place->isUserNearby($checkin['event_big'], $coords);
//		debug($isInRadius);

		$msg = '';
		if ($isInRadius == FALSE && empty($checkin['checkout']))
		{
			$acodate = date('Y-m-d H:i', strtotime('+ ' . AUTOCHECKOUT_LIMIT . ' minutes'));
			$checkin['checkout'] = $acodate;
			$msg .= 'Out of radius. Automatic checkout set to ' . $acodate . '.' ;
		}
		elseif ($isInRadius && !empty($checkin['checkout']))
		{
			$checkin['checkout'] = null;
			$msg .= 'Inside radius. Automatic checkout set to null.' ;
		}
		else
		{
			$msg .= 'Current autocheckout value is set to ' . (!empty($checkin['checkout']) ? $checkin['checkout'] : 'null') . '.';
		}

		$this->Checkin->set($checkin);
		$this->Checkin->save();

		$this->_apiOk(
			array(
				'detail_msg' => $msg,
				'checkout_dt' => $checkin['checkout'],
				'in_radius' => $isInRadius ? 1 : 0
			)
		);
	}

	public function api_last_visited()
	{
		$this->_checkVars(array('user_big'), array('offset'));

		// Variables
		$user_big = $this->api['user_big'];
		$offset = isset($this->api['offset']) ? $this->api['offset'] : 0 ;

		//find latest checkins
		$event_bigs = $this->Checkin->find('list', array(
			'conditions' => array(
				'Checkin.member_big' => $user_big,
				'Checkin.physical ' => 1,
				'OR' => array(
					'Checkin.checkout !=' => null,
					'Checkin.checkout <' => 'NOW()',
				)
			),
			'fields' => array('Checkin.event_big', 'Checkin.event_big'),//'MAX(Checkin.created) as "Checkin__created"'),
			'order' => array('MAX(Checkin.created)' => 'desc'),
			'group' => array('Checkin.event_big'),
			'recursive' => -1,
		));

		//events for latest checkins
		unbindAllBut($this->Checkin->Event, array('Place', 'DefaultPhoto', 'Gallery'));
		$checkins = $this->Checkin->Event->find('all', array(
			'conditions' => array(
				'Event.big' => $event_bigs,
				'Place.status !=' => DELETED
			),
			'fields' => array(
				'Event.big', 'Event.name', '("Event"."type"=2 and "Event"."status"=0) as "Event__hidden"', 'Event.rating_avg',
				'Place.big', 'Place.name',
				'DefaultPhoto.big', 'DefaultPhoto.original_ext',
			),
			'limit' => API_PER_PAGE,
			'offset' => $offset * API_PER_PAGE
		));

		// Get places
		$place_bigs = array();
		foreach(array_merge($checkins) as $checkin) {
			$place_bigs[] = $checkin['Place']['big'];
		}
		unbindAllBut($this->Checkin->Event->Place, array('DefaultPhoto', 'Gallery', 'Region'));
		$places = $this->Checkin->Event->Place->find('all', array(
			'conditions' => array(
				'Place.big' => $place_bigs,
			),
			'fields' => array(
				'Place.big', 'Place.name', 'Place.category_id', 'Place.rating_avg', 'Place.address_street', 'Place.address_street_no',
				'DefaultPhoto.big', 'DefaultPhoto.original_ext',
				'"Region"."city" AS "Place__city"'
			),
		));
		$places = $this->_addPlacePhotoUrls($places);
		$places = Set::combine($places, '{n}.Place.big', '{n}.Place');

		$checkins = $this->_addEventPhotoUrls($checkins);

		//add places with photos to last checkin events
		foreach($checkins as $key=>$checkin) {
			$checkins[$key]['Place'] = $places[ $checkin['Place']['big'] ];
		}

		$result = array('events' => $checkins);

		// Count
		if ($offset == 0)
		{
			$pars = array(
				'conditions' => array(
					'Checkin.member_big' => $user_big,
					'Checkin.physical' => 1,
					'OR' => array(
						'Checkin.checkout !=' => null,
						'Checkin.checkout <' => 'NOW()',
					)
				),
				'group' => array('Checkin.event_big'),
			);

			$count = $this->Checkin->find('count', $pars);

			$result['events_count'] = $count;
		}

		$this->_apiOk($result);

	}

	/**
	 * check in via frontend website
	 */
	public function in($eventBig=0, $placeBig=0) {

		//event BIG not specified - checkin via place BIG, find (or create) default event
		if ($eventBig == 0) {

			if ($placeBig == 0) {
				$this->Session->setFlash(__('No place or event specified for join.'), 'flash/error');
				$this->redirect('/');
			}

			$event = $this->Checkin->Event->Place->getCurrentEvent($placeBig);	//get place current event
			$eventBig = $event['Event']['big'];

		}
		$this->logged = $this->Member->findByBig( $this->Auth->user('big') );//don't understand why it's not already filled

		$this->Checkin->set(array(
			'member_big'	=> $this->logged['Member']['big'],
			'event_big'		=> $eventBig,
			'physical'		=> 0,
			'type'			=> 1,
			'created'		=> date('Y-m-d H:i:s'),
		));
		try {
			$result = $this->Checkin->save();
		}
		catch (Exception $e) {
			$this->Session->setFlash(__('Join failed. Event or place does not exist.'), 'flash/error');
//			$this->redirect('/');
		}

		$this->Checkin->out($this->logged['Member']['big'], $this->Checkin->id);


		$this->Checkin->Event->recursive = -1;
		$event = $this->Checkin->Event->findByBig($eventBig);

		if ($event['Event']['type'] != EVENT_TYPE_DEFAULT) {

			$this->Session->setFlash(__('You have joined an event %s', $event['Event']['name']), 'flash/success');


		} else {

			$this->Checkin->Event->Place->recursive = -1;
			$place = $this->Checkin->Event->Place->findByBig($event['Event']['place_big']);

			$this->Session->setFlash(__('You have joined an event at place %s', $place['Place']['name']), 'flash/success');
			//return $this->redirect(array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']));

		}

		return $this->redirect(array('controller' => 'events', 'action' => 'detail', $eventBig, $event['Event']['slug']));

	}

	/**
	 * check out via frontend website
	 */
	public function out() {

		//check out all other check-ins / joins
		$checkedOut = $this->Checkin->out($this->logged['Member']['big']);

		if ($checkedOut) {
			$this->Session->setFlash(__('You were succesfully checked out'), 'flash/success');
		} else {
			$this->Session->setFlash(__('You are not checked in at the moment'), 'flash/error');
		}

		$this->redirect('/');	//array('controller' => '', 'action' => ''));

	}
	
	/**
	 * Get nearby checkedin members
	 * We checkout from all events, because member cannot be checked at more than 1 events at a time
	 */
	public function api_nearby() {

		$this->_checkVars(array('lon', 'lat'));
		$memBig = $this->logged['Member']['big'];
		$lon = $this->api['lon'];
		$lat = $this->api['lat'];
		$coords = '(' . $lon . ',' . $lat . ')';

		// Match coords against regular expression ('41.873114', '12.510547')
		$crdsMatch = preg_match('/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords);
		if ($crdsMatch == FALSE) {
			$this->_apiEr('The following API variables are invalid: lon and/or lat');
		}

		$all_nearby = $this->Checkin->getNearbyCheckins($coords);
		
		$xresponse = array ();
		$xami = array ();
		
		foreach ( $all_nearby as $ami ) {

			die(debug($ami));
				
			$xami [] = $ami ;
			
				$params = array (
						'conditions' => array (
								'Member.big' => $ami[0]['member_big']
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
				
			if (isset ( $data['Member']['photo_updated'] ) && $data['Member']['photo_updated'] > 0) {
				$data['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data['Member']['big'], $data['Member']['photo_updated'] );
			} else {
			$sexpic=2;
			if($data ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$data ['Member']['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			
		}
				
			
			$xami[0]['Member']= $data['Member'];
				
			$xresponse [] = $xami [0];
			/*debug($xresponse);
		  
				 */
		}
		
		$this->_apiOk($xresponse) ;
	}
	
	

	public function api_nearbyPeople() {
	
		$this->_checkVars(array('lon', 'lat'),
			array(
				'offset')
			);
		$memBig = $this->logged['Member']['big'];
		$lon = $this->api['lon'];
		$lat = $this->api['lat'];
		$coords = '(' . $lon . ',' . $lat . ')';
		$offset = isset($this->api['offset']) ? $this->api['offset'] * API_MAP_LIMIT : 0;
		
	
		// Match coords against regular expression ('41.873114', '12.510547')
		$crdsMatch = preg_match('/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords);
		if ($crdsMatch == FALSE) {
			$this->_apiEr('The following API variables are invalid: lon and/or lat');
		}
	
	
		
		$all_nearby = $this->Checkin->getNearbyPeople($coords,$memBig,$offset);
		
	
		$xresponse = array ();
		$xami = array ();
	
		foreach ( $all_nearby as $key => &$val ) {
		
	
		// SECONDS!!
		if (!isset($val[0]['updated']) or $val[0]['updated']<(date("Y-m-d H:i:s")-86400)   )
		{
			// REMOVE
			
		}
		else 
			
		{
			$privacy=true;
			if (! $privacy)
			{
				//not il list
			}
			else 
			{
				// COMPLETE DATA AND ADD TO REQUEST!!
				//FIND CHECKIN AND PLACE
				
				// add photo
			
				if ($val[0]['photo_updated'] > 0) {
					$val[0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val[0]['big'], $val[0]['photo_updated'] );
				}
				else
				{
					// standard image
					$sexpic=2;
					if($val[0]['sex']=='f' )
					{
						$sexpic=3;
					}
					$val[0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			
				}
				
				$aa=array();
		        $allx=false;
				$val[0]['Checkin']=$this->Checkin->getNearbyCheckinsMember($val[0]['big'],$allx);
			
				if (isset ( $val[0]['Checkin'][0]['Place']['DefaultPhoto'] ['big'] ) && $val[0]['Checkin'][0] ['DefaultPhoto'] ['big'] > 0) { // add URLs to default photos
					if (isset ( $val[0]['Checkin'][0] ['DefaultPhoto'] ['status'] ) && $val[0]['Checkin'][0] ['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos
						 $val[0]['Checkin'][0]['Place'] ['photo'] = $this->FileUrl->place_photo ( $val[0]['Checkin'][0] ['Place'] ['big'], $val[0]['Checkin'][0] ['Gallery'] [0] ['big'], $val[0]['Checkin'][0] ['DefaultPhoto'] ['big'], $val[0]['Checkin'][0] ['DefaultPhoto'] ['original_ext'] );
					} else {
						 $val[0]['Checkin'][0] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val[0]['Checkin'][0] ['Place'] ['category_id'] );
					}
				} else {
				
					 $val[0]['Checkin'][0] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val[0]['Checkin'][0] ['Place'] ['category_id'] );
				}
				
				unset($val [0] ['Checkin'][0] ['DefaultPhoto']);
				unset($val [0] ['Checkin'][0] ['Gallery']);
			/*		
				if (isset ( $val[0]['Place'] ['DefaultPhotobig'] ) && $val[0]['Place'] ['DefaultPhotobig'] > 0) { // add URLs to default photos
					if (isset ( $val[0]['Place'] ['DefaultPhotostatus'] ) && $val ['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos
						$data [$key] ['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place'] ['big'], $val ['Gallery'] [0] ['big'], $val ['DefaultPhoto'] ['big'], $val ['DefaultPhoto'] ['original_ext'] );
					} else {
						$data [$key] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['category_id'] );
					}
				} else {
					$data [$key] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['category_id'] );
				}
				
*/				
				$xresponse[]=$val[0];
				
						}
				
						}
			
		}
	
		$this->_apiOk($xresponse) ;
	}
	
	
}


