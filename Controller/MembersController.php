<?php
App::uses ( 'Logger', 'Lib' );
class MembersController extends AppController {
	public $uses = array (
			'Member',
			'PushToken',
			'Operator',
			'Checkin',
			'Contact',
			'ProfileVisit',
			'Friend',
			'PrivacySetting',
			'Place',
			'Category',
			'ExtraInfos',
			'Wallet',
            'MemberSetting' ,
			'ExtraInfos'
	);
    
    var $components = array('MailchimpApi','Mandrill');
    
	public function beforeFilter() {
		parent::beforeFilter ();
		
		if (! $this->isApi) {
			$this->Auth->allow ( 'login', 'register', 'login_fb', 'forgot_password', 'change_password' ); // allow these without authentication
		}
	}
	public function admin_login() {
		$this->login ();
		$this->render ( 'login' );
	}
	public function login() {
		$this->Session->write ( 'Config.language', 'ita' );
		$test = "0";
		if ($this->request->is ( 'post' )) {
			
			unbindAllBut ( $this->Member );
			if (is_numeric ( $this->request->data ['Member'] ['email_or_phone'] )) {
				// asume login with phone number
				$test = "1";
				$data = $this->Member->findByPhone ( $this->request->data ['Member'] ['email_or_phone'] );
				$this->request->data ['Member'] ['email'] = $data ['Member'] ['email'];
			} else {
				$test = "2";
				// assume email
				$this->request->data ['Member'] ['email'] = $this->request->data ['Member'] ['email_or_phone'];
			}
			if ($this->Auth->login ()) {
				return $this->redirect ( $this->Auth->redirect () );
			} else {
				$this->Session->setFlash ( __ ( 'E-mail o password errata' . $test ), 'flash/error' );
			}
		} elseif ($this->logged) {
			
			$this->redirect ( $this->Auth->redirect () );
		}
	}
	public function unlink_fb() {
		if (isset ( $this->logged ['Member'] ['fb_id'] ) && ! empty ( $this->logged ['Member'] ['fb_id'] )) {
			
			$this->Member->save ( array (
					'Member' => array (
							'big' => $this->logged ['Member'] ['big'],
							'fb_id' => null 
					) 
			) );
			$this->Session->setFlash ( __ ( 'Il tuo account Facebook � disconnesso da Haamble' ), 'flash/success' );
		} else {
			$this->Session->setFlash ( __ ( 'Il tuo account Haamble non � connesso con un account Facebook' ), 'flash/error' );
		}
		$this->redirect ( array (
				'action' => 'edit' 
		) );
	}
	public function login_fb() {
		if (isset ( $this->params->query ) && isset ( $this->params->query ['error'] )) { // response from FB - error
			
			if ($this->params->query ['error_reason'] == 'user_denied') {
				
				if ($this->logged) {
					$member = array (
							'Member' => array (
									'big' => $this->logged ['Member'] ['big'],
									'fb_id' => null 
							) 
					);
					$this->Member->save ( $member );
					$this->Session->setFlash ( __ ( 'Il tuo account � stato scollegato. Hai vietato l\'accesso al tuo account' ), 'flash/error' );
				} else {
					$this->Session->setFlash ( __ ( 'Conferma l\'accesso al tuo account Facebook' ), 'flash/error' );
				}
			} elseif (isset ( $this->params->query ['error'] )) {
				$this->Session->setFlash ( __ ( 'Non sei abilitato alla connessione Facebook. Facebook risponde: %s', $this->params->query ['error_description'] ), 'flash/error' );
			}
		} elseif (isset ( $this->params->query ) && isset ( $this->params->query ['error_message'] )) { // response from FB - error
			
			$this->Session->setFlash ( __ ( 'Non sei abilitato alla connessione Facebook. Facebook risponde: %s', $this->params->query ['error_message'] ), 'flash/error' );
		} elseif (isset ( $this->params->query ) && isset ( $this->params->query ['code'] )) { // response from FB - success
			
			$this->Fb->exchangeToken ( $this->params->query ['code'] ); // get token
			$this->Session->write ( 'fb_token', $this->Fb->token );
			
			$fb_user = $this->Fb->user (); // FB user
			
			if ($this->logged) { // alreeady logged in - connect to facebook account
				return $this->_fb_login_connect ( $fb_user );
			} else { // not logged in - login or sign up
				return $this->_fb_login_guest ( $fb_user );
			}
		} else { // redirect to FB login page
			
			return $this->Fb->login ();
		}
		
		return $this->redirect ( '/' );
	}
	
	

