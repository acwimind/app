<?php
class RegistrationCodesController extends AppController {
	public $uses = array (
			'RegistrationCode',
			'Member' 
	);
	public $components = array (
			'Sms' 
	);
	public function api_generateVerificationCode() {
		$pushToken = isset ( $this->api ['push_token'] ) ? $this->api ['push_token'] : null;
		$platformId = isset ( $this->api ['platform_id'] ) ? $this->api ['platform_id'] : null;
		
		// conditions add numero
		// if xx->
		if (isset ( $this->api ['phone'] ) && ! empty ( $this->api ['phone'] )) {
			
			// controlla se è nei codici inviati
			$savedCodes = $this->RegistrationCode->find ( 'all', array (
					'conditions' => array (
							'RegistrationCode.phone =' => $this->api ['phone'] 
					) 
			) );
			
			// controlla se è nei membri
			$savedMember = $this->Member->find ( 'first', array (
					'conditions' => array (
							'Member.phone =' => $this->api ['phone'] 
					) 
			) );
			
			$myText = "";
			// se non membro
			if (count ( $savedMember ) == 0) {
				// se non presente...
				if (count ( $savedCodes ) == 0) {
					$myNum = rand ( 10, 99 ) * 1000 + rand ( 100, 999 );
					$myText = ( string ) $myNum;
					$myText = substr ( $myText, 0, 5 );
					$this->RegistrationCode->set ( array (
							'phone' => $this->api ['phone'],
							'code' => $myText 
					) );
					debug ( $this->RegistrationCode );
					if ($this->RegistrationCode->save ()) {
						$response ['SMS'] = $SMSReturn;
						$response ['test'] = "ok";
					} else {
						$response ['test'] = "non salvato";
					}
				} else {
					// $response ['test']=array('aa');
					// $this->_apiOk ( $response );
					$myText = $savedCodes [0] ['RegistrationCode'] ['code'];
				}
				// send sms!!
				$smsMsg = __ ( 'Ecco il tuo codice di registrazione ad Haamble: ' ) . $myText;
				
				$SMSReturn = $this->Sms->SmsSend ( $this->api ['phone'], $smsMsg );
				// $this->_apiOk ( $response );
				$this->_apiOk ( $myText );
			} else {
				$this->_apiError ( __ ( 'Member already registered' ) );
			}
		} else {
			
			$this->_apiError ( __ ( 'Wrong parameters' ) );
		}
	}
	public function api_checkVerificationCode() {
		if (isset ( $this->api ['phone'] ) && ! empty ( $this->api ['phone'] )) {
			
			$savedCodes = $this->RegistrationCode->find ( 'all', array (
					'conditions' => array (
							'RegistrationCode.phone =' => $this->api ['phone'],
							'RegistrationCode.code =' => $this->api ['code'] 
					) 
			) );
			
			// se presente...
			if (count ( $savedCodes ) == 1) {
				$this->_apiOk ();
			} else {
				$this->_apiError ( __ ( 'User does not exist' ) );
			}
		} else {
			
			$this->_apiError ( __ ( 'Wrong parameters' ) );
		}
	}
	
	public function api_inviteFriendsOLD() {
		App::uses ( 'CakeEmail', 'Network/Email' );
		$this->_checkVars ( array (), array (
				'smscontacts',
				'emailcontacts'
		) );
	
		$member = $this->Member->find ( 'first', array (
				'conditions' => array (
						'Member.big =' => $this->logged ['Member'] ['big']
				),
				'fields' => array (
						'name',
						'surname'
				),
				'recursive' => - 1
		) );
	
		$memberName = $member ['Member'] ['name'] . ' ' . $member ['Member'] ['surname'];
	
		// Per recuperare input in formato json
		// $data=$this->request->input('json_decode', true );
	
		$array_sms = (isset ( $this->api ['smscontacts'] )) ? json_decode ( $this->api ['smscontacts'] ) : null;
		$array_email = (isset ( $this->api ['emailcontacts'] )) ? json_decode ( $this->api ['emailcontacts'] ) : null;
	
		if (count ( $array_email ) > 0) {
				
			foreach ( $array_email as $key => $val ) {
	
				$email = new CakeEmail ( 'test' );
				$email->template ( 'haamble_invite', 'default' )->to ( $val )->subject ( __ ( 'Haamble - Invite' ) )->viewVars ( array (
						'name' => $memberName
				) )->send ();
			}
		}
	
		$SMSReturn = "";
		$smsCount = $this->Member->getSmsCounter ( $this->logged ['Member'] ['big'] ); // sms finora spediti
		$inviti = count ( $array_sms ); // sms da spedire
		 
			
	
		$maxsms = MAXSMSLIMIT - $smsCount; // max numero di sms spedibili
	
		if ($inviti > 0 and $smsCount < MAXSMSLIMIT) {
			// se ci sono sms da spedire e non si è raggiunto il limite
				
			if ($inviti > $maxsms) { // riduce l'array degli inviti da spedire
	
				$array_sms = array_slice ( $array_sms, 0, $maxsms );
			}
				
			$smsMsg = __ ( 'Ciao! Lo sai che %s è su Haamble? Vieni su haamble.com e scarica la applicazione per il tuo smartphone per scoprire i suoi interessi e i posti che frequenta.', $memberName );
				
			// $credito=$this->Sms->skebbyGatewayGetCredit('haamble','haamble2014');
				
			$SMSReturn = $this->Sms->SmsSend ( $array_sms, $smsMsg );
			$this->Member->setSmsCounter ( $this->logged ['Member'] ['big'], $inviti );
		}
	
		if ($smsCount >= MAXSMSLIMIT and $SMSReturn == "")
			$SMSReturn = __ ( 'SMS Limit Exceeded' );
	
		$this->_apiOk ( $SMSReturn );
	}
	
