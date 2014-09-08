<?php

class PhotosController extends AppController {
	
	public function admin_default($big=0) {
		
		$data = $this->Photo->findByBig($big);
		
		if (!empty($data['Gallery']['event_big'])) {
			
			$this->Photo->Gallery->Event->save(array(
				'Event' => array(
					'big' => $data['Gallery']['event_big'],
					'default_photo_big' => $big,
				),
			));
			
		} else {
			
			$this->Photo->Gallery->Place->save(array(
				'Place' => array(
					'big' => $data['Gallery']['place_big'],
					'default_photo_big' => $big,
				),
			));
			
		}
		
		$this->Session->setFlash(__('Photo was set as default'), 'flash/success');
		$this->redirect(array('controller' => 'galleries', 'action' => 'index', $data['Gallery']['big']));
		
	}

	public function operator_default($big=0) {
		
		unbindAllBut($this->Photo, array('Gallery'));
		$photo = $this->Photo->findByBig($big);
		$this->_checkOperatorPermissions($photo['Gallery']['place_big']);

		$this->admin_default($big);
		
	}
	
	public function admin_delete($big=0) {
		
		$this->Photo->recursive = -1;
		$data = $this->Photo->findByBig($big);
		
		$this->Photo->save(array(
			'big' => $big,
			'status' => DELETED,
		));
		
		//TODO: delete the file??
		//unlink( $this->FileUrl( /* PATH HERE */ ) );	//TODO: do not use FileUrlComponent, it is only intended for use in API!
		
		$this->Session->setFlash(__('Photo was removed from gallery'), 'flash/success');
		$this->redirect(array('controller' => 'galleries', 'action' => 'index', $data['Photo']['gallery_big']));
		
	}

	public function operator_delete($big=0) {
		
		unbindAllBut($this->Photo, array('Gallery'));
		$photo = $this->Photo->findByBig($big);
		$this->_checkOperatorPermissions($photo['Gallery']['place_big']);

		$this->admin_delete($big);
		
	}
	
	/**
	 * Upload photo
	 */
	public function api_add() {
		
		$this->_checkVars(array('photo', 'event_big'));
		
		if (!isset($this->api['photo']) || !isset($_FILES[ $this->api['photo'] ]) ) {
			$this->_apiEr(__('Please upload a file'), true);
		}
		
		$event = $this->Photo->Gallery->Event->find('first', array(
			'conditions' => array(
				'Event.big' => $this->api['event_big'],
				'Event.status' => array(ACTIVE, INACTIVE),
			),
			'recursive' => -1,
		));
		if (!$event) {
			$this->_apiEr(__('Invalid event_big'), __('Sorry, this event does not exist'));
		}
		
		$gallery = $this->Photo->Gallery->get($this->api['event_big'], 'event', GALLERY_TYPE_USERS);

		$extension = pathinfo($_FILES[ $this->api['photo'] ]['name'], PATHINFO_EXTENSION);
		
//		if ($extension == 'jpeg') {
//			$extension = 'jpg';
//		}
//		$extension = mb_substr($extension, 0, 3);
		
		$this->Photo->save(array(
			'gallery_big' => $gallery['Gallery']['big'],
			'member_big' => $this->logged['Member']['big'],
			'original_ext' => $extension,
			'status' => ACTIVE,
			'created' => DboSource::expression('now()'),
		));
		
		$event_path = EVENTS_UPLOAD_PATH . $this->api['event_big'] . DS . $gallery['Gallery']['big'] . DS;
		
		if (!is_dir($event_path)) {
			mkdir($event_path, 0777, true);
		}
		
		$uploaded = $this->Upload->directUpload(
			$_FILES[ $this->api['photo'] ],	//data from form (uploaded file)
			$event_path . $this->Photo->id . '.' . $extension	//path + filename
		);
		
		if (!$uploaded) {	//TODO: check if upload is succesfull before saving to DB?
			$this->Photo->delete( $this->Photo->id );
			$this->_apiEr(__('Photo upload failed'), true, true);
		}
		
		$this->_apiOk(array(
			'photo_big' => $this->Photo->id,
		));
		
		
	}
	

	
	
	/**
	 * Upload photo
	 */
	public function api_MemberPhotos() {
	
		$this->_checkVars ( array (
				'user_big' 
		) );
		
		$memBig = $this->api ['user_big'];
		
		$xPhoto=$this->Photo->getMemberPhotos($memBig);
		
		
		
//		debug($xPhoto);

		for($i = 0; $i < count ( $xPhoto ); $i ++) {
		
		
		$xPhoto [$i] ["Photo"] ["url"] = $this->FileUrl->event_photo ( $xPhoto [$i] ['Event'] ['big'], $xPhoto [$i] ["Photo"] ['gallery_big'], $xPhoto [$i] ["Photo"] ['big'], $xPhoto [$i] ["Photo"] ['original_ext'] );
		}
		
		$this->_apiOk($xPhoto);
		
	
	}
	
	public function admin_view($big)
	{
		unbindAllBut($this->Photo, array('Gallery'));
		$photo = $this->Photo->findByBig($big);
			
		if (empty($photo))
		{
			$this->Session->setFlash(__('Photo not found'), 'flash/error');
			$this->redirect(array('controller' => 'signalations', 'action' => 'index'));
		}
			
		$this->set('photo', $photo);
	
	}
	
	public function admin_all()
	{
	//	unbindAllBut($this->Photo, array('Gallery'));
		$photo = $this->Photo->find('all', array(
				'limit' => 10, //int($big);
				));
			
		
		$this->set('data', $photo);
	
	}
	
}