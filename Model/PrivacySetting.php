<?php
class PrivacySetting extends AppModel {
	public $primaryKey = 'big';

	public $belongsTo = array (
			'Member1' => array (
					'className' => 'Member',
					'foreignKey' => 'member_big' 
			),
/*			'Place1' => array (
					'className' => 'Place',
					'foreignKey' => 'place_big' 
			) 
*/
	);
	
	/*
	public $hasMany = array (
			'Member' => array (
					'foreignKey' => 'big'
			)
	);
	*/
	public function savePrivacySettings($data) {
		$res =false;
		$comment = array ();
	/*	foreach ( $data as $column => $field ) {
			if (isset ( $this->api [$field] )) {
				$comment [$column] = trim ( $data [$field] );
			}
		}
		 */
		
		$type = 'first';
		$params = array (
				'conditions' => array (
										'PrivacySetting.member_big' => $data['member_big']
								)				
		);
		
		$ExistingSet = $this->find ( $type, $params );
		
		if (count($ExistingSet)>0)
		{
			//die(debug($ExistingSet['PrivacySetting']['big']));
			$data['big']=$ExistingSet['PrivacySetting']['big'];
		}
		
			try {
				$res = $this->save ( $data );
			} catch ( Exception $e ) {
				//die(debug($e));
				$res = false;
			}
		return $res;
	}
		

	public function CreateSettings($dataBig) {
		$res =false;
		$comment = array ();
		/*	foreach ( $data as $column => $field ) {
		 if (isset ( $this->api [$field] )) {
		$comment [$column] = trim ( $data [$field] );
		}
		}
		*/
	
	    $data=array ();
	    $data['member_big']=$dataBig;
	    $data['visibletousers']='1';
	    $data['fbintegration']='1';
	    $data['disconnectplace']='1';
	    $data['profilestatus']='1';
	    $data['showvisitedplaces']='1';
	    $data['sharecontacts']='1';
	    $data['notifyprofileviews']='1';
	    $data['notifyfriendshiprequests']='1';
	    $data['notifychatmessages']='1';
	    $data['boardsponsor']='1';
	    $data['checkinsvisibility']='1';
	    $data['photosvisibility']='1';
	    
	    
	    
		try {
			$res = $this->save ( $data );
		} catch ( Exception $e ) {
			//die(debug($e));
			$res = false;
		}
		return $res;
	}
	
	
	
	public function getPrivacySettings($member_big) {
		$type = 'all';
		
		$params = array (
				'conditions' => array (
		
						'member_big' => $member_big 
				),
				'recursive' => -1,
		)
		;
		
		$result = $this->find ( $type, $params );
		//die(debug($result));
		return $result;
		
	}
	
	
}