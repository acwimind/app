<?php

class Member extends AppModel {

    public $uses = array ('Wallet','Member');
    
	public $primaryKey = 'big';

	public $hasMany = array(
		'Photo',
		'Rating',
		'Checkin',
		'Signalation',
		'ApiToken' => array(
			'conditions' => array('ApiToken.expired > now()'),
			'order' => array('ApiToken.expired' => 'desc'),
		),
		'PushToken',
		'PasswordResetToken',
		'MemberSetting' => array(
			'foreignKey' => false,
		),
		/*'SettingTo' => array(
			'model' => 'MemberSettings',
			'foreignKey' => 'to_big',
		),
		'SettingFrom' => array(
			'model' => 'MemberSettings',
			'foreignKey' => 'from_big',
		),
		'MemberRel' => array(
			'foreignKey' => false,
			'conditions' => array('or' => array('MemberRel.member1_big' => 'Member.big', 'MemberRel.member2_big' => 'Member.big')),
		),
		'ChatFrom' => array(
		 'model' => 'ChatMessage',
			'foreignKey' => 'from_big',
		),
		'ChatTo' => array(
			'model' => 'ChatMessage',
			'foreignKey' => 'to_big',
		),
		'ChatThread' => array(
			'model' => 'ChatMessage',
			'foreignKey' => false,
			'conditions' => array('or' => array('ChatThread.from_big' => 'Member.big', 'ChatThread.to_big' => 'Member.big')),
			'order' => array('ChatThread.created' => 'desc')
		),*/
	);

	public $hasOne = array(
		'Operator',
		'MemberPerm',
		'ExtraInfos',
        'PrivacySetting' => array('foreignKey' => 'member_big',
                                  'fields' => array(
                                            'member_big',
                                            'photosvisibility'))
	);

	public $validate = array(
		'name' => array(
			'min' => array(
				'rule' => array('minLength', 2),
				'message' => 'Please fill in first name',
			),
			'max' => array(
				'rule' => array('maxLength', 100),
				'message' => 'First name is too long',
			),
		),
		'middle_name' => array(
			'rule' => array('maxLength', 100),
			'message' => 'Middle name is too long',
		),
		'surname' => array(
			'min' => array(
				'rule' => array('minLength', 2),
				'message' => 'Please fill in last name',
			),
			'max' => array(
				'rule' => array('maxLength', 100),
				'message' => 'Last name is too long',
			),
		),
		'birth_place' => array(
			'max' => array(
				'rule' => array('maxLength', 100),
				'message' => 'Birth place is too long',
			),
			'text' => array(
				'rule' => '/^[a-zA-Z\-\' ]+$/',
				'message' => 'Please enter a valid city name',
				'allowEmpty' => true
			),
		),
		'sex' => array(
			'rule' => array('maxLength', 1),
			'message' => 'Sex is too long',
		),
		'birth_date' => array(
		        'rule' => 'isOver18',
	            'message' => 'You must be over 18',
	    ),
		'phone' => array(
			'max' => array(
				'rule' => array('maxLength', 32),
				'message' => 'Phone number is too long',
			),
			'reg' => array(
		        'rule'    => '/^[0-9\+ ]+$/',
		        'message' => 'Please enter a valid phone number',
				'allowEmpty' => true
		    )
		),
		'address_street_no' => array(
			'max' => array(
				'rule' => array('maxLength', 12),
				'message' => 'Street number is too long',
			),
			'num' => array(
				'rule' => '/^[0-9\/r]+$/',
				'message' => 'Only numeric characters and a slash allowed for street number',
				'allowEmpty' => true
			),

		),
		'address_street' => array(
			'rule' => array('maxLength', 64),
			'message' => 'Street is too long',
		),
		'address_town' => array(
			'max' => array(
				'rule' => array('maxLength', 64),
				'message' => 'City is too long',
			),
			'text' => array(
				'rule' => '/^[a-zA-Z\-\' ]+$/',
				'message' => 'Please enter a valid city name',
				'allowEmpty' => true
			),
		),
		'address_country' => array(
			'rule' => array('maxLength', 2),
			'message' => 'State is too long',
		),
		'address_zip' => array(
			'max' => array(
				'rule' => array('maxLength', 5),
				'message' => 'ZIP code is too long, maximum 5 digits',
			),
			'digits' => array(
				'rule' => '/^[0-9]+$/',
				'message' => 'Please enter a valid ZIP code',
				'allowEmpty' => true
			)
		),
		'email' => array(
			'email' => array(
				'rule' => 'email',
				'message' => 'Please enter a valid e-mail address',
			),
			'unique' => array(
				'rule' => 'unique_email',
				'message' => 'This e-mail address is already in use by another user',
			),
		),

		'phone'=> array(
			'phone' => array(
				'rule' => 'numeric',
				'message' => 'Please enter numbers only',
			),
			'unique' => array(
				'rule' => 'isUnique',
				'message' => 'This phone address is already in use by another user',
			),
			'len' => array(
					'rule' => array('minLength', 6),
					'message' => 'This phone address must be at least 6 characters long',
			),
		),
		'password' => array(
			'rule' => array('minLength', 6),
			'message' => 'Password must be at least 6 characters long',
		),
		'password2' => array(
            'rule' => array('matchingPasswords'),
            'message' => 'Passwords don\'t match',
        ),
//		'agreement' => array(
//                    'rule'     => array('equalTo', 'Y'),
//        			'required' => true,
//                    'message'  => 'Please check this box if you want to proceed'
//        ),
	);