	public function api_messa() {

		
		set_time_limit(0);  //evita timeout con corrispondente file internal error di cake
          
        /*$data = $this->Member->find ( 'all', array ( // find user in our DB
                'recursive' => - 1
        ) );*/
		
        $data = $this->Member->find ( 'all', array ( // find user in our DB
						'recursive' => - 1
		) ); 
		$cont=0;
		
        foreach ( $data as $key => $mem ) {
			
            //45920420
	//		$this->Wallet->sendChatNotification('44548401',$mem ['Member'] ['big']);
			$this->Wallet->sendChatNotification($mem ['Member'] ['big'],'Ciao, abbiamo pubblicato un aggiornamento disponibile sull\' Apple Store che richiede di essere installato per evitare eventuali messaggi di errore, appena aggiornata l\'App, disconnettiti (impostazioni->logout) e rientra (login) per sincronizzare nuovamente il tuo account. Grazie e buon divertimento!');
		    
	        $cont+=1;
		}
		/*
		 * 
		 * 				'conditions' => array (
						
						'big' => 45517058
						),
		foreach ( $MySugAffinityAll as $key => &$val ) {
				
			// check if any friendship exists yet
			// debug($val[0] ['big']);
			$this->Wallet->sendChatNotification($val [0] ['big'],'Ciao, abbiamo pubblicato un aggiornamento disponibile sull\' Apple Store che richiede di essere installato per evitare eventuali messaggi di errore, appena aggiornata l\'App, disconnettiti (impostazioni->logout) e rientra (login) per sincronizzare nuovamente il tuo account. Grazie e buon divertimento!');
			
		}
		*/
		
		$this->_apiOk("Inviati $cont avvisi");
		
	}
   
	
	private function _fb_login_connect($fb_user = array()) {
		$user = $this->Member->find ( 'first', array ( // find user in our DB
				'conditions' => array (
						'and' => array (
								'Member.fb_id' => $fb_user ['id'],
								'Member.email' => $fb_user ['email'] 
						) 
				),
				'recursive' => - 1 
		) );
		
		if ($user == false) { // not in DB, connect
			
			$member = array (
					'Member' => array (
							'big' => $this->logged ['Member'] ['big'],
							'fb_id' => $fb_user ['id'] 
					) 
			);
			
			if (empty ( $this->logged ['Member'] ['name'] )) {
				$member ['Member'] ['name'] = $fb_user ['first_name'];
			}
			
			if (empty ( $this->logged ['Member'] ['last_name'] )) {
				$member ['Member'] ['surname'] = $fb_user ['last_name'];
			}
			
			if (empty ( $this->logged ['Member'] ['birth_date'] )) {
				$tmp_birthday = explode ( '/', $fb_user ['birthday'] );
				if (count ( $tmp_birthday ) == 3) {
					$fb_birth_date = $tmp_birthday [2] . '-' . $tmp_birthday [0] . '-' . $tmp_birthday [1];
					$member ['Member'] ['birth_date'] = $fb_birth_date;
				}
			}
			
			if (empty ( $this->logged ['Member'] ['address_town'] )) {
				$tmp_location = explode ( ',', $fb_user ['location'] ['name'] );
				$member ['Member'] ['address_town'] = trim ( $tmp_location [0] );
			}
			
			if (empty ( $this->logged ['Member'] ['photo_updated'] )) {
				$this->_use_fb_picture ( $fb_user, $this->logged );
			}
			
			$this->Member->save ( $member );
			$this->Session->setFlash ( __ ( 'Il tuo account � stato collegato con l\'account Facebook' ), 'flash/success' );
		} elseif ($user ['Member'] ['big'] == $this->logged ['Member'] ['big']) { // already paired with this account
			
			$this->Session->setFlash ( __ ( 'Il tuo account � gi� collegato con l\'account Facebook' ), 'flash/success' );
		} elseif ($user ['Member'] ['fb_id'] == $fb_user ['id']) { // FB account already paired with another account
			
			$this->Session->setFlash ( __ ( 'Il tuo account Facebook � gi� collegato con un account Haamble' ), 'flash/error' );
		} else {
			
			$this->Session->setFlash ( __ ( 'Errore interno' ), 'flash/error' );
		}
		
		return $this->redirect ( array (
				'action' => 'edit' 
		) );
	}
	private function _fb_login_guest($fb_user = array()) {
		$user = $this->Member->find ( 'first', array ( // find user in our DB
				'conditions' => array (
						'or' => array (
								'Member.fb_id' => $fb_user ['id'],
								'Member.email' => $fb_user ['email'] 
						) 
				),
				'recursive' => - 1 
		) );
		
		if ($user == false) { // user not in DB, sign up
			
			$tmp_birthday = explode ( '/', $fb_user ['birthday'] );
			if (count ( $tmp_birthday ) == 3) {
				$fb_birth_date = $tmp_birthday [2] . '-' . $tmp_birthday [0] . '-' . $tmp_birthday [1];
			} else {
				$fb_birth_date = null;
			}
			
			$tmp_location = explode ( ',', $fb_user ['location'] ['name'] );
			$fb_town = trim ( $tmp_location [0] );
			
			$member = array (
					'Member' => array (
							'email' => $fb_user ['email'],
							'name' => $fb_user ['first_name'],
							'surname' => $fb_user ['last_name'],
							'fb_id' => $fb_user ['id'],
							'birth_date' => $fb_birth_date,
							'address_town' => $fb_town,
							'status' => 1,
							'type' => MEMBER_MEMBER 
					) 
			);
			$this->Member->save ( $member );
			$member ['Member'] ['big'] = $this->Member->id;
			$this->Auth->login ( $member ['Member'] );
			
			$this->_use_fb_picture ( $fb_user, $member );
			
			$this->Session->setFlash ( __ ( 'Grazie per la registrazione. Compila il tuo profilo' ), 'flash/success' );
			return $this->redirect ( array (
					'controller' => 'members',
					'action' => 'edit' 
			) );
		} elseif ($user ['Member'] ['fb_id'] == $fb_user ['id']) { // valid, paired user
			
			$this->Auth->login ( $user ['Member'] );
			return $this->redirect ( $this->Auth->redirect () );
		} elseif ($user ['Member'] ['email'] == $fb_user ['email']) { // email correct, however account not paired
		                                                              
			// $this->Auth->login($user); //we should not login them, possible security breach
		                                                              // (i can change my email on FB to any address and than login into our app)
			$this->Session->setFlash ( __ ( 'L\' indirizzo email � gi� in uso, ma non � associato con un account Facebook. Se sei gi� registrato effettua il login con email e password. Se hai perso la password puoi resettarla' ), 'flash/error' );
		} else {
			
			$this->Session->setFlash ( __ ( 'Errore interno' ), 'flash/error' );
		}
		return $this->redirect ( array (
				'action' => 'login' 
		) );
	}
	private function _use_fb_picture($fb_user, $member) {
		$tmp_name = '/tmp/haamble_fb_' . $fb_user ['id'] . '_' . uniqid ();
		$fb_photo = 'http://graph.facebook.com/' . $fb_user ['id'] . '/picture?type=large';
		
		$img = file_get_contents ( $fb_photo );
		file_put_contents ( $tmp_name, $img );
		$photo = array (
				'name' => 'fb.jpg',
				'type' => 'image/jpg',
				'size' => filesize ( $tmp_name ),
				'tmp_name' => $tmp_name,
				'error' => 0 
		);
		
		// profile picture upload
		try {
			if ($this->_upload ( $photo, $member ['Member'] ['big'], true )) {
				$this->Member->save ( array (
						'Member' => array (
								'photo_updated' => DboSource::expression ( 'now()' ) 
						) 
				) );
			}
		} catch ( UploadException $e ) {
			debug ( $e );
		}
	}
	public function register() {
		$this->set ( 'here', $this->here );
		
		if ($this->request->is ( 'post' )) {
			
			$this->request->data ['Member'] ['type'] = MEMBER_MEMBER;
			$this->request->data ['Member'] ['status'] = ACTIVE;
			
			$this->Member->create ();
			$this->Member->set ( $this->request->data );
			
			$this->Member->validate ['agreement'] = array (
					'rule' => array (
							'equalTo',
							'Y' 
					),
					'required' => true,
					'message' => 'Please check this box if you want to proceed' 
			);
			
			// if (true) {
			// 2015
			if ($this->Member->save ()) {
				
				// echo '<pre>'; print_r($this->request->data);
				
				$this->Auth->login ();
				
				$this->Session->setFlash ( __ ( 'Compila il tuo profilo' ), 'flash/info' );
				
				$this->redirect ( array (
						'action' => 'edit',
						'register' 
				) ); // redir to edit profile
			} else {
				
				echo json_encode ( $this->Member->validationErrors );
				exit ();
			}
		}
	}
	public function edit($register = false) {
		
		$this->set ( 'register', $register );
		
		if ($this->request->is ( 'put' )) {
			
			if (empty ( $this->request->data ['Member'] ['password'] )) {
				unset ( $this->request->data ['Member'] ['password'] );
				unset ( $this->request->data ['Member'] ['password2'] );
			}
			
			$this->request->data ['Member'] ['type'] = $this->logged ['Member'] ['type'];
			$this->request->data ['Member'] ['status'] = ACTIVE;
			
			// Date postprocessing
			if (! empty ( $this->request->data ['Member'] ['birth_date'] ) && is_array ( $this->request->data ['Member'] ['birth_date'] ) && array_key_exists ( 'date', $this->request->data ['Member'] ['birth_date'] )) {
				$this->request->data ['Member'] ['birth_date'] = $this->request->data ['Member'] ['birth_date'] ['date'];
			}
			
			$this->Member->create ();
			$this->Member->set ( $this->request->data );
			debug ( $this->request->data );
			if ($this->Member->save ()) {
				
				// profile picture upload
				try {
					if ($this->_upload ( $this->request->data ['Member'] ['photo'], $this->Member->id )) {
						$this->Member->save ( array (
								'Member' => array (
										'photo_updated' => DboSource::expression ( 'now()' ) 
								) 
						) );
					}
				} catch ( UploadException $e ) {
				}
				
				$this->Session->setFlash ( __ ( 'Profilo aggiornato' ), 'flash/success' );
				
				if ($register !== false) {
					$this->redirect ( '/' );
				} else {
					$this->redirect ( array (
							'action' => 'edit' 
					) );
				}
			}
		} else {
			
			$member = $this->Member->findByBig ( $this->logged ['Member'] ['big'] );
			unset ( $member ['Member'] ['password'] );
			
			$this->set ( 'fb_user', false );
			if (isset ( $member ['Member'] ['fb_id'] ) && $member ['Member'] ['fb_id'] != null) {
				$fb_user = false;
				try {
					$fb_user = $this->Fb->user ();
				} catch ( Exception $e ) {
					//
				}
				if (! $fb_user) {
					$this->redirect ( array (
							'action' => 'login_fb' 
					) );
				}
				if ($fb_user ['id'] == $this->logged ['Member'] ['fb_id']) {
					$this->set ( 'fb_user', $fb_user );
				}
			}
			
			$this->request->data = $member;
		}
	}
	public function forgot_password() {
		
		$this->layout='api';
		if ($this->logged) {
			
			$this->Session->setFlash ( __ ( 'Sei gi� loggato. Puoi cambiare la tua password qui.' ), 'flash/success' );
		/*	return $this->redirect ( array (
					'action' => 'edit' 
			) );
			*/
		}
		
		if ($this->request->is ( 'post' )) {
			
			$member = $this->Member->findByEmail ( $this->data ['Member'] ['email'] );
			
			if (! $member) {
				$this->Session->setFlash ( __ ( 'Indirizzo e-mail non valido' ), 'flash/error' );
				return false;
			}
			
			$token = hash ( 'sha256', $member ['Member'] ['created'] . time () . $member ['Member'] ['surname'] . Configure::read ( 'Security.salt' ) );
			
			$this->Member->PasswordResetToken->create ();
			$this->Member->PasswordResetToken->set ( array (
					'member_big' => $member ['Member'] ['big'],
					'token' => $token,
					'expired' => date ( 'c', strtotime ( '+24 hours' ) )  // reset token is valid for 24 hours
						) );
			$this->Member->PasswordResetToken->save ();
			
			{
				App::uses ( 'CakeEmail', 'Network/Email' );
				// $email = new CakeEmail('default');
				$email = new CakeEmail ( 'test' );
				$email->template ( 'password_reset', 'default' )->to ( $member ['Member'] ['email'] )->subject ( __ ( 'Haamble - password reset' ) )->viewVars ( array (
						'name' => $member ['Member'] ['name'] . ' ' . $member ['Member'] ['surname'],
						'member_big' => $member ['Member'] ['big'],
						'token' => $token,
						'ip' => $_SERVER ['REMOTE_ADDR'] 
				) )->send ();
			}
			
	/*		$this->Session->setFlash ( __ ( 'Please check your e-mail for further instruction. (Make sure to check your spam folder as well.)' ), 'flash/success' );
			$this->redirect ( array (
					'action' => 'postreq' 
			) );*/
			$this->layout='api';
			$this->render ( 'postreq' );
		}
	}
	
	public function postreq() {
		$this->layout='api';
		$this->render ( 'postreq' );
	}
	
