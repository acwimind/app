<?php

class PlacesController extends AppController {

	public $uses = array('Place', 'Bookmark', 'Operator','Member','Checkin','Friend');//load these models

	public function operator_index() {

		$place_bigs = $this->Operator->OperatorsPlace->find('list', array(
			'conditions' => array(
				
				'OperatorsPlace.operator_big' => 45111513 // $this->logged['Member']['big']  //,45517058 //
			),
			'fields' => array('OperatorsPlace.place_big', 'OperatorsPlace.place_big'),
			'recursive' => -1,
		));

		$this->_index(array(
			'Place.big' => $place_bigs,
		));

	}

	public function admin_index() {
		clearCache();
		$this->_savedFilter(array('operator', 'srchphr', 'CreatedFromDate', 'CreatedToDate','UpdatedFromDate', 'UpdatedToDate', 'category'));

    	$conditions = array();

	    if (isset($this->params->query['operator']) && $this->params->query['operator']==1) {
	    	$operated_places = $this->Operator->OperatorsPlace->find('list', array(
				'fields' => array('OperatorsPlace.place_big', 'OperatorsPlace.place_big'),
				'recursive' => -1,
			));
    		$conditions['Place.big'] = $operated_places;
    	}
    	if (isset($this->params->query['srchphr']) && !empty($this->params->query['srchphr'])) {
    		$conditions['OR'] = array('Place.name ILIKE' => '%' . $this->params->query['srchphr'] . '%') ;
    	}
		if (isset($this->params->query['CreatedFromDate']) && !empty($this->params->query['CreatedFromDate'])) {
    		$conditions['Place.created >='] = $this->params->query['CreatedFromDate'] . ' ' . $this->params->query['CreatedFromTime'];
    	}
    	if (isset($this->params->query['CreatedToDate']) && !empty($this->params->query['CreatedToDate'])) {
    		$conditions['Place.created <='] = $this->params->query['CreatedToDate'] . ' ' . $this->params->query['CreatedToTime'];
    	}
		if (isset($this->params->query['UpdatedFromDate']) && !empty($this->params->query['UpdatedFromDate'])) {
    		$conditions['Place.updated >='] = $this->params->query['UpdatedFromDate'] . ' ' . $this->params->query['UpdatedFromTime'];
    	}
    	if (isset($this->params->query['UpdatedToDate']) && !empty($this->params->query['UpdatedToDate'])) {
    		$conditions['Place.updated <='] = $this->params->query['UpdatedToDate'] . ' ' . $this->params->query['UpdatedToTime'];
    	}
    	if (isset($this->params->query['category']) && !empty($this->params->query['category'])) {
    		$conditions['Place.category_id'] = $this->params->query['category'];
    	}
    	if (isset($this->params->query['UpdatedBy']) && !empty($this->params->query['UpdatedBy'])) {
    		$conditions['Place.modified_by'] = $this->params->query['UpdatedBy'];
    	}
    	$this->request->data['Place'] = $this->params->query;

    	// Get categories for filter
    	$categories = $this->Place->Category->find('list', array('order' => 'Category.name'));
    	$this->set('categories',$categories);

// solo un momento sa    	$this->_index($conditions);
    //	$posti=$this->Place->find('all',$conditions);
//		debug($posti);
		//$this->render('admin_index');
		$this->_index($conditions);
	}

	private function _index($conditions=array()) {

    	$this->paginate['order'] = array('Place.name' => 'asc');

		$data = $this->paginate('Place', $conditions);
		$this->set('data', $data);

		//checked in members
		{
			$place_bigs = array();
			foreach($data as $item) {
				$place_bigs[] = $item['Place']['big'];
			}

			$checkins = array();
			$joins = array();
			if (!empty($place_bigs)) {

				$checkins = $this->Place->Event->Checkin->getCheckinCountsForPlaceBigs( $place_bigs, 1 );
				$joins = $this->Place->Event->Checkin->getCheckinCountsForPlaceBigs( $place_bigs, 0 );

			}	//if (!empty($place_bigs))

			$this->set('checkins', $checkins);
			$this->set('joins', $joins);
		}

	}

	public function admin_view($big) {

		$this->detail($big);
		$this->render('detail');

	}

	public function operator_edit($big=0) {
		$this->_checkOperatorPermissions($big);
		$this->admin_edit($big);
	}

	public function admin_add() {
		$this->admin_edit();
		$this->render('admin_edit');
	}

	public function admin_edit($big=0) {

		if ($this->request->is('post') || $this->request->is('put')) {
			$MyPlace= $this->request->data;
			$MyPlace['Place']['modified_by']=$this->Auth->user('big');
         	if ($this->Place->saveAll($MyPlace, array('validate' => 'first'))) {

				try {
					$this->_upload($this->request->data['Place']['photos'], $this->Place->id);
				} catch (UploadException $e) {
					debug($e);
				}

				if ($this->request->prefix == 'admin' && isset($this->data['Place']['operators'])) {

					$this->Operator->OperatorsPlace->deleteAll(array('place_big' => $this->Place->id));

					$operators = $this->Bookmark->Member->find('list', array(
						'conditions' => array(
							'Member.email' => json_decode($this->data['Place']['operators']),
						),
						'fields' => array(
							'big', 'big',
						),
						'recursive' => -1,
					));

					$save_operators = array();
					foreach($operators as $operator_big) {
						$save_operators[] = array(
							'place_big' => $this->Place->id,
							'operator_big' => $operator_big,
						);
					}
					$this->Operator->OperatorsPlace->saveAll($save_operators);

				}

				$this->Session->setFlash(__('Posto salvato'), 'flash/success');
				return $this->redirect(array('action' => 'index'));

			} else {
				$this->Session->setFlash(__('Errore durante il salvataggio del posto'), 'flash/error');
			}

		} elseif ($big > 0) {

			$this->request->data = $this->Place->findByBig($big);

		}

		$regions = $this->Place->Region->find('list', array('recursive' => -1, 'order' => array('name' => 'asc')));
		$this->set('regions', $regions);

		//TODO: too many regions for javascript, convert to ajax call
		/*$regions = array();
		$region_address = array();
		foreach($regions_raw as $region) {
			$regions[ $region['Region']['id'] ] = $region['Region']['name'];
			unset($region['Region']['name'], $region['Region']['city'], $region['Region']['country']);
			$region_address[ $region['Region']['id'] ] = $region['Region'];
		}
		$this->set('region_address', $region_address);*/

		if ($this->request->prefix == 'admin') {

			$categories = $this->Place->Category->find('list');
			$this->set('categories', $categories);

			$operator_emails = null;

			if ($big > 0) {

				$operators = $this->Operator->OperatorsPlace->find('list', array(
					'conditions' => array(
						'OperatorsPlace.place_big' => $big
					),
					'fields' => array('OperatorsPlace.operator_big', 'OperatorsPlace.operator_big')
				));

				$operator_emails = $this->Bookmark->Member->find('list', array(
					'conditions' => array(
						'Member.big' => $operators,
					),
					'fields' => array('Member.big', 'Member.email'),
				));

			}
			if (!empty($operator_emails))
				$operator_emails = implode('|', $operator_emails);
			$this->set('operator_emails', $operator_emails);


		}

	}

	public function admin_delete($big) {

		$this->Place->save(array(
			'big' => $big,
			'status' => DELETED,
		));

		/*
		 * TODO: delete all place items?
		 * 	- events
		 *  - galleries (with photos)
		 *  - operator relations
		 */

		$this->Session->setFlash(__('Posto eliminato'), 'flash/success');
		return $this->redirect(array('action' => 'index'));

	}

	public function operator_delete($big) {
		$this->_checkOperatorPermissions($big);
		$this->admin_delete($big);
	}

	public function operator_upload($big) {
		$this->_checkOperatorPermissions($big);
		$this->admin_upload($big);
	}

	public function admin_upload($big) {

		$place = $this->Place->findByBig($big);

		try {
			$this->_upload($this->request->data['photos'], $big);
		} catch (UploadException $e) {
			$this->Session->setFlash(__('Errore durante l\'upload della foto'), 'flash/error');
		}

		$this->Session->setFlash(__('Foto salvata'), 'flash/success');
		return $this->redirect(array('controller' => 'galleries', 'action' => 'index', $place['Gallery'][0]['big']));

	}

	private function _upload($photos, $place_big) {

		$save_photos = array();
		$all_files = explode(';', trim($photos['files'], ';'));
		foreach($all_files as $file) {
			if (!empty($file)) {

				if (!isset($gallery)) {
					$gallery = $this->Place->Gallery->get($place_big, 'place', GALLERY_TYPE_DEFAULT);
				}

//				$extension = (pathinfo($file, PATHINFO_EXTENSION) == 'jpeg') ? 'jpg' : pathinfo($file, PATHINFO_EXTENSION);
				$save_photos[] = array(
					'gallery_big' => $gallery['Gallery']['big'],
					'member_big' => $this->logged['Member']['big'],
					'original_ext' => pathinfo($file, PATHINFO_EXTENSION),
					'status' => ACTIVE,
				);

			}
		}

		if (empty($save_photos)) {
			return false;
		}

		debug("PRIMA");
		$this->Place->Gallery->Photo->saveMany($save_photos);

		//set default photo of there is none
		$first_insert_id = reset($this->Place->Gallery->Photo->inserted_ids);
		if ($first_insert_id) {
			$this->Place->recursive = -1;
			$place = $this->Place->findByBig($place_big);
			if ($place['Place']['default_photo_big'] == 0 && $first_insert_id > 0) {
				$this->Place->save(array(
					'Place' => array(
						'big' => $place['Place']['big'],
						'default_photo_big' => $first_insert_id,
					),
				));
			}
		}

		return $this->Upload->upload(
			$photos,	//data from form (temporary filenames, token)
			PLACES_UPLOAD_PATH . $gallery['Gallery']['place_big'] . DS . $gallery['Gallery']['big'] . DS,	//path
			$this->Place->Gallery->Photo->inserted_ids
		);

	}
   
