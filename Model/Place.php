<?php
class Place extends AppModel {
	public $primaryKey = 'big';
	public $belongsTo = array (
			'Category',
			'Region',
			'DefaultPhoto' => array (
					'className' => 'Photo',
					'conditions' => array (
							'DefaultPhoto.status !=' => DELETED 
					) 
			) 
	);
	public $hasMany = array (
			'Gallery' => array (
					'conditions' => array (
							'Gallery.place_big !=' => null,
							'Gallery.event_big' => null 
					) 
			),
			'Event' => array (
					'foreignKey' => 'place_big' 
			) 
	);
	
	/*
	 * public $hasAndBelongsToMany = array( 'Operator', );
	 */
	public $validate = array (
			'name' => array (
					'rule' => array (
							'minLength',
							2 
					),
					'message' => 'Please fill in place name' 
			),
			'slug' => array (
					'rulename1' => array (
							'rule' => array (
									'minLength',
									2 
							),
							'message' => 'Please fill in slug' 
					),
					'rulename2' => array (
							'rule' => '/^\S*$/',
							'message' => 'Gli spazi non sono ammessi, utilizzare il carattere trattino ( - ) invece dello spazio' 
					) 
			),
			'lonlat' => array (
					'rule' => 'lonlat',
					'message' => 'Please fill in place GPS position' 
			),
			'password' => array (
					'rule' => array (
							'minLength',
							6 
					),
					'message' => 'Password must be at least 6 characters long' 
			),
			'address_zip' => array (
					'rule' => array (
							'minLength',
							2 
					),
					'message' => 'Please fill in the zip code' 
			),
			
			'address_town' => array (
					'rule' => array (
							'minLength',
							2 
					),
					'message' => 'Please fill in the town' 
			),
			'address_province' => array (
					'rule' => array (
							'minLength',
							2 
					),
					'message' => 'Please fill in the province' 
			),
			'address_region' => array (
					'rule' => array (
							'minLength',
							2 
					),
					'message' => 'Please fill in the region' 
			) 
	// address_zip ,address_town,address_province,address_region
		);
	public function lonlat($check) {
		if (preg_match ( '/^(.+)\,(.+)$/', $check ['lonlat'] )) {
			return true;
		} else {
			return false;
		}
	}
	public function getNearbyPlaces($coords) {
		$db = $this->getDataSource ();
		$sql = 'SELECT 
					Place.big as "Place__big", 
					Place.name as "Place__name", 
					Place.lonlat as "Place__lonlat", 
				    Place.address_street ||\' \'||Place.address_street_no||\',\'||Place.address_town as "Place__address" ,
					(( Place.lonlat <@> ? )::numeric(10,1) * 1.6) AS "Place__distance",
					Place.rating_avg as "Rating__average",
					Place.rating_count as "Rating__count",
					Place.category_id as "Place__category_id",  
					photos.big as "DefaultPhoto__big", 
					photos.original_ext as "DefaultPhoto__original_ext", 
					galleries.big as "Gallery__big", 
					galleries.place_big as "Gallery__place_big", 
					galleries.name as "Gallery__name", 
					galleries.type as "Gallery__type", 
					galleries.status as "Gallery__status", 
					galleries.created as "Gallery__created", 
					galleries.updated as "Gallery__updated", 
					CatLang.name as "CatLang__name",
					CatLang.category_id as "CatLang__category_id"
				FROM places AS Place 
				LEFT JOIN cat_langs AS CatLang ON (CatLang.category_id = Place.category_id AND language_id = 1)
				LEFT JOIN regions AS Region ON (Place.region_id = Region.id)
				LEFT JOIN photos ON (Place.default_photo_big = photos.big) AND photos.status != 255  
				LEFT JOIN galleries ON (Place.big = galleries.place_big) AND galleries.status != 255  AND galleries.event_big is null  
				WHERE ( Place.lonlat <@> ? )::numeric(10,1) < ' . NEARBY_RADIUS . ' AND 
					Place.status != 255   
				ORDER BY ( Place.lonlat <@> ?)::numeric(10,1) asc LIMIT ' . API_MAP_LIMIT;
		// TODO: set a limit?
		
		// try {
		$result = $db->fetchAll ( $sql, array (
				$coords,
				$coords,
				$coords 
		) );
		// }
		// catch (Exception $e)
		// {
		// debug($e);
		// }
		
		if (empty ( $result ))
			return array ();
			
			// Transform to a friendlier format
		$res = array ();
		foreach ( $result as $key => $r ) {
			// Transform coordinates into lon and lat
			if (! empty ( $r ['Place'] ['lonlat'] )) {
				$lonlat = explode ( ',', preg_replace ( '/[\(\)]/', '', $r ['Place'] ['lonlat'] ) );
				$r ['Place'] ['lon'] = $lonlat [0];
				$r ['Place'] ['lat'] = $lonlat [1];
				unset ( $r [0] ['lonlat'] );
			}
			
			// Posprocess to match Cake like result for gallery
			$r ['Gallery'] = array (
					$r ['Gallery'] 
			);
			
			$result [$key] = $r;
		}
		
		return $result;
	}
	
	
	public function getRadarPlacesOLD($coords) {
	//	$coords='(16.2894573999997,40.6300568)';
		$db = $this->getDataSource ();
		$sql = 'SELECT
					Place.big as "Place__big",
					Place.name as "Place__name",
					Place.lonlat as "Place__lonlat",
				    Place.address_street ||\' \'||Place.address_street_no||\',\'||Place.address_town as "Place__address" ,
(( Place.lonlat <@> ? )::numeric(10,1) * 1.6) AS "Place__distance",
							Place.rating_avg as "Rating__average",
					Place.rating_count as "Rating__count",
					Place.category_id as "Place__category_id",
					photos.big as "DefaultPhoto__big",
					photos.original_ext as "DefaultPhoto__original_ext",
					galleries.big as "Gallery__big",
					galleries.place_big as "Gallery__place_big",
					galleries.name as "Gallery__name",
					galleries.type as "Gallery__type",
					galleries.status as "Gallery__status",
					galleries.created as "Gallery__created",
					galleries.updated as "Gallery__updated",
					CatLang.name as "CatLang__name",
					CatLang.category_id as "CatLang__category_id"
				FROM places AS Place
				LEFT JOIN cat_langs AS CatLang ON (CatLang.category_id = Place.category_id AND language_id = 1)
				LEFT JOIN regions AS Region ON (Place.region_id = Region.id)
				LEFT JOIN photos ON (Place.default_photo_big = photos.big) AND photos.status != 255
				LEFT JOIN galleries ON (Place.big = galleries.place_big) AND galleries.status != 255  AND galleries.event_big is null
				WHERE	Place.status != 255 ORDER BY ( Place.lonlat <@> ?)::numeric(10,1) asc LIMIT ' . API_MAP_LIMIT;
				// LIMIT ' . API_MAP_LIMIT;
		// TODO: set a limit?		(( Place.lonlat <@> ? )::numeric(10,1) * 1.6) AS "Place__distance",
		
		// try {
		$result = $db->fetchAll ( $sql,array (
				$coords,$coords
		) );
		// catch (Exception $e)
		
	//	$result = $db->fetchAll ( $sql, array (
//				$coords,$coords,$coords
//		)
//		);
		// {
		// debug($e);
		// }
	
		if (empty ( $result ))
			return array ();
			
			// Transform to a friendlier format
		$res = array ();
		foreach ( $result as $key => $r ) {
			// Transform coordinates into lon and lat
			if (! empty ( $r ['Place'] ['lonlat'] )) {
				$lonlat = explode ( ',', preg_replace ( '/[\(\)]/', '', $r ['Place'] ['lonlat'] ) );
				$r ['Place'] ['lon'] = $lonlat [0];
				$r ['Place'] ['lat'] = $lonlat [1];
				unset ( $r [0] ['lonlat'] );
			}
			
			// Posprocess to match Cake like result for gallery
			$r ['Gallery'] = array (
					$r ['Gallery'] 
			);
			
			$result [$key] = $r;
		}
		
		return $result;
	}
	
	
	
