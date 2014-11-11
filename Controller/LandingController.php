<?php

class LandingController extends Controller {
	
	var $uses = array('Checkin', 'Event', 'MemberRel', 'Bookmark', 'Region', 'Wallet', 'ProfileVisit','Friend');
	

	
	public function index() {
		
		
		//	return $this->redirect('http://www.haamble.com');
		
		$this->layout='api';
	
		return $this->render('index');
			
		}
		
	
	
	
}