     public function api_newlist() {
        // TODO
        App::uses('Search', 'Lib');
                
        $this->_checkVars(array(),array('lon','lat','name','category','city','rating','offset','sex','age','filteroptions'));
        
        $phrase = isset($this->api['name']) ? Search::PrepareTSQuery($this->api['name']) : null;
        $cat_id = isset($this->api['category']) ? $this->api['category'] : null;
        $region_id = isset($this->api['city']) ? $this->api['city'] : null;
        $rating_avg = isset($this->api['rating']) ? $this->api['rating'] : null;
        $offset = isset($this->api['offset']) ? $this->api['offset'] * API_PER_PAGE : 0;  
        
        $filteroptions = isset($this->api['filteroptions']) ? $this->api['filteroptions'] : null;
        $sex=isset($this->api['sex']) ? $this->api['sex'] : null;
        $age=isset($this->api['age']) ? $this->api['age'] : null;
        $myFilter=array();
        
        $table_options="places ";
                
        $default_lon = '16.2894573999999';
        $default_lat='40.6300568';
                       
        if  (isset($this->api['lon']) AND isset($this->api['lat']) AND intval($this->api['lon'])>0 AND intval($this->api['lat'])>0)
        {//se lon e lat sono passati e non nulli
            
            $lon = $this->api['lon'];
            $lat = $this->api['lat'];
            $coords = '(' . $lon . ',' . $lat . ')';
        
        }
        else
        { //se lon e lat non sono passati o sono passati nulli 
            
            $memberdata=$this->Member->getMemberByBig ( $this->logged ['Member'] ['big'] );
                                  
             if (count($memberdata)>0 AND $memberdata['Member']['last_lonlat']!=null)
              {//se ritorna un record di dati e le coordinate non sono nulle
                    $coords = $memberdata['Member']['last_lonlat'];
                    $lonlat=explode(',',str_replace(array('(',')'),'',$coords));
                    $lon=$lonlat[0];
                    $lat=$lonlat[1];
              
              } else {//In tutti gli altri casi prende coordinate di default              
                        $lon=$default_lon;
                        $lat=$default_lat;
                        $coords = '(' . $lon . ',' . $lat . ')';
               
               } 
           }
                               
              if ($filteroptions!=null){
            
            switch ($filteroptions){
                
                case 'bookmark': 
                
                  $table_options="(SELECT places.* ".
                                 "FROM bookmarks ".
                                 "JOIN places ON bookmarks.place_big=places.big ".
                                 "WHERE bookmarks.member_big = ".$this->logged['Member']['big'].
                                 " AND places.published = 1 ) places ";
                                   break;
                case 'friend' :
                    
                    
                  $placesFriends=$this->placesWithFriendsCheckins($this->logged['Member']['big']);
                  
                  if (count($placesFriends)>0) $placesWithFriends=implode(',',$placesFriends);
                                   else $placesWithFriends='null';
                   
                  $table_options="(SELECT places.* ".
                                 "FROM places ".
                                 "JOIN events ON events.place_big=places.big ".
                                 "JOIN checkins ON checkins.event_big=events.big ".
                                 "WHERE checkins.physical=1 AND checkins.checkout IS NULL ".
                                 "AND places.published = 1 ".
                                 "AND checkins.member_big IN (".$placesWithFriends.") ) places ";
                                 break;
            }
            
            
        }
        $this->log("----PlacesController---api_list---------------------");
        $this->log("member: ".$this->logged['Member']['big']);
        $this->log("phrase: ".$phrase." cat_id: ".$cat_id." region_id: ".$region_id." rating_avg: ".$rating_avg);
        $this->log("lon: ".$lon." lat: ".$lat." offset: ".$offset." coords: ".$coords);
        $this->log("filteroptions: ".$filteroptions);
        $this->log("sex: ".$sex." age: ".$age);
        $this->log("----------------------------------------------------");
        
        
        if ($sex!=null) $myFilter[]=" members.sex='$sex' ";
        if ($age!=null)  {
            
            
                        switch ($age){
                            //0: <25; 1: 25-35; 2: 35-45; 3: 45-55; 4: >55  
                            case 0: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) < 25) ";
                                    break;
                            case 1: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 25 AND 35) ";
                                    break;
                            case 2: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 35 AND 45) ";
                                    break;
                            case 3: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 45 AND 55) ";
                                    break;
                            case 4: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) > 55) ";
                                    break;
                              
                                }
        }
        if (count($myFilter)>0) {//filtro per sex e/o age
                                $filterString=implode('AND',$myFilter);
                                $filterString='AND '.$filterString;
                                
                $queryPrefix='WITH plisel as (SELECT DISTINCT ON (places.big) places.big '. 
                                  'FROM '.$table_options.
                                  'JOIN events ON places.big = events.place_big '.
                                  'JOIN checkins ON events.big = checkins.event_big '.
                                  'JOIN members ON checkins.member_big = members.big '.
                                  'WHERE places.status < 255 AND places.published = 1 AND checkins.checkout IS NULL '.$filterString.' '. 
                                  'ORDER BY places.big,( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC '.
                                  'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                                                                        
                            
                                
                      } else { 
                                 
               $queryPrefix='WITH plisel as ( SELECT places.big '.  
                                             'FROM '.$table_options.
                                             'WHERE places.status < 255 AND places.published = 1 '. 
                                             'ORDER BY ( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC '.
                                             'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                                             
                    $filterString='';
                     
                   // print('qpref '.$queryPrefix);                        
                                    }
        //print('name vale '.$phrase);
        // Match coords against regular expression
        $crdsMatch = preg_match('/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords);
        if ($crdsMatch == FALSE && (!empty($lon) || !empty($lat))) {
                        $this->_apiEr(__('Le coordinate non sono valide: lon and/or lat'));
                    }

        if (empty($phrase) && empty($cat_id) && empty($region_id) && empty($rating_avg) && $crdsMatch)
        {
                       
            $query = $queryPrefix.
                    "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                    "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                    "places.address_street AS \"Place__address_street\",".
                    "places.address_street_no AS \"Place__address_street_no\",".
                    "places.lonlat AS \"Place__coordinates\",regions.city AS \"Place__city\",".
                    "photos.big AS \"DefaultPhoto__big\", photos.original_ext AS \"DefaultPhoto__original_ext\",".                           "photos.gallery_big AS \"Gallery__big\",photos.status AS \"DefaultPhoto__status\",".
                    "evts.eventnames AS \"Event__names\", evts.eventdates AS \"Event__dates\",".
                    "evts.eventbigs AS \"Event__bigs\",".
                    "places.lonlat <@> '" . $coords . "'::point  /*lon,lat*/ AS \"Place__distance\" ".
                    "FROM plisel ".
                    "JOIN ".$table_options." ON places.big = plisel.big ".
                    "JOIN regions ON regions.id = places.region_id ".
                    "LEFT JOIN ".
                        "(SELECT place_big, array_agg(name) as eventnames,array_agg(created) as eventdates,".
                        "array_agg(events.big) as eventbigs ".
                        "FROM events ".
                        "WHERE place_big IN (SELECT big FROM plisel) AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < now()) ".
                        "AND (events.end_date IS NULL or events.end_date > NOW()) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big ) evts ON places.big = evts.place_big ".
                    "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                    "WHERE earth_box(ll_to_earth(" . $lat . " /*lat*/, " . $lon . " /*lon*/)," . NEARBY_RADIUS . 
                    " /* miles */ * 16.09344/*metres*/) @> ll_to_earth(places.lonlat[1], places.lonlat[0]) ". 
                    "ORDER BY \"Place__distance\" ASC";

       $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$query);
       $query_count=str_replace("ORDER BY \"Place__distance\" ASC",'',$query);
       $query_count=str_replace('ORDER BY ( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC ','',$query);
              
       $countQuery = 'WITH contatore AS ('.$query_count.')'.'SELECT COUNT(*) FROM contatore';     
                
               //print($query);
      
        }
        elseif (empty($phrase))
        {
            $whereArr = array();
            if (!empty($region_id))
            {
                $whereArr[] = ' places.region_id = ' . $region_id . ' ';
            }
            if (!empty($cat_id))
            {
                $whereArr[] = ' places.category_id = ' . $cat_id . ' ';
            }
            if (!empty($rating_avg))
            {
                $whereArr[] = ' places.rating_avg >= ' . $rating_avg . ' ';
            }
            if (!empty($whereArr))
                $where = 'AND ' . implode('AND', $whereArr);

                
            if (count($myFilter)>0){//se abbiamo anche il filtro sex e/o age
                
                                    $queryPrefix2='WITH plids as ( SELECT DISTINCT ON (places.big) places.big '.
                                                  'FROM '.$table_options.
                                                  'JOIN events ON places.big = events.place_big '.
                                                  'JOIN checkins ON events.big = checkins.event_big '.
                                                  'JOIN members ON checkins.member_big = members.big '.
                                                  'WHERE places.status < 255 AND places.published = 1 AND checkins.checkout IS NULL '.$filterString. 
                                                  (!empty($where)  ? $where : '') .' '. 
                                                  'ORDER BY places.big,places.name ASC '.
                                                  'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ')';
                       
                
            } else {//se non abbiamo filtri sex e age
                            $queryPrefix2='WITH plids as ( SELECT places.big,places.lonlat <@> \''.$coords.'\'::point AS "Place__distance" '.
                                                           'FROM '.$table_options.
                                                           'WHERE places.status < 255 AND places.published = 1 '.
                                                           (!empty($where)  ? $where : '') . ' '.
                                                           'ORDER BY "Place__distance" ASC,places.name ASC '.
                                                           'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                         
            }   
                      
            
            $query = $queryPrefix2.
                    "SELECT places.name AS \"Place__name\",places.big AS \"Place__big\",".
                    "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                    "places.address_street AS \"Place__address_street\",".
                    "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                    "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                    "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                    "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                    "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                     ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) . 
                    "FROM plids ".
                    "JOIN ".$table_options." USING (big) ".
                    "JOIN regions ON regions.id = places.region_id ".
                    "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                    "LEFT JOIN (".
                        "SELECT place_big, array_agg(events.name) as eventnames,".
                        "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                        "FROM events ".
                        "JOIN plids ON plids.big = events.place_big ".
                        "WHERE (events.status = 1) AND (events.start_date IS NULL or events.start_date < now()) ".
                        "AND (events.end_date IS NULL or events.end_date > NOW()) AND ".
                        "(events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evts ON plids.big = evts.place_big ". 
                    "ORDER BY \"Place__distance\" ASC";
                   

       $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$query);
       $countQuery = 'WITH contatore AS ('.$query_count.')'.'SELECT COUNT(*) FROM contatore';        
                   
                   
        }
        else
        {//$phrase non nullo
            
            if ($sex!=null OR $age!=null){
            
            $jointable="JOIN events ON places.big = events.place_big ".
                       "JOIN checkins ON events.big = checkins.event_big ".
                       "JOIN members ON checkins.member_big = members.big  ";
            } else $jointable="";
            
            //print($filterString);
            
            $queryOLD = "WITH plids as (".
                     "WITH tsqry as (SELECT to_tsquery('pg_catalog.italian',$$" . $phrase . "$$) as qry) ".
                     "SELECT place_big, greatest(rank_pl, rank_ev) as rank ".
                     "FROM (".
                        "SELECT places.big as place_big, ts_rank_cd(places.tsv, qry, 36) AS rank_pl, ".
                        "places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " .
                        "FROM tsqry,".$table_options.
                        $jointable.
                        " WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") . 
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= " . $rating_avg . " AND " : "") . 
                        " qry @@ places.tsv AND places.status < 255 AND places.published = 1 " . $filterString . " ) plsel ".
                        "FULL OUTER JOIN ".
                        "(SELECT place_big, AVG(ts_rank_cd(events.tsv, qry, 36)) AS rank_ev ".
                        "FROM tsqry, events ".
                        "INNER JOIN places ON places.big = events.place_big ".
                        "WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") .
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= ". $rating_avg . " AND " : "") .
                        " qry @@ events.tsv AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                        "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evsel ".
                        "USING (place_big) ".
                        "ORDER BY \"Place__distance\" ASC, rank DESC LIMIT " . API_PER_PAGE . " OFFSET ". $offset . 
                        ") ".
                        "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                        "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                        "places.address_street AS \"Place__address_street\", ".
                        "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                        "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                        "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                        "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                        "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                        ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) .
                        " FROM plids ".
                        "JOIN ".$table_options." ON plids.place_big = places.big ".
                        "JOIN regions ON regions.id = places.region_id ".
                        "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                        "LEFT JOIN ( ".
                            "SELECT place_big, array_agg(events.name) as eventnames,".
                            "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                            "FROM events ".
                            "JOIN plids USING (place_big) ".
                            "WHERE (events.status = 1) ".
                            "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                            "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                            "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                            "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                            "GROUP BY place_big) evts ON plids.place_big = evts.place_big ".
                        "ORDER BY \"Place__distance\" ASC";
           
               
            
         $query = "WITH plids as (".
                     "WITH tsqry as (SELECT to_tsquery('pg_catalog.italian',$$" . $phrase . "$$) as qry) ".
                     "SELECT place_big, greatest(rank_pl, rank_ev) as rank,\"Place__distance\" ".
                     "FROM (".
                        "SELECT places.big as place_big, ts_rank_cd(places.tsv, qry, 36) AS rank_pl, ".
                        "places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " .
                        "FROM tsqry,".$table_options.
                        $jointable.
                        " WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") . 
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= " . $rating_avg . " AND " : "") . 
                        " places.published = 1 AND ".
                        " qry @@ places.tsv AND places.status < 255 AND places.published = 1 " . $filterString . " ) plsel ".
                        "FULL OUTER JOIN ".
                        "(SELECT place_big, AVG(ts_rank_cd(events.tsv, qry, 36)) AS rank_ev ".
                        "FROM tsqry, events ".
                        "INNER JOIN places ON places.big = events.place_big ".
                        "WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") .
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= ". $rating_avg . " AND " : "") .
                        " qry @@ events.tsv AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                        "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evsel ".
                        "USING (place_big) ".
                        "WHERE \"Place__distance\" < ".NEARBY_RADIUS. " * 16.09344 ".
                        "ORDER BY \"Place__distance\" ASC, rank DESC LIMIT " . API_PER_PAGE . " OFFSET ". $offset . 
                        ") ".
                        "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                        "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                        "places.address_street AS \"Place__address_street\", ".
                        "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                        "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                        "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                        "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                        "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                        ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) .
                        " FROM plids ".
                        "JOIN ".$table_options." ON plids.place_big = places.big ".
                        "JOIN regions ON regions.id = places.region_id ".
                        "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                        "LEFT JOIN ( ".
                            "SELECT place_big, array_agg(events.name) as eventnames,".
                            "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                            "FROM events ".
                            "JOIN plids USING (place_big) ".
                            "WHERE (events.status = 1) ".
                            "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                            "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                            "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                            "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                            "GROUP BY place_big) evts ON plids.place_big = evts.place_big ".
                            "ORDER BY \"Place__distance\" ASC";
                    
             
             $countQuery=$query;
             $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$countQuery);
             $countQuery = 'WITH contatore AS ('.$query_count.')'.'SELECT COUNT(*) FROM contatore';
            
           /* $countQuery = 'WITH tsqry as (SELECT to_tsquery(\'pg_catalog.italian\',$$' . $phrase . '$$) as qry)
                SELECT COUNT(*)
                FROM
                (
                    SELECT places.big as place_big,
                    places.lonlat <@> \''. $coords .'\'::point AS "Place__distance" 
                    FROM tsqry, '.$table_options.' '.$jointable.' 
                    WHERE
                        ' . (!empty($region_id) ? 'places.region_id = ' . $region_id . ' AND ' : '') . '
                        ' . (!empty($cat_id) ? 'places.category_id = ' . $cat_id . ' AND ' : '') . '
                        ' . (!empty($rating_avg) ? 'places.rating_avg >= ' . $rating_avg . ' AND ' : '') . '
                        qry @@ places.tsv  
                        AND places.status < 255 '. $filterString .'
                ) plsel
                FULL OUTER JOIN
                (
                    SELECT place_big
                    FROM tsqry, events
                    INNER JOIN places ON places.big = events.place_big
                    WHERE
                        ' . (!empty($region_id) ? 'places.region_id = ' . $region_id . ' AND ' : '') . '
                        ' . (!empty($cat_id) ? 'places.category_id = ' . $cat_id . ' AND ' : '') . '
                        ' . (!empty($rating_avg) ? 'places.rating_avg >= ' . $rating_avg . ' AND ' : '') . '
                        qry @@ events.tsv
                        AND (events.status = 1)
                        AND (events.start_date IS NULL or events.start_date < current_timestamp) AND (events.end_date IS NULL or events.end_date > current_timestamp) AND (events.daily_start IS NULL OR events.daily_start < localtime) AND (events.daily_end IS NULL OR events.daily_end > localtime)
                    GROUP BY place_big
                ) evsel
                USING (place_big) '.
                "WHERE places.published = 1 AND \"Place__distance\" < ".NEARBY_RADIUS. " * 1.609344 "; */
        }

        $db = $this->Place->getDataSource();
       //print($countQuery);
        //print($query);
        $this->log("------------PLACES CONTROLLER-----------");
        $this->log("---------------query--------------------");
        $this->log($query);
        $this->log("------------Fine PLACES CONTROLLER------");  
        
       // debug($query);
        //print($query);
        try {
            $places = $db->fetchAll($query);
            if ($offset == 0)
            {
                $plCount = $db->fetchAll($countQuery);
                $placesCount = $plCount[0][0]['count'];
            }
        }
        catch (Exception $e)
        {
            debug($e);
        }

        
