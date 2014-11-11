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
		
		
		$pushToken = isset($this->api['push_token']) ? $this->api['push_token'] : null ;
		$platformId = isset($this->api['platform_id']) ? $this->api['platform_id'] : null ;
		
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
					$myText = substr($myText ,0,5);
					$this->RegistrationCode->set ( array (
							'phone' => $this->api ['phone'],
							'code' => $myText 
					) );
					debug($this->RegistrationCode);
					if ($this->RegistrationCode->save ()) {
						$response ['SMS'] = $SMSReturn;
						$response ['test'] = "ok";
					} else {
						$response ['test'] = "non salvato";
					}
				}
				else 
				{
				//	$response ['test']=array('aa');
					//$this->_apiOk ( $response );
				$myText = $savedCodes[0]['RegistrationCode']['code'];
				}
				// send sms!!
				$smsMsg=__('Ecco il tuo codice di registrazione ad Haamble: ').$myText;
                
				$SMSReturn = $this->Sms->SmsSend ( $this->api ['phone'], $smsMsg );
				 //$this->_apiOk ( $response );
				$this->_apiOk ( $myText );
			} else {
				$this->_apiError ( __('Member already registered') );
			}
		} else {
			
			$this->_apiError ( __('Wrong parameters') );
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
				$this->_apiError ( __('User does not exist') );
			}
		} else {
			
			$this->_apiError ( __('Wrong parameters') );
		}
	}
    
    
    public function api_inviteFriends(){
        
         App::uses ( 'CakeEmail', 'Network/Email' );
         $this->_checkVars(array(),array('smscontacts','emailcontacts'));
                 
         $member = $this->Member->find ( 'first', array (
                    'conditions' => array (
                            'Member.big =' => $this->logged['Member']['big'] 
                    ),
                    'fields' => array('name','surname'),
                    'recursive' => -1  
            ) );
         
         
         $memberName=$member['Member']['name'].' '.$member['Member']['surname'];
         
         //Per recuperare input in formato json
         //$data=$this->request->input('json_decode', true );  
                          
         $array_sms=(isset($this->api['smscontacts'])) ? json_decode($this->api['smscontacts']) : null;
         $array_email=(isset($this->api['emailcontacts'])) ? json_decode($this->api['emailcontacts']) : null;
                  
         if (count($array_email)>0){
                         
             foreach ($array_email as $key=>$val){
                           
                $email = new CakeEmail ( 'test' );
                $email->template ( 'haamble_invite', 'default' )->to ( $val )->subject ( __ ( 'Haamble - Invite' ) )->viewVars ( array('name' => $memberName) )->send ();
         }
        }
        
        
         if (count($array_sms)>0){
             
             
             $smsMsg=__('Hello, %s installed Haamble App.',$memberName);
             
             $SMSReturn = $this->Sms->SmsSend ( $array_sms, $smsMsg );
                                    
         }
         
         $this->_apiOk ('1');
         
                
    }
}