	public function getRadarPlaces($coords) {
		//	$coords='(16.2894573999997,40.6300568)';
		$db = $this->getDataSource ();
		$sql = 'SELECT
					Place.big as "big",
					Place.name as "name",
					Place.lonlat as "lonlat",
					Place.category_id as "category_id",
					photos.big as "DefaultPhoto__big",
					photos.original_ext as "DefaultPhoto__original_ext"
				FROM places AS Place
				LEFT JOIN photos ON (Place.default_photo_big = photos.big) AND photos.status != 255
				WHERE	Place.status != 255 ORDER BY ( Place.lonlat <@> ?)::numeric(10,1) asc LIMIT 15'; // todo: rimettere . API_MAP_LIMIT;
		// LIMIT ' . API_MAP_LIMIT;
		// TODO: set a limit?		(( Place.lonlat <@> ? )::numeric(10,1) * 1.6) AS "Place__distance",
	
		// try {
		$result = $db->fetchAll ( $sql,array (
				$coords
		) );
		// catch (Exception $e)
	
		//	$result = $db->fetchAll ( $sql, array (
		//				$coords,$coords,$coords
		//		)
		//		);
		// {
		
		// }
	
		if (empty ( $result ))
			return array ();
			
		// Transform to a friendlier format
		$res = array ();
		foreach ( $result as $key => $r ) {
			// Transform coordinates into lon and lat
			if (! empty ( $r  ['lonlat'] )) {
				$lonlat = explode ( ',', preg_replace ( '/[\(\)]/', '', $r ['lonlat'] ) );
				$r ['lon'] = $lonlat [0];
				$r ['lat'] = $lonlat [1];
				
			}
				
				
			//$result [$key] = $r;
			$result [$key] = $r;
		}
		return $result;
	}
	
	
	
	
	public function getBoardPlaces($MemberID) {
		$db = $this->getDataSource ();
		
		$MySql = 'select px.pbig as Place_big,px.ccheckinbig as checkinbig,px.elbig as ebig from (SELECT
		DISTINCT ON (p.big)  p.big as pbig,
		p.*,
		c.created as in_created,
		c.big as ccheckinbig,
		e.big as elbig
		FROM
		public.checkins as c,
		public.places as p,
		public.events as e
		WHERE
		c.event_big = e.big AND
		e.place_big = p.big AND
		c.member_big = ' . $MemberID . '
		ORDER BY
		p.big
		) as px
		ORDER BY px.in_created DESC LIMIT 10;';
		
