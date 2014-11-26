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
        
        
        $query = array (
                'conditions' => array (
        
                        'member_big' => $data['member_big'],
                        'checkin_big' => $data['checkin_big'],
                        'comment' => null
                              ),
                
                'recursive' => -1,
        );
        
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
				  
                  if (isset($data['likeit'])){ // viene passato il like
                  
                            $findLike = $this->find('first', $query);  
                  
                            if (count($findLike)>0){//esiste già un like
                                    
                                    //$likeval=!$findLike['Comment']['likeit'];
                                    $likeval=$data['likeit'];
                                    $update=array('likeit' => $likeval);    
                                    $cond=array('member_big' => $data['member_big'],'checkin_big' => $data['checkin_big'],'comment'=>null);  
                                    
                                    $res = $this->updateAll($update,$cond);
                                              
                                   } else {//non c'era un like
                                                            
                                             $res = $this->save( $data );
                                                                                                                          
                                          }
                                           
                   } else { //viene passato il comment
                              $data['likeit']=false;
                              $res = $this->save( $data );
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