<?php

class LandingController extends Controller {
	
	var $uses = array('Checkin', 'Event', 'MemberRel', 'Bookmark', 'Region', 'Wallet', 'ProfileVisit','Friend');
	

	
	public function index() {
		
		
		//	return $this->redirect('http://www.haamble.com');
		
		$this->layout='api';
	
		return $this->render('index');
			
		}
		
		public function indexen() {
		
		
			//	return $this->redirect('http://www.haamble.com');
		
			$this->layout='api';
		
			return $this->render('indexen');
				
		}

		
		public function thankyou() {
		
		
			//	return $this->redirect('http://www.haamble.com');
		
			$this->layout='api';
		
			return $this->render('thankyou');
		
		}
		
		public function careers() {
		
		
			//	return $this->redirect('http://www.haamble.com');
		
			$this->layout='api';
		
			return $this->render('careers');
		
		}
	
		public function careers2() {
		
		
			//	return $this->redirect('http://www.haamble.com');
		
			$this->layout='api';
		
			return $this->render('careers2');
		
		}
		
}