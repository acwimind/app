<?php
class Comment extends AppModel {
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
	public function saveComment($data) {
		$res =false;
		$comment = array ();
	/*	foreach ( $data as $column => $field ) {
			if (isset ( $this->api [$field] )) {
				$comment [$column] = trim ( $data [$field] );
			}
		}
		if ($member1_big != $member2_big) {
				
			$data = array (
					'ProfileVisit' => array (
							'visitor_big' => $member1_big,
							'visited_big' => $member2_big,
							'created' => 'NOW()'
					)
			); */
			try {
				$res = $this->save ( $data );
				if($data['likeit']==0)
				{
					$db = $this->getDataSource ();
					//TODO: add a chance to unlike places?
					$sql = 'UPDATE comments set likeit=false where member_big='.$data['member_big'].' AND checkin_big='.$data['checkin_big']; 
					$this->query($sql);
				}
				
		
			} catch ( Exception $e ) {
				//die(debug($e));
				$res = false;
			}
	
	
	
	/*	if ($member1_big != $member2_big) {
			
			$data = array (
					'ProfileVisit' => array (
							'visitor_big' => $member1_big,
							'visited_big' => $member2_big,
							'created' => 'NOW()' 
					) 
			);
			try {
				$res = $this->save ( $data );
			} catch ( Exception $e ) {
				// debug($e);
				$res = false;
			}
			
			
			 // $this->ProfileVisits->set ( 'visitor_big', $member1_big ); $this->ProfileVisits->set ( 'visited_big', $member2_big ); if ($this->ProfileVisits->save ()) { return true; }
	
		}
		*/
		return $res;
	}
	public function getLikesCount($checkin_big,$isplace) {
		$type = 'count';
		
		if ($isplace==1)
		{	
		$params = array (
				'conditions' => array (
		
						'place_big' => $checkin_big ,
						'likeit '=> TRUE
						// NON NEL COUNT!! 'comment !=' => ''
				),
				'fields' => array (
						'Comment.big' ,
						'Comment.created'
				) ,
				'order' => array('Comment.created' => 'desc'),
				'recursive' => -1,
		)
		;
		} else {
			$params = array (
					'conditions' => array (
			
							'checkin_big' => $checkin_big ,
							'likeit '=> TRUE
							// NON NEL COUNT!! 'comment !=' => ''
					),
					'fields' => array (
							'Comment.big' ,
							'Comment.created'
					) ,
					'order' => array('Comment.created' => 'desc'),
					'recursive' => -1,
			)
			;

		}
		
		$result = $this->find ( $type, $params );
	//	die(debug($result));
		return $result;
		
	
	}
	
	public function getCommentsCount($checkin_big,$isplace) {
		$type = 'count';
		
		 if ($isplace==1)
		 {
		 $params = array (
				'conditions' => array (
						
						'place_big' => $checkin_big ,
						'likeit '=> FALSE,
						'comment !=' => ''
				),
				'fields' => array (
						'Comment.big' ,
						'Comment.created'
				) ,
				'order' => array('Comment.created' => 'desc'),
				'recursive' => -1,
		)
		;
		 }
		 else 
		 {
		 	
		 	$params = array (
		 			'conditions' => array (
		 	
		 					'checkin_big' => $checkin_big ,
		 				'likeit '=> FALSE,
		 			 'comment !=' => ''
		 			),
		 			'fields' => array (
		 					'Comment.big' ,
		 					'Comment.created'
		 			) ,
		 			'order' => array('Comment.created' => 'desc'),
		 			'recursive' => -1,
		 	)
		 	;
	 	
		 }
		
		$result = $this->find ( $type, $params );
//die(debug($result));
		return $result;

	}
	
	
	public function getComments($checkin_big) {
		$type = 'all';
		
		
		$params = array (
				'conditions' => array (
		
						'checkin_big' => $checkin_big ,
						'likeit != '=> true,
						'comment !=' => ''
				),
				'fields' => array (
						'Comment.big',
						'Comment.member_big' ,
						'Comment.comment' ,
						'Comment.created'
				) ,
				'order' => array('Comment.created' => 'desc'),
				'recursive' => -1,
		)
		;
		
		$result = $this->find ( $type, $params );
		//die(debug($result));
		return $result;

	}
	
	
}