<?php

class EventsController extends AppController {
	
	var $uses = array('Event', 'Checkin', 'Rating', 'Operator');	
	
	public function operator_index() {

		$place_bigs = $this->Operator->OperatorsPlace->find('list', array(
			'conditions' => array(
				'OperatorsPlace.operator_big' => $this->logged['Member']['big'],
			),
			'fields' => array('OperatorsPlace.operator_big', 'OperatorsPlace.place_big'),
			'recursive' => -1,
		));

		if (isset($this->params->named['place']) && $this->params->named['place']>0 && in_array($this->params->named['place'], $place_bigs)) {
			
			$place_bigs = array(
				$this->params->named['place'],
			);

			$this->Event->Place->recursive = -1;
			$place = $this->Event->Place->findByBig($this->params->named['place']);
			$this->set('place', $place);

		}

		$this->_index(array(
			'Event.place_big' => $place_bigs,
		));

	}

	public function admin_index() {
		
		$this->_savedFilter(array('srchname', 'srchplace', 'CreatedFromDate', 'CreatedToDate'));

		$conditions = array();
		
		if (isset($this->params->query['srchname']) && !empty($this->params->query['srchname'])) {
    		$conditions['Event.name ILIKE'] = '%' . $this->params->query['srchname'] . '%';
    	}
		if (isset($this->params->query['srchplace']) && !empty($this->params->query['srchplace'])) {
    		$conditions['Place.name ILIKE'] = '%' . $this->params->query['srchplace'] . '%';
    	}
    	if (isset($this->params->query['CreatedFromDate']) && !empty($this->params->query['CreatedFromDate'])) {
			$conditions['Event.created >='] = $this->params->query['CreatedFromDate'] . ' ' . $this->params->query['CreatedFromTime'];
    	}
    	if (isset($this->params->query['CreatedToDate']) && !empty($this->params->query['CreatedToDate'])) {
    		$conditions['Event.created <='] = $this->params->query['CreatedToDate'] . ' ' . $this->params->query['CreatedToTime'];
    	}
    	
    	$conditions['Event.status'] = array(ACTIVE, INACTIVE);
    	$conditions['Place.status'] = array(ACTIVE, INACTIVE);
    	
    	$this->request->data['Event'] = $this->params->query;

    	$this->_index($conditions);
		
	}

	private function _index($conditions=array()) {
		
		$this->paginate['order'] = array('Event.name' => 'asc');
		$data = $this->paginate('Event', $conditions);

		//checked in members
		{
			$event_bigs = array();
			foreach($data as $item) {
				$event_bigs[] = $item['Event']['big'];
			}
			
			$checkins = array();
			$joins = array();
			if (!empty($event_bigs)) {
				
				$checkins = $this->Event->Checkin->getCheckinCountsForEventBigs( $event_bigs, 1 );
				$joins = $this->Event->Checkin->getCheckinCountsForEventBigs( $event_bigs, 0 );
				
			}	//if (!empty($event_bigs))
			
			$this->set('checkins', $checkins);
			$this->set('joins', $joins);
		}

		$this->set('data', $data);

	}
	
	public function admin_view($big) {
	
		$this->detail($big);
		$this->render('detail');
	
	}

	public function operator_add($place_big=0) {

		$this->operator_edit(0, $place_big);
		$this->render('operator_edit');

	}

	public function operator_edit($big=0, $place_big=0) {
		
		if ($big > 0) {
			$this->Event->recursive = -1;
			$event = $this->Event->findByBig($big);
			$this->_checkOperatorPermissions($event['Event']['place_big']);
		}

		if ($this->request->is('post') || $this->request->is('put')) {
			$this->_checkOperatorPermissions($this->request->data['Place']['big']);
		}

		$this->admin_edit($big, $place_big);

	}
	
	public function admin_add($place_big=0) {
		
		if ($this->request->prefix == 'admin' && $place_big == 0) {
			$this->Session->setFlash(__('Please select a place to create a new event for (use the icon on right)'), 'flash/info');
			return $this->redirect(array('controller' => 'places', 'action' => 'index'));
		}
		
		$this->admin_edit(0, $place_big);
		$this->render('admin_edit');
	}
	