	public function change_password($member_big = 0, $token = '') {
		$this->layout='api';
		$data = $this->Member->PasswordResetToken->find ( 'first', array (
				'conditions' => array (
						'PasswordResetToken.token !=' => null,
						'PasswordResetToken.token !=' => '',
						'PasswordResetToken.member_big' => $member_big 
				),
				'order' => array (
						'PasswordResetToken.expired' => 'desc' 
				),
				'recursive' => - 1,
				'limit' => 1 
		) );
		
		// invalid token
		if ($data ['PasswordResetToken'] ['token'] != $token) {
			$this->Session->setFlash ( __ ( 'Link reset password non valido. Usa il form per generare un nuovo link.' ), 'flash/error' );
			return $this->redirect ( array (
					'action' => 'forgot_password' 
			) );
		}
		
		// already logged in - redirect to edit profile
		if ($this->logged) {
			
			if ($this->logged ['Member'] ['big'] == $member_big) { // if logged in as the same user that the token belongs to, delete tokens
				$this->Member->PasswordResetToken->deleteAll ( array (
						'PasswordResetToken.member_big' => $member_big 
				) );
			}
			
			$this->Session->setFlash ( __ ( 'Sei gi� loggato. Puoi cambiare la password qui.' ), 'flash/success' );
			return $this->redirect ( array (
					'action' => 'edit' 
			) );
		}
		
		// form submited
		if ($this->request->is ( 'post' )) {
			
			// save password
			$this->Member->create ();
			$this->Member->set ( array (
					'big' => $data ['PasswordResetToken'] ['member_big'],
					'password' => $this->data ['Member'] ['password'],
					'password2' => $this->data ['Member'] ['password2'] 
			) );
			$this->Member->save ();
			
			$this->Member->PasswordResetToken->deleteAll ( array (
					'PasswordResetToken.member_big' => $member_big 
			) ); // delete all tokens (no longer needed, potantial security risk)
			
		/*	$this->Session->setFlash ( __ ( 'Password succesfully changed. Now use your new password to login.' ), 'flash/success' );
			return $this->redirect ( array (
					'action' => 'login' 
			) );
			*/
			$this->layout='api';
			$this->render ( 'post_password' );
			return true;
		}
	}
	public function admin_logout() {
		$this->logout ();
	}
	public function logout() {
		
		// Do a checkout if possible
		$this->Checkin->checkout ( $this->logged ['Member'] ['big'] );
		return $this->redirect ( $this->Auth->logout () );
	}
	public function admin_index() {
		$conditions = array ();
		
		$this->_savedFilter ( array (
				'lang',
				'srchphr',
				'RegisteredFromDate',
				'RegisteredToDate',
				'type' 
		) );
		
		if (isset ( $this->params->query ['lang'] ) && is_numeric ( $this->params->query ['lang'] )) {
			$conditions ['Member.lang'] = $this->params->query ['lang'];
		}
		if (isset ( $this->params->query ['srchphr'] ) && ! empty ( $this->params->query ['srchphr'] )) {
			$conditions ['OR'] = array (
					'Member.name ILIKE' => '%' . $this->params->query ['srchphr'] . '%',
					'Member.middle_name ILIKE' => '%' . $this->params->query ['srchphr'] . '%',
					'Member.surname ILIKE' => '%' . $this->params->query ['srchphr'] . '%',
					'Member.email ILIKE' => '%' . $this->params->query ['srchphr'] . '%' 
			);
		}
		if (isset ( $this->params->query ['RegisteredFromDate'] ) && ! empty ( $this->params->query ['RegisteredFromDate'] )) {
			$conditions ['Member.created >='] = $this->params->query ['RegisteredFromDate'] . ' ' . $this->params->query ['RegisteredFromTime'];
		}
		if (isset ( $this->params->query ['RegisteredToDate'] ) && ! empty ( $this->params->query ['RegisteredToDate'] )) {
			$conditions ['Member.created <='] = $this->params->query ['RegisteredToDate'] . ' ' . $this->params->query ['RegisteredToTime'];
		}
		if (isset ( $this->params->query ['type'] ) && ! empty ( $this->params->query ['type'] )) {
			$conditions ['Member.type'] = $this->params->query ['type'];
		}
		
		$this->request->data ['Member'] = $this->params->query;
		
		$this->paginate ['order'] = array (
				'Member.email' => 'asc' 
		);
		$data = $this->paginate ( 'Member', $conditions );
		$this->set ( 'data', $data );
	}
	public function admin_view($big) {
		$this->public_profile ( $big );
		$this->render ( 'public_profile' );
	}
	public function admin_add() {
		$this->admin_edit ();
		$this->render ( 'admin_edit' );
	}
	public function admin_edit($big = 0) {
		if ($this->request->is ( 'post' ) || $this->request->is ( 'put' )) {
			
			// Disallow admin to degrade/ delete his account
			if ($this->request->data ['Member'] ['big'] == $this->Auth->user ( 'big' )) {
				unset ( $this->request->data ['Member'] ['status'] );
				unset ( $this->request->data ['MemberPerm'] ['login'] );
			}
			
			if (isset ( $this->request->data ['Operator'] )) {
				if ($this->request->data ['Member'] ['type'] == MEMBER_OPERATOR)
					$operatorTemp = $this->request->data ['Operator'];
				unset ( $this->request->data ['Operator'] );
			}
			
			if ($this->Member->saveAll ( $this->request->data, array (
					'validate' => 'first' 
			) )) {
				
				// profile picture upload
				try {
					if ($this->_upload ( $this->request->data ['Member'] ['photo'], $this->Member->id )) {
						$this->Member->save ( array (
								'Member' => array (
										'photo_updated' => DboSource::expression ( 'now()' ) 
								) 
						) );
					}
				} catch ( UploadException $e ) {
				}
				
				if ($this->request->data ['Member'] ['type'] == MEMBER_OPERATOR) {
					$this->Operator->create ();
					$defs = array (
							'member_big' => $this->Member->id,
							'payment_plan_id' => 1,
							'fiscal_code' => 1,
							'vat_id' => 1,
							'status' => 1 
					);
					$operator = array_merge ( $operatorTemp, $defs );
					$this->Operator->save ( $operator );
				} else {
					$this->Operator->delete ( $this->Member->id );
				}
				
				$this->Session->setFlash ( __ ( 'Utente salvato' ), 'flash/success' );
				return $this->redirect ( array (
						'action' => 'index' 
				) );
			} else {
				
				$this->Session->setFlash ( __ ( 'Errore durante il salvataggio dell\'utente' ), 'flash/error' );
			}
		} elseif ($big > 0) {
			$params = array (
					'conditions' => array (
							'Member.big' => $big 
					),
					'recursive' => 0 
			);
			$this->request->data = $this->Member->find ( 'first', $params );
			unset ( $this->request->data ['Member'] ['password'] );
		}
	}
	private function _upload($photo, $id, $direct = false) {
		if ($direct) {
			/*
			 * if (!in_array($photo['type'], array('image/jpg', 'image/jpeg'))) { throw new UploadException(__('Only JPG images allowed')); }
			 */
			
			$extension = pathinfo ( $photo ['name'], PATHINFO_EXTENSION );
			
			// if ($extension == 'jpeg') {
			// $extension = 'jpg';
			// }
			// $extension = mb_substr($extension, 0, 3);
			
			// Remove old picture
			$exts = array (
					'jpg',
					'jpeg',
					'png' 
			);
			foreach ( $exts as $ext ) {
				$path = MEMBERS_UPLOAD_PATH . $id . '.' . $ext;
				if (is_file ( $path )) {
					unlink ( $path );
					break;
				}
			}
			
			return $this->Upload->directUpload ( $photo, 			// data from form (uploaded file)
			MEMBERS_UPLOAD_PATH . $id . '.' . $extension ); // . '.jpg' //path + filename
		} else {
			
			return $this->Upload->upload ( $photo, 			// data from form (temporary filenames, token)
			MEMBERS_UPLOAD_PATH, 			// path
			$id ); // filename
		}
	}
	public function admin_delete($big) {
		$this->Member->save ( array (
				'big' => $big,
				'status' => DELETED 
		) );
		
		/*
		 * TODO: delete all member items? - chat messages - member rels - member settings - signalations - member perms - password reset tokens - api tokens - push tokens - operators - photos - ratings - checkins
		 */
		
		$this->Session->setFlash ( __ ( 'Utente cancellato' ), 'flash/success' );
		return $this->redirect ( array (
				'action' => 'index' 
		) );
	}
	public function admin_complete_operators() {
		$filter = $this->request->query ['q'];
		
		$operators_raw = $this->Member->find ( 'list', array (
				'conditions' => array (
						'Member.type' => MEMBER_OPERATOR,
						'Member.email LIKE' => $filter . '%' 
				),
				'fields' => array (
						'Member.big',
						'Member.email' 
				),
				'recursive' => - 1 
		) );
		
		$operators = array ();
		foreach ( $operators_raw as $item ) {
			$operators [] = $item;
		}
		
		echo json_encode ( $operators );
		
		exit ();
	}
	public function unsubscribe() {
		if (empty ( $this->logged ['Member'] )) {
			return;
		}
		
		$this->Member->save ( array (
				'big' => $this->logged ['Member'] ['big'],
				'status' => DELETED 
		) );
		
		/*
		 * TODO: delete all member items? Not yet. - chat messages - member rels - member settings - signalations - member perms - password reset tokens - api tokens - push tokens - operators - photos - ratings - checkins
		 */
		
		$this->Session->setFlash ( __ ( 'Deregistrazione effettuata' ), 'flash/success' );
		return $this->redirect ( array (
				'action' => 'index' 
		) );
	}
	public function checkAppUpdate($platformId, $usedVersion) {
		/*
		 * $platformId = 1 Android; 2 iOS; 3 WindowsPhone $usedVersion = versione usata dall'utente x.y (1.0,1.4,1.5,....2.1,2.4,...) return true se la versione che si sta usando � vecchia return false se la versione che si sta usando � l'ultima
		 */
		$status = false;
		
		switch ($platformId) {
			
			case 1 : // Android
				if (strcmp((string)$usedVersion, (string)ANDROID_APP_VERSION)>=0) {
				//if ($usedVersion>=ANDROID_APP_VERSION) {
				
					$status = true;
				}
				
				break;
			case 2 : // iOS
				if (strcmp((string)$usedVersion, (string)IOS_APP_VERSION)>=0) {
				//	if ($usedVersion>=IOS_APP_VERSION) {
					$status = true;
				}
				
				break;
			
			case 3 : // Windows Phone
				//if ($usedVersion>=WPHONE_APP_VERSION) {
			    if (strcmp((string)$usedVersion, (string)WPHONE_APP_VERSION)>=0) {
					$status = true;
				}
		}
		
		return $status;
	}
	
	/**
	 * authenticate member in API
	 */
	public function api_login() {
		CakeLog::info ( 'Login called' );
		
		$member = $this->_apiLogin ();
		
        if (isset($member['Member']['last_mobile_activity']) AND $member['Member']['last_mobile_activity']!=null){
        //codice per i crediti e rank
        $lastLogin=date("d",strtotime($member['Member']['last_mobile_activity']));
        $nowLogin=date("d",time());
        
        if ($lastLogin!=$nowLogin){
                      //crediti e rank per il primo login giornaliero  
                      $this->Wallet->addAmount($member['Member']['big'], '5', 'Primo login del giorno' );
                      $this->Member->rank($member['Member']['big'],5);   
                
                }
        }
		if (isset ( $this->api ['version'] ) and $this->api ['version'] != null) {
			
			if (!$this->checkAppUpdate ( $this->api ['platform_id'], $this->api ['version'] )) {
				$this->_apiEr ( __( 'La tua versione di Haamble non � aggiornata all\'ultima versione. Per favore aggiorna l\'App' ), true,null,null,'010');
			}
		}
		
		CakeLog::info ( 'Result: Api token = ' . $member ['ApiToken'] ['token'] . ' Member big = ' . $member ['Member'] ['big'] );
		
		// Save push token if present
		$pushToken = isset ( $this->api ['push_token'] ) ? $this->api ['push_token'] : null;
		$platformId = isset ( $this->api ['platform_id'] ) ? $this->api ['platform_id'] : null;
		if (! empty ( $pushToken ) && ! empty ( $platformId )) {
			$res = $this->PushToken->checkIfUniqueAndSave ( $pushToken, $member ['Member'] ['big'], $platformId );
			if ($res == false) {
				CakeLog::error ( 'Push token not saved for member_big ' . $member ['Member'] ['big'] . '. PT: ' . $pushToken . ' Platform: ' . $platformId );
			}
		}
		
		$member ['Member']['isvip']=($member ['Member']['type'] == MEMBER_VIP);
		
		$this->_apiOk ( array (
				'Member' => $member ['Member'],
				'api_token' => $member ['ApiToken'] ['token'],
				'expired' => $member ['ApiToken'] ['expired'] 
		), 'login_data' );
	}
	
