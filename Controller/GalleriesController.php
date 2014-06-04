<?php

class GalleriesController extends AppController {
	
	public function admin_index($big=0) {
		
		$data = $this->Gallery->find('first', array(
			'conditions' => array('Gallery.big' => $big),
		));
		$this->set('data', $data);
		
	}
	
	public function admin_all() {
	
		$data = $this->Gallery->find('all', array(
				'limit' => 10, //int
		));
		$this->set('data', $data);
	
	}

	public function operator_index($big=0) {
		
		unbindAllBut($this->Gallery, array('Place'));
		$gallery = $this->Gallery->findByBig($big);
		$this->_checkOperatorPermissions($gallery['Place']['big']);

		$this->admin_index($big);
		
	}
	
	/**
	 * List uploaded photos
	 */
	public function api_list() {
		
		$this->_checkVars(array('event_big'));
		
		unbindAllBut($this->Gallery, array('Photo'));
		$gallery = $this->Gallery->findByEventBig( $this->api['event_big'] );
		
		if (!$gallery) {
			return $this->_apiOk(array('Photos' => $gallery));
		}
		
		$data = array();
		foreach($gallery['Photo'] as $key=>$val) {
			$data[] = array(
				'big' => $val['big'],
				'original_ext' => $val['original_ext'],
				'member_big' => $val['member_big'],
				'created' => $val['created'],
				'url' => $this->FileUrl->event_photo($gallery['Gallery']['event_big'], $gallery['Gallery']['big'], $val['big'], $val['original_ext'])
			);
		}
		
		$this->_apiOk(array('Photos' => $data));
		
	}
	
}