	public function matchingPasswords($data) {
        return $this->data['Member']['password'] == $data['password2'];
    }

	public function unique_email($check) {

		$exists = $this->find('count', array(
			'conditions' => array(
				'email' => $check['email'],
				'big !=' => isset($this->data['Member']['big']) ? $this->data['Member']['big'] : 0,
			),
			'recursive' => -1,
		));

		return $exists == 0;

	}

		
	public function getAffinityMembers($memberBig,$offset=0)
	{//debug($check);
	
	$IPmember=($this->getMemberByBig($memberBig));
	$Imember=$IPmember['Member'];
	//	$birthdate = strtotime();
	//	 (strtotime($birthdate . '+18 year') > time())
	$Iyear = date('Y', strtotime($Imember['birth_date']));
	$coords =$Imember['last_lonlat'];
	//$date = DateTime::createFromFormat("Y-m-d", $birthdate);
	//	$Iyear =  $birthdate->format("Y");
	$db = $this->getDataSource ();
    
    $FriendModel = ClassRegistry::init('Friend');
    $Friends=$FriendModel->findAllfriends($memberBig);
    
    foreach($Friends as $key=>$val){
        
        $amici[]=($val['Friend']['member1_big']==$memberBig)? $val['Friend']['member2_big']:$val['Friend']['member1_big'];
                        
    }
    
    $lista_amici=implode(',',$amici);
       if (count($lista_amici)>0){//se ci sono amici allora filtra
           
           $filtroNonAmici=" AND members.big NOT IN ($lista_amici) ";
                     
       } else {//se non ha amici non occorre filtrare
           
           $filtroNonAmici=' ';
       }
       
     $filtroBloccati=" AND members.big NOT IN (SELECT to_big as \"blockedbig\" ".
                                               "FROM member_settings ".
                                               "WHERE from_big=$memberBig AND chat_ignore=1 ".
                                               "UNION ".
                                               "SELECT from_big as \"blockedbig\" ".
                                               "FROM member_settings ".
                                               "WHERE to_big=$memberBig AND chat_ignore=1) "; 
      
    $ageAttempts=array(10,15,20);  //increasing values
    $distanceAttempts=array(10,50,100);   //increasing values
    
    //for loops first search the nearest members and then search for the most distant members     
    for ($i=0; $i<count($distanceAttempts); $i++){ //distance array iterator index
                                                        
        
        for ($j=0; $j<count($ageAttempts); $j++){ //age array iterator index
    
                         
       $sql2 = "SELECT members.big,members.name,members.surname,members.updated,members.birth_date,".
               "members.photo_updated,members.sex,".
               "members.last_lonlat AS \"coordinates\",((members.last_lonlat <@> ? )::numeric(10,1) * 1.6) ".
               "AS \"distance\" ".
               "FROM members ".
               "JOIN privacy_settings ON members.big=privacy_settings.member_big ".
               "WHERE ((".$Iyear." - DATE_PART('year', birth_date)) BETWEEN -".$ageAttempts[$j]." ".
               "AND ".$ageAttempts[$j].") ".
               "AND sex != '".$Imember['sex']."' ".
               "AND	(members.big <> ".$memberBig.") ".
               "AND (members.status <> 255) ".
               "AND (privacy_settings.visibletousers>0) ".
               $filtroNonAmici.
               $filtroBloccati.
               "AND	( members.last_lonlat <@> ? )::numeric(10,1) < (". NEARBY_RADIUS. "*".$distanceAttempts[$i].
               ") ".
               "ORDER BY ( members.last_lonlat <@> ?)::numeric(10,1) ".
               "ASC LIMIT ". API_MAP_LIMIT ." OFFSET ".$offset;
	                 
        $result = $db->fetchAll ( $sql2, array ($coords,  $coords,  $coords  ));
        
        if (count($result)>=MIN_AFFINITY_MEMBERS) {
                                break 2;
                                }
        }
            
    } 
     
     if (!count($result)>=MIN_AFFINITY_MEMBERS){// extreme attempt : max age diff, max distance and any members sex
         
         $sql2 = "SELECT members.big,members.name,members.surname,members.updated,members.birth_date,".
                 "members.photo_updated,members.sex,".
                 "members.last_lonlat AS \"coordinates\",((members.last_lonlat <@> ? )::numeric(10,1) * 1.6) AS ".
                 "\"distance\" ".
                 "FROM members ".
                 "JOIN privacy_settings ON members.big=privacy_settings.member_big ".             
                 "WHERE (( ".$Iyear." - DATE_PART(\'year\', birth_date)) BETWEEN -".
                 $ageAttempts[count($ageAttempts)-1]." ".
                 "AND ".$ageAttempts[count($ageAttempts)-1]." ) ".
                 "AND (members.big <> ".$memberBig.") AND (members.status <> 255) ".
                 "AND (privacy_settings.visibletousers>0) ".
                  $filtroNonAmici.
                  $filtroBloccati.
                 "AND ( members.last_lonlat <@> ? )::numeric(10,1) < (" . NEARBY_RADIUS . "*".$distanceAttempts[count($distanceAttempts)-1].") ".
                 "ORDER BY ( members.last_lonlat <@> ?)::numeric(10,1) ".
                 "ASC LIMIT ". API_MAP_LIMIT. " OFFSET ".$offset; 
         
         
          $result = $db->fetchAll ( $sql2, array ($coords,  $coords,  $coords    ) );
     }
      
	/*
	
	*
	* //$dbo = $this->Member->getDatasource();
	$logs = $db->getLog();
	$lastLog = end($logs['log']);
	die(debug($lastLog['query']));
	*/
	
	$serviceList=explode(",",ID_VISIBILITY_PRODUCTS);
    
    $ordered_result=$result;
    
    $modelWallet = ClassRegistry::init ( 'Wallet' );  
           
    foreach ($ordered_result as $key=>$value){
        
        $ordered_result[$key][0]['position_bonus']=$modelWallet->hasActiveService($serviceList,$value[0]['big']);
               
    }
    
      
    //print_r($ordered_result);
    return $ordered_result;
        	
	}
	
	
	
	
	public function getRadarMembers($memberBig)
	{//debug($check);
		
		$IPmember=($this->getMemberByBig($memberBig));
		$Imember=$IPmember['Member'];
	//	$birthdate = strtotime();
	//	 (strtotime($birthdate . '+18 year') > time())
		$Iyear = date('Y', strtotime($Imember['birth_date']));
		$coords =$Imember['last_lonlat'];
		//$date = DateTime::createFromFormat("Y-m-d", $birthdate);
	//	$Iyear =  $birthdate->format("Y");
	$db = $this->getDataSource ();
		$sql2 = 'SELECT
 members.big,
  members.name,
				members.surname,
				members.updated,
				members.photo_updated,
				members.sex,
	members.last_lonlat AS "coordinates",
((members.last_lonlat <@> ? )::numeric(10,1) * 1.6) AS "distance"
		
FROM
  public.members
				where members.big<>'.$memberBig.'   AND members.status < 255  
						order by  ( members.last_lonlat <@> ?)::numeric(10,1) asc LIMIT ' . API_MAP_LIMIT;
		
		
		
		//debug($sql2));
		
		// try {
		$result = $db->fetchAll ( $sql2, array (
				$coords,  $coords
		) );
		
		
	/*	

	 * 
	 * //$dbo = $this->Member->getDatasource();
		$logs = $db->getLog();
		$lastLog = end($logs['log']);
		die(debug($lastLog['query']));
		*/
		
		return $result;

	}

