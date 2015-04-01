<?php
class PhotosController extends AppController {
	public function admin_default($big = 0) {
		$data = $this->Photo->findByBig ( $big );
		
		if (! empty ( $data ['Gallery'] ['event_big'] )) {
			
			$this->Photo->Gallery->Event->save ( array (
					'Event' => array (
							'big' => $data ['Gallery'] ['event_big'],
							'default_photo_big' => $big 
					) 
			) );
		} else {
			
			$this->Photo->Gallery->Place->save ( array (
					'Place' => array (
							'big' => $data ['Gallery'] ['place_big'],
							'default_photo_big' => $big 
					) 
			) );
		}
		
		$this->Session->setFlash ( __ ( 'La foto è stata impostata come predefinita' ), 'flash/success' );
		$this->redirect ( array (
				'controller' => 'galleries',
				'action' => 'index',
				$data ['Gallery'] ['big'] 
		) );
	}
	public function operator_default($big = 0) {
		unbindAllBut ( $this->Photo, array (
				'Gallery' 
		) );
		$photo = $this->Photo->findByBig ( $big );
		$this->_checkOperatorPermissions ( $photo ['Gallery'] ['place_big'] );
		
		$this->admin_default ( $big );
	}
	public function admin_delete($big = 0) {
		$this->Photo->recursive = - 1;
		$data = $this->Photo->findByBig ( $big );
		
		$this->Photo->save ( array (
				'big' => $big,
				'status' => DELETED 
		) );
		
		// TODO: delete the file??
		// unlink( $this->FileUrl( /* PATH HERE */ ) ); //TODO: do not use FileUrlComponent, it is only intended for use in API!
		
		$this->Session->setFlash ( __ ( 'La foto è stata rimossa dalla galleria' ), 'flash/success' );
		$this->redirect ( array (
				'controller' => 'galleries',
				'action' => 'index',
				$data ['Photo'] ['gallery_big'] 
		) );
	}
	public function operator_delete($big = 0) {
		unbindAllBut ( $this->Photo, array (
				'Gallery' 
		) );
		$photo = $this->Photo->findByBig ( $big );
		$this->_checkOperatorPermissions ( $photo ['Gallery'] ['place_big'] );
		
		$this->admin_delete ( $big );
	}
	