		// try {
		$result = $db->fetchAll ( $MySql );
		
		if (empty ( $result ))
			return array ();
			
			// Transform to a friendlier format
		
		$xresponse = array ();
		
		$ThePlace = array ();
		
		foreach ( $result as $r ) {
			$ThePlace = $this->find ( 'first', array (
					'conditions' => array (
							'Place.big' => $r [0] ["place_big"] 
					),
					'recursive' => - 1,
					'order' => array (
							'Place.updated' => 'DESC' 
					) 
			) );
			
			// die(debug($key));
			// die(debug($r[0]["place_big"]));
			$r ["Place"] = $ThePlace;
			$r ["Place"]["Place"]["eventBig"] = $r [0] ["ebig"];;
			// $r[0] = $ThePlace;
			$r ["Checkinbig"] = $r [0] ["checkinbig"];
			$xresponse [] = $r;
			unset ( $ThePlace );
		}
		
		return $xresponse;
	}
	public function getCurrentEvent($place_big, $select_photo = false, $ob_created = false) {
		$exceptions = array ();
		if ($select_photo) {
			$exceptions = array (
					'Gallery',
					'DefaultPhoto' 
			);
		}
		
		if ($ob_created) {
			$orderBy = array (
					'Event.created' => 'DESC' 
			);
		} else {
			$orderBy = array (
					'(Event.type = ' . EVENT_TYPE_NORMAL . ')' => 'desc',
					'Event.start_date' => 'desc',
					'Event.daily_start' => 'desc',
					'Event.end_date' => 'asc',
					'Event.daily_end' => 'asc' 
			);
		}
		
		unbindAllBut ( $this->Event, $exceptions );
		
		$current_event = $this->Event->find ( 'first', array (
				'conditions' => array (
						'Event.place_big' => $place_big,
						'OR' => array (
								'Event.type' => EVENT_TYPE_DEFAULT,
								array (
										'Event.status' => ACTIVE,
										'Event.start_date <=' => date ( 'Y-m-d H:i:s' ),
										'Event.end_date >=' => date ( 'Y-m-d H:i:s' ),
										'Event.daily_start <' => date ( 'H:i:s' ),
										'Event.daily_end >' => date ( 'H:i:s' ) 
								) 
						),
						'Event.status' => array (
								ACTIVE,
								INACTIVE 
						) 
				),
				'order' => $orderBy 
		) );
		
		if (empty ( $current_event ) || $current_event == false) {
			$current_event = $this->Event->getDefault ( $place_big );
		}
		
		return $current_event;
	}
	
	/**
	 * Returns most popular places for home screen
	 *
	 * @return Ambigous <multitype:, NULL, mixed>
	 */
	public function getMostPopularEvents() {
		unbindAllBut ( $this, array (
				'DefaultPhoto',
				'Gallery' 
		) );
		$events = $this->find ( 'all', array (
				'fields' => array (
						'Place.big',
						'Place.name',
						'Place.slug',
						'Place.category_id',
						'Place.rating_avg',
						'DefaultPhoto.big',
						'DefaultPhoto.original_ext',
						'DefaultPhoto.gallery_big' 
				),
				'order' => array (
						'Place.rating_avg' => 'desc' 
				),
				'limit' => 10 
		) );
		
		return $events;
	}
	
	/**
	 * Checks whether user is within checkin radius.
	 * For autocheckout
	 *
	 * @param int $eventBig        	
	 * @param string $coords
	 *        	The coords of user's position in format (<lon>,<lat>)
	 * @return boolean true if user is whithin chekcin radius, false otherwise
	 */
	public function isUserNearby($eventBig, $coords) {
		$db = $this->getDataSource ();
		$query = 'SELECT places.lonlat <@> \'' . $coords . '\'::point AS "distance" FROM events
			INNER JOIN places ON (events.place_big = places.big)
			WHERE events.big = ? ';
		try {
			$dist = $db->fetchAll ( $query, array (
					$eventBig 
			) );
		} catch ( Exception $e ) {
			// debug($e);
		}
		
		if (empty ( $dist ))
			return false;
		
		$distance = $dist [0] [0] ['distance'];
		// debug(floatval($distance));
		return floatval ( $distance ) <= floatval ( CHECKIN_RADIUS );
	}
}