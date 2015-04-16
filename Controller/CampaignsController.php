<?php

class CampaignsController extends AppController {
	

	/**
	 * Return list of campaigns
	 */
	public function api_add() {
	
		
		$camps = array('categories' => $this->Category->find('all'));
	
		$this->_apiOk($camps);
	
	}
	
	/**
	 * Return list of campaigns
	 */
	public function api_list() {
		
		
		$camps = array('categories' => $this->Category->find('all'));
		
		$this->_apiOk($camps);
		
	}
	
}