	/*
	 * log out current member
	 */
	function api_logout() {
		
		/*
		 * $this->Member->ApiToken->updateAll( array( 'expired'		=> date('Y-m-d H:i:s', strtotime('-1 second')), ),array( 'member_big'	=> $this->logged['Member']['big'], 'token'			=> $this->api['api_token'], ) );
		 */
		$where = array (
				'member_big' => $this->logged ['Member'] ['big'] 
		);
		
		if (isset ( $this->api ['api_token'] )) {
			$where ['token'] = $this->api ['api_token'];
		}
		
		$this->Member->ApiToken->deleteAll ( $where, false );
		
		// Delete all push tokens
		$memBig = $this->logged ['Member'] ['big'];
		$this->Member->PushToken->deleteAllPushTokens ( $memBig );
		
		CakeSession::destroy ();
		
		// Do a checkout if possible
		$this->Checkin->checkout ( $this->logged ['Member'] ['big'] );
		
		$this->_apiOk ( array (
				'status' => 1 
		), 'data' );
	}
	
    
     public function mandrill_BenvenutoReminder($email,$user_name){
                       
       
       $message = array('message'=>array(
                                            'subject' => "$user_name Benvenuto su Haamble",
                                            'from_email' => 'haamble@haamble.com',
                                            'to' => array(array('email' => "$email", 
                                                                'name' => "$user_name"))));
                        
                        

       $template_name = array('template_name'=>'Benvenuto_reminder');

       
       $template_content = array('template_content'=>array(array(
                                                                    'name' => 'main',
                                                                    'content' => ''
                                                                    )
                                                          )      
                                );
                                
       $params=array_merge($template_name,$template_content,$message);                                
              
       //risposta non usata per verificare failure
       $this->Mandrill->messagesSend_template($params);
           
       
   } 
          
    
	/**
	 * sign up new member
	 */
	public function api_register() {
		$pushToken = isset ( $this->api ['push_token'] ) ? $this->api ['push_token'] : null;
		$platformId = isset ( $this->api ['platform_id'] ) ? $this->api ['platform_id'] : null;
		
		// save new member
		$member = $this->_save ();
        // INSERISCE I PRIVACY SETTINGS tutti a 1!!
        $this->PrivacySetting->CreateSettings ( $member ['big'] );
        // INSERISCE I EXTRA INFOs
        $this->ExtraInfos->CreateInfos ( $member ['big'],$member ['sex'] );
        
        //Registra l'utente in list sull'account mailchimp di haamble
       $this->MailchimpApi->addMembers(MAILCHIMP_HAAMBLE_LIST_ID,$this->api['email'],$this->api['name'],$this->api['surname']);
		
        $mandrillResult=$this->mandrill_BenvenutoReminder($this->api['email'],$this->api['name']);
                        
		$response = $this->_apiLogin ();
		
		$response ['user_msg'] = __( 'Registration succesfull' );
		
		// Save push token if present
		if (! empty ( $pushToken ) && ! empty ( $platformId )) {
			$res = $this->PushToken->checkIfUniqueAndSave ( $pushToken, $member ['big'], $platformId );
			if ($res == false) {
				CakeLog::error ( 'Push token not saved for member_big ' . $member ['big'] . '. PT: ' . $pushToken . ' Platform: ' . $platformId );
			}
		}
		
		// photo upload
		try {
			$this->_api_photo_upload ( $member ['big'] );
		} catch ( UploadException $e ) {
			$response ['user_msg'] .= $e->getMessage ();
		}
		
		
		// give some credit
        
        $currentTimestamp=time();
            if (($currentTimestamp>=1418338800 AND $currentTimestamp<=1418425200) OR ($currentTimestamp>=1419030000 AND $currentTimestamp<=1419152400))
            
            $this->Wallet->addAmount ( $member['big'], '500', 'Welcome to Haamble extra' );  
                else
                 $this->Wallet->addAmount ( $member['big'], '50', 'Welcome to Haamble' );  
        
		//$this->Wallet->addAmount ( $member ['big'], '50', 'Welcome to Haamble' );
		$this->Wallet->sendChatNotification($member ['big'], 'Benvenuto su Haamble');
		$this->_apiOk ( $response );
	}
	