	public function getRadarPrivacyMembers($memberBig, $serviceList=array(), $bonusOrder=false)
    {/*
        Se si passa solo il $memberBig allora restituisce solo i dati del member e della sua privacy
        ordinando l'output in base alla distanza crescente.
        
        Se si passa l'array $serviceList con l'id dei servizi allora per ogni membro vede se ha attivo
        uno o più servizi specificati e in output aggiunge il campo [position_bonus] = n dove n è il numero
        dei servizi attivi tra quelli specificati.
        
        Se si passa anche il $bonusOrder allora ordina l'output mettendo per primi i membri che hanno
        attivi i servizi specificati nell'array. In output aggiunge il campo [position_bonus] = n dove n è           il numero dei servizi attivi tra quelli specificati.
    
    */
        
        $IPmember=($this->getMemberByBig($memberBig));
        $Imember=$IPmember['Member'];
        $coords =$Imember['last_lonlat'];
        
        $db = $this->getDataSource ();
        $sql = 'SELECT members.big,members.name,members.surname,members.updated,members.photo_updated,'.
                        'members.sex,members.last_lonlat AS "coordinates",'.
                        '((members.last_lonlat <@> ? )::numeric(10,1) * 1.6) AS "distance", '.
                        'visibletousers,fbintegration,disconnectplace,profilestatus,showvisitedplaces,'.                             'sharecontacts,notifyprofileviews,notifyfriendshiprequests,notifychatmessages,'.
                        'boardsponsor,checkinsvisibility,photosvisibility '.
                 'FROM members '.
                 'JOIN privacy_settings ON members.big=privacy_settings.member_big '.
                 'WHERE members.big <>'.$memberBig.' AND members.status<>255 '.    
                 'ORDER BY ( members.last_lonlat <@> ?)::numeric(10,1) ASC LIMIT ' . API_MAP_LIMIT;
        
        $this->log("query ".$sql);
        $result = $db->fetchAll ( $sql, array ($coords,  $coords ) );
       
        if (count($serviceList)>0){
            //attacca il position_bonus al result e poi ordina per position_bonus e distanza.
         $modelWallet = ClassRegistry::init ( 'Wallet' );
         
         $foundMembers=$result;
            
            foreach($foundMembers as $key=>$val){
                
                $val=$val[0];
                $foundMembers[$key][0]['position_bonus']=$modelWallet->hasActiveService($serviceList,$val['big']);
                
            }
            
           if ($bonusOrder){
               //ordina per position_bonus
               
               usort ( $foundMembers, 'Member::multiFieldSortArray' );
               
           }  
                   
          $result=$foundMembers;  
        }       
       
        
        return $result;

    }
    