//        debug($places);
//        debug($placesCount);

//        if (empty($places) && $placesCount > 0 && isset($params['offset'])) {    //if no results on this page, go to first page
//            unset($params['offset']);
//            unbindAllBut($this->Place, array('Gallery', 'DefaultPhoto'));
//            $places = $this->Place->find('all', $params);
//        }

        // Add photos
        // Preprocessing to fit the methods
        if (count($places)>0){
        
        foreach ($places as &$plc)
        {
            if ($plc['Place']['distance'] < CHECKIN_RADIUS) {
                $plc['Place']['Checkable'] = '1';
            } else {
                    $plc['Place']['Checkable'] = '0';
            }
            
            
            $gallery = $plc['Gallery'];
            unset($plc['Gallery']);
            $plc['Gallery'][0] = $gallery;

            $names = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['names']));
            $dates = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['dates']));
            $bigs = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['bigs']));
            $name = '';
            $date = '';
            $big = '';
            foreach ($dates as $key=>$val)
            {
                if (empty($date) || $date < $val)
                {
                    $date = $val;
                    $name = $names[$key];
                    $big = $bigs[$key];
                }
            }
            unset($plc['Event']['names']);
            unset($plc['Event']['dates']);
            unset($plc['Event']['bigs']);
            $plc['Event']['name'] = $name;
            $plc['Event']['big'] = $big;
        }
        }
        $places = $this->_addPlacePhotoUrls($places);
        
        $places = $this->_addPlaceCheckedIn($places);
   
        
        //print_r($places);
        
       if ($filteroptions=='rating') usort( $places, 'PlacesController::multiFieldSortArray' );
                             
               
        $result = array('places' => $places);
        if (isset($placesCount))
            $result['places_count'] = $placesCount;
        
        
        
        $this->_apiOk($result);
      
           
    //print($countQuery);
    }
   
    
    
    
     public function api_list() {

        // Variables
        App::uses('Search', 'Lib');
        
        // removed lon and lat for no geoloc
        $this->_checkVars(array(),array('lon','lat','name','category','city','rating','offset','sex','age','filteroptions'));
        
        $phrase = isset($this->api['name']) ? Search::PrepareTSQuery($this->api['name']) : null;
        $cat_id = isset($this->api['category']) ? $this->api['category'] : null;
        $region_id = isset($this->api['city']) ? $this->api['city'] : null;
        $rating_avg = isset($this->api['rating']) ? $this->api['rating'] : null;
        $offset = isset($this->api['offset']) ? $this->api['offset'] * API_PER_PAGE : 0;
    

        $this->log("var passate: ".serialize($this->api));
        $this->log("getquotes ".get_magic_quotes_gpc());
        // PUT IN DEFAULT FILE!!!
        $coords = '(40.6300568,16.2894573999997)';
        $lon = '16.2894573999999';
        $lat='40.6300568';
                
        if  (isset($this->api['lon']) AND isset($this->api['lat']))
        {
        $lon = isset($this->api['lon']) ? $this->api['lon'] : null;
        $lat = isset($this->api['lat']) ? $this->api['lat'] : null;
        $coords = '(' . $lon . ',' . $lat . ')';
        
        }
        else
        {  // try
            //Gran parte del codice sotto potrebbe essere abbreviato visto che Places uses Member
            //$memberdata=$this->Member->getMemberByBig ( $this->logged ['Member'] ['big'] )
            //$coords=$memberdata['Member']['last_lonlat']
             
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
            //    debug($datapos['Member']['last_lonlat']);
                if ($datapos['Member']['last_lonlat']!=null)
                {
                     $coords = $datapos['Member']['last_lonlat'];
                    $xcoords  = str_replace("(", "", $coords);
                    $xcoords  = str_replace(")", "", $xcoords);
                    $lecoords=split(',',$xcoords);
                    $lon=$lecoords[0];
                    $lat=$lecoords[1];
                //    debug('a');
                }
            }
            
        }
              
        
        $filteroptions = isset($this->api['filteroptions']) ? $this->api['filteroptions'] : null;
        $sex=isset($this->api['sex']) ? $this->api['sex'] : null;
        $age=isset($this->api['age']) ? $this->api['age'] : null;
        $myFilter=array();
        
        $table_options="places ";
        
        if ($filteroptions!=null){
            
            switch ($filteroptions){
                
                case 'bookmark': 
                
                  $table_options="(SELECT places.* ".
                                 "FROM bookmarks ".
                                 "JOIN places ON bookmarks.place_big=places.big ".
                                 "WHERE bookmarks.member_big = ".$this->logged['Member']['big'].
                                 " AND places.published = 1 ) places ";
                                   break;
                case 'friend' :
                    
                    
                  $placesFriends=$this->placesWithFriendsCheckins($this->logged['Member']['big']);
                  
                  if (count($placesFriends)>0) $placesWithFriends=implode(',',$placesFriends);
                                   else $placesWithFriends='null';
                   
                  $table_options="(SELECT places.* ".
                                 "FROM places ".
                                 "JOIN events ON events.place_big=places.big ".
                                 "JOIN checkins ON checkins.event_big=events.big ".
                                 "WHERE checkins.physical=1 AND checkins.checkout IS NULL ".
                                 "AND places.published = 1 ".
                                 "AND checkins.member_big IN (".$placesWithFriends.") ) places ";
                                 break;
            }
            
            
        }
        $this->log("----PlacesController---api_list---------------------");
        $this->log("member: ".$this->logged['Member']['big']);
        $this->log("phrase: ".$phrase." cat_id: ".$cat_id." region_id: ".$region_id." rating_avg: ".$rating_avg);
        $this->log("lon: ".$lon." lat: ".$lat." offset: ".$offset." coords: ".$coords);
        $this->log("filteroptions: ".$filteroptions);
        $this->log("sex: ".$sex." age: ".$age);
        $this->log("----------------------------------------------------");
        
        
        if ($sex!=null) $myFilter[]=" members.sex='$sex' ";
        if ($age!=null)  {
            
            
                        switch ($age){
                            //0: <25; 1: 25-35; 2: 35-45; 3: 45-55; 4: >55  
                            case 0: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) < 25) ";
                                    break;
                            case 1: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 25 AND 35) ";
                                    break;
                            case 2: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 35 AND 45) ";
                                    break;
                            case 3: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 45 AND 55) ";
                                    break;
                            case 4: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) > 55) ";
                                    break;
                              
                                }
        }
        if (count($myFilter)>0) {//filtro per sex e/o age
                                $filterString=implode('AND',$myFilter);
                                $filterString='AND '.$filterString;
                                
                $queryPrefix='WITH plisel as (SELECT DISTINCT ON (places.big) places.big '. 
                                  'FROM '.$table_options.
                                  'JOIN events ON places.big = events.place_big '.
                                  'JOIN checkins ON events.big = checkins.event_big '.
                                  'JOIN members ON checkins.member_big = members.big '.
                                  'WHERE places.status < 255 AND places.published = 1 AND checkins.checkout IS NULL '.$filterString.' '. 
                                  'ORDER BY places.big,( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC '.
                                  'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                                                                        
                            
                                
                      } else { 
                                 
               $queryPrefix='WITH plisel as ( SELECT places.big '.  
                                             'FROM '.$table_options.
                                             'WHERE places.status < 255 AND places.published = 1 '. 
                                             'ORDER BY ( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC '.
                                             'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                                             
                    $filterString='';
                     
                   // print('qpref '.$queryPrefix);                        
                                    }
        //print('name vale '.$phrase);
        // Match coords against regular expression
        $crdsMatch = preg_match('/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords);
        if ($crdsMatch == FALSE && (!empty($lon) || !empty($lat))) {
                        $this->_apiEr(__('Le coordinate non sono valide: lon and/or lat'));
                    }

        if (empty($phrase) && empty($cat_id) && empty($region_id) && empty($rating_avg) && $crdsMatch)
        {
                       
            $query = $queryPrefix.
                    "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                    "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                    "places.address_street AS \"Place__address_street\",".
                    "places.address_street_no AS \"Place__address_street_no\",".
                    "places.lonlat AS \"Place__coordinates\",regions.city AS \"Place__city\",".
                    "photos.big AS \"DefaultPhoto__big\", photos.original_ext AS \"DefaultPhoto__original_ext\",".                           "photos.gallery_big AS \"Gallery__big\",photos.status AS \"DefaultPhoto__status\",".
                    "evts.eventnames AS \"Event__names\", evts.eventdates AS \"Event__dates\",".
                    "evts.eventbigs AS \"Event__bigs\",".
                    "places.lonlat <@> '" . $coords . "'::point  /*lon,lat*/ AS \"Place__distance\" ".
                    "FROM plisel ".
                    "JOIN ".$table_options." ON places.big = plisel.big ".
                    "JOIN regions ON regions.id = places.region_id ".
                    "LEFT JOIN ".
                        "(SELECT place_big, array_agg(name) as eventnames,array_agg(created) as eventdates,".
                        "array_agg(events.big) as eventbigs ".
                        "FROM events ".
                        "WHERE place_big IN (SELECT big FROM plisel) AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < now()) ".
                        "AND (events.end_date IS NULL or events.end_date > NOW()) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big ) evts ON places.big = evts.place_big ".
                    "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                    "WHERE earth_box(ll_to_earth(" . $lat . " /*lat*/, " . $lon . " /*lon*/)," . NEARBY_RADIUS . 
                    " /* miles */ * 16.09344/*metres*/) @> ll_to_earth(places.lonlat[1], places.lonlat[0]) ". 
                    "ORDER BY \"Place__distance\" ASC";

       $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$query);
       $query_count=str_replace("ORDER BY \"Place__distance\" ASC",'',$query);
       //$query_count=str_replace('ORDER BY ( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC ','',$query);
              
       $countQuery = 'WITH contatore AS ('.$query_count.')'.'SELECT COUNT(*) FROM contatore';     
                
               //print($query);
      
        }
        elseif (empty($phrase))
        {
            $whereArr = array();
            if (!empty($region_id))
            {
                $whereArr[] = ' places.region_id = ' . $region_id . ' ';
            }
            if (!empty($cat_id))
            {
                $whereArr[] = ' places.category_id = ' . $cat_id . ' ';
            }
            if (!empty($rating_avg))
            {
                $whereArr[] = ' places.rating_avg >= ' . $rating_avg . ' ';
            }
            if (!empty($whereArr))
                $where = 'AND ' . implode('AND', $whereArr);

                
            if (count($myFilter)>0){//se abbiamo anche il filtro sex e/o age
                
                                    $queryPrefix2='WITH plids as ( SELECT DISTINCT ON (places.big) places.big '.
                                                  'FROM '.$table_options.
                                                  'JOIN events ON places.big = events.place_big '.
                                                  'JOIN checkins ON events.big = checkins.event_big '.
                                                  'JOIN members ON checkins.member_big = members.big '.
                                                  'WHERE places.status < 255 AND places.published = 1 AND checkins.checkout IS NULL '.$filterString. 
                                                  (!empty($where)  ? $where : '') .' '. 
                                                  'ORDER BY places.big,places.name ASC '.
                                                  'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ')';
                       
                
            } else {//se non abbiamo filtri sex e age
                            $queryPrefix2='WITH plids as ( SELECT places.big,places.lonlat <@> \''.$coords.'\'::point AS "Place__distance" '.
                                                           'FROM '.$table_options.
                                                           'WHERE places.status < 255 AND places.published = 1 '.
                                                           (!empty($where)  ? $where : '') . ' '.
                                                           'ORDER BY "Place__distance" ASC,places.name ASC '.
                                                           'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                         
            }   
                      
            
            $query = $queryPrefix2.
                    "SELECT places.name AS \"Place__name\",places.big AS \"Place__big\",".
                    "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                    "places.address_street AS \"Place__address_street\",".
                    "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                    "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                    "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                    "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                    "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                     ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) . 
                    "FROM plids ".
                    "JOIN ".$table_options." USING (big) ".
                    "JOIN regions ON regions.id = places.region_id ".
                    "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                    "LEFT JOIN (".
                        "SELECT place_big, array_agg(events.name) as eventnames,".
                        "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                        "FROM events ".
                        "JOIN plids ON plids.big = events.place_big ".
                        "WHERE (events.status = 1) AND (events.start_date IS NULL or events.start_date < now()) ".
                        "AND (events.end_date IS NULL or events.end_date > NOW()) AND ".
                        "(events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evts ON plids.big = evts.place_big ". 
                    "ORDER BY \"Place__distance\" ASC";
                   

       $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$query);
       $countQuery = 'WITH contatore AS ('.$query_count.')'.'SELECT COUNT(*) FROM contatore';        
                   
                   
        }
        else
        {//$phrase non nullo
            
            if ($sex!=null OR $age!=null){
            
            $jointable="JOIN events ON places.big = events.place_big ".
                       "JOIN checkins ON events.big = checkins.event_big ".
                       "JOIN members ON checkins.member_big = members.big  ";
            } else $jointable="";
            
            //print($filterString);
            
            $queryOLD = "WITH plids as (".
                     "WITH tsqry as (SELECT to_tsquery('pg_catalog.italian',$$" . $phrase . "$$) as qry) ".
                     "SELECT place_big, greatest(rank_pl, rank_ev) as rank ".
                     "FROM (".
                        "SELECT places.big as place_big, ts_rank_cd(places.tsv, qry, 36) AS rank_pl, ".
                        "places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " .
                        "FROM tsqry,".$table_options.
                        $jointable.
                        " WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") . 
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= " . $rating_avg . " AND " : "") . 
                        " qry @@ places.tsv AND places.status < 255 AND places.published = 1 " . $filterString . " ) plsel ".
                        "FULL OUTER JOIN ".
                        "(SELECT place_big, AVG(ts_rank_cd(events.tsv, qry, 36)) AS rank_ev ".
                        "FROM tsqry, events ".
                        "INNER JOIN places ON places.big = events.place_big ".
                        "WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") .
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= ". $rating_avg . " AND " : "") .
                        " qry @@ events.tsv AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                        "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evsel ".
                        "USING (place_big) ".
                        "ORDER BY \"Place__distance\" ASC, rank DESC LIMIT " . API_PER_PAGE . " OFFSET ". $offset . 
                        ") ".
                        "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                        "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                        "places.address_street AS \"Place__address_street\", ".
                        "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                        "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                        "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                        "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                        "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                        ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) .
                        " FROM plids ".
                        "JOIN ".$table_options." ON plids.place_big = places.big ".
                        "JOIN regions ON regions.id = places.region_id ".
                        "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                        "LEFT JOIN ( ".
                            "SELECT place_big, array_agg(events.name) as eventnames,".
                            "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                            "FROM events ".
                            "JOIN plids USING (place_big) ".
                            "WHERE (events.status = 1) ".
                            "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                            "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                            "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                            "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                            "GROUP BY place_big) evts ON plids.place_big = evts.place_big ".
                        "ORDER BY \"Place__distance\" ASC";
           
               
            
         $query = "WITH plids as (".
                     "WITH tsqry as (SELECT to_tsquery('pg_catalog.italian',$$" . $phrase . "$$) as qry) ".
                     "SELECT place_big, greatest(rank_pl, rank_ev) as rank,\"Place__distance\" ".
                     "FROM (".
                        "SELECT places.big as place_big, ts_rank_cd(places.tsv, qry, 36) AS rank_pl, ".
                        "places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " .
                        "FROM tsqry,".$table_options.
                        $jointable.
                        " WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") . 
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= " . $rating_avg . " AND " : "") . 
                        " places.published = 1 AND ".
                        " qry @@ places.tsv AND places.status < 255 AND places.published = 1 " . $filterString . " ) plsel ".
                        "FULL OUTER JOIN ".
                        "(SELECT place_big, AVG(ts_rank_cd(events.tsv, qry, 36)) AS rank_ev ".
                        "FROM tsqry, events ".
                        "INNER JOIN places ON places.big = events.place_big ".
                        "WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") .
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= ". $rating_avg . " AND " : "") .
                        " qry @@ events.tsv AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                        "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evsel ".
                        "USING (place_big) ".
                        "WHERE \"Place__distance\" < ".NEARBY_RADIUS. " * 16.09344 ".
                        "ORDER BY \"Place__distance\" ASC, rank DESC LIMIT " . API_PER_PAGE . " OFFSET ". $offset . 
                        ") ".
                        "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                        "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                        "places.address_street AS \"Place__address_street\", ".
                        "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                        "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                        "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                        "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                        "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                        ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) .
                        " FROM plids ".
                        "JOIN ".$table_options." ON plids.place_big = places.big ".
                        "JOIN regions ON regions.id = places.region_id ".
                        "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                        "LEFT JOIN ( ".
                            "SELECT place_big, array_agg(events.name) as eventnames,".
                            "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                            "FROM events ".
                            "JOIN plids USING (place_big) ".
                            "WHERE (events.status = 1) ".
                            "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                            "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                            "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                            "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                            "GROUP BY place_big) evts ON plids.place_big = evts.place_big ".
                            "ORDER BY \"Place__distance\" ASC";
                    
             
             $countQuery=$query;
             $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$countQuery);
             $countQuery = 'WITH contatore AS ('.$query_count.')'.'SELECT COUNT(*) FROM contatore';
            
           /* $countQuery = 'WITH tsqry as (SELECT to_tsquery(\'pg_catalog.italian\',$$' . $phrase . '$$) as qry)
                SELECT COUNT(*)
                FROM
                (
                    SELECT places.big as place_big,
                    places.lonlat <@> \''. $coords .'\'::point AS "Place__distance" 
                    FROM tsqry, '.$table_options.' '.$jointable.' 
                    WHERE
                        ' . (!empty($region_id) ? 'places.region_id = ' . $region_id . ' AND ' : '') . '
                        ' . (!empty($cat_id) ? 'places.category_id = ' . $cat_id . ' AND ' : '') . '
                        ' . (!empty($rating_avg) ? 'places.rating_avg >= ' . $rating_avg . ' AND ' : '') . '
                        qry @@ places.tsv  
                        AND places.status < 255 '. $filterString .'
                ) plsel
                FULL OUTER JOIN
                (
                    SELECT place_big
                    FROM tsqry, events
                    INNER JOIN places ON places.big = events.place_big
                    WHERE
                        ' . (!empty($region_id) ? 'places.region_id = ' . $region_id . ' AND ' : '') . '
                        ' . (!empty($cat_id) ? 'places.category_id = ' . $cat_id . ' AND ' : '') . '
                        ' . (!empty($rating_avg) ? 'places.rating_avg >= ' . $rating_avg . ' AND ' : '') . '
                        qry @@ events.tsv
                        AND (events.status = 1)
                        AND (events.start_date IS NULL or events.start_date < current_timestamp) AND (events.end_date IS NULL or events.end_date > current_timestamp) AND (events.daily_start IS NULL OR events.daily_start < localtime) AND (events.daily_end IS NULL OR events.daily_end > localtime)
                    GROUP BY place_big
                ) evsel
                USING (place_big) '.
                "WHERE places.published = 1 AND \"Place__distance\" < ".NEARBY_RADIUS. " * 1.609344 "; */
        }

        $db = $this->Place->getDataSource();
       //print($countQuery);
        //print($query);
        $this->log("------------PLACES CONTROLLER-----------");
        $this->log("---------------query--------------------");
        $this->log($query);
        $this->log("------------Fine PLACES CONTROLLER------");  
        
       // debug($query);
        //print($query);
        try {
            $places = $db->fetchAll($query);
            if ($offset == 0)
            {
                $plCount = $db->fetchAll($countQuery);
                $placesCount = $plCount[0][0]['count'];
            }
        }
        catch (Exception $e)
        {
            debug($e);
        }

        
