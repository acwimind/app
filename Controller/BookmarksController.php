<?php

class BookmarksController extends AppController {
	
	/**
	 * Return list of bookmarks
	 */
	public function api_list() {
		
		
		$memBig = $this->logged['Member']['big'];
		
		unbindAllBut($this->Bookmark, array('Place', 'Region'));
		unbindAllBut($this->Bookmark->Place, array('DefaultPhoto', 'Gallery', 'Region'));
		
		$params = array(
			'conditions' => array(
				'Bookmark.member_big' => $memBig,
				'Place.status !=' => DELETED,
			 ),
			 'fields' => array(
			 	'Bookmark.place_big',
			 	'Bookmark.member_big',
			 	'Place.name',
			 	'Place.category_id',
			 	'Place.address_street',
			 	'Place.address_street_no',
			 	'Place.rating_avg',
			 	'Place.lonlat',
			 ),
			'recursive' => 2,
		);
		$bms_raw = $this->Bookmark->find('all', $params);

		if (empty($bms_raw)) {
			return $this->_apiOk(array(
				'bookmarks' => null,
			));
		}
		
		$categories = $this->Bookmark->Place->Category->getAll();
		
		$bms = array();
		foreach($bms_raw as $key=>$val) {
			
			$bms[] = array(
				'Bookmark' => $val['Bookmark'],
				'Place' => array(
					'big' => $val['Place']['big'],
					'name' => $val['Place']['name'],
					'category_id' => $val['Place']['category_id'],
					'address_street' => $val['Place']['address_street'],
					'address_street_no' => $val['Place']['address_street_no'],
					'rating_avg' => $val['Place']['rating_avg'],
					'city' => $val['Place']['Region']['city'],
					'coordinates' => $val['Place']['lonlat'],	
				),
				'CatLang' => array(
					'category_id' => $val['Place']['category_id'],
					'name' => $categories[ $val['Place']['category_id'] ]['CatLang']['name'],
					'photo' => $this->FileUrl->category_picture($val['Place']['category_id'], $categories[ $val['Place']['category_id'] ]['Category']['updated']),
				),
				'DefaultPhoto' => $val['Place']['DefaultPhoto'],
				'Gallery' => $val['Place']['Gallery'],
			);
			
		}
		$bms = $this->_addPlacePhotoUrls($bms);
		
		$this->_apiOk(array(
			'bookmarks' => $bms
		));
		
	}
	
	/**
	 * Add place to bookmarks of this member
	 */
	public function api_add() {
		
		$this->_checkVars(array('place_big'));

		try {
			
			$this->_add($this->api['place_big']);
			$this->_apiOk(array(
				'added' => true,
			));


		} catch (ErrorEx $e) {

			$this->_apiEr( $e->getMessage() );

		}

	}
	public function _add($placeBig) {

		$memBig = $this->logged['Member']['big'];

		$result = $this->Bookmark->addBookmark($memBig, $placeBig);
		
		if ($result !== false) {// Bookmark added
        
            $this->Member->rank($memBig,1);
            
			return true;
		} else {
			throw new ErrorEx(__('Place is already bookmarked'));
		}

	}
	
	/**
	 * Remove place/s from bookmarks of this member
	 */
	public function api_remove() {
		
		$this->_checkVars(array('place_big'));

		try {
			
			$this->_remove($this->api['place_big']);
			$this->_apiOk(array(
				'removed' => true,
			));

		} catch (ErrorEx $e) {

			$this->_apiEr( $e->getMessage() );

		}
		
		
	}

	public function _remove($placeBig) {
		
		$memBig = $this->logged['Member']['big'];
		
		// error handling for invalid format (i.e. other than id,id2,id3,...,idn)
		if (!is_numeric($placeBig)) {
			$plcBigs = explode(',', $placeBig);
			foreach ($plcBigs as $key=>$pbig) {
				if (!is_numeric($pbig)) {
					unset($plcBigs[$key]);
				}
			}
			$placeBig = $plcBigs;
		} 

		try {
			$result = $this->Bookmark->deleteAll(array('Bookmark.member_big' => $memBig, 'Bookmark.place_big' => $placeBig), false);
		} catch (Exception $e) {
			$result = false;
		}

		if ($result !== false) {// bookmarks successfully removed
			
            $this->Member->rank($memBig,2);
            
            return true;
		} else {
			throw new ErrorEx('Error occured. Bookmark not removed.');
		}
	}

	public function add($place_big=0) {
		
		try {
			
			$this->_add($place_big);
			$this->Session->setFlash(__('Posto salvato nei preferiti'), 'flash/success');

		} catch (ErrorEx $e) {

			$this->Session->setFlash( $e->getMessage(), 'flash/error' );

		}

		$this->Bookmark->Place->recursive = -1;
		$place = $this->Bookmark->Place->findByBig($place_big);
		debug($place);

		return $this->redirect(array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']));
		
	}

	public function remove($place_big=0) {
		
		try {
			
			$this->_remove($place_big);
			$this->Session->setFlash(__('Posto rimosso dai preferiti'), 'flash/success');

		} catch (ErrorEx $e) {

			$this->Session->setFlash( $e->getMessage(), 'flash/error' );

		}

		$this->Bookmark->Place->recursive = -1;
		$place = $this->Bookmark->Place->findByBig($place_big);
		debug($place);

		return $this->redirect(array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']));
		
	}

	public function index() {
		
		unbindAllBut($this->Bookmark, array('Place'));
		unbindAllBut($this->Bookmark->Place, array('DefaultPhoto', 'Gallery'));
		$bookmarks = $this->Bookmark->find('all', array(
			'conditions' => array(
				'Bookmark.member_big' => $this->logged['Member']['big'],
				'Place.status !=' => DELETED,
			 ),
			 'fields' => array(
			 	'Bookmark.place_big',
			 	'Bookmark.member_big',
			 	'Place.name',
			 	'Place.category_id'
			 ),
			'recursive' => 2,
		));
		$this->set('bookmarks', $bookmarks);		

	}
	
}