	public function admin_edit($big=0, $place_big=0) {
		
		if ($this->request->is('post') || $this->request->is('put')) {
			
			if ($this->request->data['Event']['type'] == EVENT_TYPE_NORMAL) {	//normal event
				
				$this->request->data['Event']['start_date'] = ctt($this->request->data['Event']['start_date']);
				$this->request->data['Event']['end_date'] = ctt($this->request->data['Event']['end_date']);
				$this->request->data['Event']['daily_start'] = ctt($this->request->data['Event']['daily_start']);
				$this->request->data['Event']['daily_end'] = ctt($this->request->data['Event']['daily_end']);
				
			} else {	//default event
				
				$this->request->data['Event']['start_date'] = null;
				$this->request->data['Event']['end_date'] = null;
				$this->request->data['Event']['daily_start'] = null;
				$this->request->data['Event']['daily_end'] = null;
				
			}
			
			if ($this->Event->saveAll($this->request->data, array('validate' => 'first'))) {
				
				try {
					$this->_upload($this->request->data['Event']['photos'], $this->Event->id);
				} catch (UploadException $e) {
				
				}
				
				$this->Session->setFlash(__('Event saved'), 'flash/success');
				return $this->redirect(array('action' => 'index'));
				
			} else {
				$this->Session->setFlash(__('Error while saving event'), 'flash/error');
			}
			
		} elseif ($big > 0) {
			
			$this->Event->recursive = 1;
			$data = $this->Event->findByBig($big);
			$data['Event']['start_date'] = convert_to_date_array($data['Event']['start_date']);
			$data['Event']['end_date'] = convert_to_date_array($data['Event']['end_date']);
			$data['Event']['daily_start'] = convert_to_date_array($data['Event']['daily_start']);
			$data['Event']['daily_end'] = convert_to_date_array($data['Event']['daily_end']);
			$this->request->data = $data;
			$place_big = isset($this->request->data['Place']['big']) ? $this->request->data['Place']['big'] : null;
			
		}
		
		if ($this->request->prefix == 'admin') {

			$place = $this->Event->Place->find('first', array('conditions' => array('Place.big' => $place_big), 'recursive' => -1));
			
			if (!$place && $big == 0) {
				$this->Session->setFlash(__('This place does not exist'), 'flash/error');
				return $this->redirect(array('controller' => 'places', 'action' => 'index'));
			}
			
			$this->set('place', $place);

			$this->request->data['Place']['big'] = $place['Place']['big'];

		} else {


			$place_bigs = $this->Operator->OperatorsPlace->find('list', array(
				'conditions' => array(
					'OperatorsPlace.operator_big' => $this->logged['Member']['big'],
				),
				'fields' => array('OperatorsPlace.operator_big', 'OperatorsPlace.place_big'),
				'recursive' => -1,
			));
			$places = $this->Event->Place->find('list', array('conditions' => array('Place.big' => $place_bigs), 'recursive' => -1));

			$this->set('places', $places);

			$this->request->data['Place']['big'] = $place_big;

		}

	}
	
	public function admin_delete($big) {
		
		$this->Event->save(array(
			'big' => $big,
			'status' => DELETED,
		));
		
		/*
		 * TODO: delete all event items?
		 *  - galleries (with photos)
		 *  - ratings
		 *  - checkins
		 */
		
		$this->Session->setFlash(__('Event deleted'), 'flash/success');
		return $this->redirect(array('action' => 'index'));
		
	}

	public function operator_delete($big) {
		
		$this->Event->recursive = -1;
		$event = $this->Event->findByBig($big);
		$this->_checkOperatorPermissions($event['Event']['place_big']);

		$this->admin_delete($big);
		
	}

	public function operator_upload($big) {
		
		$this->Event->recursive = -1;
		$event = $this->Event->findByBig($big);
		$this->_checkOperatorPermissions($event['Event']['place_big']);

		$this->admin_upload($big);
		
	}

	public function admin_upload($big) {

		$event = $this->Event->findByBig($big);

		try {
			$this->_upload($this->request->data['photos'], $big);
		} catch (UploadException $e) {
			$this->Session->setFlash(__('Error while uploading photos'), 'flash/error');
		}
		
		$this->Session->setFlash(__('Photos saved'), 'flash/success');
		return $this->redirect(array('controller' => 'galleries', 'action' => 'index', $event['Gallery'][0]['big']));
		
	}
	