//        debug($places);
//        debug($placesCount);

//        if (empty($places) && $placesCount > 0 && isset($params['offset'])) {    //if no results on this page, go to first page
//            unset($params['offset']);
//            unbindAllBut($this->Place, array('Gallery', 'DefaultPhoto'));
//            $places = $this->Place->find('all', $params);
//        }

        // Add photos
        // Preprocessing to fit the methods
        if (count($places)>0){
        
        foreach ($places as &$plc)
        {
            if ($plc['Place']['distance'] < CHECKIN_RADIUS) {
                $plc['Place']['Checkable'] = '1';
            } else {
                    $plc['Place']['Checkable'] = '0';
            }
            
            
            $gallery = $plc['Gallery'];
            unset($plc['Gallery']);
            $plc['Gallery'][0] = $gallery;

            $names = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['names']));
            $dates = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['dates']));
            $bigs = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['bigs']));
            $name = '';
            $date = '';
            $big = '';
            foreach ($dates as $key=>$val)
            {
                if (empty($date) || $date < $val)
                {
                    $date = $val;
                    $name = $names[$key];
                    $big = $bigs[$key];
                }
            }
            unset($plc['Event']['names']);
            unset($plc['Event']['dates']);
            unset($plc['Event']['bigs']);
            $plc['Event']['name'] = $name;
            $plc['Event']['big'] = $big;
        }
        }
        $places = $this->_addPlacePhotoUrls($places);
        //Aggiunge il numero di Join e Checkins dei Places passati
        $places = $this->_addPlaceCheckedInCount($places);
        //$places=null;
                
       if ($filteroptions=='rating') usort( $places, 'PlacesController::multiFieldSortArray' );
        //print($placesCount);                     
               
        $result = array('places' => $places);
        if (isset($placesCount))
            $result['places_count'] = $placesCount;
        
                
        $this->_apiOk($result);
      
    //print($countQuery);
    }
    
    
    
    public function api_listOLD() {

        // Variables
        App::uses('Search', 'Lib');
        
        // removed lon and lat for no geoloc
        $this->_checkVars(array(),array('lon','lat','name','category','city','rating','offset','sex','age','filteroptions'));
        
        $phrase = isset($this->api['name']) ? Search::PrepareTSQuery($this->api['name']) : null;
        $cat_id = isset($this->api['category']) ? $this->api['category'] : null;
        $region_id = isset($this->api['city']) ? $this->api['city'] : null;
        $rating_avg = isset($this->api['rating']) ? $this->api['rating'] : null;
        $offset = isset($this->api['offset']) ? $this->api['offset'] * API_PER_PAGE : 0;
    

	    $this->log("var passate: ".serialize($this->api));
        $this->log("getquotes ".get_magic_quotes_gpc());
        // PUT IN DEFAULT FILE!!!
        $coords = '(40.6300568,16.2894573999997)';
        $lon = '16.2894573999999';
        $lat='40.6300568';
                
        if  (isset($this->api['lon']) AND isset($this->api['lat']))
        {
        $lon = isset($this->api['lon']) ? $this->api['lon'] : null;
        $lat = isset($this->api['lat']) ? $this->api['lat'] : null;
        $coords = '(' . $lon . ',' . $lat . ')';
        
        }
        else
        {  // try
            //Gran parte del codice sotto potrebbe essere abbreviato visto che Places uses Member
            //$memberdata=$this->Member->getMemberByBig ( $this->logged ['Member'] ['big'] )
            //$coords=$memberdata['Member']['last_lonlat']
             
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
            //    debug($datapos['Member']['last_lonlat']);
                if ($datapos['Member']['last_lonlat']!=null)
                {
                     $coords = $datapos['Member']['last_lonlat'];
                    $xcoords  = str_replace("(", "", $coords);
                    $xcoords  = str_replace(")", "", $xcoords);
                    $lecoords=split(',',$xcoords);
                    $lon=$lecoords[0];
                    $lat=$lecoords[1];
                //    debug('a');
                }
            }
            
        }
              
        
        $filteroptions = isset($this->api['filteroptions']) ? $this->api['filteroptions'] : null;
        $sex=isset($this->api['sex']) ? $this->api['sex'] : null;
        $age=isset($this->api['age']) ? $this->api['age'] : null;
        $myFilter=array();
        
        $table_options="places ";
        
        if ($filteroptions!=null){
            
            switch ($filteroptions){
                
                case 'bookmark': 
                
                  $table_options="(SELECT places.* ".
                                 "FROM bookmarks ".
                                 "JOIN places ON bookmarks.place_big=places.big ".
                                 "WHERE bookmarks.member_big = ".$this->logged['Member']['big'].
                                 " AND places.published = 1 ) places ";
                                   break;
                case 'friend' :
                    
                    
                  $placesFriends=$this->placesWithFriendsCheckins($this->logged['Member']['big']);
                  
                  if (count($placesFriends)>0) $placesWithFriends=implode(',',$placesFriends);
                                   else $placesWithFriends='null';
                   
                  $table_options="(SELECT places.* ".
                                 "FROM places ".
                                 "JOIN events ON events.place_big=places.big ".
                                 "JOIN checkins ON checkins.event_big=events.big ".
                                 "WHERE checkins.physical=1 AND checkins.checkout IS NULL ".
                                 "AND places.published = 1 ".
                                 "AND checkins.member_big IN (".$placesWithFriends.") ) places ";
                                 break;
            }
            
            
        }
        $this->log("----PlacesController---api_list---------------------");
        $this->log("member: ".$this->logged['Member']['big']);
        $this->log("phrase: ".$phrase." cat_id: ".$cat_id." region_id: ".$region_id." rating_avg: ".$rating_avg);
        $this->log("lon: ".$lon." lat: ".$lat." offset: ".$offset." coords: ".$coords);
        $this->log("filteroptions: ".$filteroptions);
        $this->log("sex: ".$sex." age: ".$age);
        $this->log("----------------------------------------------------");
        
        
        if ($sex!=null) $myFilter[]=" members.sex='$sex' ";
        if ($age!=null)  {
            
            
                        switch ($age){
                            //0: <25; 1: 25-35; 2: 35-45; 3: 45-55; 4: >55  
                            case 0: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) < 25) ";
                                    break;
                            case 1: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 25 AND 35) ";
                                    break;
                            case 2: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 35 AND 45) ";
                                    break;
                            case 3: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) BETWEEN 45 AND 55) ";
                                    break;
                            case 4: $myFilter[]=" (date_part('year',age(now(),members.birth_date)) > 55) ";
                                    break;
                              
                                }
        }
        if (count($myFilter)>0) {//filtro per sex e/o age
                                $filterString=implode('AND',$myFilter);
                                $filterString='AND '.$filterString;
                                
                $queryPrefix='WITH plisel as (SELECT DISTINCT ON (places.big) places.big '. 
                                  'FROM '.$table_options.
                                  'JOIN events ON places.big = events.place_big '.
                                  'JOIN checkins ON events.big = checkins.event_big '.
                                  'JOIN members ON checkins.member_big = members.big '.
                                  'WHERE places.status < 255 AND places.published = 1 AND checkins.checkout IS NULL '.$filterString.' '. 
                                  'ORDER BY places.big,( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC '.
                                  'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                                                                        
                            
                                
                      } else { 
                                 
               $queryPrefix='WITH plisel as ( SELECT places.big '.  
                                             'FROM '.$table_options.
                                             'WHERE places.status < 255 AND places.published = 1 '. 
                                             'ORDER BY ( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC '.
                                             'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                                             
                    $filterString='';
                     
                   // print('qpref '.$queryPrefix);                        
                                    }
        //print('name vale '.$phrase);
        // Match coords against regular expression
        $crdsMatch = preg_match('/^\(([\-\+\d\.]+),([\-\+\d\.]+)\)$/', $coords);
        if ($crdsMatch == FALSE && (!empty($lon) || !empty($lat))) {
                        $this->_apiEr(__('Le coordinate non sono valide: lon and/or lat'));
                    }

        if (empty($phrase) && empty($cat_id) && empty($region_id) && empty($rating_avg) && $crdsMatch)
        {
                       
            $query = $queryPrefix.
                    "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                    "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                    "places.address_street AS \"Place__address_street\",".
                    "places.address_street_no AS \"Place__address_street_no\",".
                    "places.lonlat AS \"Place__coordinates\",regions.city AS \"Place__city\",".
                    "photos.big AS \"DefaultPhoto__big\", photos.original_ext AS \"DefaultPhoto__original_ext\",".                           "photos.gallery_big AS \"Gallery__big\",photos.status AS \"DefaultPhoto__status\",".
                    "evts.eventnames AS \"Event__names\", evts.eventdates AS \"Event__dates\",".
                    "evts.eventbigs AS \"Event__bigs\",".
                    "places.lonlat <@> '" . $coords . "'::point  /*lon,lat*/ AS \"Place__distance\" ".
                    "FROM plisel ".
                    "JOIN ".$table_options." ON places.big = plisel.big ".
                    "JOIN regions ON regions.id = places.region_id ".
                    "LEFT JOIN ".
                        "(SELECT place_big, array_agg(name) as eventnames,array_agg(created) as eventdates,".
                        "array_agg(events.big) as eventbigs ".
                        "FROM events ".
                        "WHERE place_big IN (SELECT big FROM plisel) AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < now()) ".
                        "AND (events.end_date IS NULL or events.end_date > NOW()) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big ) evts ON places.big = evts.place_big ".
                    "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                    "WHERE earth_box(ll_to_earth(" . $lat . " /*lat*/, " . $lon . " /*lon*/)," . NEARBY_RADIUS . 
                    " /* miles */ * 16.09344/*metres*/) @> ll_to_earth(places.lonlat[1], places.lonlat[0]) ". 
                    "ORDER BY \"Place__distance\" ASC";

       $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$query);
       $query_count=str_replace("ORDER BY \"Place__distance\" ASC",'',$query);
      //$query_count=str_replace('ORDER BY ( places.lonlat <-> \'' . $coords . '\'::point /*lon,lat*/) ASC ','',$query);
              
       $countQuery = 'WITH contatore AS ('.$query_count.')'.' SELECT COUNT(*) FROM contatore ';     
                
               //print($query);
      
        }
        elseif (empty($phrase))
        {
            $whereArr = array();
            if (!empty($region_id))
            {
                $whereArr[] = ' places.region_id = ' . $region_id . ' ';
            }
            if (!empty($cat_id))
            {
                $whereArr[] = ' places.category_id = ' . $cat_id . ' ';
            }
            if (!empty($rating_avg))
            {
                $whereArr[] = ' places.rating_avg >= ' . $rating_avg . ' ';
            }
            if (!empty($whereArr))
                $where = 'AND ' . implode('AND', $whereArr);

                
            if (count($myFilter)>0){//se abbiamo anche il filtro sex e/o age
                
                                    $queryPrefix2='WITH plids as ( SELECT DISTINCT ON (places.big) places.big '.
                                                  'FROM '.$table_options.
                                                  'JOIN events ON places.big = events.place_big '.
                                                  'JOIN checkins ON events.big = checkins.event_big '.
                                                  'JOIN members ON checkins.member_big = members.big '.
                                                  'WHERE places.status < 255 AND places.published = 1 AND checkins.checkout IS NULL '.$filterString. 
                                                  (!empty($where)  ? $where : '') .' '. 
                                                  'ORDER BY places.big,places.name ASC '.
                                                  'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ')';
                       
                
            } else {//se non abbiamo filtri sex e age
                            $queryPrefix2='WITH plids as ( SELECT places.big,places.lonlat <@> \''.$coords.'\'::point AS "Place__distance" '.
                                                           'FROM '.$table_options.
                                                           'WHERE places.status < 255 AND places.published = 1 '.
                                                           (!empty($where)  ? $where : '') . ' '.
                                                           'ORDER BY "Place__distance" ASC,places.name ASC '.
                                                           'LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset . ') ';
                                         
            }   
                      
            
            $query = $queryPrefix2.
                    "SELECT places.name AS \"Place__name\",places.big AS \"Place__big\",".
                    "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                    "places.address_street AS \"Place__address_street\",".
                    "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                    "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                    "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                    "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                    "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                     ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) . 
                    "FROM plids ".
                    "JOIN ".$table_options." USING (big) ".
                    "JOIN regions ON regions.id = places.region_id ".
                    "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                    "LEFT JOIN (".
                        "SELECT place_big, array_agg(events.name) as eventnames,".
                        "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                        "FROM events ".
                        "JOIN plids ON plids.big = events.place_big ".
                        "WHERE (events.status = 1) AND (events.start_date IS NULL or events.start_date < now()) ".
                        "AND (events.end_date IS NULL or events.end_date > NOW()) AND ".
                        "(events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evts ON plids.big = evts.place_big ". 
                    "ORDER BY \"Place__distance\" ASC";
                   

       $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$query);
       $countQuery = 'WITH contatore AS ('.$query_count.')'.'SELECT COUNT(*) FROM contatore';        
                   
                   
        }
        else
        {//$phrase non nullo
            
            if ($sex!=null OR $age!=null){
            
            $jointable="JOIN events ON places.big = events.place_big ".
                       "JOIN checkins ON events.big = checkins.event_big ".
                       "JOIN members ON checkins.member_big = members.big  ";
            } else $jointable="";
            
            //print($filterString);
            
            $queryOLD = "WITH plids as (".
                     "WITH tsqry as (SELECT to_tsquery('pg_catalog.italian',$$" . $phrase . "$$) as qry) ".
                     "SELECT place_big, greatest(rank_pl, rank_ev) as rank ".
                     "FROM (".
                        "SELECT places.big as place_big, ts_rank_cd(places.tsv, qry, 36) AS rank_pl, ".
                        "places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " .
                        "FROM tsqry,".$table_options.
                        $jointable.
                        " WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") . 
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= " . $rating_avg . " AND " : "") . 
                        " qry @@ places.tsv AND places.status < 255 AND places.published = 1 " . $filterString . " ) plsel ".
                        "FULL OUTER JOIN ".
                        "(SELECT place_big, AVG(ts_rank_cd(events.tsv, qry, 36)) AS rank_ev ".
                        "FROM tsqry, events ".
                        "INNER JOIN places ON places.big = events.place_big ".
                        "WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") .
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= ". $rating_avg . " AND " : "") .
                        " qry @@ events.tsv AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                        "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evsel ".
                        "USING (place_big) ".
                        "ORDER BY \"Place__distance\" ASC, rank DESC LIMIT " . API_PER_PAGE . " OFFSET ". $offset . 
                        ") ".
                        "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                        "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                        "places.address_street AS \"Place__address_street\", ".
                        "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                        "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                        "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                        "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                        "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                        ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) .
                        " FROM plids ".
                        "JOIN ".$table_options." ON plids.place_big = places.big ".
                        "JOIN regions ON regions.id = places.region_id ".
                        "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                        "LEFT JOIN ( ".
                            "SELECT place_big, array_agg(events.name) as eventnames,".
                            "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                            "FROM events ".
                            "JOIN plids USING (place_big) ".
                            "WHERE (events.status = 1) ".
                            "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                            "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                            "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                            "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                            "GROUP BY place_big) evts ON plids.place_big = evts.place_big ".
                        "ORDER BY \"Place__distance\" ASC";
           
               
            
         $query = "WITH plids as (".
                     "WITH tsqry as (SELECT to_tsquery('pg_catalog.italian',$$" . $phrase . "$$) as qry) ".
                     "SELECT place_big, greatest(rank_pl, rank_ev) as rank,\"Place__distance\" ".
                     "FROM (".
                        "SELECT places.big as place_big, ts_rank_cd(places.tsv, qry, 36) AS rank_pl, ".
                        "places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " .
                        "FROM tsqry,".$table_options.
                        $jointable.
                        " WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") . 
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= " . $rating_avg . " AND " : "") . 
                        " places.published = 1 AND ".
                        " qry @@ places.tsv AND places.status < 255 AND places.published = 1 " . $filterString . " ) plsel ".
                        "FULL OUTER JOIN ".
                        "(SELECT place_big, AVG(ts_rank_cd(events.tsv, qry, 36)) AS rank_ev ".
                        "FROM tsqry, events ".
                        "INNER JOIN places ON places.big = events.place_big ".
                        "WHERE ".
                        (!empty($region_id) ? "places.region_id = " . $region_id . " AND " : "") .
                        (!empty($cat_id) ? "places.category_id = " . $cat_id . " AND " : "") . 
                        (!empty($rating_avg) ? "places.rating_avg >= ". $rating_avg . " AND " : "") .
                        " qry @@ events.tsv AND (events.status = 1) ".
                        "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                        "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                        "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                        "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                        "GROUP BY place_big) evsel ".
                        "USING (place_big) ".
                        "WHERE \"Place__distance\" < ".NEARBY_RADIUS. " * 16.09344 ".
                        "ORDER BY \"Place__distance\" ASC, rank DESC LIMIT " . API_PER_PAGE . " OFFSET ". $offset . 
                        ") ".
                        "SELECT places.name AS \"Place__name\", places.big AS \"Place__big\",".
                        "places.rating_avg AS \"Place__rating_avg\", places.category_id AS \"Place__category_id\",".
                        "places.address_street AS \"Place__address_street\", ".
                        "places.address_street_no AS \"Place__address_street_no\", regions.city AS \"Place__city\",".
                        "places.lonlat AS \"Place__coordinates\",photos.big AS \"DefaultPhoto__big\",".
                        "photos.original_ext AS \"DefaultPhoto__original_ext\", photos.gallery_big AS \"Gallery__big\",".
                        "photos.status AS \"DefaultPhoto__status\",evts.eventnames AS \"Event__names\",".
                        "evts.eventdates AS \"Event__dates\", evts.eventbigs AS \"Event__bigs\" ".
                        ($crdsMatch ? ", places.lonlat <@> '" . $coords . "'::point AS \"Place__distance\" " : "" ) .
                        " FROM plids ".
                        "JOIN ".$table_options." ON plids.place_big = places.big ".
                        "JOIN regions ON regions.id = places.region_id ".
                        "LEFT JOIN photos ON (places.default_photo_big = photos.big) ".
                        "LEFT JOIN ( ".
                            "SELECT place_big, array_agg(events.name) as eventnames,".
                            "array_agg(events.created) as eventdates, array_agg(events.big) as eventbigs ".
                            "FROM events ".
                            "JOIN plids USING (place_big) ".
                            "WHERE (events.status = 1) ".
                            "AND (events.start_date IS NULL or events.start_date < current_timestamp) ".
                            "AND (events.end_date IS NULL or events.end_date > current_timestamp) ".
                            "AND (events.daily_start IS NULL OR events.daily_start < localtime) ".
                            "AND (events.daily_end IS NULL OR events.daily_end > localtime) ".
                            "GROUP BY place_big) evts ON plids.place_big = evts.place_big ".
                            "ORDER BY \"Place__distance\" ASC";
                    
             
             $countQuery=$query;
             $query_count=str_replace('LIMIT ' . API_PER_PAGE . ' OFFSET ' . $offset,'',$countQuery);
             $countQuery = 'WITH contatore AS ('.$query_count.')'.'SELECT COUNT(*) FROM contatore';
            
           /* $countQuery = 'WITH tsqry as (SELECT to_tsquery(\'pg_catalog.italian\',$$' . $phrase . '$$) as qry)
                SELECT COUNT(*)
                FROM
                (
                    SELECT places.big as place_big,
                    places.lonlat <@> \''. $coords .'\'::point AS "Place__distance" 
                    FROM tsqry, '.$table_options.' '.$jointable.' 
                    WHERE
                        ' . (!empty($region_id) ? 'places.region_id = ' . $region_id . ' AND ' : '') . '
                        ' . (!empty($cat_id) ? 'places.category_id = ' . $cat_id . ' AND ' : '') . '
                        ' . (!empty($rating_avg) ? 'places.rating_avg >= ' . $rating_avg . ' AND ' : '') . '
                        qry @@ places.tsv  
                        AND places.status < 255 '. $filterString .'
                ) plsel
                FULL OUTER JOIN
                (
                    SELECT place_big
                    FROM tsqry, events
                    INNER JOIN places ON places.big = events.place_big
                    WHERE
                        ' . (!empty($region_id) ? 'places.region_id = ' . $region_id . ' AND ' : '') . '
                        ' . (!empty($cat_id) ? 'places.category_id = ' . $cat_id . ' AND ' : '') . '
                        ' . (!empty($rating_avg) ? 'places.rating_avg >= ' . $rating_avg . ' AND ' : '') . '
                        qry @@ events.tsv
                        AND (events.status = 1)
                        AND (events.start_date IS NULL or events.start_date < current_timestamp) AND (events.end_date IS NULL or events.end_date > current_timestamp) AND (events.daily_start IS NULL OR events.daily_start < localtime) AND (events.daily_end IS NULL OR events.daily_end > localtime)
                    GROUP BY place_big
                ) evsel
                USING (place_big) '.
                "WHERE places.published = 1 AND \"Place__distance\" < ".NEARBY_RADIUS. " * 1.609344 "; */
        }

        $db = $this->Place->getDataSource();
       //print($countQuery);
        //print($query);
        $this->log("------------PLACES CONTROLLER-----------");
        $this->log("---------------query--------------------");
        $this->log($query);
        $this->log("------------Fine PLACES CONTROLLER------");  
        
       // debug($query);
        //print($query);
        try {
            $places = $db->fetchAll($query);
            if ($offset == 0)
            {
                $plCount = $db->fetchAll($countQuery);
                //print($countQuery);
                $placesCount = $plCount[0][0]['count'];
            }
        }
        catch (Exception $e)
        {
            debug($e);
        }

        