	/**
	 * update existing member profile
	 */
	public function api_edit() {
	//	$this->log("------------EDIT--------------------");
		// update existing member
		$member = $this->_save($this->logged['Member']['big']);
		 
    //     $this->log("memberBig: ".$this->logged['Member']['big']);
         
        if ($member) $this->log("member->_save: OK"); else $this->log("member->_save: Fallito"); 
  //      $this->log("member: ".serialize($member));
        
		if (! $member) {
			$this->_apiEr ( __( 'Si � verificato un errore durante il salvataggio del profilo' ), true );
		}
		
		$params2 = array(
				'conditions' => array(
						'Member.big' => $member['big']
				),
				'recursive' => 0,
		);
		
		
		$memberResp = $this->Member->find ( 'first', $params2 );
		
		unset ( $memberResp ['Member'] ['password'] );
		unset ( $memberResp ['Member'] ['salt'] );
		
		
		
		if ($memberResp ['Member'] ['photo_updated'] > 0) {
			$memberResp ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $memberResp ['Member'] ['big'], $memberResp ['Member'] ['photo_updated'] );
		} else {
			$sexpic=2;
			if($memberResp ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
		
			$memberResp ['Member']['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
				
		}
		
		
		$response = array (
				'user_msg' => __('Profile update succesfull'),
				'member' => $memberResp
		);
		
		try {
			$this->_api_photo_upload($member['big']);
			$amount = 30;
			$reason = "Uploaded picture";
            if ($this->Wallet->getCreditByReason($this->logged['Member']['big'],'Uploaded picture')==0){
                            $this->Wallet->addAmount($member['big'], $amount, $reason );     
                            }
			
		} catch ( UploadException $e ) {
			$response ['user_msg'] .= $e->getMessage ();
		}
//		$this->log("------------FINE EDIT--------------------");
		$this->_apiOk ( $response );
	}
	
	// TODO: move this function to model, makes more sense there? or not, if we need to call component
	private function _api_photo_upload() {
		$msg = ', however there was an error uploading your profile picture';
		
		if (! isset ( $this->api ['photo'] )) {
			return false;
		}
		
		if (! isset ( $_FILES [$this->api ['photo']] )) {
			return false;
		}
		
		try {
			$uploaded = $this->_upload ( $_FILES [$this->api ['photo']], $this->Member->id, true );
		} catch ( UploadException $e ) {
			throw new UploadException ( __ ( $msg ) . ': ' . $e->getMessage () );
		}
		
		if ($uploaded) {
			$this->Member->save ( array (
					'Member' => array (
							'photo_updated' => DboSource::expression ( 'now()' ) 
					) 
			) );
		} else {
			throw new UploadException ( __ ( $msg ) );
		}
		
		return true;
	}
	
	// TODO: move this function to model, makes more sense there?
	private function _save($big = 0) {
		if ($big == 0) { // new member
			
			$required_fields = array (
					'email' => 'email',
					'password' => 'password',
					'name' => 'name',
					'surname' => 'surname' 
			);
			$optional_fields = array ();
		} else { // edit member
			
			$required_fields = array ();
			$optional_fields = array (
					'password' => 'password',
					'name' => 'name',
					'surname' => 'surname',
                    'description' => 'description' 
			);
		}
		
		// debug($this->request['data']['address_town']);
		if (isset ( $this->request ['data'] ['address_street'] ) or isset ( $this->request ['data'] ['address_town'] ) or isset ( $this->request ['data'] ['address_country'] ) or isset ( $this->request ['data'] ['lang'] ) or isset ( $this->request ['data'] ['address_zip'] )) {
			$optional_fields += array (
					'photo' => 'photo',
					'middle_name' => 'middle_name',
					'lang' => 'lang',
					'birth_date' => 'birth_date',
					'sex' => 'sex',
					//'phone' => 'phone',
					'birth_place' => 'birth_place',
					'address_street' => 'address_street',
					'address_street_no' => 'address_street_no',
					'address_town' => 'address_town',
					'address_province' => 'address_province',
					'address_region' => 'address_region',
					'address_country' => 'address_country',
					'address_zip' => 'address_zip' 
			);
			if ($big==0){//nuovo utente
			                $opt_fields_rank = 10;
                            $opt_fields_credit = 0;
                            } else {//edit utente
                            
                             $opt_fields_rank = 5;
                             $opt_fields_credit= 5;
                                
                                
                            }
            
            
		} else {
			$optional_fields += array (
					'photo' => 'photo',
					'middle_name' => 'middle_name',
					'lang' => 'language',
					'birth_date' => 'birth_date',
					'sex' => 'sex',
					//'phone' => 'phone',
					'birth_place' => 'birth_place',
					'address_street' => 'street',
					'address_street_no' => 'street_no',
					'address_town' => 'city',
					'address_province' => 'province',
					'address_region' => 'region',
					'address_country' => 'state',
					'address_zip' => 'zip' 
			);
			if ($big==0) {//nuovo utente
                            $opt_fields_rank = 0;
                            $opt_fields_credit = 0;
                            } else {//edit utente
                            
                             $opt_fields_rank = 5;
                             $opt_fields_credit= 5;
                                
                                
                            }
			
		}
		$all_fields = array_merge ( $required_fields, $optional_fields );
		
		$this->_checkVars ( $required_fields, $optional_fields );
		
		$member = array ();
		foreach ( $all_fields as $column => $field ) {
			if (isset ( $this->api [$field] )) {
				$member [$column] = trim ( $this->api [$field] );
			}
		}
		
		// TODO: check field format? check in Member model?
		if ($big==0){//se � nuovo utente imposta il type altrimenti lo lascia invariato
		$member ['type'] = MEMBER_MEMBER;
        }
		$member ['status'] = ACTIVE;
		
		if ($big > 0) { // editing
			$member ['big'] = $big;
		}
		$member['sex']=strtolower($member['sex']);
		$this->Member->set ( $member );
		$this->Member->save ();
		
		if (! empty ( $this->Member->validationErrors )) { // we have errors while saving the data
                       
            //$this->_apiEr ( __( 'Please fill in all required fields' ), true, false, array ( 'fields' => $this->Member->validationErrors ) );
                      
            $firstError = implode('',reset($this->Member->validationErrors));
            $this->_apiEr ( __( $firstError ),true, false, array ( 'fields' => $this->Member->validationErrors ),'010');
            
        }
		
		$member ['big'] = $this->Member->id;
		
          if ($this->Wallet->getCreditByReason($member['big'],'Edit profilo')==0){
                                   
                                   // crediti e rank per nuova registrazione o edit profilo
                                   $this->Member->rank ($member['big'], 10 + $opt_fields_rank );
                                   $this->Wallet->addAmount($member['big'], $opt_fields_credit, 'Edit profilo' ); 
                                   }
        //print_r($member);		
		return $member;
	}
	
	/**
	 * set member position
	 */
	public function api_setposition() {
		$this->_checkVars ( array (
				'lat',
				'lon' 
		), array (
				'big' 
		) );
		
         
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		/*
		 * Coordinate invertite $memb = array ( 'big' => $this->api ['big'], 'last_lonlat' => '(' . $this->api ['lat'] . ',' . $this->api ['lon'] . ')' );
		 */
		
		// Coordinate come da campo in Member lon,lat
		$memb = array (
				'big' => $this->api ['big'],
				'last_lonlat' => '(' . $this->api ['lon'] . ',' . $this->api ['lat'] . ')' 
		);
		
     //   $this->log("memb: ".serialize($memb));
        
		$this->Member->set ( $memb );
		try {
			$res = $this->Member->save ();
		} catch ( Exception $e ) {
			$this->_apiEr ( __("Errore") );
		}
		
		// AUTO CHECKIN!!!
		
		// $myC = $this->Checkin->AutoCheckin( '(' . $this->api ['lat'] . ',' . $this->api ['lon'] . ')',$this->api ['big']);
		
		$myC = $this->Checkin->AutoCheckin('('.$this->api ['lon'].','.$this->api['lat'].')', $this->api ['big']);
		
		$this->_apiOk ( __("Position set") );
	}
	
	/**
	 * get member position
	 */
	public function api_getposition() {
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$params = array (
				'conditions' => array (
						'Member.big' => $this->api ['big'] 
				),
				'fields' => array (
						'big',
						'last_lonlat',
						'updated' 
				),
				'recursive' => - 1 
		);
		
		try {
			$data = $this->Member->find ( 'first', $params );
			
			$this->_apiOk ( $data );
		} catch ( Exception $e ) {
			$this->_apiEr ( __("Errore") );
		}
	}
	
	/**
	 * view member profile
	 * TODO: at the moment this is method is still incomplete
	 */
	public function api_profile() {
		$this->_checkVars ( array (), array (
				'big' 
		) );
		
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$params = array (
				'conditions' => array (
						'Member.big' => $this->api ['big'] 
				),
				'recursive' => - 1 
		);
		
		$data = $this->Member->find ( 'first', $params );
		
		$data ['Member']['isvip']=($data ['Member'] ['type'] == MEMBER_VIP);
		

		
		
		$db = $this->Member->getDataSource();
		
		$serviceList=explode(',',ID_RADAR_VISIBILITY_PRODUCTS);
		$query='SELECT count(*) FROM wallets WHERE member1_big='.$this->api ['big'] .' AND expirationdate>NOW() AND product_id IN ('.ID_RADAR_VISIBILITY_PRODUCTS.')';
		
		try {
		$mwal=$db->fetchAll($query);
		$data ['Member']['ishot']=(count($mwal)>0);
		}
		catch (Exception $e)
		{
			
		$this->_apiEr( $e);
				
		}
		
// count amici
		// count amici
        /*$query='SELECT * FROM friends WHERE status=\'A\' and (member1_big='.$this->api ['big'] .' or member2_big='.$this->api ['big'].')';
        
        try {
            $mwal=$db->fetchAll($query);
        }
        catch (Exception $e)
        {
                
            $this->_apiEr( $e);
        
        }
        
        $data ['Member']['friendscount']=count($mwal);*/
        
        $data['Member']['friendscount']=count($this->Friend->findAllFriendsNew($this->api['big'],'A'));

	
		$now = new DateTime();
	$olddate = date('m/d/Y h:i:s a', time());	
		date_sub($now, date_interval_create_from_date_string('5 days'));
		$data ['Member']['isnew']=($data ['Member'] ['created']) > $now;
		
	
		unset ( $data ['Member'] ['password'] );
		unset ( $data ['Member'] ['salt'] );
		unset ( $data ['Member'] ['created'] );
		unset ( $data ['Member'] ['updated'] );
		unset ( $data ['Member'] ['last_mobile_activity'] );
		unset ( $data ['Member'] ['last_web_activity'] );
		unset ( $data ['Member'] ['status'] );
		unset ( $data ['Member'] ['type'] );
		

		$datapic=$this->Member->getMembersPhotos( $data ['Member'] ['big']);
		foreach ( $datapic as $key => $val ) {

			$data ['Member']['Pictures'][$key]=$val;

		}

			
		
		
		
		if ($data ['Member'] ['photo_updated'] > 0) {
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
		} else {
			// standard image
			$sexpic = 2;
			if ($data ['Member'] ['sex'] == 'f') {
				$sexpic = 3;
			}
			
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
		}
		
		// save profil visit
		$this->ProfileVisit->saveVisit ( $this->logged ['Member'] ['big'], $this->api ['big'] );
		
		// TODO : do we want to add push for visits??
		$this->Member->rank ( $this->api ['big'], 1 ); // rank +1 visualizza proprio profilo
		
		$this->_apiOk ( $data );
	}
	public function api_profileextended() {
		$this->_checkVars ( array (), array (
				'big' 
		) );
		
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$params = array (
				'conditions' => array (
						'Member.big' => $this->api ['big'] 
				),
				'recursive' => 0 
		);
		
		$data = $this->Member->find ( 'first', $params );
		
		unset ( $data ['Member'] ['password'] );
		unset ( $data ['Member'] ['salt'] );
		unset ( $data ['Member'] ['created'] );
		unset ( $data ['Member'] ['updated'] );
		unset ( $data ['Member'] ['last_mobile_activity'] );
		unset ( $data ['Member'] ['last_web_activity'] );
		unset ( $data ['Member'] ['status'] );
		unset ( $data ['Member'] ['type'] );
		
		if ($data ['Member'] ['photo_updated'] > 0) {
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
		} else {
			// standard image
			$sexpic = 2;
			if ($data ['Member'] ['sex'] == 'f') {
				$sexpic = 3;
			}
			
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
		}
		$this->_apiOk ( $data );
	}
	
	/**
	 * get member extraindfos
	 */
	public function api_getExtraInfos_old() {
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$params = array (
				'conditions' => array (
						'member_big' => $this->api ['big'] 
				),
				'recursive' => - 1 
		);
		
		try {
			$data = $this->ExtraInfos->find ( 'first', $params );
			
			$this->_apiOk ( $data );
		} catch ( Exception $e ) {
			$this->_apiEr ( __("Errore") );
		}
	}
	public function api_getExtraInfos() {
		if (! isset ( $this->api ['big'] ) or $this->api ['big'] == '') {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$params = array (
				'conditions' => array (
						'member_big' => $this->api ['big'] 
				),
				'recursive' => - 1 
		);
		
		try {
			$data = $this->ExtraInfos->find ( 'first', $params );
			
			if (count ( $data ) > 0)
				
				$this->_apiOk ( $data );
			else {
				
				$data ['ExtraInfos'] = array (
						'country_code' => null,
						'city' => null,
						'occupation' => null,
						'music' => null,
						'food' => null,
						'fashion' => null,
						'primary_language' => null,
						'secondary_language' => null,
						'member_big' => $this->logged ['Member'] ['big'],
						'looking_for' => null,
						'emotional_status' => null 
				);
				$this->_apiOk ( $data );
			}
		} catch ( Exception $e ) {
			$this->_apiEr ( __("Errore") );
		}
	}
	
	/**
	 * view member profile visits
	 * TODO: at the moment this is method is still incomplete
	 */
	public function api_profilevisitsold() {
		$this->_checkVars ( array (), array (
				'big' 
		) );
		debug ( "1 ".date('h:i:s') );
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		$all_nearby = $this->ProfileVisit->getVisits ( $this->api ['big'] );
		$xresponse = array ();
		$xami = array ();
		$counter = 0;
		debug ( "2 ".date('h:i:s') );
		// print_r($all_nearby);
		foreach ( $all_nearby as $ami ) {
			debug ( "21 ".date('h:i:s'). " ".$ami ['ProfileVisit'] ['visitor_big'] );
			$ami ['ProfileVisit'] ['last_visit'] = $ami [0] ['created'];
			$ami ['ProfileVisit'] ['number_of_visits'] = $ami [0] ['number_of_visits'];
			unset ( $ami [0] );
			
			$xami [] = $ami;
			// print_r($ami);
			// print_r($xami);
			$params = array (
					'conditions' => array (
							'Member.big' => strval ( $ami ['ProfileVisit'] ['visitor_big'] ) 
					),
					'fields' => array (
							'Member.big',
							'Member.name',
							'Member.middle_name',
							'Member.surname',
							'Member.photo_updated',
							'Member.sex',
							'Member.birth_date',
							'Member.address_town',
							'Member.address_country' 
					),
					'recursive' => - 1 
			);
			
			$data = $this->Member->find ( 'first', $params );
			
			// print_r($data);
			// debug ( $data ['Member'] );
			
			if (isset ( $data ['Member'] ['photo_updated'] ) && $data ['Member'] ['photo_updated'] > 0) {
				$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
			} else {
				// standard image
				$sexpic = 2;
				if ($data ['Member'] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			}
			
			// ADDED key for frindship
			$xfriend = $this->Friend->FriendsAllRelationship ( $this->logged ['Member'] ['big'], $data ['Member'] ['big'] );
			$xisFriend = 0;
			$xstatus = 'NO';
			if (count ( $xfriend ) > 0) {
				$xisFriend = 1;
				$data ['Member'] ['friendstatus'] = $xfriend [0] ['Friend'] ['status'];
				$xstatus = $xfriend [0] ['Friend'] ['status'];
			}
			
			if ($xstatus != 'A') {
				$data ['Member'] ['surname'] = mb_substr ( $data ['Member'] ['surname'], 0, 1 ) . '.';
			}
			
			$data ['Member'] ['isFriend'] = $xisFriend;
			
			$xami [$counter] ['Member'] = $data ['Member'];
			
			$xresponse [] = $xami [$counter];
			/*
			 * debug($xresponse);
			 */
			
			$counter += 1;
		}
		debug ( "3 ".date('h:i:s') );
		// reset visits read count
		
		$this->ProfileVisit->markAsRead ( $this->api ['big'] );
		debug ( "4 ".date('h:i:s') );
		$this->_apiOk ( $xresponse );
		
		// $this->_apiOk ( $data );
	}
	
	
	
	public function api_profilevisits() {
		
		$this->_checkVars ( array (), array (
				'big'
		) );
	//	debug ( "1 ".date('h:i:s') );
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$db = $this->Member->getDataSource();
	
	$query="select profile_visits.visitor_big as visitor , MAX(profile_visits.created) AS created,COUNT(profile_visits.visitor_big) AS number_of_visits , MAX(members.name) as name,MAX(members.middle_name) as middle_name, max(members.surname) as surname, max(members.photo_updated) as photo_updated,max(members.sex) as sex ".
			",max(members.type) as type,max(members.created) as mcreated".
 ", max(member_settings.chat_ignore) as ignora , max(friends.status) as amico from profile_visits  ".
 "left outer join members on (profile_visits.visitor_big=members.big ) ".
"left outer join friends on ( (friends.member1_big=profile_visits.visitor_big or friends.member2_big=profile_visits.visitor_big) and ( friends.member1_big=".$this->api ['big']." or friends.member2_big=".$this->api ['big'].")) ".
"left outer join member_settings on ( (member_settings.from_big=profile_visits.visitor_big or member_settings.to_big=profile_visits.visitor_big)and ( member_settings.from_big=".$this->api ['big']." or member_settings.to_big=".$this->api ['big'].")) ".
"   where visited_big=".$this->api ['big']." and members.status<>255 and (member_settings.chat_ignore<>1 or member_settings is null)   group by profile_visits.visitor_big order by created desc";
        ;
				
	try {
		$membersArray=$db->fetchAll($query);
	}
	catch (Exception $e)
	{
	$this->_apiOk ( $e);
		
	}
	
	$counter = 0;
	$xresponse =array();
	foreach ( $membersArray as $ami ) {
		//debug ( $ami['visitor'] );
		$xami=array();
		$xami['ProfileVisit'] ['visitor_big'] = $ami [0] ['visitor'];
		$xami['ProfileVisit'] ['last_visit'] = $ami [0] ['created'];
		$xami ['ProfileVisit'] ['number_of_visits'] = $ami [0] ['number_of_visits'];
	$xami['Member']['big']=$ami[0]['visitor'];
	
	
	$xami ['Member']['isvip']=($ami[0] ['type'] == MEMBER_VIP);
	
	
	
	
	$db = $this->Member->getDataSource();
	
	$serviceList=explode(',',ID_RADAR_VISIBILITY_PRODUCTS);
	$query='SELECT count(*) FROM wallets WHERE member1_big='.$this->api ['big'] .' AND expirationdate>NOW() AND product_id IN ('.ID_RADAR_VISIBILITY_PRODUCTS.')';
	
	try {
		$mwal=$db->fetchAll($query);
		$xami  ['Member']['ishot']=(count($mwal)>0);
	}
	catch (Exception $e)
	{
	
		$this->_apiEr( $e);
	
	}
	
	$now = new DateTime();
	$olddate = date('m/d/Y h:i:s a', time());
	date_sub($now, date_interval_create_from_date_string('5 days'));
	$xami  ['Member']['isnew']=($ami[0] ['mcreated']) > $now;
	
	
	
	$xami['Member']['name']=$ami[0]['name'];
	$xami['Member']['surname']=$ami[0]['surname'];
	$xami['Member']['sex']=$ami[0]['sex'];
//	$xami['Member']['big']=$ami[0]['visitor'];
	if (isset ( $ami[0] ['photo_updated'] ) && $ami[0] ['photo_updated'] > 0) {
		$xami ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $ami[0] ['visitor'], $ami[0] ['photo_updated'] );
	} else {
		// standard image
		$sexpic = 2;
		if ($ami[0]  ['sex'] == 'f') {
			$sexpic = 3;
		}
	
		$xami ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
	}

	$xami ['Member'] ['isFriend'] = strlen($ami[0]['amico']);
	
	if ($ami[0]['amico'] != 'A') {
		$xami ['Member'] ['surname'] = mb_substr ( $xami ['Member'] ['surname'], 0, 1 ) . '.';
	}
	
	
	/*	unset ( $ami [0] );
			
		$xami [] = $ami;
		// print_r($ami);
		// print_r($xami);
		$params = array (
				'conditions' => array (
						'Member.big' => strval ( $ami ['ProfileVisit'] ['visitor_big'] )
				),
				'fields' => array (
						'Member.big',
						'Member.name',
						'Member.middle_name',
						'Member.surname',
						'Member.photo_updated',
						'Member.sex',
						'Member.birth_date',
						'Member.address_town',
						'Member.address_country'
				),
				'recursive' => - 1
		);
		
		$data = $this->Member->find ( 'first', $params );
			
		// print_r($data);
		// debug ( $data ['Member'] );
			
		if (isset ( $data ['Member'] ['photo_updated'] ) && $data ['Member'] ['photo_updated'] > 0) {
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
		} else {
			// standard image
			$sexpic = 2;
			if ($data ['Member'] ['sex'] == 'f') {
				$sexpic = 3;
			}
	
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
		}
			
		// ADDED key for frindship
		$xfriend = $this->Friend->FriendsAllRelationship ( $this->logged ['Member'] ['big'], $data ['Member'] ['big'] );
		$xisFriend = 0;
		$xstatus = 'NO';
		if (count ( $xfriend ) > 0) {
			$xisFriend = 1;
			$data ['Member'] ['friendstatus'] = $xfriend [0] ['Friend'] ['status'];
			$xstatus = $xfriend [0] ['Friend'] ['status'];
		}
			
		if ($xstatus != 'A') {
			$data ['Member'] ['surname'] = mb_substr ( $data ['Member'] ['surname'], 0, 1 ) . '.';
		}
			
		$data ['Member'] ['isFriend'] = $xisFriend;
			
		$xami [$counter] ['Member'] = $data ['Member'];
		*/		
		$xresponse [] = $xami;
		/*
		 * debug($xresponse);
	
				*/
		$counter += 1;
	}
	$this->ProfileVisit->markAsRead ( $this->api ['big'] );
	$this->_apiOk ( $xresponse );
	
	}
	
	/*
	 * --api_CheckContactsprofile Saves the phone contacts and return a suggested list of members
	 */
	public function api_CheckContactsprofile() {
		$InputData = $this->api;
		$AppoMem = array ();
		
		$membersMails = array ();
		$membersPhones = array ();
        $this->log("############CHECKCONTACTSPROFILE#############");
        $this->log("MEMBER ->".$this->api ['member_big']);
        
		$ContactBIG = $this->api ['member_big'];
		$PhoneContacts = array ();
		
		$chunk = '1'; // default
		
		if (isset ( $this->api ['chunk'] )) {
			$chunk = $this->api ['chunk'];
		}
        $this->log("CHUNK -> ".$chunk);
		$numChunks = 1;
		if (isset ( $this->api ['chunksCount'] )) {
			$numChunks = $this->api ['chunksCount'];
		}
		$this->log("NUMCHUNK -> ".$numChunks);
        
		$Privacyok = $this->PrivacySetting->getPrivacySettings ( $this->api ['member_big'] );
		$goonPrivacy = true;
		if (count ( $Privacyok ) > 0) {
			if ($Privacyok [0] ['PrivacySetting'] ['sharecontacts'] == 0) {
				$goonPrivacy = false;
			}
		}
		if ($goonPrivacy) {
			for($i = 1; $i <= $numChunks; $i++) {
				
                $this->log("CHUNK -> $i ");
				$xPhoneContacts = $this->api ['contacts' . $i];
                $this->log("xPhoneContacts -> ".serialize($xPhoneContacts));
				$XCo2 = json_decode ( $xPhoneContacts, true );
				$PhoneContacts = array_merge ( $PhoneContacts, $XCo2 );
			}
            $this->log("##########################PhoneContacts#######################");
			$this->log("PhoneContacts -> ".serialize($PhoneContacts));
			// array_merge
			// delete all existing contacts
			if ($chunk == '0') 			// only for chunk=0
			{
                $FirstIns=$this->Contact->find('count', array('conditions' => array('Contact.member_big' => $ContactBIG)));
				
                if ($FirstIns<1){
                    //crediti e rank per condivisione rubrica la prima volta
                    $this->Wallet->addAmount($ContactBIG, '100', 'Condividi Rubrica' );
                    $this->Member->rank($ContactBIG,100);
                    
                } 
                
                $this->Contact->deleteAll ( array (
						'Contact.member_big' => $ContactBIG 
				), false );
			}
			// delete to mantain android compatibility
			if (!isset( $this->api['chunk'] )) {
				$this->Contact->deleteAll ( array (
						'Contact.member_big' => $ContactBIG 
				), false );
			}
			                    
            $phonePattern = '/[()]+|[a-zA-Z]+|[.]+|[ ]+|[#*]+[0-9]+[#*]+|[\\/]+[0-9]+|[-]+|[#*]$/';
            $tox_chars = array ('.',',',' ','(',')');
            
			foreach ( $PhoneContacts as $val ) {
				$Contacts = array ();
				// parte inserimento nel db...
				// se non esiste
                
                $val['internal_name']=(strlen($val['internal_name']) < 300) ? $val['internal_name'] : substr ($val['internal_name'], 0, 300 );
                
				$paramsCont = array (
						'conditions' => array (
                                'Contact.name' => $val ['internal_name'],
								'Contact.member_big' => $ContactBIG) 
						// 'Contact.phone' => $val ['phone_number'],
						// 'Contact.email' => $val ['mail_address']
					);
				if (isset($val['phone_number'])) {
                    //pulisce il numero di telefono da tutto cio che non sono cifre
                    $phoneNumber = preg_replace($phonePattern,'',str_replace($tox_chars,'',$val['phone_number']));
                    
					$val['phone_number'] = (strlen($phoneNumber) < 32) ? $phoneNumber : substr($phoneNumber, 0, 32 );
					
					$paramsCont["conditions"]['Contact.phone'] = $val['phone_number'];
				}
				
				if (isset($val['mail_address'])) {
					$val['mail_address'] = (strlen($val['mail_address']) < 50) ? $val['mail_address'] : substr($val['mail_address'], 0, 50 );
					
					$paramsCont["conditions"]['Contact.email'] = $val['mail_address'];
				}
				               
				//restituisce 0 se il contatto si trova in Contacts altrimenti >0
                $contactCount = $this->Contact->find('count', $paramsCont );
				
				// insert unique
				
				if ($contactCount == 0) {//significa che il contatto non � presente nella tabella Contacts
									     //pertanto lo inserisce
					$Contacts['member_big'] = $ContactBIG;
					$Contacts['email'] = $val['mail_address'];
					$Contacts['phone'] = $val['phone_number'];
					$Contacts['name'] = $val['internal_name'];
                    
					$this->Contact->set($Contacts);
					$this->Contact->save();
				} 
				
				/*	else {
				
					 * $dbo = $this->Contact->getDatasource(); $logs = $dbo->getLog(); $lastLog = end($logs['log']);
					
					// return $lastLog['query'];
					if (isset ( $val ['mail_address'] )) {
						$this->log ( 'Scarto ' . $val ['mail_address'] );
					}
					$this->log ( 'Scarto ' . $val ['internal_name'] );
					if (isset ( $val ['phone_number'] )) {
						$this->log ( 'Scarto ' . $val ['phone_number'] );
					}
				}
				 */
				
				unset ( $Contacts );
				unset ( $this->Contact->id );
				
				// preparazione per ricerca
				if (isset($val['mail_address'])) {
					$membersMails[] = $val['mail_address'];
				}
				;
				if (isset($val['phone_number'])) {
					$membersPhones[] = $val['phone_number'];
				}
				
			}
			
			// TODO: find a better way
			// fast fix for empties
			
			// chunck for many position!!!
			// $allMail= array_chunk($membersMails , 200));
			// for count($allMail)...
			
			if (count ( $membersMails ) == 0)
				$membersMails [] = 'nomail';
			
			if (count ( $membersPhones ) == 0)
				$membersPhones [] = 'nophone';
				
				// Qui controlla se tra i membri c'� qualcuno dei nostri contatti
			$params = array (
					'conditions' => array (
							'Member.status' => 1,
							'OR' => array (
											'Member.email' => $membersMails, 
									        'Member.phone' => $membersPhones 
									) 
							),
					'recursive' => - 1,
					
					'fields' => array (
							'Member.big',
							'Member.name',
							'Member.middle_name',
							'Member.surname',
							'Member.photo_updated',
							'Member.sex',
							'Member.phone',
							'Member.birth_date',
							'Member.address_town',
							'Member.address_country' 
					) 
			);
			
			$data = $this->Member->find ( 'all', $params );
			// $AppoMem = array ();
			
			foreach ( $data as $key => &$mem ) {
				
				// check if any friendship exists yet
				$AlreadyFr = $this->Friend->FriendsAllRelationship ( $ContactBIG, $mem ['Member'] ['big'] );
				
				if (count ( $AlreadyFr ) == 0) {
					
					if ($mem ['Member'] ['photo_updated'] > 0) {
						$mem ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $mem ['Member'] ['big'], $mem ['Member'] ['photo_updated'] );
					} else {
						// standard image
						$sexpic = 2;
						if ($mem ['Member'] ['sex'] == 'f') {
							$sexpic = 3;
						}
						
						$mem ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
					}
					$mem ['Member'] ['surname'] = mb_substr ( $mem ['Member'] ['surname'], 0, 1 ) . '.';
						
					$AppoMem [] = $mem;
				}
			}
		}
		/*
		 * $dbo = $this->Member->getDatasource (); $logs = $dbo->getLog (); $lastLog = end ( $logs ['log'] ); debug ( $lastLog ['query'] );
		 */
		$this->log("############FINE CHECKCONTACTSPROFILE#############");
		$this->_apiOk ( $AppoMem );
	}
	
        
    
	/**
	 * View public member profile
	 */
	public function api_public() {
		$this->_checkVars ( array (
				'user_big' 
		) );
		
		$memBig = $this->api ['user_big'];
		
        
		// Get member data
		unbindAllBut ( $this->Member );
		$params = array (
				'conditions' => array (
						'Member.big' => $this->api ['user_big'] 
				),
				'fields' => array (
						'Member.big',
						'Member.name',
						'Member.middle_name',
						'Member.surname',
						'Member.photo_updated',
						'Member.sex',
						'Member.birth_date',
						'Member.address_town',
						'Member.address_country',
						'Member.description',
						'Member.type'
				) 
		);
		
                
		$privacySettings = $this->PrivacySetting->getPrivacySettings ( $memBig );
		$photosVisibility = $privacySettings [0] ['PrivacySetting'] ['photosvisibility'];
		       
		$data = $this->Member->find ( 'first', $params );
		$xisFriend = 0;
		$xfriend = $this->Friend->FriendsAllRelationship ( $this->api ['user_big'], $this->api ['member_big'] );
		if (count ( $xfriend ) > 0) {
			$xisFriend = 1;
			$data ['Member'] ['friendstatus'] = $xfriend [0] ['Friend'] ['status'];
		}
		
		$datapic=$this->Member->getMembersPhotos( $data ['Member'] ['big']);
		foreach ( $datapic as $key => $val ) {
		
			$data ['Member']['Pictures'][$key]=$val;
		
		}
		
		$data ['Member']['isvip']=($data ['Member'] ['type'] == MEMBER_VIP);
		$data ['Member']['isnew']=($this->Member->isnew($this->api ['user_big']));
        $data ['Member']['ishot']=($this->Member->ishot($this->api ['user_big']));
        $data ['Member']['friendscount']=count($this->Friend->findAllFriendsNew($memBig,'A'));
		$data ['Member']['isFriend'] = $xisFriend;
        // debug($data);
		// Get checkin or join
        
        $checkinsVisibility=$privacySettings[0]['PrivacySetting']['checkinsvisibility'];
       
        switch ($checkinsVisibility){
            
            case 0 : //visibile a nessuno
                    $checkin = array();
                    break;
            
            case 1 : // visibile a tutti
                    $checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
                                    
                    break;
            
            case 2 : //visibile solo ad amici. Verificare che non sia amico poi bloccato
                     //perch� il blocco non tocca lo status di amico                    
                   $amico=$this->Friend->FriendsRelationship($memBig, $this->api ['member_big'],'A');
                   $bloccato=$this->MemberSetting->isOnIgnoreListDual($memBig,$this->api['member_big']);
                    if (count($amico)>0 AND !$bloccato){//sono amici non bloccati quindi ok visualizzazione Places
                        
                         $checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
                                   
                    } else {//non sono amici oppure lo erano e ora sono bloccati quindi no visualizzazione Places
                        
                             $checkin = array();
                        
                    }
        }
		
        //$checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
		// debug($checkin);
		if (! empty ( $checkin ) && $checkin ['Event'] ['type'] == 2 && $checkin ['Event'] ['status'] == 0) {
			
			$params = array (
					'conditions' => array (
							'Place.big' => $checkin ['Event'] ['place_big'] 
					),
					'recursive' => - 1 
			);
			$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
			
			/*
			 * from condition...			'fields' => array ( 'Place.name', 'Place.default_photo_big', 'Place.category_id' ),
			 */
			
			$place = $this->_addPlacePhotoUrls ( $place );
			
			if (empty ( $place )) {
				$this->_apiEr ( __('Posto inesistente.') );
			}
			
			$category = $this->Place->Category->getOne ( $place ['Place'] ['category_id'] );
			
			$place ['CatLang'] = $category ['CatLang'];
			$place ['CatLang'] ['photo'] = $this->FileUrl->category_picture ( $place ['Place'] ['category_id'], $category ['Category'] ['updated'] );
			
			$data ['Member'] ['place_big'] = $checkin ['Event'] ['place_big'];
			$data ['Member'] ['event_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
			$data ['Member'] ['place_category_id'] = $place ['Place'] ['category_id'];
			/*
			 * $data ['Member'] ['place_address_street'] = $place ['Place'] ['address_street']; $data ['Member'] ['place_address_street_no'] = $place ['Place'] ['address_street_no']; $data ['Member'] ['place_rating'] = $place ['Place'] ['rating_avg']; $data ['Member'] ['place_rating'] = $place ['Place'] ['rating_avg']; $data ['Member'] ['place_rating'] = $place ['Place'] ['rating_avg'];
			 */
			
			$data ['Member'] ['Place'] = $place ['Place'];
		} elseif (! empty ( $checkin )) {
			
			$params = array (
					'conditions' => array (
							'Place.big' => $checkin ['Event'] ['place_big'] 
					),
					
					'recursive' => - 1 
			);
			$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
			
			$place = $this->_addPlacePhotoUrls ( $place );
			
			if (empty ( $place )) {
				$this->_apiEr ( __('Posto inesistente.') );
			}
			
			$category = $this->Place->Category->getOne ( $place ['Place'] ['category_id'] );
			
			$place ['CatLang'] = $category ['CatLang'];
			$place ['CatLang'] ['photo'] = $this->FileUrl->category_picture ( $place ['Place'] ['category_id'], $category ['Category'] ['updated'] );
			
			$data ['Member'] ['event_big'] = $checkin ['Event'] ['big'];
			$data ['Member'] ['event_name'] = $checkin ['Event'] ['name'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
			$data ['Member'] ['place_category_id'] = $place ['Place'] ['category_id'];
			$data ['Member'] ['Place'] = $place ['Place'];
		}
		
		// Get checkins count
		$checkinsCount = $this->Member->Checkin->getCheckinsCountForMember ( $memBig );
		$data ['Member'] ['checkins_count'] = intval ( $checkinsCount );
		
		// Photos processing
		if (isset ( $data ['Member'] ['photo_updated'] ) and $data ['Member'] ['photo_updated'] > 0) {
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
		} else {
			$sexpic = 2;
			if ($data ['Member'] ['sex'] == 'f') {
				$sexpic = 3;
			}
			
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
		}
		unset ( $data ['Member'] ['photo_updated'] );
		
		// Get uploaded photos if photosVisibility=1
		
		$params = array (
				'conditions' => array (
						'Photo.member_big' => $memBig 
				),
				'fields' => array (
						'Photo.big',
						'Photo.original_ext',
						'Gallery.*' 
				),
				'joins' => array (
						array (
								'table' => 'galleries',
								'alias' => 'Gallery',
								'type' => 'LEFT',
								'conditions' => array (
										'Photo.gallery_big = Gallery.big' 
								) 
						) 
				),
				'recursive' => - 1 
		);
		
		if ($photosVisibility > 0) {
			
			$photos = $this->Member->Photo->find ( 'all', $params );
			$photosCount = $this->Member->Photo->find ( 'count', $params );
			
			$photos = $this->_addMemberPhotoUrls ( $photos );
		} else {
			$photos = array ();
			$photosCount = $this->Member->Photo->find ( 'count', $params );
		}
		$data ['Uploaded'] = $photos;
		$data ['Member'] ['photos_count'] = $photosCount;
		
		// ADDED key for frindship
		$xfriend = $this->Friend->FriendsAllRelationship ( $this->logged ['Member'] ['big'], $data ['Member'] ['big'] );
		$xisFriend = 0;
		$xstatus = 'NO';
		if (count ( $xfriend ) > 0) {
			$xisFriend = 1;
			$data ['Member'] ['friendstatus'] = $xfriend [0] ['Friend'] ['status'];
			$xstatus = $xfriend [0] ['Friend'] ['status'];
		}
		
		if ($xstatus != 'A') {
			$data ['Member'] ['surname'] = mb_substr ( $data ['Member'] ['surname'], 0, 1 ) . '.';
		}
		
		$data ['Member'] ['isFriend'] = $xisFriend;
		// debug($data);
		// $this->Util->transform_name ( $data );
		// debug($data);
		// SAVES A VISIT TO PROFILE!!
		// debug ( $data );
		$this->ProfileVisit->saveVisit ( $this->logged ['Member'] ['big'], $this->api ['user_big'] );
		
		$this->_apiOk ( $data );
	}
	public function public_profile() {
		$this->_sidebarPlaces (); // places for right sidebar
		                          // debug($this->request);
		$memBig = isset ( $this->request ['pass'] [0] ) ? $this->request ['pass'] [0] : false;
		$showEvents = isset ( $this->request ['pass'] [1] ) && $this->request ['pass'] [1] == 'events' ? TRUE : FALSE;
		
		if (empty ( $memBig )) {
			$memBig = $this->logged ['Member'] ['big'];
		}
		
		$this->set ( 'memBig', $memBig );
		
		// Get member data
		unbindAllBut ( $this->Member );
		$params = array (
				'conditions' => array (
						'Member.big' => $memBig 
				),
				'fields' => array (
						'Member.big',
						'Member.name',
						'Member.middle_name',
						'Member.surname',
						'Member.photo_updated' 
				) 
		);
		
		$member = $this->Member->find ( 'first', $params );
		$this->set ( 'member', $member );
		
		if (! $member) {
			$this->Session->setFlash ( __ ( 'L\'utente non esiste' ), 'flash/error' );
			return $this->redirect ( '/' );
		}
		
		// Get checkin or join
		$checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
		$this->set ( 'checkin', $checkin );
		
		// Get place details
		$params = array (
				'conditions' => array (
						'Place.big' => $checkin ['Event'] ['place_big'] 
				),
				'fields' => array (
						'Place.big',
						'Place.name',
						'Place.slug' 
				),
				'recursive' => - 1 
		);
		$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
		$this->set ( 'place', $place );
		
		// debug($checkin);
		if (! empty ( $checkin ) && $checkin ['Event'] ['type'] == 2 && $checkin ['Event'] ['status'] == 0) {
			$data ['Member'] ['place_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['place_slug'] = $place ['Place'] ['slug'];
			$data ['Member'] ['place_big'] = $place ['Place'] ['big'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
		} elseif (! empty ( $checkin )) {
			$data ['Member'] ['place_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['place_slug'] = $place ['Place'] ['slug'];
			$data ['Member'] ['place_big'] = $place ['Place'] ['big'];
			$data ['Member'] ['event_name'] = $checkin ['Event'] ['name'];
			$data ['Member'] ['event_slug'] = $checkin ['Event'] ['slug'];
			$data ['Member'] ['event_big'] = $checkin ['Event'] ['big'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
		}
		
		$this->set ( 'is_ignored', $this->Member->MemberSetting->isOnIgnoreList ( $this->logged ['Member'] ['big'], $member ['Member'] ['big'] ) );
	}
	public function my_profile() {
		$this->logged = $this->Member->findByBig ( $this->Auth->user ( 'big' ) ); // don't understand why it's not already filled
		$this->_sidebarPlaces (); // places for right sidebar
		                          // debug($this->request);
		$memBig = $this->logged ['Member'] ['big'];
		$showEvents = isset ( $this->request ['pass'] [1] ) && $this->request ['pass'] [1] == 'events' ? TRUE : FALSE;
		
		$this->set ( 'memBig', $memBig );
		
		// Get member data
		$member = $this->logged;
		$this->set ( 'member', $member );
		
		if (! $member) {
			$this->Session->setFlash ( __ ( 'L\'utente non esiste' ), 'flash/error' );
			return $this->redirect ( '/' );
		}
		
		// Get checkin or join
		$checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
		$this->set ( 'checkin', $checkin );
		
		// Get place details
		$params = array (
				'conditions' => array (
						'Place.big' => $checkin ['Event'] ['place_big'] 
				),
				'fields' => array (
						'Place.big',
						'Place.name',
						'Place.slug' 
				),
				'recursive' => - 1 
		);
		$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
		$this->set ( 'place', $place );
		
		// debug($checkin);
		if (! empty ( $checkin ) && $checkin ['Event'] ['type'] == 2 && $checkin ['Event'] ['status'] == 0) {
			$data ['Member'] ['place_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['place_slug'] = $place ['Place'] ['slug'];
			$data ['Member'] ['place_big'] = $place ['Place'] ['big'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
		} elseif (! empty ( $checkin )) {
			$data ['Member'] ['place_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['place_slug'] = $place ['Place'] ['slug'];
			$data ['Member'] ['place_big'] = $place ['Place'] ['big'];
			$data ['Member'] ['event_name'] = $checkin ['Event'] ['name'];
			$data ['Member'] ['event_slug'] = $checkin ['Event'] ['slug'];
			$data ['Member'] ['event_big'] = $checkin ['Event'] ['big'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
		}
	}
	public function events($memBig) {
		$events = $this->Member->Checkin->Event->getAttendedEventsForMember ( $memBig );
		$this->set ( 'events', $events );
	}
	public function places($memBig) {
		$places = $this->Member->Checkin->Event->getAttendedEventsForMember ( $memBig );
		$this->set ( 'places', $places );
	}
	public function gallery($memBig = 0) {
		$photos = $this->Member->Photo->getMemberPhotos ( $memBig );
		$this->set ( 'photos', $photos );
		$this->set ( 'memberBig', $memBig );
		
		$this->set ( 'loggedBig', $this->logged ['Member'] ['big'] );
	}
	public function api_getAffinityMembers() {
		$this->_checkVars ( array (
				'member_big' 
		), array (
				'big' 
		) );
		
		(empty ( $this->api ['big'] )) ? $memBig = $this->api ['member_big'] : $memBig = $this->api ['big'];
		
		$MySugAffinityAll = array ();
		$MySugAffinity = array ();
		$MySugAffinityAll = $this->Member->getAffinityMembersNew ( $memBig );
		
		// debug($MySugAffinityAll);
		
		// REMOVE FRIENDS!!
		foreach ( $MySugAffinityAll as $key => &$val ) {
			
			// check if any friendship exists yet
			// debug($val[0] ['big']);
			$AlreadyFr = $this->Friend->FriendsAllRelationship ( $val [0] ['big'], $memBig );
			$dbo = $this->Friend->getDatasource ();
			$logs = $dbo->getLog ();
			$lastLog = end ( $logs ['log'] );
			if (count ( $AlreadyFr ) == 0) {
				$MySugAffinityAll [$key] [0] ['surname'] = mb_substr ( $MySugAffinityAll [$key] [0] ['surname'], 0, 1 ) . '.';
				$MySugAffinity [] = $MySugAffinityAll [$key];
			}
		}
		
		foreach ( $MySugAffinity as $key => &$val ) {
			
			// ADD MEMBER PHOTO
			// debug( $val ['Member']['Member'] ['photo_updated'] );
			if ($MySugAffinity [$key] [0] ['photo_updated'] > 0) {
				$MySugAffinity [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $val [0] ['big'], $val [0] ['photo_updated'] );
			} else {
				$sexpic = 2;
				if ($MySugAffinity [$key] [0] ['sex'] == 'f') {
					$sexpic = 3;
				}
				
				$MySugAffinity [$key] [0] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			}
		}
		
		// usort($MySugAffinity,array('MembersController','multiFieldSortArray'));
		usort ( $MySugAffinity, 'MembersController::multiFieldSortArray' );
		
		$this->_apiOK ( $MySugAffinity );
	}
	public static function multiFieldSortArray($x, $y) { // sort an array by position_bonus DESC and distance ASC
		if ($x [0] ['position_bonus'] == $y [0] ['position_bonus']) {
			
			return ($x [0] ['distance'] < $y [0] ['distance']) ? - 1 : + 1;
		} else
			
			return ($x [0] ['position_bonus'] > $y [0] ['position_bonus']) ? - 1 : + 1;
	}

	public function api_removeMember(){
        
        $this->_checkVars (array('delete_id'), array ());
        
        $memberid=$this->api['delete_id'];
        
        $params = array(
                'conditions' => array(
                        'Member.big' => $memberid
                ),
                'recursive' => -1,
        );
        
        
        $memberVal = $this->Member->find ( 'first', $params );
        
        if (count($memberVal)>0) {        
                  
        $result=$this->Member->deleteMember($memberid); 
        
        } else $result="Membro inesistente";
        
        $this->_apiOk ( $result );
        
    }

}
