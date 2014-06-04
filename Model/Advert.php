<?php

class Advert extends AppModel {
	
	public $validate = array(
		'heading' => array(
			'min' => array(
				'rule' => array('minLength', 5),
				'message' => 'Please fill in heading of the advertisment',
			),
			'max' => array(
				'rule' => array('maxLength', 100),
				'message' => 'Heading is too long',
			),
		),
		'url' => array(
				'rule' => array('maxLength', 250),
				'message' => 'Url is too long',
				'allowEmpty' => true
		),
		'text' => array(
				'rule' => array('maxLength', 250),
				'message' => 'Advertisment text is too long',
				'allowEmpty' => true
		),
	);
	
	
	public function getBoardAds($MemberID) {
	
	
		$type = 'all';
		$params = array (
			   'order' => 'RANDOM()',
   				'limit' => 10
		);
		
		
		$result = $this->find ( $type, $params );
		return $result;
	
	}
	
	
	
	
}