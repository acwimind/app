<?php
class ProfileVisit extends AppModel {
	public $primaryKey = 'big';
	public $belongsTo = array (
			'Friend1' => array (
					'className' => 'Member',
					'foreignKey' => 'visitor_big' 
			),
			'Friend2' => array (
					'className' => 'Member',
					'foreignKey' => 'visited_big' 
			) 
	);
	
	
	public $hasMany = array (
			'Member' => array (
					'foreignKey' => 'big'
			)
	);
	
	public function saveVisit($member1_big, $member2_big) {
		$res =false;
		if ($member1_big != $member2_big) {
			
			$data = array (
					'ProfileVisit' => array (
							'visitor_big' => $member1_big,
							'visited_big' => $member2_big,
							'created' => 'NOW()' 
					) 
			);
			try {
				$res = $this->save ( $data );
				
				// PUSH notifica
				
			} catch ( Exception $e ) {
				// debug($e);
				$res = false;
			}
			
			/*
			 * $this->ProfileVisits->set ( 'visitor_big', $member1_big ); $this->ProfileVisits->set ( 'visited_big', $member2_big ); if ($this->ProfileVisits->save ()) { return true; }
			 */
		}
		return $res;
	}
	public function getVisits($member_big) {
		$type = 'all';
		$params = array (
				'conditions' => array (
						
						'visited_big' => $member_big 
				),
				'fields' => array (
						'ProfileVisit.visitor_big' ,
						'ProfileVisit.created'
				) ,
				'order' => array('ProfileVisit.created' => 'desc'),
				'recursive' => -1,
		)
		;
		
		$result = $this->find ( $type, $params );
		return $result;
	}
}