//        debug($places);
//        debug($placesCount);

//        if (empty($places) && $placesCount > 0 && isset($params['offset'])) {    //if no results on this page, go to first page
//            unset($params['offset']);
//            unbindAllBut($this->Place, array('Gallery', 'DefaultPhoto'));
//            $places = $this->Place->find('all', $params);
//        }

        // Add photos
        // Preprocessing to fit the methods
        if (count($places)>0){
        
        foreach ($places as &$plc)
        {
            if ($plc['Place']['distance'] < CHECKIN_RADIUS) {
                $plc['Place']['Checkable'] = '1';
            } else {
                    $plc['Place']['Checkable'] = '0';
            }
            
            
            $gallery = $plc['Gallery'];
            unset($plc['Gallery']);
            $plc['Gallery'][0] = $gallery;

            $names = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['names']));
            $dates = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['dates']));
            $bigs = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['bigs']));
            $name = '';
            $date = '';
            $big = '';
            foreach ($dates as $key=>$val)
            {
                if (empty($date) || $date < $val)
                {
                    $date = $val;
                    $name = $names[$key];
                    $big = $bigs[$key];
                }
            }
            unset($plc['Event']['names']);
            unset($plc['Event']['dates']);
            unset($plc['Event']['bigs']);
            $plc['Event']['name'] = $name;
            $plc['Event']['big'] = $big;
        }
        }
        $places = $this->_addPlacePhotoUrls($places);
        
        $places = $this->_addPlaceCheckedIn($places);
   
                
       if ($filteroptions=='rating') usort( $places, 'PlacesController::multiFieldSortArray' );
                             
               
        $result = array('places' => $places);
        if (isset($placesCount))
            $result['places_count'] = $placesCount;
        
                
        $this->_apiOk($result);
      
    //print($countQuery);
    }

    public static function multiFieldSortArray($x, $y) { // sort an array by position_bonus DESC and distance ASC
                
            return ($x ['Place'] ['rating_avg'] > $y ['Place'] ['rating_avg']) ? - 1 : + 1;
    }
    
    public function placesWithFriendsCheckins($memBig){
    //restituisce i place_big che hanno in checkins i nostri amici
       
              
       $friends=$this->Friend->findAllFriends($memBig);
       
       foreach ($friends as $key=>$val){
        
            $friendsID[]=($val['Friend']['member1_big']==$memBig) ? $val['Friend']['member2_big'] : $val['Friend']['member1_big']; 
                
            }   
       
       if (count($friendsID)>0){
       
       $idFriends=implode(",",$friendsID);     
       
       $db = $this->Place->getDataSource();
              
      
       $query="SELECT places.big ". 
              "FROM places ".
              "JOIN events ON places.big=events.place_big ".
              "JOIN checkins ON events.big=checkins.event_big ".
              "WHERE checkins.physical=1 AND checkins.checkout IS NULL ".
              "AND checkins.member_big IN (".$idFriends.")";
        
       
       $result = $db->fetchAll($query);
       
       foreach ($result as $key=>$val){
           
           $places[]=$result[$key][0]['big'];
           
       }
       
       return $places;
       } else 
        return array();
         
    }
    
    public function isInBookmark($member_big,$place_big){
    
        $db = $this->Place->getDataSource();
        
        $query="SELECT COUNT(*) FROM bookmarks WHERE member_big=$member_big AND place_big=$place_big";
        
        $status = $db->fetchAll($query);
                                        
        $result=($status[0][0]['count']>0) ? true : false;
        
        return $result;
     }
	
    /**
	 * Return detailed place data
	 */
	public function api_detail() {

		$this->_checkVars(array('place_big'));
        $memBig = $this->logged['Member']['big'];
        
		$params = array();
		$conditions = array();
		if (isset($this->api['place_big']))
		{
			$conditions['Place.big'] = $this->api['place_big'];
		}

		$params['conditions'] = $conditions;
		$params['fields'] = array(
			'Place.big',
			'Place.category_id',
			'Place.name',
			'Place.short_desc',
			'Place.long_desc',
			'Place.url',
			'Place.phone',
			'Place.email',
			'Place.address_formated',
			'Place.address_street_no',
			'Place.address_street',
			'Place.address_town',
			'Place.address_province',
			'Place.address_region',
			'Place.address_country',
			'Place.address_zip',
			'Place.opening_hours',
			'Place.news',
			'Place.photo_updated',
			'Place.lonlat',
			'Place.rating_avg',
			'Place.rating_count',
			'Place.default_photo_big',
			'Place.lonlat',
			'DefaultPhoto.*',
			'Region.city',
			'Region.country',
		);

		unbindAllBut($this->Place, array('Gallery', 'DefaultPhoto', 'CatLang', 'Region'));
		$place = $this->Place->find('first', $params);
		$place = $this->_addPlacePhotoUrls($place);

		if (empty($place))
		{
			$this->_apiEr(__('Posto inesistente.'));
		}

		$category = $this->Place->Category->getOne( $place['Place']['category_id'] );

		$place['CatLang'] = $category['CatLang'];
		$place['CatLang']['photo'] = $this->FileUrl->category_picture($place['Place']['category_id'], $category['Category']['updated']);
        
        /* QUESTA PARTE DI CODICE GENERAVA I CONTEGGI JOIN E CHECKINS SULL'ULTIMO EVENTO DEL PLACE
        
		$event = $this->Place->getCurrentEvent($place['Place']['big'], true, true);

		// Add hidden parameter
		if ($event['Event']['type'] == EVENT_TYPE_DEFAULT && $event['Event']['status'] == INACTIVE)
		{
			$event['Event']['hidden'] = TRUE;
		}
		else
		{
			$event['Event']['hidden'] = FALSE;
		}

		// Add number of people joined and checked in
		
        $checkinsCount = $this->Place->Event->Checkin->getCheckinsTotalFor($event['Event']['big']);
		$joinsCount = $this->Place->Event->Checkin->getJoinsCountFor($event['Event']['big'], $memBig);
        */
        
        $checkinsCount = intval($this->Place->Event->Checkin->getCheckinCountsForPlaceBigs( $this->api['place_big'], 1 ));
        $joinsCount = intval($this->Place->Event->Checkin->getCheckinCountsForPlaceBigs( $this->api['place_big'], 0 ));
		$event['Checkins'] = array('count' => $checkinsCount);
		//changed to all count 8/10(14 $event['Checkins'] = array('count' => $checkinsCount);
		$event['Joins'] = array('count' => $joinsCount);

		// Checkin valid indicator
		$checkinValid = $this->Place->Event->Checkin->isOrWasCheckedIn($memBig, $event['Event']['big']);
		$event['Event']['checkin_valid'] = $checkinValid;

		// Event unset unneeded
		unset($event['Event']['slug']);
		unset($event['Event']['type']);
		unset($event['Event']['status']);
		unset($event['Event']['created']);
		unset($event['Event']['updated']);
		unset($event['Event']['tsv']);

		$place += $event;
		$place = $this->_addEventPhotoUrls($place);

		$bookmark = $this->Bookmark->find('count', array(
			'conditions' => array(
				'Bookmark.place_big' => $place['Place']['big'],
				'Bookmark.member_big' => $this->logged['Member']['big'],
			),
			'recursive' => -1,
		));

		$place['Bookmark'] = (bool) $bookmark;

		// Determine if there are past events
		$params = array(
			'conditions' => array(
				'Event.place_big' => $this->api['place_big'],
				'Event.type !=' => EVENT_TYPE_DEFAULT,
				'Event.status !=' => INACTIVE,
				'Event.end_date <' => 'NOW()',
			)
		);
		$pastEvents = $this->Place->Event->find('count', $params);
		$place['Place']['past_events_count'] = $pastEvents;

		$this->_apiOk($place);
	}

	/**
	 * Get nearby places for checkin
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
			$this->_apiEr(__('Le coordinate non sono valide: lon and/or lat'));
		}

		$all_nearby = $this->Place->getNearbyPlaces($coords);

		// Add pictures
		$all_nearby = $this->_addPlacePhotoUrls($all_nearby);

		// Add checked in
		
		$all_nearby = $this->_addPlaceCheckedIn($all_nearby);
		
		
		$categories = $this->Place->Category->getAll();

		// Find places where the user can check in
		$checkable = array();
		$nearby = array();
		foreach ($all_nearby as $key=>$place) {

			$place['CatLang'] = $categories[ $place['Place']['category_id'] ]['CatLang'];
			$place['CatLang']['photo'] = $this->FileUrl->category_picture($place['Place']['category_id'], $categories[ $place['Place']['category_id'] ]['Category']['updated']);

			$place['Place']['distance'] = floatval($place['Place']['distance']);
			$place['Place']['lat'] = floatval($place['Place']['lat']);
			$place['Place']['lon'] = floatval($place['Place']['lon']);

			if ($place['Place']['distance'] < CHECKIN_RADIUS) {
				$checkable[] = $place;
			} else {
				$nearby[] = $place;
			}

		}

		// NEW for Win app
		
		$all_nearby = $this->Checkin->getNearbyPeople($coords,$memBig);
		
		
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
		
					$val[0]['Checkin']=$this->Checkin->getNearbyCheckinsMember($val[0]['big']);
	
		
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
		
		
		
		
		$result = array('nearby' => $nearby, 'checkable' => $checkable);
		$result['People'] =$xresponse; 
		if (empty($result)) {
			$this->_apiEr(__('Errore. Nessun Posto trovato nelle vicinanze.'), __('Non ci sono Posti nelle vicinanze.'));
		} else {
			$this->_apiOk($result);
		}

	}

	/**
	 * Displaying places list for frontend
	 */
	public function index() {

		$this->_listing();
		$this->set('listing', true);

	}

	/**
	 * Map of places for frontend
	 */
	public function map() {

		if (isset($this->request->params['named']['map_bounds'])) {
			$map_bounds = $this->request->params['named']['map_bounds'];
			foreach($map_bounds as $key=>$val) {
				$val = explode(',', $val);
				$map_bounds[ $key ] = array('lon' => trim($val[1]), 'lat' => trim($val[0]));
			}
			$bounds_array = array($map_bounds['ll']['lon'], $map_bounds['ll']['lat'], $map_bounds['ur']['lon'], $map_bounds['ur']['lat']);
		} else {
			$bounds_array = array();
		}

		$this->_listing('map', $bounds_array);

		if ($this->isAjax) {
			$this->render('map_json');
		}

	}

	/**
	 * Get listing (for both list and map)
	 */
	private function _listing($type='list', $map_bounds=array()) {

		// Variables
		App::uses('Search', 'Lib');
//		debug($this->request);
//		debug($this->request->params);
//		debug($this->request->data);
		if (isset($this->request->data['Place']))
		{
			$this->request->params['named'] = array_merge($this->request->params['named'], $this->request->data['Place']);
		}
//		debug($this->request->params);
		if (empty($this->request->params['named']['rating']))
			unset($this->request->params['named']['rating']);
		if (empty($this->request->params['named']['country']))
			unset($this->request->params['named']['country']);
		if (empty($this->request->params['named']['city']))
		{
			unset($this->request->params['named']['city']);
			if (isset($this->request->params['named']['country']))
				unset($this->request->params['named']['country']);
		}
		if (empty($this->request->params['named']['distance']))
			unset($this->request->params['named']['distance']);
		$this->set('pars', $this->request->params['named']);

		$offset = isset($this->request->params['named']['offset']) ? $this->request->params['named']['offset'] : 0 ;
		$this->set('offset', $offset);

		$category_id = isset($this->request->params['named']['category']) ? $this->request->params['named']['category'] : NULL ;
		$this->set('category_id', $category_id);

		$city = !empty($this->request->params['named']['city']) ? $this->request->params['named']['city'] : NULL ;
		$this->set('city', $city);

		$distance = !empty($this->request->params['named']['distance']) ? $this->request->params['named']['distance'] : NULL ;
		$this->set('distance', $distance);

		$sort = isset($this->request->params['named']['sort']) ? $this->request->params['named']['sort'] : NULL ;
		$this->set('sort', $sort);

		$searchVal =	isset($this->request->params['named']['search']) ? $this->request->params['named']['search'] : NULL ;
		$this->set('search', $searchVal);

		$rating_avg = !empty($this->request->params['named']['rating']) ? $this->request->params['named']['rating'] : NULL;
		$this->set('rating', $rating_avg);
		$this->_sidebarPlaces();

		// Get filter sidebar data
		$whereCats = array();
		$country   = !empty($this->request->params['named']['country']) ? $this->request->params['named']['country'] : NULL ;
		$region    = $this->Place->Region->getRegionByCityAndCountry($city, $country);
		$region_id = !empty($region['Region']['id']) ? $region['Region']['id'] : null;
		if (isset($region_id))
		{
			$whereCats[] = ' places.region_id = ' . $region_id;
		}
		if (isset($rating_avg))
		{
			$whereCats[] = ' places.rating_avg >= ' . $rating_avg;
		}

		if (!empty($map_bounds)) {

			$polygon = array(
				'll' => $map_bounds[0].','.$map_bounds[1],
				'lr' => $map_bounds[0].','.$map_bounds[3],
				'ur' => $map_bounds[2].','.$map_bounds[3],
				'ul' => $map_bounds[2].','.$map_bounds[1],
			);

			$whereCats[] = " polygon '((".implode('),(', $polygon)."))' @> places.lonlat ";

		}

		$search     = isset($this->request->params['named']['search']) ? Search::PrepareTSQuery($this->request->params['named']['search']) : NULL ;
		$category = $this->Place->Category;/* @var $category Category */
		$categories = $category->getCategoriesWithPlaceCounts($whereCats, $search);
		$this->set('categories', $categories);
//		debug($categories);

		// Get filter topbar data
		$category_name = 'All venues';
		foreach ($categories as $cat)
		{
			if ($cat['Category']['id'] == $category_id)
			{
				$category_name = $cat['Category']['name'];
				break;
			}
		}
		$this->set('category_name', $category_name);

		// ads here will be on the left side

		if ($type == 'map' && empty($map_bounds)) {
			$this->set('places', array());
			$this->set('placesCount', 0);
			return;
		}

		// Get Places
		$query = 'SELECT
				places.name AS "Place__name", places.big AS "Place__big", places.short_desc AS "Place__short_desc", places.slug AS "Place__slug",
				places.default_photo_big AS "Place__default_photo_big", places.rating_avg AS "Place__rating_avg", places.lonlat AS "Place__lonlat",
				places.category_id AS "Place__category_id", places.address_street AS "Place__address_street", places.address_street_no AS "Place__address_street_no",
				regions.city AS "Region__city",
				evts.eventnames AS "Event__names", evts.eventdates AS "Event__dates", evts.eventbigs AS "Event__bigs", evts.slugs AS "Event__slugs",
				evts.dstarts AS "Event__dstarts", evts.dends AS "Event__dends",
				photos.big AS "DefaultPhoto__big", photos.original_ext AS "DefaultPhoto__original_ext", photos.gallery_big AS "Gallery__big", photos.status AS "DefaultPhoto__status"
			FROM places
			LEFT JOIN regions ON (places.region_id = regions.id)
			LEFT JOIN photos ON (places.default_photo_big = photos.big)
			LEFT JOIN (
			 SELECT events.place_big, array_agg(events.big) as eventbigs, array_agg(events.name) as eventnames, array_agg(events.slug) as slugs,
			  array_agg(events.daily_start) as dstarts, array_agg(events.daily_end) as dends,
			 array_agg(events.created) as eventdates  FROM events
			 WHERE  (events.start_date IS NULL or events.start_date < now()) AND (events.end_date IS NULL or events.end_date > now())
			 AND (events.daily_start IS NULL OR events.daily_start < localtime) AND (events.daily_end IS NULL OR events.daily_end > localtime) AND (events.status = 1)
			 ' . (!empty($search) ? 'AND to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$) @@ events.tsv ' : '' ) .
			 ' GROUP BY events.place_big
			) as evts ON evts.place_big = places.big
			 WHERE places.status != ' . DELETED . ' ' . (!empty($search) ? 'AND to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$) @@ places.tsv ' : '' ) .
			' ';