	/**
	 * Upload photo
	 */
	public function api_add() {
		$this->_checkVars ( array (
				'photo',
				'event_big' 
		) );
		$pollo="prima";
        
        $this->log("-----------FULL POST LOG------------");
        $this->log("THIS API ".serialize($this->api));
        $this->log("------------------------------------");
        $this->log("POST ".serialize($_POST));
        $this->log("-----------FINE POST LOG------------");
        
        
        
        $this->log("var photo ".$this->api['photo']);
        $this->log("var event_big ".$this->api['event_big']);
        $this->log("GlobalVAR FILES ".serialize($_FILES));
        
		try {
			
			if (! isset ( $this->api ['photo'] ) || ! isset ( $_FILES [$this->api ['photo']] )) {
				$this->_apiEr ( __ ( 'Per favore fai l\'upload del file' ), true );
			}
			
			$event = $this->Photo->Gallery->Event->find ( 'first', array (
					'conditions' => array (
							'Event.big' => $this->api ['event_big'],
							'Event.status' => array (
									ACTIVE,
									INACTIVE 
							) 
					),
					'recursive' => - 1 
			) );
            
            $this->log("Evento ".serialize($event));
            
			if (! $event) {
				$this->_apiEr ( __ ( 'Evento non valido' ), __ ( 'Spiacenti, l\'evento non esiste' ) );
			}
			
			$gallery = $this->Photo->Gallery->get ( $this->api ['event_big'], 'event', GALLERY_TYPE_USERS );
			$this->log("gallery ".serialize($gallery));
            
			$extension = pathinfo ( $_FILES [$this->api ['photo']] ['name'], PATHINFO_EXTENSION );
            $this->log("extension ".$extension);
			$this->log("cosa contiene ".$_FILES[$this->api ['photo']]['name']);
			// if ($extension == 'jpeg') {
			// $extension = 'jpg';
			// }
			// $extension = mb_substr($extension, 0, 3);
			
			$this->Photo->save ( array (
					'gallery_big' => $gallery ['Gallery'] ['big'],
					'member_big' => $this->logged ['Member'] ['big'],
					'original_ext' => $extension,
					'status' => ACTIVE,
					'created' => DboSource::expression ( 'now()' ) 
			) );
			
			$event_path = EVENTS_UPLOAD_PATH . $this->api ['event_big'] . DS . $gallery ['Gallery'] ['big'] . DS;
			
			if (! is_dir ( $event_path )) {
				mkdir ( $event_path, 0777, true );
			}
		} catch ( Exception $e ) {
			$pollo="ex1".serialize ( $e );
			$this->log ( "ex1".serialize ( $e ) );
			$this->log ( serialize ( $e ) );
		}
		
		try {
			$this->log ( "ex3" );
			$this->log (  $event_path  );
			$this->log (  $this->Photo->id  );
			$this->log (  $extension  );
		$uploaded = $this->Upload->directUpload ( $_FILES [$this->api ['photo']], 		// data from form (uploaded file)
		$event_path . $this->Photo->id . '.' . $extension )		// path + filename
		;

	//			$uploaded = $this->Upload->upload ( $_FILES [$this->api ['photo']], 		// data from form (uploaded file)
	//				$event_path , $this->Photo->id . '.' . $extension )		// path + filename
	//				;
					
		} catch ( Exception $e ) {
			$this->log ( "ex2" );
			$this->log ( serialize ( $e ) );
			$pollo="ex2".serialize ( $e );
			$this->_apiEr ( __ ( serialize ( $e ) ), __ ( serialize ( $e )), true, null, '989' );
		}
		if (! $uploaded) { // TODO: check if upload is succesfull before saving to DB?
			$this->Photo->delete ( $this->Photo->id );
			//$this->_apiEr ( __ ( 'Photo upload failed' ), __ ( 'Photo upload failed' ), true, null, '989' );
			$this->_apiEr ( $pollo,$pollo, true, null, '989' );
		}
		        
        $photoUrl = $this->FileUrl->event_photo($this->api['event_big'],$gallery ['Gallery']['big'],$this->Photo->id,$extension);
		
        //$this->log("photoUrl ".$photoUrl);
        $this->_apiOk ( array (
				'photo_big' => $this->Photo->id, 
                'photo_url'=> $photoUrl 
		) );
	}
	
    
    public function api_addnew() {
        $this->_checkVars ( array (
                'photo',
                'event_big' 
        ) );
      
        $this->log("PHOTOS_DEBUG: VAR photo ".$this->api['photo']);
        $this->log("PHOTOS_DEBUG: VAR event_big ".$this->api['event_big']);
        $this->log("PHOTOS_DEBUG: GlobalVAR FILES ".serialize($_FILES));
        
        try {
            
            if (! isset ( $this->api ['photo'] ) || ! isset ( $_FILES [$this->api ['photo']] )) {
                $this->_apiEr ( __ ( 'Per favore fai l\'upload del file' ), true );
            }
            
            $event = $this->Photo->Gallery->Event->find ( 'first', array (
                    'conditions' => array (
                            'Event.big' => $this->api ['event_big'],
                            'Event.status' => array (
                                    ACTIVE,
                                    INACTIVE 
                            ) 
                    ),
                    'recursive' => - 1 
            ) );
            
            $this->log("PHOTOS_DEBUG: Evento ".serialize($event));
            
            if (! $event) {
                $this->_apiEr ( __ ( 'Evento non valido' ), __ ( 'Spiacenti, l\'evento non esiste' ) );
            }
            
            $gallery = $this->Photo->Gallery->get( $this->api ['event_big'], 'event', GALLERY_TYPE_USERS );
            $this->log("PHOTOS_DEBUG: Gallery ".serialize($gallery));
            
            $extension = pathinfo ( $_FILES [$this->api ['photo']] ['name'], PATHINFO_EXTENSION );
            $this->log("PHOTOS_DEBUG: EXTENSION ".$extension);
            $this->log("PHOTOS_DEBUG: INDICE NAME DI FILES ".$_FILES[$this->api ['photo']]['name']);
            // if ($extension == 'jpeg') {
            // $extension = 'jpg';
            // }
            // $extension = mb_substr($extension, 0, 3);
            
            $this->Photo->save ( array (
                    'gallery_big' => $gallery ['Gallery'] ['big'],
                    'member_big' => $this->logged ['Member'] ['big'],
                    'original_ext' => $extension,
                    'status' => ACTIVE,
                    'created' => DboSource::expression ( 'now()' ) 
            ) );
            
            $event_path = EVENTS_UPLOAD_PATH . $this->api ['event_big'] . DS . $gallery ['Gallery'] ['big'] . DS;
            
            if (! is_dir ( $event_path )) {
                mkdir ( $event_path, 0777, true );
            }
        } catch ( Exception $e ) {
                      $this->log("PHOTOS_DEBUG: FALLITO primo TRY ".$e);
        }
        
        try {
              $this->log ('PHOTOS_DEBUG: event_path '.  $event_path  );
              $this->log ('PHOTOS_DEBUG: Photo->id '.  $this->Photo->id  );
              $this->log ('PHOTOS_DEBUG: extension '. $extension  );
              $uploaded = $this->Upload->directUpload( $_FILES[$this->api['photo']],        // data from form (uploaded file)
                          $event_path . $this->Photo->id . '.' . $extension );        // path + filename
              $this->log ('PHOTOS_DEBUG: Uploaded response '. $uploaded  );
    //            $uploaded = $this->Upload->upload ( $_FILES [$this->api ['photo']],         // data from form (uploaded file)
    //                $event_path , $this->Photo->id . '.' . $extension )        // path + filename
    //                ;
                    
        } catch ( Exception $e ) {
                                $this->log("PHOTOS_DEBUG: FALLITO Upload->directUpload ".$e);
                            }
        if (! $uploaded) { // TODO: check if upload is succesfull before saving to DB?
            $this->Photo->delete ('PHOTOS_DEBUG: '. $this->Photo->id );
            $this->_apiEr ( __ ( 'Upload della foto fallito' ), __ ( 'Upload della foto fallito' ), true, null, '989' );
            
        }
        
        $this->_apiOk ( array (
                'photo_big' => $this->Photo->id 
        ) );
    }
    
    
    