    public static function multiFieldSortArray($x, $y) { // sort an array by position_bonus DESC and distance ASC
        if ($x [0] ['position_bonus'] == $y [0] ['position_bonus']) {
            
            return ($x [0] ['distance'] < $y [0] ['distance']) ? - 1 : + 1;
        } else
            
            return ($x [0] ['position_bonus'] > $y [0] ['position_bonus']) ? - 1 : + 1;
    }
    
    
	public function isActive($bigmem)
	{
		
	//	die(debug($bigmem));
		
	$Themem = $this->getMemberByBig($bigmem);

	//	die(debug($Themem)); //['status']));
		

	if (count($Themem)>0)
	{
	
	if ($Themem['Member']['status']!= DELETED)
	{
			return true ;
	}
	}
	return false;
	}
	
	public function isOver18($check)
	{//debug($check);
		$birthdate = $check['birth_date'];
		if (strtotime($birthdate . '+18 year') > time())
		{
//			$this->invalidate('Member.birth_date.date', 'You have to be 18 or older');
			$this->validationErrors['birth_date'] = array('date' => array('You must be over 18'));
			return false;
		}
		return true;
	}

	public function afterValidate() {

		App::uses('HaambleAuthenticate', 'Controller/Component/Auth');

		if ((!isset($this->data['Member']['big']) || empty($this->data['Member']['big'])) && !isset($this->data['Member']['salt']) && isset($this->data['Member']['email'])) {
			$salt = $this->data['Member']['salt'] = HaambleAuthenticate::generateSalt( $this->data['Member']['email'] );
		}

		if (isset($this->data['Member']['password'])) {

			if (!isset($this->data['Member']['salt'])) {
				if (!isset($salt)) {	//update - member already has salt
					// strange eror 14-10
					$salt = $this->find('first', array('fields' => array('salt'), 'conditions' => array('Member.big' => $this->data['Member']['big'])));
					$salt = $salt['Member']['salt'];
				}
			} else {	//we have salt in saved data
				$salt = $this->data['Member']['salt'];
			}

			$this->data['Member']['password'] = HaambleAuthenticate::hash($this->data['Member']['password'], $salt);
		}

	}

	public function memberSettingsFrom($from, $to=null) {
		return $this->memberSettings($from, $to);
	}

	public function memberSettingsTo($to, $from=null) {
		return $this->memberSettings($from, $to);
	}

	protected function memberSettings($from, $to) {

		if ($from === null && $to === null) {
			return false;
		} elseif ($from === null) {
			$from = $this->id;
		} elseif ($to === null) {
			$to = $this->id;
		}

		$settings = $this->Member->MemberSetting->find('first', array(
			'conditions' => array(
				'from_big' => $from,
				'to_big' => $to,
			),
			'recursive' => -1,
		));
		return $settings;

	}

