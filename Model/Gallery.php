<?php

class Gallery extends AppModel {
	
	public $primaryKey = 'big';
	
	public $belongsTo = array(
		'Place',
		'Event',
	);
	
	public $hasMany = array(
		'Photo' => array(
			'conditions' => array(
				'Photo.status !=' => DELETED,
			),
		),
	);
	
	public function get($big, $what='event', $type=GALLERY_TYPE_USERS) {
		
		$type = GALLERY_TYPE_DEFAULT;
		
		$what = $what=='event' ? 'event' : 'place';
		
		$conditions = array(
			$what.'_big' => $big,
			'type' => $type,
			'status' => ACTIVE,
		);
		if ($what == 'place') {
			$conditions['event_big'] = null;
		}
		
		$gallery = $this->find('first', array(
			'conditions' => $conditions,
			'recursive' => -1,
		));
		
		if ($gallery == false) {
			
			$gallery = array(
				'Gallery' => array(
					'name' => 'Default',
					'type' => $type,
					'status' => ACTIVE,
					$what.'_big' => $big,
					//'created' => DboSource::expression('now()'),
				),
			);
			
			if ($what == 'event') {
				
				$event = $this->Event->find('first', array(
					'conditions' => array(
						'Event.big' => $big,
						'Event.status' => array(ACTIVE, INACTIVE),
					),
					'recursive' => -1,
				));
				$gallery['Gallery']['place_big'] = $event['Event']['place_big'];
				
			}
			
			$this->save($gallery['Gallery']);
			
			$gallery['Gallery']['big'] = $this->id;
			
		}
		
		return $gallery;
		
	}
	
}