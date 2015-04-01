<?php
class Checkin extends AppModel {
	public $primaryKey = 'big';
	public $belongsTo = array (
			'Event',
			'Member' 
	);
	public $hasMany = array (
			'ChatMessage' 
	);
	
	/**
	 * Check if member is checked in a place or was checked in and the period while he retains
	 * the capability of performing functions is not over yet.
	 * 
	 * @param unknown_type $memberBig        	
	 * @param unknown_type $eventBig        	
	 * @param unknown_type $returnCheckin
	 *        	Default is false. When set to true, returns the Checkin object
	 * @return boolean Ambigous NULL, mixed>
	 */
	public function isOrWasCheckedIn($memberBig, $eventBig, $returnCheckin = false) {
		$queryParams = array (
				'conditions' => array (
						'Checkin.member_big' => $memberBig,
						'Checkin.event_big' => $eventBig,
						'Checkin.physical' => 1,
						'OR' => array (
								'Checkin.checkout' => null,
								'Checkin.checkout >' => date ( 'Y-m-d H:i', strtotime ( '- ' . API_RETAIN_CHECK_IN_FUNC . ' days' ) ) 
						// 'Checkin.checkout >' => 'NOW() - interval \'' . API_RETAIN_CHECK_IN_FUNC . ' days\''
												) 
				),
				'order' => array (
						'Checkin.created' => 'desc' 
				) 
		);
		
		$result = $this->find ( 'first', $queryParams );
		
		if (empty ( $result ))
			return false;
		
		if ($returnCheckin)
			return $result;
		
		return true;
	}
	
