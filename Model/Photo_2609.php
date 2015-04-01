<?php

class Photo extends AppModel {
	
	public $primaryKey = 'big';
	
	public $belongsTo = array(
		'Gallery',
		'Member',
	);
	
	/**
	 * Get photos uploaded by member and sorted by events. For public profile.
	 * @param unknown_type $memBig
	 * @return unknown
	 */
	public function getMemberPhotos($memBig)
	{
		/*
		$params = array(
			'conditions' => array(
				'photos.member_big' => $memBig
			),
			'fields' => array(
				'Photo.big',
				'Photo.original_ext',
				'Photo.gallery_big',
				'Event.big',
				'Event.name',
				'Place.big',
				'Place.name',
			),
			'joins' => array(
			    array('table' => 'galleries',
			        'alias' => 'Gallery',
			        'type' => 'LEFT',
			        'conditions' => array(
			            'Photo.gallery_big = Gallery.big',
			        )
			    ),
			    array('table' => 'places',
			        'alias' => 'Place',
			        'type' => 'LEFT',
			        'conditions' => array(
			            'Gallery.place_big = Place.big',
			        )
			    ),
			    array('table' => 'events',
			        'alias' => 'Event',
			        'type' => 'LEFT',
			        'conditions' => array(
			            'Gallery.event_big = Event.big',
			        )
			    ),
		    ),
		    'recursive' => -1
		);
		
		$photos = $this->find('all', $params);
		*/
		
		$db = $this->getDataSource();
		$query = 'SELECT photos.big AS "Photo__big", photos.original_ext AS "Photo__original_ext", photos.gallery_big AS "Photo__gallery_big", photos.member_big AS "Photo__member_big", 
				events.big AS "Event__big", events.name AS "Event__name", (events.type=2 AND events.status=0) as "Event__hidden", events.slug AS "Event__slug",
				places.big AS "Place__big", places.name AS "Place__name", places.slug AS "Place__slug"
			FROM photos 
			LEFT JOIN galleries ON (photos.gallery_big = galleries.big)
			LEFT JOIN places ON (galleries.place_big = places.big)
			LEFT JOIN events ON (galleries.event_big = events.big)
			WHERE photos.member_big = ? AND photos.status < 255';
		$photos = $db->fetchAll($query, array($memBig));
		
		return $photos;
		
	}

	public function getEventPhotos($eventBig) {

		$db = $this->getDataSource();
		$query = 'SELECT photos.big AS "Photo__big", photos.original_ext AS "Photo__original_ext", photos.gallery_big AS "Photo__gallery_big",
				events.big AS "Event__big", events.name AS "Event__name", (events.type=2 AND events.status=0) as "Event__hidden",
				places.big AS "Place__big", places.name AS "Place__name", places.slug AS "Place__slug",
				members.big AS "Member__big", members.name AS "Member__name", members.surname AS "Member__surname"
			FROM photos 
			LEFT JOIN galleries ON (photos.gallery_big = galleries.big)
			LEFT JOIN events ON (galleries.event_big = events.big)
			LEFT JOIN places ON (galleries.place_big = places.big)
			LEFT JOIN members ON (members.big = photos.member_big)
			WHERE galleries.event_big = ? AND photos.status < 255';
		$photos = $db->fetchAll($query, array($eventBig));
		
		return $photos;

	}

	public function getPlacePhotos($placeBig) {

		$db = $this->getDataSource();
		$query = 'SELECT photos.big AS "Photo__big", photos.original_ext AS "Photo__original_ext", photos.gallery_big AS "Photo__gallery_big", 
				events.big AS "Event__big", events.name AS "Event__name", (events.type=2 AND events.status=0) as "Event__hidden",  events.slug AS "Event__slug",
				places.big AS "Place__big", places.name AS "Place__name", places.slug AS "Place__slug",
				members.big AS "Member__big", members.name AS "Member__name", members.surname AS "Member__surname"
			FROM photos 
			LEFT JOIN galleries ON (photos.gallery_big = galleries.big)
			LEFT JOIN events ON (galleries.event_big = events.big)
			LEFT JOIN places ON (galleries.place_big = places.big)
			LEFT JOIN members ON (members.big = photos.member_big)
			WHERE events.place_big = ? AND photos.status < 255';
		$photos = $db->fetchAll($query, array($placeBig));
		
		return $photos;

	}
	
	public function getPhotos($gallery_big, $limit=4, $omit=null)
	{
		$params = array(
			'conditions' => array(
				'Photo.gallery_big' => $gallery_big,
				'Photo.original_ext !=' => '',
				'Photo.big !=' => $omit,
			),
			'fields' => array(
				'Photo.big',
				'Photo.original_ext',
			),
			'recursive' => -1,
			'limit' => $limit
		);
		
		return $this->find('all', $params);
		
	}
	
	public function getSignaledPhoto($photo_id)
	{
		$params = array(
			'conditions' => array(
				'Photo.big' => $photo_id,
			),
			'fields' => array(
				'Photo.big',
				'Photo.original_ext',
				'Photo.gallery_big',
				'Gallery.place_big',
				'Gallery.event_big',
			),
			'recursive' => 0
		);
		
		return $this->find('first', $params);
	}

}