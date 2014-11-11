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
        
        $db = $this->getDataSource();
        
         $sql = "SELECT to_big as \"blockedbig\" ".
                "FROM member_settings ".
                "WHERE from_big=$member_big AND chat_ignore=1 ".
                "UNION ".
                "SELECT from_big as \"blockedbig\" ".
                "FROM member_settings ".
                "WHERE to_big=$member_big AND chat_ignore=1 ";
        
        $bloccati = $db->fetchAll ( $sql );
        
        foreach ($bloccati as $key=>$val){
            
            $utentiBloccati[]=$val[0]['blockedbig'];
      }
                    
        $type = 'all';
        $params = array (
                'conditions' => array (
                        
                        'visited_big' => $member_big,
                         
                ),
                'fields' => array (
                        'ProfileVisit.visitor_big' ,
                        'MAX(ProfileVisit.created) AS created',
                        'COUNT(visitor_big) AS number_of_visits'
                ) ,
                'group' => array('ProfileVisit.visitor_big'),
                'order' => array('created' => 'desc'),
                'recursive' => -1,
        );
        
         if (count($utentiBloccati)>0){
              
           $params['conditions'][]=array('NOT'=>array('visitor_big'=> $utentiBloccati));
                   
        } 
                
        $result = $this->find ( $type, $params );
                        
        return $result;
    }
    
    public function getVisitsOLD($member_big) {
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
        
        //print_r($result);
        
		return $result;
	}
    
    
    public function markAsRead($memBig)
    {
       
        $db = $this->getDataSource();
        $sql = 'UPDATE profile_visits SET read=1 WHERE read= ? AND visited_big= ? ';
        try {
            $db->fetchAll($sql,array('0',$memBig));
        }
        catch (Exception $e)
        {
            debug($e);
            return false;
        }

        return true;
                       
        
    }
    
    
    
    public function getNotReadVisits($memBig){
        
        $db = $this->getDataSource();
        
         $sql = "SELECT to_big as \"blockedbig\" ".
                "FROM member_settings ".
                "WHERE from_big=$memBig AND chat_ignore=1 ".
                "UNION ".
                "SELECT from_big as \"blockedbig\" ".
                "FROM member_settings ".
                "WHERE to_big=$memBig AND chat_ignore=1 ";
        
        $bloccati = $db->fetchAll ( $sql );
        
        foreach ($bloccati as $key=>$val){
            
            $utentiBloccati[]=$val[0]['blockedbig'];
      }
        
        
        $params=array(
            'conditions' => array(
                'read' => 0, 'visited_big' => $memBig
            ));
        
        
        if (count($utentiBloccati)>0){
              
           $params['conditions'][]=array('NOT'=>array('visitor_big'=> $utentiBloccati));
                   
        } 
               
        
        $counter = $this->find('count', $params);
                     
               
        //$db = $this->getDataSource();
        //$sql = 'SELECT COUNT(*) AS visits FROM public.profile_visits WHERE read=0 AND visited_big='.$memBig;
        //        
        //$result=$db->fetchAll($sql);
        
        return $counter;
         
        
    }
    
    
    
}