	/**
	 * Return event_big of the event where this member is currently checked in/joined, or the whole checkin object depending on parameters.
	 * 
	 * @param unknown_type $memberBig        	
	 * @param unknown_type $returnCheckin        	
	 * @return boolean Ambiguous
	 */
	public function getCheckedinEventFor($memberBig, $returnCheckin = FALSE) {
		$queryParams = array (
				// 'fields' => array('Checkin.event_big'),
				'conditions' => array (
						'Checkin.member_big' => $memberBig,
						'OR' => array (
								'Checkin.checkout' => null,
								'Checkin.checkout >' => 'NOW()' 
						) 
				),
				'order' => array (
						'Checkin.created' => 'desc' 
				),
				'recursive' => 0 
		);
		
		// try {
		$result = $this->find ( 'first', $queryParams );
		// }
		// catch (Exception $e)
		// {
		// debug($e);
		// }
		
		if (empty ( $result ))
			return false;
		
		if ($returnCheckin)
			return $result;
		
		return $result ['Checkin'] ['event_big'];
	}
	public function getMemberAvailableToChat($eventBig, $memberBig) {
		$db = $this->getDataSource ();
		$query = 'SELECT "Member"."big" AS "Member__big", "Member"."name" AS "Member__name","Member"."birth_date" AS "Member__birth_date","Member"."sex" AS "Member__sex", "Member"."middle_name" AS "Member__middle_name", "Member"."surname" AS "Member__surname", "Member"."photo_updated" AS "Member__photo_updated", "Checkin"."big" AS "Checkin__big", "Checkin"."physical" AS "Checkin__physical", "ChatMessage"."count" AS "ChatMessage__unread_count" 
			FROM "checkins" AS "Checkin" 
			LEFT JOIN "members" AS "Member" ON ("Checkin"."member_big" = "Member"."big")  
			LEFT JOIN (SELECT from_big, COUNT(*) AS count FROM chat_messages WHERE read = 0 AND to_big = ? GROUP BY from_big) AS "ChatMessage" ON ("Member"."big" = "ChatMessage"."from_big") 
			LEFT JOIN "events" AS "Event" ON ("Checkin"."event_big" = "Event"."big") 
			WHERE "Checkin"."event_big" = ? AND "Member"."status" != 255 AND 
            "Member"."big" NOT IN (
                                SELECT to_big as "blockedbig"
                                FROM member_settings
                                WHERE from_big='.$memberBig.' AND chat_ignore=1 '.
                                'UNION
                                SELECT from_big as "blockedbig"
                                FROM member_settings
                                WHERE to_big='.$memberBig.' AND chat_ignore=1) 
			AND (("Checkin"."checkout" IS NULL) OR ("Checkin"."checkout" > \'NOW()\')) 
			AND NOT ("Checkin"."member_big" = ?)   
			ORDER BY "Checkin"."created" desc';
		
		try {
			$result = $db->fetchAll ( $query, array (
					$memberBig,
					$eventBig,
					$memberBig 
			) );
		} catch ( Exception $e ) {
			// debug($e);
		}
		
		if (empty ( $result )) {
			return array ();
		}
		
		return $result;
	}
	
	/**
	 * Check if member is checked in or joined a place and is not yet checked out.
	 * 
	 * @param unknown_type $memberBig        	
	 * @param unknown_type $eventBig        	
	 * @param unknown_type $physical        	
	 * @param unknown_type $returnCheckin
	 *        	Default is false. When set to true, returns the Checkin object
	 * @return boolean Ambigous NULL, mixed>
	 */
	public function hasJoinedOrCheckedIn($memberBig, $eventBig, $physical = false, $returnCheckin = false) {
		$queryParams = array (
				'conditions' => array (
						'Checkin.member_big' => $memberBig,
						'Checkin.event_big' => $eventBig,
						'Checkin.checkout' => null 
				),
				'order' => array (
						'Checkin.created' => 'desc' 
				) 
		);
		
		if ($physical !== false) {
			$queryParams ['conditions'] ['Checkin.physical'] = $physical;
		}
		
		$result = $this->find ( 'first', $queryParams );
		
		if (empty ( $result ))
			return false;
		
		if ($returnCheckin)
			return $result;
		
		return true;
	}
	
	
	public function getCheckinsCountFor($eventBig, $memberBig = null) {
		$pars = array (
				'conditions' => array (
						'Checkin.event_big' => $eventBig,
						'Checkin.physical' => 1,
						'Member.status !=' => DELETED,
						'OR' => array (
								'Checkin.checkout' => null,
								'Checkin.checkout >' => 'NOW()' 
						) 
				) 
		);
		
		if (! empty ( $memberBig ))
			$pars ['conditions'] ['Checkin.member_big !='] = $memberBig;
		
		return intval ( $this->find ( 'count', $pars ) );
	}
	
	// SUM
	public function getCheckinsTotalFor($eventBig) {
		$pars = array (
				'conditions' => array (
						'Checkin.event_big' => $eventBig,
					
						'Member.status !=' => DELETED
				)
		);
	
	
		return intval ( $this->find ( 'count', $pars ) );
	}
	
	
	public function getCheckinsCountForMember($memberBig) {
		$pars = array (
				'conditions' => array (
						'Checkin.member_big' => $memberBig,
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
		
		return intval ( $this->find ( 'count', $pars ) );
	}
	
	/*
	 * Find last checkins for friends
	 */
	public function getCheckinsForFriends($memberBig) {
		$pars = array (
				'conditions' => array (
						'Checkin.member_big' => $memberBig,
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
		
		return intval ( $this->find ( 'count', $pars ) );
	}
	public function getJoinsCountFor($eventBig, $memberBig = null) {
		$params = array (
				'conditions' => array (
						'Checkin.event_big' => $eventBig,
						'Checkin.physical' => 0,
						'Member.status !=' => DELETED,
						'OR' => array (
								'Checkin.checkout' => null,
								'Checkin.checkout >' => 'NOW()' 
						) 
				) 
		);
		
		if (! empty ( $memberBig ))
			$params ['conditions'] ['Checkin.member_big !='] = $memberBig;
		
		return intval ( $this->find ( 'count', $params ) );
	}
	
	
	
	public function getJoinsandCheckinsCountFor($eventBig, $memberBig = null) {
		$params = array (
				'conditions' => array (
						'Checkin.event_big' => $eventBig,
						'Member.status !=' => DELETED,
						'OR' => array (
								'Checkin.checkout' => null,
								'Checkin.checkout >' => 'NOW()'
						)
				)
		);
	
		if (! empty ( $memberBig ))
			$params ['conditions'] ['Checkin.member_big !='] = $memberBig;
	
		return intval ( $this->find ( 'count', $params ) );
	}
	
	/**
	 * Chcek out / join out from all events (you can have oinly 1 at a time)
	 * 
	 * @param int $member_big
	 *        	of the member to check out
	 * @param array $except_big
	 *        	array of checkout BIGs to avoid checking out
	 * @return boolean true for succesfull checkout
	 */
	public function out($member_big, $except_big = array()) {
		
		/*
		 * { $checkins = $this->find('all', array( 'exceptions' => array( 'big !=' => $except_big, 'member_big' => $member_big, 'OR' => array( 'checkout' => null, 'checkout >' => 'NOW()', ) ), 'recursive' => -1, )); foreach($checkins as $item) { $members = $this->ChatCache->read($item['Checkin']['big'].'_members'); $members = str_replace(',', $this->logged['Member']['big'], $members); $this->ChatCache->write($item['Checkin']['big'].'_members', $members); } $this->ChatCache->write($this->logged['Member']['big'].'_checkin_event_big', 0); }
		 */
		unbindAllBut ( $this );
		$this->updateAll ( array (
				'checkout' => DboSource::expression ( 'now()' ) 
		), array (
				'big !=' => $except_big,
				'member_big' => $member_big,
				'OR' => array (
						'checkout' => null,
						'checkout >' => 'NOW()' 
				) 
		) );
		
		return ( bool ) $this->getAffectedRows ();
	}
	public function getCheckinCountsForPlaceBigs($place_bigs, $physical = 1) {
		$event_bigs = $this->Event->find ( 'list', array (
				'conditions' => array (
						'Event.place_big' => $place_bigs,
						'Event.status' => array (
								ACTIVE,
								INACTIVE 
						),
						'or' => array (
								array (
										'Event.type' => EVENT_TYPE_DEFAULT,
										'Event.status' => array (
												ACTIVE,
												INACTIVE 
										) 
								),
								array (
										'Event.type' => EVENT_TYPE_NORMAL,
										'Event.status' => ACTIVE 
								) 
						) 
				),
				'fields' => array (
						'Event.big',
						'Event.place_big' 
				) 
		) );
		
		$event_checkin_counts = $this->getCheckinCountsForEventBigs ( array_keys ( $event_bigs ), $physical );
		
		$checkins = array ();
		foreach ( $event_checkin_counts as $event_big => $count ) {
			if (! isset ( $checkins [$event_bigs [$event_big]] )) {
				$checkins [$event_bigs [$event_big]] = 0;
			}
			$checkins [$event_bigs [$event_big]] += $count;
		}
		return $checkins;
	}
	public function getCheckinCountsForEventBigs($event_bigs, $physical = 1) {
		unbindAllBut ( $this, array (
				'Member' 
		) );
		$checkins_raw = $this->find ( 'all', array (
				'conditions' => array (
						'Checkin.event_big' => $event_bigs,
						'Checkin.physical' => $physical,
						'Member.status !=' => DELETED,
						'OR' => array (
								'Checkin.checkout' => null,
								'Checkin.checkout >' => 'NOW()' 
						) 
				),
				'group' => array (
						'Checkin.event_big' 
				), // , 'Checkin.member_big'), //TODO
				'fields' => array (
						'Checkin.event_big',
						'count(Checkin.event_big) as "Checkin__count"' 
				) 
		) );
		
		$checkins = array ();
		foreach ( $checkins_raw as $item ) {
			$checkins [$item ['Checkin'] ['event_big']] = $item ['Checkin'] ['count'];
		}
		
		return $checkins;
	}
	public function checkout($member_bigs, $cronjobs = false) {
		if (empty ( $member_bigs ))
			return false;
		
		if (! is_numeric ( $member_bigs )) {
			// handle format of big1,big2,...,bign or array of bigs
			if (is_string ( $member_bigs )) {
				$member_bigs = explode ( ',', $member_bigs );
			} elseif (! is_array ( $member_bigs )) {
				return false;
			}
			
			foreach ( $member_bigs as $key => $val ) {
				if (is_numeric ( $val ) === false)
					unset ( $member_bigs [$key] );
			}
			
			$member_bigs = implode ( '\',\'', $member_bigs );
			if (empty ( $member_bigs ))
				return false;
		}
		
		$db = $this->getDataSource ();
		$query = 'UPDATE checkins SET checkout = NOW() WHERE checkout IS NULL AND member_big IN (\'' . $member_bigs . '\')';
		$result = false;
		try {
			$result = $db->execute ( $query );
		} catch ( Exception $e ) {
			if ($cronjobs)
				return $e;
			
			CakeLog::error ( $e );
		}
		
		return $result;
	}
	public function findInactiveMembers() {
		$params = array (
				'conditions' => array (
						'Checkin.checkout' => null,
						array (
								'OR' => array (
										'Member.last_web_activity <' => date ( 'Y-m-d H:i', strtotime ( '- ' . ONLINE_TIMEOUT . ' hours' ) ),
										'Member.last_web_activity' => null 
								) 
						),
						array (
								'OR' => array (
										'Member.last_mobile_activity <' => date ( 'Y-m-d H:i', strtotime ( '- ' . ONLINE_TIMEOUT . ' hours' ) ),
										'Member.last_mobile_activity' => null 
								) 
						) 
				),
				'recursive' => 0,
				'fields' => array (
						'Member.big' 
				) 
		);
		
		return $this->find ( 'list', $params );
	}
	public function getNearbyCheckinsNew($coords, $optParams,$membig) {
		
        $this->log("user ".$membig." func->getNearbyCheckinsNew");
        $this->log(serialize($optParams));
        
        if ($optParams['sex']!=null) $filter[]="members.sex='$optParams[sex]'";
        
        if ($optParams['age']!=null) {
            
            switch ($optParams['age']){
              //0: <25; 1: 25-35; 2: 35-45; 3: 45-55; 4: >55  
                case 0: $filter[]=" (date_part('year',age(now(),members.birth_date)) < 25) ";
                    break;
                case 1: $filter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 25 AND 35) ";
                    break;
                case 2: $filter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 35 AND 45) ";
                    break;
                case 3: $filter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 45 AND 55) ";
                    break;
                case 4: $filter[]=" (date_part('year',age(now(),members.birth_date)) > 55) ";
                    break;
                              
            }
                        
        }
        //over: > 62 miglia; 1..n: 1..n miglia 1km=0.62mi
        if ($optParams['distance']!=null) {
            
            if ($optParams['distance']=='over') $nearby_radius='> 62'; 
                        else
                         $nearby_radius='< '.$optParams['distance'] * .621; 
           } else // if null uses default value
                    $nearby_radius='< '.NEARBY_RADIUS; 
        
       
        if ($optParams['category']!=null) $filter[]=" places.category_id=$optParams[category] ";
        
        if (count($filter)>0) {
                                $filterString=implode('AND',$filter);
                                $filterString='AND '.$filterString;
                                } else 
                                    $filterString='';
                                            
        $db = $this->getDataSource ();
		$sql2 = 'SELECT checkins.member_big,checkins.checkout,Places.big,Places.name,
	             Places.lonlat AS "Place__coordinates",(( Places.lonlat <@> ? )::numeric(10,1) * 1.6) AS "Place__distance"
				 FROM public.checkins, 
                      public.events, 
                      public.places as Places, 
                      public.members
                 WHERE checkins.member_big<> ' . $membig . ' 
                       AND checkins.checkout ISNULL 
                       AND checkins.event_big = events.big                          
                       AND checkins.member_big=members.big
                       AND members.status < 255
                       AND events.place_big = Places.big '.$filterString. '
                       AND ( Places.lonlat <@> ? )::numeric(10,1) ' . $nearby_radius . ' 
                 ORDER BY ( Places.lonlat <@> ?)::numeric(10,1) ASC
                 LIMIT ' . API_MAP_LIMIT;
		

		$this->log("query : ".$sql2."coords : ".$coords);
		// try {
		$result = $db->fetchAll ( $sql2, array (
				$coords,
				$coords,
				$coords 
		) );
		// }
		// catch (Exception $e)
		// {
		// debug($e);
		// }
		
		/*
		 * AND ( checkins.lonlat <@> ? )::numeric(10,1) < ' . NEARBY_RADIUS . ' if (empty ( $result )) return array (); // Transform to a friendlier format $res = array (); foreach ( $result as $key => $r ) { // Transform coordinates into lon and lat if (! empty ( $r ['Place'] ['lonlat'] )) { $lonlat = explode ( ',', preg_replace ( '/[\(\)]/', '', $r ['Place'] ['lonlat'] ) ); $r ['Place'] ['lon'] = $lonlat [0]; $r ['Place'] ['lat'] = $lonlat [1]; unset ( $r [0] ['lonlat'] ); } // Posprocess to match Cake like result for gallery $r ['Gallery'] = array ( $r ['Gallery'] ); $result [$key] = $r; }
		 */
		return $result;
	}
    
    public function getNearbyCheckins($coords, $membig) {
        $db = $this->getDataSource ();
        $sql2 = 'SELECT checkins.member_big,checkins.checkout,Places.big,Places.name,Places.lonlat AS "Place__coordinates",
                (( Places.lonlat <@> ? )::numeric(10,1) * 1.6) AS "Place__distance"
                FROM public.checkins, public.events, public.places as Places
                WHERE checkins.member_big<> ' . $membig . ' AND checkins.checkout ISNULL AND checkins.event_big = events.big 
                AND events.place_big = Places.big AND ( Places.lonlat <@> ? )::numeric(10,1) < ' . NEARBY_RADIUS . ' 
                ORDER BY ( Places.lonlat <@> ?)::numeric(10,1) ASC 
                LIMIT ' . API_MAP_LIMIT;
        
        // try {
        $result = $db->fetchAll ( $sql2, array (
                $coords,
                $coords,
                $coords 
        ) );
        // }
        // catch (Exception $e)
        // {
        // debug($e);
        // }
        
        /*
         * AND ( checkins.lonlat <@> ? )::numeric(10,1) < ' . NEARBY_RADIUS . ' if (empty ( $result )) return array (); // Transform to a friendlier format $res = array (); foreach ( $result as $key => $r ) { // Transform coordinates into lon and lat if (! empty ( $r ['Place'] ['lonlat'] )) { $lonlat = explode ( ',', preg_replace ( '/[\(\)]/', '', $r ['Place'] ['lonlat'] ) ); $r ['Place'] ['lon'] = $lonlat [0]; $r ['Place'] ['lat'] = $lonlat [1]; unset ( $r [0] ['lonlat'] ); } // Posprocess to match Cake like result for gallery $r ['Gallery'] = array ( $r ['Gallery'] ); $result [$key] = $r; }
         */
        return $result;
    }

    
    
    
    
	public function getNearbyCheckinsMember($Idmem,$all,$offset=0) {
		$db = $this->getDataSource ();
		$sql2 = "SELECT Places.big as \"Place__big\",Places.name as \"Place__name\",".
                "Places.category_id as \"Place__category_id\",Places.lonlat as \"Place__coordinates\",".
	            "photos.big as \"DefaultPhoto__big\",photos.original_ext as \"DefaultPhoto__original_ext\",".
                "photos.status as \"DefaultPhoto__status\",photos.big as \"DefaultPhoto__big\",".
                "photos.original_ext as \"DefaultPhoto__original_ext\",galleries.big as \"Gallery__big\",".
                "galleries.place_big as \"Gallery__place_big\",galleries.name as \"Gallery__name\",".
                "galleries.type as \"Gallery__type\",galleries.status as \"Gallery__status\",".
                "galleries.created as \"Gallery__created\",galleries.updated as \"Gallery__updated\" ".
                "FROM public.checkins,public.events,public.places as Places ".
                "LEFT JOIN photos ON (places.default_photo_big = photos.big) AND photos.status != 255 ".
                "LEFT JOIN galleries ON (places.big = galleries.place_big) AND galleries.status != 255 ".
                "AND galleries.event_big IS NULL ".
                "WHERE checkins.member_big = " . $Idmem . " AND ";

	            if (!$all ){	
                            $sql2 .= "checkins.checkout ISNULL AND ";
	                        }
		
        $sql2 .= "checkins.event_big = events.big AND events.place_big = Places.big ORDER BY checkins.created DESC ";
		
        $sql2 .= "LIMIT ".LIMIT_QUERY_CONTENT." OFFSET ".$offset;
        
		// try {
		$result = $db->fetchAll ( $sql2 );
		// }
		// catch (Exception $e)
		// {
		// debug($e);
		// }
		return $result;
	}
	public function getNearbyPeopleWithinLimit($coords, $membig) {
		$db = $this->getDataSource ();
		$sql2 = 'SELECT members.big,members.name,members.updated,members.photo_updated,	members.sex,
	                    members.last_lonlat AS "coordinates",((members.last_lonlat <@> ? )::numeric(10,1) * 1.6) AS "distance"
                 FROM public.members 
                 WHERE (members.big <> ' . $membig . ')	 AND ( members.last_lonlat <@> ? )::numeric(10,1) < ' . NEARBY_RADIUS . '
	             AND members.status < 255 
                 ORDER BY ( members.last_lonlat <@> ?)::numeric(10,1) ASC 
                 LIMIT ' . API_MAP_LIMIT;
		
		// try {
		$result = $db->fetchAll ( $sql2, array (
				$coords,
				$coords,
				$coords 
		) );
		
		return $result;
	}
	
	/*
	 * Find nearby people and autocheckout old checins
	 */
	public function getNearbyPeople($coords, $membig,$offset) {
		$myc = $this->AutoCheckout ();
		
		$db = $this->getDataSource ();
		$sql2 = 'SELECT
 				members.big,
  				members.name,
				members.surname,
				members.updated,
				members.photo_updated,
				members.sex,
			members.last_lonlat AS "coordinates",
		((members.last_lonlat <@> ? )::numeric(10,1) * 1.6) AS "distance"
		FROM   public.members WHERE members.status < 255 AND (members.big <> ' . $membig . ')	 
        ORDER BY ( members.last_lonlat <@> ?)::numeric(10,1) ASC LIMIT ' . API_MAP_LIMIT ;
		if(isset($offset))
		{
			
			$sql2 .= " OFFSET ". $offset;
		}
		$result = $db->fetchAll ( $sql2, array (
				$coords,
				$coords 
		) );
		
		return $result;
	}
	
    
    public function getNearbyPeopleNew($coords, $optParams,$membig,$offset) {
        $myc = $this->AutoCheckout ();
        
        //$BUGLIMIT=API_MAP_LIMIT + 250;
	    //Per ios usano pagine da 100
        $API_MAP_LIMIT=100;
        $offset=$offset*$API_MAP_LIMIT;

        $name=strtolower($optParams['name']);
        
        if ($optParams['name']!=null) $filter[]=" (LOWER(members.name) LIKE '%$name%' OR LOWER(members.surname) LIKE '%$name%') "; 
        
        if ($optParams['sex']!=null) $filter[]=" members.sex='$optParams[sex]' ";
        
        
        if ($optParams['onlyfriends']!=null AND $optParams['onlyfriends'] > 0 ) {
                    $filter[]=" members.big IN (SELECT member2_big AS \"onlyfriends\" ".
                                                "FROM friends ".
                                                "WHERE member1_big=$membig AND status='A' ".
                                                "UNION ".
                                                "SELECT member1_big AS \"onlyfriends\" ".
                                                "FROM friends ".
                                                "WHERE member2_big=$membig AND status='A' ) ";}
        
        
        if ($optParams['age']!=null) {
            
            switch ($optParams['age']){
              //0: <25; 1: 25-35; 2: 35-45; 3: 45-55; 4: >55  
                case 0: $filter[]=" (date_part('year',age(now(),members.birth_date)) < 25) ";
                    break;
                case 1: $filter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 25 AND 35) ";
                    break;
                case 2: $filter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 35 AND 45) ";
                    break;
                case 3: $filter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 45 AND 55) ";
                    break;
                case 4: $filter[]=" (date_part('year',age(now(),members.birth_date)) > 55) ";
                    break;
                              
            }
                        
        }
        //over: > 62 miglia; 1..n: 1..n miglia 1km=0.62mi
        if ($optParams['distance']!=null) {
            
            if ($optParams['distance']=='over') $nearby_radius='distance > 62 AND '; 
                        else
                         $nearby_radius='distance <= '.$optParams['distance'] .' AND '; 
           
           //$distanceFilter=" ((members.last_lonlat <@> '$coords')::numeric(10,1) * 1.6) ".$nearby_radius." AND ";
           $distanceFilter=" ((members.last_lonlat <@> '$coords')::numeric(10,1)) ".$nearby_radius." AND ";
        }
            else // if null uses default value
                    
                   { 
                    $distanceFilter="";
                    $nearby_radius=''; 
                   }
       
        if ($optParams['category']!=null) $filter[]=" places.category_id=$optParams[category] ";
        
        if (count($filter)>0) {
                                $filterString=implode('AND',$filter);
                                $filterString='AND '.$filterString;
                                } else 
                                    $filterString='';
        
        $db = $this->getDataSource ();
        
         if ($optParams['category']!=null) {
             
              $sql2 = "WITH tblcategory AS (SELECT DISTINCT ON (members.big) members.big,members.name,".
                                           "members.surname,members.updated,members.photo_updated,".
                                           "members.sex,members.birth_date,".
                                           "date_part('year',age(now(),members.birth_date)) AS \"age\",".                                                        "members.last_lonlat AS \"coordinates\",".
                                           "((members.last_lonlat <@> '$coords')::numeric(10,1)*1.6) AS \"distance\" ".
                                           "FROM public.members ".
                                           "JOIN checkins on members.big=checkins.member_big ".
                                           "JOIN events on events.big=checkins.event_big ".
                                           "JOIN places on places.big=events.place_big ".
                                           "WHERE members.status<255 ".
                                                  "AND checkins.checkout IS NULL ".
                                                  "AND places.status < 255 ".
                                                  "AND events.status < 255 ".
                                                  "AND $nearby_radius ".
                                                  "members.big NOT IN ( ".
                                                        "SELECT to_big as \"blockedbig\" ".
                                                        "FROM member_settings ".
                                                        "WHERE from_big=$membig AND chat_ignore=1 ".
                                                        "UNION ".
                                                        "SELECT from_big as \"blockedbig\" ".
                                                        "FROM member_settings ".
                                                        "WHERE to_big=$membig AND chat_ignore=1) ".
                                                        "AND (members.big <> $membig ) $filterString ".
                                                        "ORDER BY members.big ) ".
                     "SELECT tblcategory.*,visibletousers FROM tblcategory ".
                     "JOIN privacy_settings ON (tblcategory.big=privacy_settings.member_big) ".
                     "WHERE visibletousers=1 ".
                     "LIMIT ".$API_MAP_LIMIT;
               
             
         } else {
        
               
        $sql2 = "SELECT members.*,visibletousers FROM ".
                "(SELECT members.big,members.name,members.surname,members.status,members.updated,".
                "members.photo_updated,".
                "members.sex,members.birth_date,date_part('year',age(now(),members.birth_date)) AS \"age\",".
                "members.last_lonlat AS \"coordinates\",".
                "((members.last_lonlat <@> '$coords')::numeric(10,1) * 1.6) AS \"distance\" ".
                "FROM public.members) AS members ".
                "JOIN privacy_settings ON (members.big=privacy_settings.member_big) ". 
                "WHERE members.status<255 AND ".//$distanceFilter  
                $nearby_radius.
                "visibletousers=1 ".
                /*"AND NOW() < members.updated + interval '1 day' ".*/              
                "AND members.big NOT IN ".
             
                    "(SELECT to_big AS \"blockedbig\" FROM member_settings ".
                    "WHERE from_big=$membig AND chat_ignore=1 ".
                    "UNION ".
                    "SELECT from_big AS \"blockedbig\" ".
                    "FROM member_settings ".
                    "WHERE to_big=$membig AND chat_ignore=1) ".
                  
                "AND (members.big <> $membig ) ".$filterString." ".
                "ORDER BY distance ASC ".
                "LIMIT ".$API_MAP_LIMIT;
                
                
         }      
        $this->log("filterString ".$filterString);
	$this->log("Query ".$sql2);         
        if(isset($offset))
        {
            
            $sql2 .= " OFFSET ". $offset;
        }
        //$result = $db->fetchAll ( $sql2, array ($coords, $coords) );
        
        $result = $db->fetchAll ( $sql2 );
        
        /* istruzioni debug query
        $modelAnimal = ClassRegistry::init ( 'Animal' );  
        $result=$modelAnimal->AddQuery($sql2);
        */
         $this->log($sql2);             
        return $result;
    }
    
    
    
    
	/*
	 * AutoCheckout after 2 days!!
	 */
	public function AutoCheckout() {
		$db = $this->getDataSource ();
		$sql2 = 'update checkins set 	physical =0 where physical =1 and 	checkout IS NULL   and (created + interval \'2 days\')>NOW()';
		
		$result = $this->query ( $sql2 );
		
		return $result;
	}
	
