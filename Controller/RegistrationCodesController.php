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
				
				$SMSReturn = $this->Sms->SmsSend ( $this->api ['phone'], $myText );
				 //$this->_apiOk ( $response );
				$this->_apiOk ( $myText );
			} else {
				$this->_apiError ( 'Member already registered' );
			}
		} else {
			
			$this->_apiError ( 'Wrong parameters' );
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
				$this->_apiError ( 'User does not exist' );
			}
		} else {
			
			$this->_apiError ( 'Wrong parameters' );
		}
	}
}