//photos.status != ' . DELETED . ' and 
		$countQuery = 'SELECT count (*) FROM places
			LEFT JOIN (
			 SELECT events.place_big, array_agg(events.big) as eventbigs, array_agg(events.name) as eventnames, array_agg(events.created) as eventdates  FROM events
			 WHERE  (events.start_date IS NULL or events.start_date < now()) AND (events.end_date IS NULL or events.end_date > now())
			 AND (events.daily_start IS NULL OR events.daily_start < localtime) AND (events.daily_end IS NULL OR events.daily_end > localtime) AND (events.status = 1)
			 ' . (!empty($search) ? 'AND to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$) @@ events.tsv ' : '' ) .
			 ' GROUP BY events.place_big
			) as evts ON evts.place_big = places.big
			 WHERE places.status != ' . DELETED . ' ' . (!empty($search) ? 'AND to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$) @@ places.tsv ' : '' ) .
			' ';

		$where = array();
		if (isset($category_id))
		{
			$where[] = ' places.category_id = ' . $category_id;
		}
		if (isset($region_id))
		{
			$where[] = ' places.region_id = ' . $region_id;
		}
		if (isset($rating_avg))
		{
			$where[] = ' places.rating_avg >= ' . $rating_avg;
		}

		if (!empty($map_bounds)) {

			$polygon = array(
				'll' => $map_bounds[0].','.$map_bounds[1],
				'lr' => $map_bounds[0].','.$map_bounds[3],
				'ur' => $map_bounds[2].','.$map_bounds[3],
				'ul' => $map_bounds[2].','.$map_bounds[1],
			);

			$where[] = " polygon '((".implode('),(', $polygon)."))' @> places.lonlat ";

		}

		// TODO Distance filter

		// Add other params
		if (!empty($where))
		{
			$query .= '  AND ' . implode(' AND ', $where) . ' ';
			$countQuery .= ' AND ' . implode(' AND ', $where) . ' ';
		}

		if (!empty($map_bounds)) {

			$center_lon = floatval( $map_bounds[0] + abs($map_bounds[2] - $map_bounds[0]) / 2 );
			$center_lat = floatval( $map_bounds[1] + abs($map_bounds[1] - $map_bounds[3]) / 2 );

			$query .= 'ORDER BY places.rating_avg DESC, POINT('.$center_lon.','.$center_lat.') <-> places.lonlat ASC LIMIT ' . MAP_MAXIMUM_RESULTS;

		} else {

			//Order by
			switch ($sort)
			{
				case 'rating':
					$query .= ' ORDER BY COALESCE(places.rating_avg, 0) DESC';
					break;
//				case 'distance':
//					$query .= ' ORDER BY places.rating_avg DESC';
//					break;
				case 'relevance':
					if (isset($search))
					{
						$query .= ' ORDER BY ts_rank_cd(places.tsv, to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$), 36) DESC ';
						break;
					}
				case 'name':
				default:
					$query .= ' ORDER BY places.name ASC ';
			}

			$query .= ' LIMIT ' . FRONTEND_PER_PAGE;
			if ($offset)
			{
				$query .= ' OFFSET ' . $offset * FRONTEND_PER_PAGE;
			}

		}