	/**
	 * Upload photo
	 */
	public function api_MemberPhotos() {
		$this->_checkVars ( array (
				'user_big' 
		) );
		
		$memBig = $this->api ['user_big'];
		
		$xPhoto = $this->Photo->getMemberPhotos ( $memBig );
		
		// debug($xPhoto);
		
		for($i = 0; $i < count ( $xPhoto ); $i ++) {
			
			$xPhoto [$i] ["Photo"] ["url"] = $this->FileUrl->event_photo ( $xPhoto [$i] ['Event'] ['big'], $xPhoto [$i] ["Photo"] ['gallery_big'], $xPhoto [$i] ["Photo"] ['big'], $xPhoto [$i] ["Photo"] ['original_ext'] );
		}
		
		$this->_apiOk ( $xPhoto );
	}
	public function admin_view($big) {
		unbindAllBut ( $this->Photo, array (
				'Gallery' 
		) );
		$photo = $this->Photo->findByBig ( $big );
		
		if (empty ( $photo )) {
			$this->Session->setFlash ( __ ( 'Foto non trovata' ), 'flash/error' );
			$this->redirect ( array (
					'controller' => 'signalations',
					'action' => 'index' 
			) );
		}
		
		$this->set ( 'photo', $photo );
	}
	public function admin_all() {
		// unbindAllBut($this->Photo, array('Gallery'));
		$photo = $this->Photo->find ( 'all', array (
				'limit' => 10  // int($big);
				) );
		
		$this->set ( 'data', $photo );
	}
}