	private function _upload($photos, $event_big) {
		
		$save_photos = array();
		$all_files = explode(';', trim($photos['files'], ';'));
		foreach($all_files as $file) {
			if (!empty($file)) {

				if (!isset($gallery)) {
					$gallery = $this->Event->Gallery->get($event_big, 'event', GALLERY_TYPE_DEFAULT);
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

		$this->Event->Gallery->Photo->saveMany($save_photos);

		//set default photo of there is none
		$first_insert_id = reset($this->Event->Gallery->Photo->inserted_ids);
		if ($first_insert_id) {
			$this->Event->recursive = -1;
			$event = $this->Event->findByBig($event_big);
			if ($event['Event']['default_photo_big'] == 0 && $first_insert_id > 0) {
				$this->Event->save(array(
					'Event' => array(
						'big' => $event['Event']['big'],
						'default_photo_big' => $first_insert_id,
					),
				));
			}
		}
	
		return $this->Upload->upload(
			$photos,	//data from form (temporary filenames, token)
			EVENTS_UPLOAD_PATH . $gallery['Gallery']['event_big'] . DS . $gallery['Gallery']['big'] . DS,	//path
			$this->Event->Gallery->Photo->inserted_ids
		);
	
	}
	
	/**
	 * List all events (will contain filtering, sorting, search)
	 */
	public function api_list() {
		
		$params = array('limit' => API_PER_PAGE);
		if (isset($this->api['offset']))
		{
			$params['offset'] = $this->api['offset'] * API_PER_PAGE;
		}
		$conditions = array();
		if (isset($this->api['place_big']))
		{
			$conditions['Event.place_big'] = $this->api['place_big']; 
		}
		
		if (isset($this->api['past']) && $this->api['past']==1) {	//past events
			$conditions += array(
				'Event.status' => ACTIVE,
				'Event.end_date <=' => date('Y-m-d H:i:s'),
			);
		} else {	//only active events
			$conditions['OR'] = array(
				'Event.type' => EVENT_TYPE_DEFAULT,
				array(
					'Event.status' => ACTIVE,
					'Event.start_date <=' => date('Y-m-d H:i:s'),
					'Event.end_date >=' => date('Y-m-d H:i:s'),
					'Event.daily_start <' => date('H:i:s'),
					'Event.daily_end >' => date('H:i:s'),
					'Place.status !=' => DELETED,
				),
			);
		}
		
		$params['conditions'] = $conditions;
		$params['fields'] = array(
			'Event.big', 'Event.name', 'Event.start_date', 'Event.end_date', 'Event.rating_avg', 'Event.rating_count', 
			'Place.big', 'Place.name', 'Place.default_photo_big', 'Place.category_id',
			'DefaultPhoto.big', 'DefaultPhoto.original_ext'
		);
		
		unbindAllBut($this->Event, array('Place', 'Gallery', 'DefaultPhoto'));
		$events = $this->Event->find('all', $params);
		$events = $this->_addEventPhotoUrls($events);
		$eventsCount = $this->Event->find('count', array('conditions' => $conditions));
		
		// Add photos to places
		$plcPhotoIds = array();
		$plcPhotos = array();
		foreach ($events as $evt) 
		{
			if ($evt['Event']['photo'] == null)
			{
				if ($evt['Place']['default_photo_big'] != 0)
				{
					$plcPhotoIds[$evt['Place']['big']] = $evt['Place']['default_photo_big'];
				}
				else
				{
					$plcPhotos[]['Place'] = $evt['Place']; 
				}
			}
		}
		$pars = array(
			'conditions' => array(
				'Photo.big' => $plcPhotoIds
			),
			'fields' => array(
				'Photo.big',
				'Photo.original_ext',
				'Photo.gallery_big',
			)
		);
		
		$photos = $this->Event->Gallery->Photo->find('all', $pars);
		
		$photoPlcIds = array_flip($plcPhotoIds);
		foreach ($photos as $ph)
		{
			$plcPhotos[] = array(
				'Place' => array(
					'big' => $photoPlcIds[$ph['Photo']['big']]
				),
				'DefaultPhoto' => array( 
					'big' => $ph['Photo']['big'],
					'original_ext' => $ph['Photo']['original_ext'],
				),
				'Gallery' => array(
					0 => array(
						'big' => $ph['Photo']['gallery_big']
					)
				)
			);
		}
		
		$plcPhotos = $this->_addPlacePhotoUrls($plcPhotos);
		
		foreach ($plcPhotos as $plcp)
		{
			foreach ($events as &$ev)
			{
				if ($ev['Place']['big'] == $plcp['Place']['big'])
				{
					$ev['Place']['photo'] = $plcp['Place']['photo'];
					unset($ev['Place']['default_photo_big']);
				}
			}
		}
		
		$this->_apiOk(array(
			'events' => $events,
			'events_count' => $eventsCount,
		));
		
	}
	
	/**
	 * Return event detailed information
	 */
	public function api_detail() {
		
		$this->_checkVars(array('event_big'));
		
		$conditions = array();
		if (isset($this->api['event_big'])) {
			$conditions['Event.big'] = $this->api['event_big']; 
		}
		
		$params = array(
			'conditions' => $conditions,
			'fields' => array(
				'Event.big',
				'Event.place_big',
				'Event.name',
				'Event.start_date',
				'Event.end_date',
				'Event.daily_start',
				'Event.daily_end',
				'Event.short_desc',
				'Event.long_desc',
				'Event.default_photo_big',
				'Event.rating_avg',
				'Place.name',
				'Place.category_id',
				'DefaultPhoto.big',
				'DefaultPhoto.original_ext',
				'DefaultPhoto.gallery_big',
			),
		);
		
		unbindAllBut($this->Event, array('Gallery', 'DefaultPhoto', 'Place'));
		$event = $this->Event->find('first', $params);
		
		if (!empty($event)) {
			
			$event['Gallery']['big'] = $event['DefaultPhoto']['gallery_big'];
			unset($event['DefaultPhoto']['gallery_big']);
			
			$event = $this->_addEventPhotoUrls($event);
			
			$memBig = $this->logged['Member']['big'];
			$checkinsCount = $this->Event->Checkin->getCheckinsCountFor($event['Event']['big'], $memBig);
			$joinsCount = $this->Event->Checkin->getJoinsCountFor($event['Event']['big'], $memBig);
			$event['Checkins'] = array('count' => $checkinsCount);
			$event['Joins'] = array('count' => $joinsCount);
			
			// Checkin valid indicator
			$checkinValid = $this->Event->Checkin->isOrWasCheckedIn($memBig, $event['Event']['big']);
			$event['Event']['checkin_valid'] = $checkinValid;
			
		}
		
		$this->_apiOk($event);
		
	}
	
	/**
	 * Return all user uploaded pictures for event (gallery)
	 */
	public function api_user_photos() {
		
	}
	
	/**
	 * Rate event
	 */
	public function api_rate() {

		$this->_checkVars(array('event_big', 'rating'));
		
		try {
			$this->_rate($this->api['event_big'], $this->logged['ApiToken']['member_big'], $this->api['rating']);
			$this->_apiOk();
		} catch (ErrorEx $e) {
			$this->_apiEr( $e->getMessage() );
		}
		
	}

	private function _rate($eventBig, $memBig, $rating) {

		// Check if member is or was checked in
		$hasCheckin = $this->Checkin->isOrWasCheckedIn($memBig, $eventBig);
		if (!$hasCheckin)
		{
			throw new ErrorEx('User has no valid checkin. Cannot rate.');
		}
		
		// Check if rating with same event_big and member_big exist, if true, update
		$prevRat = $this->Rating->hasRating($memBig, $eventBig);
		if (empty($prevRat))
		{
			$result = $this->Rating->insertRating($memBig, $eventBig, $rating);
			if ($result !== false)
				return true;
			else
				throw new ErrorEx('Error occured. Rating not saved.');
		}
		else 
		{
			$result = $this->Rating->updateRating($memBig, $eventBig, $rating);
			if ($result !== false)
				return true;
			else
				throw new ErrorEx('Error occured. Rating not updated.');
		}

	}
	
	/**
	 * Displaying events list for frontend
	 */
	public function index() {
		
		$this->_listing();
		$this->set('listing', true);
		
	}
	
	/**
	 * Map of events for frontend
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
//		debug($this->request->params);
		$this->set('pars', $this->request->params['named']);
		if (isset($this->request->data['Event']))
		{
			$this->request->params['named'] = array_merge($this->request->params['named'], $this->request->data['Event']);
		}
//		debug($this->request);
		App::uses('Search', 'Lib');
		
		$sort = isset($this->request->params['named']['sort']) ? $this->request->params['named']['sort'] : NULL ;
		$this->set('sort', $sort);
		
		$offset = isset($this->request->params['named']['offset']) ? $this->request->params['named']['offset'] : 0 ;
		$this->set('offset', $offset);
		$category_id = isset($this->request->params['named']['category']) ? $this->request->params['named']['category'] : NULL ;
		$this->set('category_id', $category_id);

		$country = isset($this->request->params['named']['country']) ? $this->request->params['named']['country'] : NULL ;
		$city = isset($this->request->params['named']['city']) ? $this->request->params['named']['city'] : NULL ;
		$this->set('city', $city);
		$region = $this->Event->Place->Region->getRegionByCityAndCountry($city, $country);
		$region_id = !empty($region['Region']['id']) ? $region['Region']['id'] : null;
		$distance = isset($this->request->params['named']['distance']) ? $this->request->params['named']['distance'] : NULL ;
		$search =	isset($this->request->params['named']['search']) ? Search::PrepareTSQuery($this->request->params['named']['search']) : NULL ;
		$searchVal =	isset($this->request->params['named']['search']) ? $this->request->params['named']['search'] : NULL ;
		$this->set('search', $searchVal);
		
		// ads here will be on the left side
		$this->_sidebarPlaces();
		
		// Get filter sidebar data
		$whereCats = array();
		if (isset($region_id))
		{
			$whereCats[] = ' places.region_id = ' . $region_id; 
		}

		if (isset($search))
		{
			$whereCats[] = ' (to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$) @@ events.tsv ) ';
		}
		
		// TODO Distance filter
		
		if (!empty($map_bounds)) {
		
			$polygon = array(
				'll' => $map_bounds[0].','.$map_bounds[1],
				'lr' => $map_bounds[0].','.$map_bounds[3],
				'ur' => $map_bounds[2].','.$map_bounds[3],
				'ul' => $map_bounds[2].','.$map_bounds[1],
			);
		
			$whereCats[] = " polygon '((".implode('),(', $polygon)."))' @> places.lonlat ";
		
		}
		$categories = $this->Event->Place->Category->getCategoriesWithEventCounts($whereCats);
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
		
		// Get Events
		$query = 'SELECT 
				places.name AS "Place__name", places.big AS "Place__big", places.slug AS "Place__slug", places.category_id AS "Place__category_id",
				places.address_street AS "Place__address_street", places.address_street_no AS "Place__address_street_no",
				regions.city AS "Region.city",
				events.default_photo_big AS "Event__default_photo_big", events.rating_avg AS "Event__rating_avg", places.lonlat AS "Place__lonlat",
				events.name AS "Event__name", events.big AS "Event__big", events.daily_start AS "Event__daily_start", events.daily_end AS "Event__daily_end",
				events.slug AS "Event__slug",
				pags.photobig AS "DefaultPhoto__big", pags.original_ext AS "DefaultPhoto__original_ext",
				pags.gallery_big AS "Gallery__big"
			FROM events
			LEFT JOIN (SELECT photos.big as photobig, photos.original_ext, photos.gallery_big FROM photos 
				LEFT JOIN galleries ON (photos.gallery_big = galleries.big)
				LEFT JOIN events ON (galleries.event_big = events.big)
				WHERE photos.big = events.default_photo_big
				) AS pags ON (events.default_photo_big = pags.photobig)
			LEFT JOIN places ON (events.place_big = places.big)
			LEFT JOIN regions ON (places.region_id = regions.id)
			WHERE events.status = 1 AND events.start_date < NOW() AND events.end_date > NOW() 
			AND places.status != ' . DELETED . ' ';
		
		$countQuery = 'SELECT count (*) FROM events LEFT JOIN places ON (events.place_big = places.big) 
			WHERE events.status = 1 AND events.start_date < NOW() AND events.end_date > NOW() 
			AND places.status != ' . DELETED . ' ';
		
		$where = array();
		if (isset($category_id))
		{
			$where[] = ' places.category_id = ' . $category_id; 
		}
		if (isset($region_id))
		{
			$where[] = ' places.region_id = ' . $region_id; 
		}

		if (isset($search))
		{
			$where[] = ' (to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$) @@ events.tsv ) ';
		}
		
		// TODO Distance filter
		
		if (!empty($map_bounds)) {
		
			$polygon = array(
				'll' => $map_bounds[0].','.$map_bounds[1],
				'lr' => $map_bounds[0].','.$map_bounds[3],
				'ur' => $map_bounds[2].','.$map_bounds[3],
				'ul' => $map_bounds[2].','.$map_bounds[1],
			);
		
			$where[] = " polygon '((".implode('),(', $polygon)."))' @> places.lonlat ";
		
		}
		
		// Add other params
		if (!empty($where))
		{
			$query .= ' AND ' . implode(' AND ', $where) . ' ';
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
					$query .= ' ORDER BY events.rating_avg DESC';
					break;
//				case 'distance':
//					$query .= ' ORDER BY places.rating_avg DESC';
//					break;
				case 'relevance':
					if (isset($search)) 
					{
						$query .= ' ORDER BY ts_rank_cd(events.tsv, to_tsquery(\'pg_catalog.italian\',$$' . $search . '$$), 36) DESC ';
						break;
					}
				case 'name':
				default:	
					$query .= ' ORDER BY events.name ASC ';
			}
			
			$query .= ' LIMIT ' . FRONTEND_PER_PAGE;	
			if ($offset)
			{
				$query .= ' OFFSET ' . $offset * FRONTEND_PER_PAGE;
			}
			
		}
		
		$db = $this->Event->getDataSource();
		try {
			$events = $db->fetchAll($query);
			$plCount = $db->fetchAll($countQuery);
			$eventsCount = $plCount[0][0]['count'];
		}
		catch (Exception $e)
		{
			debug($e->getMessage());
		}
//		debug($places);
//		debug($placesCount);

		// Send variables to view
		$this->set('events', $events);
		$this->set('eventsCount', $eventsCount);
		
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
				'Event.big' => $big
			),
//			recursive => 0
		);
		
		$event = $this->Event->find('first', $params);
		$this->set('event', $event);

		if (!$event) {
			$this->Session->setFlash(__('The event does not exist'), 'flash/error');
			return $this->redirect('/');
		}

		if ($event['Event']['type'] == EVENT_TYPE_DEFAULT && !$event['Event']['status'] == ACTIVE) {
			return $this->redirect(array('controller' => 'places', 'action' => 'detail', $event['Place']['big'], $event['Place']['slug']));
		}
		
		// Get photos
		$gallery_big = $event['Gallery'][0]['big'];
		$photos = $this->Event->Gallery->Photo->getPhotos($gallery_big, 4, $event['Event']['default_photo_big']);
		//debug($photos);
		$this->set('photos', $photos);
		
		// Get people
		$params = array(
			'conditions' => array(
				'Checkin.event_big' => $event['Event']['big'],
				'Checkin.checkout >' => 'NOW()',
				'Checkin.member_big <>' => $this->logged['Member']['big'],
			),
			'fields' => array(
				'Checkin.physical',
				'Member.big',
				'Member.name',
				'Member.middle_name',
				'Member.surname',
			),
			'recursive' => 0
		);
		
		$members = $this->Event->Checkin->find('all', $params);
		//debug($members);
		$this->set('members', $members);
		
	}

	public function rate($event_big=0, $rating=0) {

		try {

			$this->_rate($event_big, $this->logged['Member']['big'], $rating);

			$msg = __('Rating saved');
			$element = 'flash/success';

			
		} catch (ErrorEx $e) {

			$msg = $e->getMessage();
			$element = 'flash/error';

		}

		//get event
		$this->Checkin->Event->recursive = -1;
		$event = $this->Checkin->Event->findByBig($event_big);

		if ($this->isAjax) {

			$this->set(array(
				'event'	=> $event,
				'msg'	=> $msg,
				'elm'	=> $element,
			));

		} else {

			$this->Session->setFlash($msg, $element);
			return $this->redirect(array('controller' => 'events', 'action' => 'detail', $event['Event']['big'], $event['Event']['slug']));

		}
		
	}

	public function people($event_big=0) {

		$people = $this->Event->getCurrentMembers($event_big, 0, $this->logged['Member']['big']);
		$this->set('people', $people);
		$this->render('/Places/people');

	}

	public function gallery($event_big=0) {

		$photos = $this->Event->Gallery->Photo->getEventPhotos($event_big);
		$this->set('photos', $photos);
		
		$this->set('loggedBig', $this->logged['Member']['big']);

	}
	
}