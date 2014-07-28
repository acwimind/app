<?php

class HomeController extends AppController {
	
	var $uses = array('Checkin', 'Event', 'MemberRel', 'Bookmark', 'Region');
	
	public function api_show() {
		
		$event_big = $this->Checkin->getCheckedinEventFor($this->logged['Member']['big']);	// Is checked in?
		
		if (!empty($event_big)) {	//checked in -> show place detail
			
			return $this->_homeEvent($event_big);
			
		} else {	//not checked in
			
			return $this->_homeDefault();
			
		}
		
	}
	
	private function _homeDefault() {
		
		//find latest checkins
		$event_bigs = $this->Checkin->find('list', array(
			'conditions' => array(
				'Checkin.member_big' => $this->logged['Member']['big'],
				'Checkin.created !=' => null,
				'Checkin.checkout !=' => null,
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
				'Event.big', 'Event.name', 'Event.place_big', '("Event"."type"=2 and "Event"."status"=0) as "Event__hidden"', 'Event.rating_avg',
				'Place.big', 'Place.name','Place.address_street','Place.address_street_no','Place.address_town',
				'DefaultPhoto.big', 'DefaultPhoto.original_ext',
			),
			'limit' => 10,
		));
		
		//most popular events
		{
			$events = $this->Event->getMostPopularEvents();
			$events = $this->_addEventPhotoUrls($events);
		}
		
		//find places with photos
		{
			$place_bigs = array();
			foreach(array_merge($checkins, $events) as $key=>$checkin) {
				$place_bigs[] = $checkin['Place']['big'];
			}
			unbindAllBut($this->Checkin->Event->Place, array('DefaultPhoto', 'Gallery'));
			$places = $this->Checkin->Event->Place->find('all', array(
				'conditions' => array(
					'Place.big' => $place_bigs,
				),
				'fields' => array(
					'Place.big', 'Place.name', 'Place.category_id', 'Place.rating_avg','Place.address_street','Place.address_street_no','Place.address_town',
					'DefaultPhoto.big', 'DefaultPhoto.original_ext',
				),
			));
			$places = $this->_addPlacePhotoUrls($places);
			$places = Set::combine($places, '{n}.Place.big', '{n}.Place');
		}
		
		//add places with photos to most popular events
		foreach($events as $key=>$event) {
			$events[$key]['Place'] = $places[ $event['Place']['big'] ];
		}
		
		$this->_apiOk(array('events' => $events));
		
		if (! empty ( $checkins )) { // we have checkins
			
			$checkins = $this->_addEventPhotoUrls ( $checkins );
			
			// add places with photos to last checkin events
			foreach ( $checkins as $key => $checkin ) {
				$checkins [$key] ['Place'] = $places [$checkin ['Place'] ['big']];
			}
			
			$this->_apiOk ( array (
					'checkins' => $checkins 
			) );
			
			$conversations = $this->MemberRel->findConversations ( $this->logged ['Member'] ['big'] );
			$this->Util->transform_name ( $conversations );
			
			// Add photos
			$result = $conversations ['conversations'];
			foreach ( $result as &$val ) {
				// Sender
				if ($val ['Sender'] ['photo_updated'] > 0) {
					$val ['Sender'] ['photo'] = $this->FileUrl->profile_picture ( $val ['Sender'] ['big'], $val ['Sender'] ['photo_updated'] );
				} else {
					$sexpic = 2;
					if ($val ['Sender'] ['sex'] == 'f') {
						$sexpic = 3;
					}
					
					$val ['Sender'] ['photo'] = $this->FileUrl->profile_picture ( $sexpic );
				}
				unset($val['Sender']['photo_updated']);
				//Recipient
				if ($val['Recipient']['photo_updated'] > 0) {
					$val['Recipient']['photo'] = $this->FileUrl->profile_picture($val['Recipient']['big'], $val['Recipient']['photo_updated']);
				}  else  {
			$sexpic=2;
			if($val['Recipient']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$val['Recipient']['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			
		}
				unset($val['Recipient']['photo_updated']);
			}
			$conversations['conversations'] = $result;
			
			if (!empty($conversations)) {
				$this->_apiOk($conversations);
			}
			$this->_apiOk(array('screen_type' => HOME_MATURE_USER));
		
		} else {	//no checkins - show popular events
			
			$this->_apiOk(array('screen_type' => HOME_FIRST_LOGIN));
			
		}
		
		return true;
		
	}
	
	/**
	 * Display home screen for checkin/joined users (event/place detail)
	 * @param int $event_big
	 * @return boolean
	 */
	private function _homeEvent($event_big) {
		
		unbindAllBut($this->Event, array('Place', 'Gallery', 'Photo', 'DefaultPhoto', 'Region'));
		$this->Event->belongsTo += array(
			'Checkin' => array(
				'conditions' => array(
					'Checkin.event_big = Event.big',
					'Checkin.member_big' => $this->logged['Member']['big'],
					'Checkin.checkout' => null,
				),
			),
		);
		$event = $this->Event->find('first', array(
			'conditions' => array('Event.big' => $event_big),
			'fields' => array(
				'Event.big', 'Event.name', '("Event"."type"=2 and "Event"."status"=0) as "Event__hidden"', 'Event.rating_avg',
				'Place.big', 'Place.name','Place.address_street','Place.address_street_no','Place.address_town', 'Place.rating_avg',
				'DefaultPhoto.big', 'DefaultPhoto.original_ext',
				'Checkin.created', 'Checkin.physical'
			),
		));
		$gallery_big = isset($event['Gallery'][0]['big']) ? $event['Gallery'][0]['big'] : null;
		$event = $this->_addEventPhotoUrls($event);
		
		$place = $this->Event->Place->find('first', array(
			'conditions' => array('Place.big' => $event['Place']['big']),
			'fields' => array(
				'Place.big', 'Place.name', 'Place.category_id', 'Place.rating_avg','Place.address_street','Place.address_street_no','Place.address_town',
				'Region.city', 'Region.country',
				'DefaultPhoto.big', 'DefaultPhoto.original_ext',
			),
		));
		$place = $this->_addPlacePhotoUrls($place);
		$event['Place'] = $place['Place'];
		$event['Region'] = $place['Region'];
		
		if (isset($gallery_big)) {
			$event['Gallery']['count'] = $this->Event->Gallery->Photo->find('count', array(
				'conditions' => array('Photo.gallery_big' => $gallery_big),
				'recursive' => -1,
			));
		} else {
			$event['Gallery']['count'] = 0;
		}
		
		$memBig =  $this->logged['Member']['big'];
		$event['Event']['checkins_count'] = $this->Checkin->getCheckinsCountFor($event_big, $memBig);
		$event['Event']['joins_count'] = $this->Checkin->getJoinsCountFor($event_big, $memBig);
		
		$this->_apiOk($event);
		
		
		
		// ADDED CHECKINS !!!!!
		
		//find latest checkins
		$event_bigs = $this->Checkin->find('list', array(
				'conditions' => array(
						'Checkin.member_big' => $this->logged['Member']['big'],
						'Checkin.created !=' => null,
						'Checkin.checkout !=' => null,
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
						'Event.big', 'Event.name', 'Event.place_big', '("Event"."type"=2 and "Event"."status"=0) as "Event__hidden"', 'Event.rating_avg',
						'Place.big', 'Place.name','Place.address_street','Place.address_street_no','Place.address_town',
						'DefaultPhoto.big', 'DefaultPhoto.original_ext',
				),
				'limit' => 10,
		));
		
		if (! empty ( $checkins )) { // we have checkins
				
			$checkins = $this->_addEventPhotoUrls ( $checkins );
				
			// add places with photos to last checkin events
			foreach ( $checkins as $key => $checkin ) {
				$checkins [$key] ['Place'] = $places [$checkin ['Place'] ['big']];
			}
				
			$this->_apiOk ( array (
					'checkins' => $checkins
			) );
		
		}
		$this->_apiOk(array('screen_type' => HOME_CHECKED_IN));
		return true;
		
	}
	
	public function index() {
		
		
		
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		switch ($lang){
			case "fr":
				//echo "PAGE FR";
				include("index_fr.php");//include check session FR
				break;
			case "it":
				//echo "PAGE IT";
				$this->Session->write('Config.language', 'ita');
				break;
			case "en":
				//echo "PAGE EN";
				$this->Session->write('Config.language', 'eng');
				break;
			default:
				//echo "PAGE EN - Setting Default";
				$this->Session->write('Config.language', 'eng');
				break;
		}
		
		debug($lang);
		$this->_sidebarPlaces();	//places for right sidebar
		
		//find latest checkins
		$event_bigs = $this->Checkin->find('list', array(
			'conditions' => array(
				'Checkin.member_big' => $this->logged['Member']['big'],
				'Checkin.created !=' => null,
				'Checkin.checkout !=' => null,
			),
			'fields' => array('Checkin.event_big', 'Checkin.event_big'),//'MAX(Checkin.created) as "Checkin__created"'),
			'order' => array('MAX(Checkin.created)' => 'desc'),
			'group' => array('Checkin.event_big'),
			'recursive' => -1,
		));
		
		if (!empty($event_bigs)) {
			
			$this->indexMatureUser();
			return $this->render('index');
			
		} else {
			
			$this->indexFirstLogin();
			return $this->render('index_first_login');
			
		}
		
	}
	
	private function indexMatureUser() {
		
		//find latest checkins
		$event_bigs = $this->Checkin->find('list', array(
			'conditions' => array(
				'Checkin.member_big' => $this->logged['Member']['big'],
				'Checkin.created !=' => null,
				'Checkin.checkout !=' => null,
			),
			'fields' => array('Checkin.event_big', 'Checkin.event_big'),//'MAX(Checkin.created) as "Checkin__created"'),
			'order' => array('MAX(Checkin.created)' => 'desc'),
			'group' => array('Checkin.event_big'),
			'recursive' => -1,
		));
		
		//events for latest checkins
		{
			unbindAllBut($this->Checkin->Event, array('Place', 'DefaultPhoto', 'Gallery'));
			unbindAllBut($this->Checkin->Event->Place, array('DefaultPhoto', 'Gallery'));
			unbindAllBut($this->Checkin->Event->DefaultPhoto, array());
			unbindAllBut($this->Checkin->Event->Gallery, array());
			$events = $this->Checkin->Event->find('all', array(
				'conditions' => array(
					'Event.big' => $event_bigs,
					'Event.type !=' => EVENT_TYPE_DEFAULT,
					'Event.status !=' => INACTIVE,
				),
				'fields' => array(
					'Event.big', 'Event.name', 'Event.slug', 'Event.short_desc', 'Event.place_big',
					'Event.rating_avg',
					'Place.big', 'Place.name', 'Place.category_id', 'Place.slug',
					'DefaultPhoto.big', 'DefaultPhoto.gallery_big', 'DefaultPhoto.original_ext',
				),
				'limit' => HOME_LIST_LIMIT,
				'recursive' => 2,
			));
			$this->set('last_events', $events);
		}
		
		//places for latest checkins
		{
			$db = $this->Checkin->getDatasource();
			$query = 'SELECT "Event"."big" AS "Event__big", "Event"."name" AS "Event__name", "Event"."slug" AS "Event__slug", "Event"."short_desc" AS "Event__short_desc", "Event"."place_big" AS "Event__place_big", "Event"."rating_avg" AS "Event__rating_avg", 
					"Place"."big" AS "Place__big", "Place"."name" AS "Place__name", "Place"."category_id" AS "Place__category_id", "Place"."slug" AS "Place__slug",
					"Place"."address_street" AS "Place__address_street", "Place"."address_street_no" AS "Place__address_street_no", "Place"."rating_avg" AS "Place__rating_avg", 
					"DefaultPhoto"."big" AS "DefaultPhoto__big", "DefaultPhoto"."gallery_big" AS "DefaultPhoto__gallery_big", "DefaultPhoto"."original_ext" AS "DefaultPhoto__original_ext", 
					"Region"."city" AS "Region__city" 
				FROM "events" AS "Event" 
				LEFT JOIN "places" AS "Place" ON ("Event"."place_big" = "Place"."big") 
				LEFT JOIN regions as "Region" ON ("Place"."region_id" = "Region"."id")
				LEFT JOIN "photos" AS "DefaultPhoto" ON ("Event"."default_photo_big" = "DefaultPhoto"."big" AND "DefaultPhoto"."status" != 255) 
				WHERE "Event"."big" IN (' . implode(',', $event_bigs) . ') 
				LIMIT ' . HOME_LIST_LIMIT;

			$places = $db->fetchAll($query);
			$this->set('last_places', $places);
			
//			unbindAllBut($this->Checkin->Event->Place, array('DefaultPhoto','Region'));
//			$places = $this->Checkin->Event->Place->find('all', array(
//				'conditions' => array(
//					'Place.big' => $place_bigs,
//				),
//				'fields' => array(
//					'Place.big', 'Place.name', 'Place.slug', 'Place.category_id', 'Place.address_street', 'Place.address_street_no', 'Place.address_town',
//					'Place.rating_avg',
//					'DefaultPhoto.big', 'DefaultPhoto.gallery_big', 'DefaultPhoto.original_ext',
//					'Region.*'
//				),
//				'limit' => 10,
//			));
//			$this->set('last_places', $places);
		}
		
		//bookmarked places
		{
			unbindAllBut($this->Bookmark, array('Place'));
			unbindAllBut($this->Bookmark->Place, array('DefaultPhoto', 'Region'));
			$bookmarks = $this->Bookmark->find('all', array(
				'conditions' => array(
					'Bookmark.member_big' => $this->logged['Member']['big'],
				),
				'recursive' => 2,
				'limit' => HOME_LIST_LIMIT,
			));
			$this->set('bookmarks', $bookmarks);
		}
		
	}
	
	private function indexFirstLogin() {
		
		$places = $this->Event->Place->getMostPopularEvents();
		$this->set('popular_places', $places);
		
	}
	
}