	/*
	 * Manage checkins ( join/checkin) logics
	 */
	public function AutoCheckin($coords, $membig) {
		$db = $this->getDataSource ();
		$sql2 = "SELECT checkins.big,checkins.member_big,checkins.physical,checkins.checkout,".
                "((places.lonlat <@> ? )::numeric(10,1) * 1.6) AS \"distance\" ".
                "FROM public.places, public.checkins, public.events ".
                "WHERE checkins.event_big = events.big AND events.place_big = places.big ".
                "AND checkins.checkout IS NULL AND checkins.member_big = " . $membig;
		
		$result = $db->fetchAll($sql2, array($coords));
					
		//	die(debug($sql2));
		
		if (count ( $result ) > 0) {
			// AUTOMATIC PHISICAL CHECKIN
			if ($result [0][0] ['distance'] > 1 and $result [0][0] ['physical'] == true) {
			//	die("i".debug($result [0][0] ['distance']));
					$sql3 = "UPDATE checkins SET physical=0 WHERE big=" . $result [0][0] ['big'];
				$this->query ( $sql3 );
                
                
                
			}
			// AUTOMATIC non PHISICAL join
			if ($result [0] [0]['distance'] < 1 and $result [0][0] ['physical'] == false) {
			//	die("o".debug($result[0] [0] ['distance']));
				$sql3 = "UPDATE checkins SET physical=1 WHERE big=" . $result[0] [0] ['big'];
				$this->query ( $sql3 );
                
                //Crediti + Rank per join fisico
                $WalletModel = ClassRegistry::init('Wallet');
                $MemberModel = ClassRegistry::init('Member');
                $WalletModel->addAmount ( $membig, '5', 'Join fisico' );
                $MemberModel->rank($membig,5);
			}
		}
		return $result;
	}
}