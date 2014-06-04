<?php

class Event extends AppModel {
	
	public $primaryKey = 'big';
	
	public $belongsTo = array(
		'Place' => array(
			'foreignKey' => 'place_big'
		),
		'DefaultPhoto' => array(
			'className' => 'Photo',
			'conditions' => array(
				'DefaultPhoto.status !=' => DELETED,
			),
		),
	);
	
	public $hasMany = array(
		'Gallery' => array(
			'conditions' => array(
				'Gallery.event_big !=' => null,
			),
		),
		'Checkin',
		'Rating',
	);
	
	/*public $hasAndBelongsToMany = array(
		'Operator',
	);*/
	
	public $validate = array(
		'name' => array(
			'rule' => array('minLength', 2),
			'message' => 'Please fill in place name',
		),
		'slug' => array(
			'rule' => array('minLength', 2),
			'message' => 'Please fill in slug',
		),
		'lonlat' => array(
			'rule' => 'lonlat',
			'message' => 'Please fill in place GPS position',
		),
		'password' => array(
			'rule' => array('minLength', 6),
			'message' => 'Password must be at least 6 characters long',
		),
	);
	
	public function beforeFind($queryData) {
		
		if (!isset($queryData['conditions']['big']) && !isset($queryData['conditions']['Event.big']) && !isset($queryData['conditions']['status']) && !isset($queryData['conditions']['Event.status'])) {
			$queryData['conditions']['Event.status'] = ACTIVE;
		}
		
		return $queryData;
		
	}
	
	public function beforeValidate($options) {
		
		if (isset($this->data['Event']['type']) && $this->data['Event']['type'] == EVENT_TYPE_DEFAULT && isset($this->data['Place']['big'])) {
			//find default event
			$default_event = $this->find('count', array(
				'conditions' => array(
					'Event.place_big' => $this->data['Place']['big'],
					'Event.type' => EVENT_TYPE_DEFAULT,
					'Event.status' => array(ACTIVE, INACTIVE),
					'Event.big !=' => isset($this->data['Event']['big']) ? $this->data['Event']['big'] : null,
				)
			));
			if ($default_event > 0) {
				$this->invalidate('type', __('This place already has a default event'));
			}
		}
		
		return true;
		
	}
	
	public function lonlat($check) {
		
		if (preg_match('/^(.+)\,(.+)$/', $check['lonlat'])) {
			return true;
		} else {
			return false;
		}
		
	}
	
	/**
	 * Returns most popular events for home screen carousel
	 * @return Ambigous <multitype:, NULL, mixed>
	 */
	public function getMostPopularEvents()
	{
		unbindAllBut($this, array('Place', 'DefaultPhoto', 'Gallery'));
		$events = $this->find('all', array(
			'fields' => array(
				'Event.big', 'Event.name', 'Event.place_big', '("Event"."type"=2 and "Event"."status"=0) as "Event__hidden"', 'Event.rating_avg',
				'Place.big', 'Place.name', 'Place.slug',
				'DefaultPhoto.big', 'DefaultPhoto.original_ext', 'DefaultPhoto.gallery_big'
			),
			'conditions' => array(
				'Event.status' => ACTIVE,
				'Event.start_date <=' => 'now',
				'Event.end_date >=' => 'now',
				'Event.daily_start <' => 'now',
				'Event.daily_end >' => 'now',
				'Place.status !=' => DELETED,
			),
			'order' => array('Event.rating_avg' => 'desc'),
			'limit' => 10,
		));
		
		
		return $events;
	}
	