	public function getMemberByBig($memberBig)
	{
		$params = array(
			'conditions' => array(
				'Member.big' => $memberBig
			),
			'recursive' => -1
		);
		
		 $return=$this->find('first', $params);
		 return $return;
	}
		
        public function rank($memberBig,$value){
                
        if (($memberBig!='') AND ($value>0)){
        
        $query='UPDATE members '.
               'SET rank=rank + '.$value.' '.
               'WHERE big= '.$memberBig; 
        
        $db = $this->getDataSource ();
        
        $db->fetchAll($query);
        
        $count=$this->getAffectedRows();
         
        if ($count>0) return true; else   
       
        return false;
        } else
            return false;
    }
        
        
        public function deleteMember($memberId){
        
        
        $db = $this->getDataSource ();
        $query = "UPDATE places ".
                 "SET default_photo_big=NULL ".
                 "WHERE default_photo_big IN (SELECT big FROM photos WHERE member_big=$memberId)";
        
        $res['places']=$db->fetchAll($query);
        
        $query = "DELETE FROM operators ".
                 "WHERE member_big=$memberId";
        
        $res['operators']=$db->fetchAll($query);
       
        $query = "DELETE FROM password_reset_tokens ".
                 "WHERE member_big=$memberId";
        
        $res['password_reset_tokens']=$db->fetchAll($query);
        
        $query = "DELETE FROM extra_infos ".
                 "WHERE member_big=$memberId";
        
        $res['extra_infos']=$db->fetchAll($query);
                 
        $query = "DELETE FROM contacts ".
                 "WHERE member_big=$memberId";
        
        $res['contacts']=$db->fetchAll($query);
                 
        $query = "DELETE FROM push_tokens ".
                 "WHERE member_big=$memberId";

        $res['push_tokens']=$db->fetchAll($query);
                 
        $query = "DELETE FROM privacy_settings ".
                 "WHERE member_big=$memberId";
        
        $res['privacy_settings']=$db->fetchAll($query);

        $query = "DELETE FROM comments ".
                 "WHERE member_big=$memberId";
                 
        $res['comments']=$db->fetchAll($query);

        $query = "DELETE FROM signalations ".
                 "WHERE member_big=$memberId";
                 
        $res['signalations']=$db->fetchAll($query);

        $query = "DELETE FROM ratings ".
                 "WHERE member_big=$memberId";
                 
        $res['ratings']=$db->fetchAll($query);

        $query = "DELETE FROM member_settings ".
                 "WHERE from_big=$memberId OR to_big=$memberId";
                 
        $res['member_settings']=$db->fetchAll($query);
        
        $query = "DELETE FROM photos ".
                 "WHERE member_big=$memberId";
                 
        $res['photos']=$db->fetchAll($query);

	$query = "DELETE FROM profile_visits ".
                 "WHERE visitor_big=$memberId OR visited_big=$memberId";
                 
        $res['profile_visits']=$db->fetchAll($query);

        $query = "DELETE FROM api_tokens ".
                 "WHERE member_big=$memberId";
                 
        $res['api_tokens']=$db->fetchAll($query);
        
        $query = "DELETE FROM member_perms ".
                 "WHERE member_big=$memberId";
                 
        $res['member_perms']=$db->fetchAll($query);

        $query = "DELETE FROM friends ".
                 "WHERE member1_big=$memberId OR member2_big=$memberId";
                 
        $res['friends']=$db->fetchAll($query);

        $query = "DELETE FROM chat_messages ".
                 "WHERE from_big=$memberId OR to_big=$memberId";
                 
        $res['chat_messages']=$db->fetchAll($query);

        $query = "DELETE FROM bookmarks ".
                 "WHERE member_big=$memberId";
                 
        $res['bookmarks']=$db->fetchAll($query);

        $query = "DELETE FROM checkins ".
                 "WHERE member_big=$memberId";
                 
        $res['checkins']=$db->fetchAll($query);

        $query = "DELETE FROM member_rels ".
                 "WHERE member1_big=$memberId OR member2_big=$memberId";
                 
        $res['member_rels']=$db->fetchAll($query);

        $query = "DELETE FROM gems ".
                 "WHERE member_big=$memberId";
                 
        $res['gems']=$db->fetchAll($query);

        $query = "DELETE FROM wallets ".
                 "WHERE member1_big=$memberId ";
                 
        $res['wallets']=$db->fetchAll($query);
        
        $query = "DELETE FROM members ".
                 "WHERE big=$memberId ";
                 
        $res['members']=$db->fetchAll($query);
        
        return $res;
        
    }   

}