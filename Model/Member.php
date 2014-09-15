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
		'ExtraInfos'
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
			'unique' => array(
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

		
	public function getAffinityMembers($memberBig)
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
    
    $ageAttempts=array(10,15,20);  //increasing values
    $distanceAttempts=array(10,50,100);   //increasing values
    
    //for loops first search the nearest members and then search for the most distant members     
    for ($i=0; $i<count($distanceAttempts); $i++){ //distance array iterator index
                                                        
        
        for ($j=0; $j<count($ageAttempts); $j++){ //age array iterator index
    
                         
        $sql2 = 'SELECT members.big,members.name,members.surname,members.updated,members.photo_updated,members.sex,
	             members.last_lonlat AS "coordinates",((members.last_lonlat <@> ? )::numeric(10,1) * 1.6) AS "distance"
             
                FROM public.members
             
                WHERE	 (( '.$Iyear.' - DATE_PART(\'year\', birth_date)  ) BETWEEN -'.$ageAttempts[$j].' AND '.$ageAttempts[$j].' ) AND sex != \''.$Imember['sex'].'\'
			        AND	(members.big <> '.$memberBig	.')
	                AND	( members.last_lonlat <@> ? )::numeric(10,1) < (' . NEARBY_RADIUS . '*'.$distanceAttempts[$i].')
					ORDER BY  ( members.last_lonlat <@> ?)::numeric(10,1) 
                    ASC LIMIT ' . API_MAP_LIMIT;
	
         
        $result = $db->fetchAll ( $sql2, array ($coords,  $coords,  $coords  ));
        
        if (count($result)>=MIN_AFFINITY_MEMBERS) {
                                break 2;
                                }
        }
            
    } 
     
     if (!count($result)>=MIN_AFFINITY_MEMBERS){// extreme attempt : max age diff, max distance and any members sex
         
         $sql2 = 'SELECT members.big,members.name,members.surname,members.updated,members.photo_updated,members.sex,
                  members.last_lonlat AS "coordinates",((members.last_lonlat <@> ? )::numeric(10,1) * 1.6) AS "distance"
             
                  FROM public.members
             
                  WHERE     (( '.$Iyear.' - DATE_PART(\'year\', birth_date)  ) BETWEEN -'.$ageAttempts[count($ageAttempts)-1].' AND '.$ageAttempts[count($ageAttempts)-1].' )
                    AND    (members.big <> '.$memberBig    .')
                    AND    ( members.last_lonlat <@> ? )::numeric(10,1) < (' . NEARBY_RADIUS . '*'.$distanceAttempts[count($distanceAttempts)-1].')
                    ORDER BY  ( members.last_lonlat <@> ?)::numeric(10,1) 
                    ASC LIMIT ' . API_MAP_LIMIT; 
         
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
				where members.big<>'.$memberBig.'    
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
					$salt = $this->find('first', array('fields' => array('salt'), 'conditions' => array('big' => $this->data['Member']['big'])));
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
           

}