	/**
	 * Return default event for default place, creates it if it does not exit
	 * @param int $place_big
	 * @param bool $select_photo
	 */
	public function getDefault($place_big, $select_photo=false) {
		
		$exceptions = array();
		if ($select_photo) {
			$exceptions = array('Gallery', 'DefaultPhoto');
		}
		
		unbindAllBut($this, $exceptions);
		
		$event = $this->find('first', array(
			'conditions' => array(
				'Event.place_big' => $place_big,
				'Event.type' => EVENT_TYPE_DEFAULT,
				'Event.status' => array(ACTIVE, INACTIVE),
			),
		));
		
		if ($event == false) {
			
			$event = array(
				'Event' => array(
					'name' => __('Default'),
					'slug' => 'default',
					'place_big' => $place_big,
					'type' => EVENT_TYPE_DEFAULT,
					'status' => INACTIVE,
					'created' => date('Y-m-d H:i:s'),	//DboSource::expression('now()'),
				),
			);
			$this->create();
			$this->save($event['Event']);
			$event['Event']['big'] = $this->id;
			
		}
		
		return $event;
		
	}
	
	public function getAttendedEventsForMember($memBig)
	{
		$db = $this->getDataSource();
		$query = 'SELECT 
			  events.name AS "Event__name", 
			  events.slug AS "Event__slug", 
			  events.big AS "Event__big", 
			  places.big AS "Place__big", 
			  places.name AS "Place__name",
			  places.slug AS "Place__slug",
			  places.category_id AS "Place__category_id",  
			  (events.type = 2 AND events.status = 0) AS "Event__hidden",
			  (CASE WHEN events.type = 2 AND events.status = 0 THEN pp.big ELSE ep.big END) AS "Photo__big", 
			  (CASE WHEN events.type = 2 AND events.status = 0 THEN pp.gallery_big ELSE ep.gallery_big END) AS "Photo__gallery_big", 
			  (CASE WHEN events.type = 2 AND events.status = 0 THEN pp.original_ext ELSE ep.original_ext END) AS "Photo__original_ext"
			FROM events
			INNER JOIN (SELECT DISTINCT(event_big) as diseve FROM checkins WHERE member_big = ?) AS chcks ON (events.big = chcks.diseve) 
			INNER JOIN places ON (events.place_big = places.big)
			LEFT JOIN photos AS ep ON (events.default_photo_big = ep.big) 
			LEFT JOIN photos AS pp ON (places.default_photo_big = pp.big)';
		
		 $events = $db->fetchAll($query, array($memBig));
		 return $events;
	}

	public function getCurrentMembers($event_big, $place_big=0, $exclude_member = null) {

		$conditions = array(
			'Checkin.created <' => date('c'),
			'or' => array(
				'Checkin.checkout' => null,
				'Checkin.checkout >' => date('c'),
			),
		);

		if ($event_big > 0) {
			$conditions['Event.big'] = $event_big;
		} else {
			$conditions['Event.place_big'] = $place_big;
		}
		
		if (isset($exclude_member))
		{
			$conditions['Member.big !='] = $exclude_member;
		}

		unbindAllBut($this->Checkin, array('Event', 'Member'));
		$members = $this->Checkin->find('all', array(
			'conditions' => $conditions,
			'fields' => array('Checkin.*', 'Member.*', 'Event.*'),
		));

		return $members;

	}
	
	public function getEventsWithUserPhotoForPlace($place_big)
	{
		
		$db = $this->getDataSource();
		$query = 'SELECT
			  photos.big AS "Photo__big", photos.original_ext AS "Photo__original_ext", photos.gallery_big AS "Photo__gallery_big",  
			  events.name AS "Event__name", 
			  events.slug AS "Event__slug", 
			  events.big AS "Event__big", 
			  (events.type = 2 AND events.status = 0) AS "Event__hidden"
			FROM events
			LEFT JOIN galleries ON (events.big = galleries.event_big) AND galleries.type = '. GALLERY_TYPE_USERS . ' 
			LEFT JOIN photos ON (galleries.big = photos.gallery_big) AND photos.created = (SELECT MAX(created) FROM photos WHERE gallery_big = galleries.big)
			WHERE events.place_big = ? AND photos.status < 255';
				
		 $events = $db->fetchAll($query, array($place_big));
		 return $events;
		
	}
	
}