	public function mobileNumberClean($array_sms){
        
        //print_r($array_sms);
        
        $array_sms_clean=array();
        
        foreach ( $array_sms as $key => $val ) {
            
            $newval = str_replace ( '+','', $val );//tolgo il +
                      
            if (is_numeric($newval)){ // è un numero senza caratteri strani quindi vai avanti
                                      // altrimenti butta via il numero sporco
                        
                        $lenght=strlen($newval);//lunghezza $val
            
                        switch ($lenght) {
                            //Analizza solo lunghezze con 10 o 12 cifre il resto viene scartato
                
                
                            case 10 : if ($newval{0} == '3'){// allora è un numero mobile senza 39
                                    
                                                    $newval = "39" . $newval;
                                                    $array_sms_clean[]=$newval;
                                                
                                                }
                                    break;
                       
                            case 12 : if (substr($newval,0,3)=='393'){//numero mobile compreso codice 39
                    
                                                            $array_sms_clean[]=$newval; 
                                                            }             
                
                
                                }
                                 
                    }
                      
        }
            
            return $array_sms_clean;    
        
    }
    
    
	public function api_inviteFriends() {
		App::uses ( 'CakeEmail', 'Network/Email' );
		$this->_checkVars ( array (), array (
				'smscontacts',
				'emailcontacts' 
		) );
		
		$member = $this->Member->find ( 'first', array (
				'conditions' => array (
						'Member.big =' => $this->logged ['Member'] ['big'] 
				),
				'fields' => array (
						'name',
						'surname' 
				),
				'recursive' => - 1 
		) );
		
		$memberName = $member ['Member'] ['name'] . ' ' . $member ['Member'] ['surname'];
		
		// Per recuperare input in formato json
		// $data=$this->request->input('json_decode', true );
		
		$array_sms = (isset ( $this->api ['smscontacts'] )) ? json_decode ( $this->api ['smscontacts'] ) : null;
		$array_email = (isset ( $this->api ['emailcontacts'] )) ? json_decode ( $this->api ['emailcontacts'] ) : null;
		
		if (count ( $array_email ) > 0) {
			
			foreach ( $array_email as $key => $val ) {
				
				$email = new CakeEmail ( 'test' );
				$email->template ( 'haamble_invite', 'default' )->to ( $val )->subject ( __ ( 'Haamble - Invite' ) )->viewVars ( array (
						'name' => $memberName 
				) )->send ();
			}
		}
		
		$SMSReturn = "";
		$smsCount = $this->Member->getSmsCounter ( $this->logged ['Member'] ['big'] ); // sms finora spediti
		$array_sms_checked = $this->mobileNumberClean($array_sms);
        $this->log("array sms ".serialize($array_sms_checked));
        $inviti = count ( $array_sms_checked ); // sms da spedire
		
        $maxsms = MAXSMSLIMIT - $smsCount; // max numero di sms spedibili
		
		if ($inviti > 0 AND $smsCount < MAXSMSLIMIT) {
			// se ci sono sms da spedire e non si è raggiunto il limite
			
			if ($inviti > $maxsms) { // riduce l'array degli inviti da spedire
				
				$array_sms_checked = array_slice ( $array_sms_checked, 0, $maxsms );
			}
			
			$smsMsg = __ ( 'Ciao! Lo sai che %s è su Haamble? Vieni su haamble.com e scarica la applicazione per il tuo smartphone per scoprire i suoi interessi e i posti che frequenta.', $memberName );
			
			// $credito=$this->Sms->skebbyGatewayGetCredit('haamble','haamble2014');
			
			$SMSReturn = $this->Sms->SmsSend ( $array_sms_checked, $smsMsg );
			$this->Member->setSmsCounter ( $this->logged ['Member'] ['big'], $inviti );
		}
		
		if ($smsCount >= MAXSMSLIMIT and $SMSReturn == "")
			$SMSReturn = __ ( 'SMS Limit Exceeded' );
		
		$this->_apiOk ( $SMSReturn );
	}
}