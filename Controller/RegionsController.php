<?php
class RegionsController extends AppController {
	public function admin_index() {
		$this->_savedFilter ( array (
				'srchphr',
				'country' 
		) );
		
		$conditions = array ();
		
		if (isset ( $this->params->query ['srchphr'] ) && ! empty ( $this->params->query ['srchphr'] )) {
			$conditions ['OR'] = array (
					'Region.city ILIKE' => '%' . $this->params->query ['srchphr'] . '%' 
			);
		}
		if (isset ( $this->params->query ['country'] ) && ! empty ( $this->params->query ['country'] )) {
			$conditions ['Region.country'] = $this->params->query ['country'];
		}
		
		$this->request->data ['Region'] = $this->params->query;
		
		$data = $this->paginate ( 'Region', $conditions );
		$this->set ( 'data', $data );
	}
	public function admin_add() {
		$this->admin_edit ();
		$this->render ( 'admin_edit' );
	}
	public function admin_edit($id = 0) {
		if ($this->request->is ( 'post' ) || $this->request->is ( 'put' )) {
			
			if ($this->Region->saveAll ( $this->request->data, array (
					'validate' => 'first' 
			) )) {
				$this->Session->setFlash ( __ ( 'Region saved' ), 'flash/success' );
				return $this->redirect ( array (
						'action' => 'index' 
				) );
			} else {
				$this->Session->setFlash ( __ ( 'Error while saving region' ), 'flash/error' );
			}
		} elseif ($id > 0) {
			
			$this->request->data = $this->Region->getRegionLangs ( $id );
		}
	}
	public function admin_delete($id) {
		$has_places = $this->Region->Place->find ( 'count', array (
				'conditions' => array (
						'Place.region_id' => $id 
				),
				'recursive' => - 1 
		) );
		
		if ($has_places == 0) {
			$this->Region->RegionLang->deleteAll ( array (
					'RegionLang.region_id' => $id 
			) );
			$this->Region->deleteAll ( array (
					'Region.id' => $id 
			) );
			$this->Session->setFlash ( __ ( 'Region deleted' ), 'flash/success' );
		} else {
			$this->Session->setFlash ( __ ( 'Unable to delete region, because it contains places. Please delete places first.' ), 'flash/error' );
		}
		return $this->redirect ( array (
				'action' => 'index' 
		) );
	}
	
	/**
	 * Return list of regions
	 */
	public function api_list() {
		$this->_checkVars ( array (), array (
				'name' 
		) );
		
		unbindAllBut ( $this->Region );
		
		if (isset ( $this->api ['name'] )) {
			$city = $this->api ['name'];
			$params = array (
					'conditions' => array (
							'Region.city ILIKE' => "$city%" 
					),
					'fields' => array (
							'Region.id',
							'Region.city' 
					),
					'order' => array (
							'Region.city' => 'asc' 
					),
					'recursive' => - 1 ,
					'limit' => 1 
			);
		} else { // try
			$params = array (
					'fields' => array (
							'Region.id',
							'Region.city' 
					),
					'order' => array (
							'Region.city' => 'asc' 
					),
					'recursive' => - 1 
			);
		}
		
		try {
			
			$cities = $this->Region->find ( 'list', $params );
			// array(
			// 'fields' => array('Region.id', 'Region.city'),
			// 'order' => array('Region.city' => 'asc'),
			// 'recursive' => -1,
			// ));
		} catch ( Exception $e ) {
			$this->_apiEr ( __ ( "Error" ) );
		}
		
		// $cities_array = array();
		// foreach ($cities as $row) {
		// $cities_array[] = $row;
		// }
		
//		$this->_apiOk ( array (
//				'cities' => $cities 
//		) );
		$this->_apiOk ($cities);
		
	}
	public function autocomplete($city = null, $country = null) {
		
		/*
		 * if (!$this->isAjax) {	//allow only ajax access $this->Session->setFlash(__('Access denied'), 'flash/error'); return $this->redirect('/'); }
		 */
		if (empty ( $city )) {
			$city = $_POST ['city'];
		}
		if (empty ( $country )) {
			$country = $_POST ['country'];
		}
		
		$city = mb_strtolower ( trim ( $city ) );
		$country = mb_strtolower ( trim ( $country ) );
		
		$limit = 10;
		
		// find regions
		$regions = $this->Region->find ( 'list', array (
				'conditions' => array (
						'lower(Region.city) LIKE' => $city . '%',
						'lower(Region.country)' => $country 
				),
				'fields' => array (
						'Region.id',
						'Region.city' 
				),
				'order' => array (
						'Region.city' => 'asc' 
				),
				'recursive' => - 1,
				'limit' => $limit 
		) );
		
		$regions_array = array ();
		foreach ( $regions as $item ) {
			$regions_array [] = $item;
		}
		
		$this->set ( 'regions', $regions_array );
	}
}