//		debug($query);
		$db = $this->Place->getDataSource();
		try {
			$places = $db->fetchAll($query);
			$plCount = $db->fetchAll($countQuery);
			$placesCount = $plCount[0][0]['count'];
		}
		catch (Exception $e)
		{
//			debug($e->getMessage());
		}
//		debug($places);
//		debug($placesCount);

		// Postprocessing
		foreach ($places as &$plc)
		{
			$names = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['names']));
			$dates = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['dates']));
			$bigs = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['bigs']));
			$slugs = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['slugs']));
			$dstarts = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['dstarts']));
			$dends = explode(',', str_replace(array('{','}','"'), '', $plc['Event']['dends']));
			$key = null;
			$date = '';
			foreach ($dates as $k=>$val)
			{
				if (empty($date) || $date < $val)
				{
					$date = $val;
					$key = $k;
				}
			}
			unset($plc['Event']['names']);
			unset($plc['Event']['dates']);
			unset($plc['Event']['bigs']);
			unset($plc['Event']['slugs']);
			unset($plc['Event']['dstarts']);
			unset($plc['Event']['dends']);

			$plc['Event']['name'] = $names[$key];
			$plc['Event']['big'] = $bigs[$key];
			$plc['Event']['slug'] = $slugs[$key];
			$plc['Event']['daily_start'] = $dstarts[$key];
			$plc['Event']['daily_end'] = $dends[$key];
		}

		// Send variables to view
		$this->set('places', $places);
		$this->set('placesCount', $placesCount);

	}

	
	/**
	 * Add place photo URL to data
	 *
	 * @param array $data
	 * @return array $data with photos for places
	 */
	private function _addPlaceCheckedIn($data) {
		if (empty ( $data )) {
			return $data;
		}
	
		if (array_keys ( $data ) !== range ( 0, count ( $data ) - 1 )) {
			$data = array (
					$data
			);
			$assoc = true;
		}
	
		foreach ( $data as $key => $val ) {

			$checkins = array();
			$joins = array();
	

			$checkins = $this->Place->Event->Checkin->getCheckinCountsForPlaceBigs( $data [$key]['Place']['big'], 1 );
			$joins = $this->Place->Event->Checkin->getCheckinCountsForPlaceBigs( $data [$key]['Place']['big'], 0 );

			

				$data [$key]['CheckinsCount']=$checkins;
				$data [$key]['JoinsCount']=$joins;
			
			//	debug($checkins[0]);
		}
	
	
		return $data;
	}
	
    
    private function _addPlaceCheckedInCount($data) {
        //usato nella risposta modificata di places/list in stile places/detail come richiesto da Dev App
        if (empty ( $data )) {
            return $data;
        }
    
        if (array_keys ( $data ) !== range ( 0, count ( $data ) - 1 )) {
            $data = array (
                    $data
            );
            $assoc = true;
        }
    
        foreach ( $data as $key => $val ) {

            $checkins = array();
            $joins = array();
    

            $checkins = $this->Place->Event->Checkin->getCheckinCountsForPlaceBigs( $data [$key]['Place']['big'], 1 );
            $joins = $this->Place->Event->Checkin->getCheckinCountsForPlaceBigs( $data [$key]['Place']['big'], 0 );

            /*print_r($val);    
            print_r($checkins);
            print_r($joins);
                */
                $data [$key]['Checkins']['count']=intval($checkins[$val['Place']['big']]);
                $data [$key]['Joins']['count']=intval($joins[$val['Place']['big']]);
            
            //    debug($checkins[0]);
        }
    
    
        return $data;
    }
	
	
	public function detail($big, $slug='')
	{
		if (empty($big))
		{
			return $this->redirect(array('action' => 'index'));
		}

		$this->_sidebarPlaces();	//places for right sidebar

		$params = array(
			'conditions' => array(
				'Place.big' => $big
			),
//			recursive => 0
		);

		$place = $this->Place->find('first', $params);
//		debug($place);
		$this->set('place', $place);

		if (!$place) {
			$this->Session->setFlash(__('Il Posto non esiste'), 'flash/error');
			return $this->redirect('/');
		}

		// Get photos
		if (!empty($place['Gallery'])) {
			$gallery_big = $place['Gallery'][0]['big'];
			$photos = $this->Place->Gallery->Photo->getPhotos($gallery_big, 0, $place['Place']['default_photo_big']);
//			debug($photos);
		} else {
			$photos = array();
		}
		$this->set('photos', $photos);

		$event = $this->Place->getCurrentEvent($big);
		$this->set('is_joined', $event['Event']['big'] == $this->logged['Checkin']['event_big']);

		$this->set('is_bookmarked', $this->Bookmark->isBookmarked($this->logged['Member']['big'], $place['Place']['big']));

	}

	public function events($place_big=0, $type='recent') {

		$conditions = array(
			'Event.place_big' => $place_big,
			'Event.status ' => 1,
		);

		if ($type == 'past') {
			$conditions['Event.end_date <'] = 'NOW()';
		} elseif ($type == 'upcoming') {
			$conditions['Event.start_date >'] = 'NOW()';
		} else {	//if ($type == 'recent') {
			$conditions['Event.start_date <'] = 'NOW()';
			$conditions['Event.end_date >'] = 'NOW()';
		}

		$events = $this->Place->Event->find('all', array('conditions' => $conditions));

		$this->set('events', $events);

	}

	public function people($place_big=0) {

		$people = $this->Place->Event->getCurrentMembers(0, $place_big, $this->logged['Member']['big']);
		$this->set('people', $people);

	}

	public function news($place_big=0) {

		$place = $this->Place->findByBig($place_big);
		$this->set('place', $place);

	}

	public function gallery($place_big=0) {

		$photos = $this->Place->Event->getEventsWithUserPhotoForPlace($place_big);
		$this->set('photos', $photos);

//		$this->set('loggedBig', $this->logged['Member']['big']);
		$this->set('placeBig', $place_big);

	}

	public function admin_clear()
	{
		 $status = Cache::clear();
		 $this->set('status', $status);

	}

	public function show_map($place_big=0) {

		$params = array(
			'conditions' => array(
				'Place.big' => $place_big
			),
//			recursive => 0
		);

		$place = $this->Place->find('first', $params);
		if (!empty($place))
		{
			$lonlat = preg_replace('/[\(\)]/', '', $place['Place']['lonlat']);
			$lonlatArr = explode(',', $lonlat);
			$latlon = $lonlatArr[1] . ',' . $lonlatArr[0];
			$place['Place']['lonlat'] = $latlon;

//			debug($place);
			$this->set('place', $place);
		}
	}

    
    public function api_placeswithpeople(){
        
         $this->_checkVars(array('city'),array());
        
        
         $region_id = isset($this->api['city']) ? $this->api['city'] : null;
         
         if (is_numeric($region_id)){//verifica che region_id sia un numero (sql inj prevention)
            
         $query="SELECT places.name,places.big,places.rating_avg,places.category_id,places.address_street,".
                "places.address_street_no,places.lonlat AS coordinates,places.address_town AS city,".
                "region_id,ARRAY_AGG(member_big) AS members,COUNT(member_big) AS numbers ".
                "FROM places ".
                "JOIN events ON (places.big=events.place_big) ".
                "JOIN checkins ON (events.big=checkins.event_big) ".
                "WHERE checkins.checkout IS NULL ".
                "AND events.status!=255 ".
                "AND region_id=$region_id ".
                "GROUP BY places.name,places.big ".
                "ORDER BY numbers DESC ".
                "LIMIT 15";
        
       $db = $this->Place->getDataSource();
       
       try {
            $places = $db->fetchAll($query);
            
        }
        catch (Exception $e)
        {
            debug($e);
        }
        
        foreach ($places as $key=>$val){
            
            
            $res[$key]['Place']=$val[0];
            
        }
        
        $result = array('places' => $res);
        //print_r($result);
        
        $this->_apiOk($result);
        
        
    } else 
        $this->_apiEr ( __("Parametro Errato") );
   }
    
    
}
