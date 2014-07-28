<?php
class MembersController extends AppController {
	public $uses = array (
			'Member',
			'PushToken',
			'Operator',
			'Checkin',
			'Contact',
			'ProfileVisit',
			'Friend',
			'PrivacySetting' 
	);
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
				$this->Session->setFlash ( __ ( 'E-mail or password is incorrect' . $test ), 'flash/error' );
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
			$this->Session->setFlash ( __ ( 'Your Facebook account was disconnected from Haamble' ), 'flash/success' );
		} else {
			$this->Session->setFlash ( __ ( 'Your Haamble account is not connected to any Facebook account' ), 'flash/error' );
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
					$this->Session->setFlash ( __ ( 'Your Facebook account was unlinked, you denied access to your account' ), 'flash/error' );
				} else {
					$this->Session->setFlash ( __ ( 'Please confirm access to your Facebook account' ), 'flash/error' );
				}
			} elseif (isset ( $this->params->query ['error'] )) {
				$this->Session->setFlash ( __ ( 'We were not able to connect to your Facebook. Facebook told us: %s', $this->params->query ['error_description'] ), 'flash/error' );
			}
		} elseif (isset ( $this->params->query ) && isset ( $this->params->query ['error_message'] )) { // response from FB - error
			
			$this->Session->setFlash ( __ ( 'We were not able to connect to your Facebook. Facebook told us: %s', $this->params->query ['error_message'] ), 'flash/error' );
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
			$this->Session->setFlash ( __ ( 'Your account was linked with your Facebook account' ), 'flash/success' );
		} elseif ($user ['Member'] ['big'] == $this->logged ['Member'] ['big']) { // already paired with this account
			
			$this->Session->setFlash ( __ ( 'Your account is already linked with this Facebook account' ), 'flash/success' );
		} elseif ($user ['Member'] ['fb_id'] == $fb_user ['id']) { // FB account already paired with another account
			
			$this->Session->setFlash ( __ ( 'Your Facebook account is already linked to another Haamble account' ), 'flash/error' );
		} else {
			
			$this->Session->setFlash ( __ ( 'Internal error occured' ), 'flash/error' );
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
			
			$this->Session->setFlash ( __ ( 'Thank you for registering. Please fill in your profile' ), 'flash/success' );
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
			$this->Session->setFlash ( __ ( 'The e-mail address is already in use, but the account is not paired with your Facebook account. If you already signed up, please login with your email and password. If you forgot your password you can reset it.' ), 'flash/error' );
		} else {
			
			$this->Session->setFlash ( __ ( 'Internal error occured' ), 'flash/error' );
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
				
				$this->Session->setFlash ( __ ( 'Please fill in your profile' ), 'flash/info' );
				
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
				
				$this->Session->setFlash ( __ ( 'Profile updated' ), 'flash/success' );
				
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
		if ($this->logged) {
			
			$this->Session->setFlash ( __ ( 'You are already logged in. You can change your password here.' ), 'flash/success' );
			return $this->redirect ( array (
					'action' => 'edit' 
			) );
		}
		
		if ($this->request->is ( 'post' )) {
			
			$member = $this->Member->findByEmail ( $this->data ['Member'] ['email'] );
			
			if (! $member) {
				$this->Session->setFlash ( __ ( 'Invalid e-mail address' ), 'flash/error' );
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
			
			$this->Session->setFlash ( __ ( 'Please check your e-mail for further instruction. (Make sure to check your spam folder as well.)' ), 'flash/success' );
			$this->redirect ( array (
					'action' => 'login' 
			) );
		}
	}
	public function change_password($member_big = 0, $token = '') {
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
			$this->Session->setFlash ( __ ( 'Invalid password reset link. Please use the form below to generate new link.' ), 'flash/error' );
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
			
			$this->Session->setFlash ( __ ( 'You are already logged in. You can change your password here.' ), 'flash/success' );
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
			
			$this->Session->setFlash ( __ ( 'Password succesfully changed. Now use your new password to login.' ), 'flash/success' );
			return $this->redirect ( array (
					'action' => 'login' 
			) );
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
				
				$this->Session->setFlash ( __ ( 'User saved' ), 'flash/success' );
				return $this->redirect ( array (
						'action' => 'index' 
				) );
			} else {
				
				$this->Session->setFlash ( __ ( 'Error while saving user' ), 'flash/error' );
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
		
		$this->Session->setFlash ( __ ( 'User deleted' ), 'flash/success' );
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
		
		$this->Session->setFlash ( __ ( 'Unsubscribed successuly' ), 'flash/success' );
		return $this->redirect ( array (
				'action' => 'index' 
		) );
	}
	
	/**
	 * authenticate member in API
	 */
	public function api_login() {
		CakeLog::info ( 'Login called' );
		
		$member = $this->_apiLogin ();
		
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
	
	/**
	 * sign up new member
	 */
	public function api_register() {
		$pushToken = isset ( $this->api ['push_token'] ) ? $this->api ['push_token'] : null;
		$platformId = isset ( $this->api ['platform_id'] ) ? $this->api ['platform_id'] : null;
		
		// save new member
		$member = $this->_save ();
		
		$response = $this->_apiLogin ();
		
		$response ['user_msg'] = __ ( 'Registration succesfull' );
		
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
		
		// INSERISCE I PRIVACY SETTINGS tutti a 1!!
		$this->PrivacySetting->CreateSettings($member ['big']);
		
		$this->_apiOk ( $response );
	}
	
	/**
	 * update existing member profile
	 */
	public function api_edit() {
		
		
		// update existing member
		$member = $this->_save ( $this->logged ['Member'] ['big'] );
		
		if (! $member) {
			$this->_apiEr ( __ ( 'There was an error while saving your profile data' ), true );
		}
		
		$response = array (
				'user_msg' => 'Profile update succesfull' 
		);
		
		try {
			$this->_api_photo_upload ( $member ['big'] );
		} catch ( UploadException $e ) {
			$response ['user_msg'] .= $e->getMessage ();
		}
		
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
		} else {
			
			$required_fields = array ();
			$optional_fields = array (
					'password' => 'password',
					'name' => 'name',
					'surname' => 'surname' 
			);
		}
		
	//	debug($this->request['data']['address_town']);
		if (isset($this->request['data']['address_street']) or 
			isset($this->request['data']['address_town']) or
			isset($this->request['data']['address_country']) or
			isset($this->request['data']['lang']) or
			isset($this->request['data']['address_zip'])
			)
		{
			$optional_fields += array (
					'photo' => 'photo',
					'middle_name' => 'middle_name',
					'lang' => 'lang',
					'birth_date' => 'birth_date',
					'sex' => 'sex',
					'phone' => 'phone',
					'birth_place' => 'birth_place',
					'address_street' => 'address_street',
					'address_street_no' => 'address_street_no',
					'address_town' => 'address_town',
					'address_province' => 'address_province',
					'address_region' => 'address_region',
					'address_country' => 'address_country',
					'address_zip' => 'address_zip'
			);
			
		}
		else 
		{
		$optional_fields += array (
				'photo' => 'photo',
				'middle_name' => 'middle_name',
				'lang' => 'language',
				'birth_date' => 'birth_date',
				'sex' => 'sex',
				'phone' => 'phone',
				'birth_place' => 'birth_place',
				'address_street' => 'street',
				'address_street_no' => 'street_no',
				'address_town' => 'city',
				'address_province' => 'province',
				'address_region' => 'region',
				'address_country' => 'state',
				'address_zip' => 'zip' 
		);
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
		
		$member ['type'] = MEMBER_MEMBER;
		$member ['status'] = ACTIVE;
		
		if ($big > 0) { // editing
			$member ['big'] = $big;
		}
		
		$this->Member->set ( $member );
		$this->Member->save ();
		
		if (! empty ( $this->Member->validationErrors )) { // we have errors while saving the data
			$this->_apiEr ( __ ( 'Please fill in all required fields' ), true, false, array (
					'fields' => $this->Member->validationErrors 
			) );
		}
		
		$member ['big'] = $this->Member->id;
		
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
		
		$memb = array (
				'big' => $this->api ['big'],
				'last_lonlat' => '(' . $this->api ['lat'] . ',' . $this->api ['lon'] . ')' 
		);
		debug ( $memb );
		$this->Member->set ( $memb );
		try {
			$res = $this->Member->save ();
		} catch ( Exception $e ) {
			$this->_apiEr ( "Error" );
		}
		
		// AUTO CHECKIN!!!
		
		$myC = $this->Checkin->AutoCheckin( '(' . $this->api ['lat'] . ',' . $this->api ['lon'] . ')',$this->api ['big']);
		
		$this->_apiOk ( "Position set" );
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
			$this->_apiEr ( "Error" );
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
		}
		else
		{
			// standard image
			$sexpic=2;
			if($data ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
				
		}
		
		// save profil visit
		$this->ProfileVisit->saveVisit ( $this->logged ['Member'] ['big'], $this->api ['big'] );
		
		// TODO : do we want to add push for visits??
		
		
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
		}else
		{
			// standard image
			$sexpic=2;
			if($data ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
				
		}
		$this->_apiOk ( $data );
	}
	
	/**
	 * view member profile visits
	 * TODO: at the moment this is method is still incomplete
	 */
	public function api_profilevisits() {
		$this->_checkVars ( array (), array (
				'big' 
		) );
		
		if (! isset ( $this->api ['big'] )) {
			$this->api ['big'] = $this->logged ['Member'] ['big'];
		}
		
		$all_nearby = $this->ProfileVisit->getVisits ( $this->api ['big'] );
		$xresponse = array ();
		$xami = array ();
		
		foreach ( $all_nearby as $ami ) {
			
			$xami [] = $ami;
			
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
			
			debug ( $data ['Member'] );
			
			if (isset ( $data ['Member'] ['photo_updated'] ) && $data ['Member'] ['photo_updated'] > 0) {
				$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
			} else
		{
			// standard image
			$sexpic=2;
			if($data ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
				
		}
			
			$xami [0] ['Member'] = $data ['Member'];
			
			$xresponse [] = $xami [0];
			/*
			 * debug($xresponse);
			 */
		}
		
		$this->_apiOk ( $xresponse );
		
		// $this->_apiOk ( $data );
	}
	
	/*
	 * --api_CheckContactsprofile Saves the phone contacts and return a suggested list of members
	 */
	public function api_CheckContactsprofile() {
		$InputData = $this->api;
		
		$membersMails = array ();
		$membersPhones = array ();
		$ContactBIG = $this->api ['member_big'];
		$PhoneContacts = array ();
		
		$numChunks = $this->api ['chunksCount'];
		for($i = 1; $i <= $numChunks; $i ++) {
			
			$xPhoneContacts = $this->api ['contacts' . $i];
			$XCo2 = json_decode ( $xPhoneContacts, true );
			$PhoneContacts = array_merge ( $PhoneContacts, $XCo2 );
		}
		
		// array_merge
		// delete all existing contacts
		$this->Contact->deleteAll ( array (
				'Contact.member_big' => $ContactBIG 
		), false );
		
		foreach ( $PhoneContacts as $val ) {
			$Contacts = array ();
			// parte inserimento nel db...
			// se non esiste
			$paramsCont = array (
					'conditions' => array (
							'Contact.name' => $val ['internal_name'],
							'Contact.member_big' => $ContactBIG 
					// 'Contact.phone' => $val ['phone_number'],
					// 'Contact.email' => $val ['mail_address']
										) 
			);
			if (isset ( $val ['phone_number'] )) {
				$paramsCont ["conditions"] [] = array (
						'Contact.phone' => $val ['phone_number'] 
				);
			}
			;
			if (isset ( $val ['mail_address'] )) {
				$paramsCont ["conditions"] [] = array (
						'Contact.email' => $val ['mail_address'] 
				);
			}
			;
			
			$contactCount = $this->Contact->find ( 'count', $paramsCont );
			
			// insert unique
			
			if ($contactCount == 0) {
				
				$Contacts ['member_big'] = $ContactBIG;
				if (isset ( $val ['mail_address'] )) {
					$Contacts ['email'] = $val ['mail_address'];
				}
				if (isset ( $val ['phone_number'] )) {
					$Contacts ['phone'] = $val ['phone_number'];
				}
				$Contacts ['name'] = $val ['internal_name'];
				$this->Contact->set ( $Contacts );
				$this->Contact->save ();
			}
			;
			unset ( $Contacts );
			unset ( $this->Contact->id );
			
			// preparazione per ricerca
			if (isset ( $val ['mail_address'] )) {
				$membersMails [] = $val ['mail_address'];
			}
			;
			if (isset ( $val ['phone_number'] )) {
				$membersPhones [] = $val ['phone_number'];
			}
			;
		}
		
		// TODO: find a better way
		// fast fix for empties
		if (count ( $membersMails ) == 0)
			$membersMails [] = 'nomail';
		
		if (count ( $membersPhones ) == 0)
			$membersPhones [] = 'nophone';
			
			// query
		$params = array (
				'conditions' => array (
						'Member.status' => 1,
						"OR" => array (
								
								array (
										'Member.email' => $membersMails 
								),
								array (
										'Member.phone' => $membersPhones 
								) 
						),
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
		
		$AppoMem = array ();
		
		foreach ( $data as $key => &$mem ) {
			
			// check if any friendship exists yet
			$AlreadyFr = $this->Friend->FriendsAllRelationship ( $ContactBIG, $mem ['Member'] ['big'] );
			
			if (count ( $AlreadyFr ) == 0) {
			
				if ($mem ['Member'] ['photo_updated'] > 0) {
					$mem ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $mem ['Member'] ['big'], $mem ['Member'] ['photo_updated'] );
				}
				else 
				{
					// standard image
					$sexpic=2;
					if($mem ['Member']['sex']=='f' )
					{
						$sexpic=3;
					}
					
					$mem ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
					
				}
				$AppoMem [] = $mem;
			}
			
		
		}
		
/*		$dbo = $this->Member->getDatasource ();
		$logs = $dbo->getLog ();
		$lastLog = end ( $logs ['log'] );
		debug ( $lastLog ['query'] );
*/		
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
						'Member.address_country' 
				) 
		);
		
		$data = $this->Member->find ( 'first', $params );
		
		// Get checkin or join
		$checkin = $this->Member->Checkin->getCheckedinEventFor ( $memBig, true );
		if (! empty ( $checkin ) && $checkin ['Event'] ['type'] == 2 && $checkin ['Event'] ['status'] == 0) {
			$params = array (
					'conditions' => array (
							'Place.big' => $checkin ['Event'] ['place_big'] 
					),
					'fields' => array (
							'Place.name',
							'Place.default_photo_big',
							'Place.category_id' 
					),
					'recursive' => - 1 
			);
			$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
			$data ['Member'] ['place_big'] = $checkin ['Event'] ['place_big'];
			$data ['Member'] ['event_name'] = $place ['Place'] ['name'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
			$data ['Member'] ['place_category_id'] = $place ['Place'] ['category_id'];
		} elseif (! empty ( $checkin )) {
			$params = array (
					'conditions' => array (
							'Place.big' => $checkin ['Event'] ['place_big'] 
					),
					'fields' => array (
							'Place.name',
							'Place.default_photo_big',
							'Place.category_id' 
					),
					'recursive' => - 1 
			);
			$place = $this->Member->Checkin->Event->Place->find ( 'first', $params );
			
			$data ['Member'] ['event_big'] = $checkin ['Event'] ['big'];
			$data ['Member'] ['event_name'] = $checkin ['Event'] ['name'];
			$data ['Member'] ['physical'] = $checkin ['Checkin'] ['physical'];
			$data ['Member'] ['place_category_id'] = $place ['Place'] ['category_id'];
		}
		
		// Get checkins count
		$checkinsCount = $this->Member->Checkin->getCheckinsCountForMember ( $memBig );
		$data ['Member'] ['checkins_count'] = intval ( $checkinsCount );
		
		debug ( $data ['Member'] ['photo_updated'] );
		// Photos processing
		if (isset ( $data ['Member'] ['photo_updated'] ) && $data ['Member'] ['photo_updated'] > 0) {
			$data ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $data ['Member'] ['big'], $data ['Member'] ['photo_updated'] );
		} else {
			$sexpic=2;
			if($data ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$data ['Member']['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			
		}
		unset ( $data ['Member'] ['photo_updated'] );
		
		// Get uploaded photos
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
		
		$photos = $this->Member->Photo->find ( 'all', $params );
		$photosCount = $this->Member->Photo->find ( 'count', $params );
		
		$photos = $this->_addMemberPhotoUrls ( $photos );
		$data ['Uploaded'] = $photos;
		$data ['Member'] ['photos_count'] = $photosCount;
		
		$this->Util->transform_name ( $data );
		
		// SAVES A VISIT TO PROFILE!!
		debug ( "q" );
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
			$this->Session->setFlash ( __ ( 'The user does not exist' ), 'flash/error' );
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
			$this->Session->setFlash ( __ ( 'The user does not exist' ), 'flash/error' );
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
        ), array ('big'));
        
        (empty($this->api ['big'])) ? $memBig=$this->api ['member_big'] : $memBig=$this->api ['big'];
        
        
        $MySugAffinity=array();
        
        $MySugAffinity=$this->Member->getAffinityMembers( $memBig );
        
        debug($MySugAffinity);
        
        foreach ( $MySugAffinity as $key => &$val ) {
        
            // ADD MEMBER PHOTO
            // debug( $val ['Member']['Member'] ['photo_updated'] );
            if ($MySugAffinity [$key] ['Member'] ['photo_updated'] > 0) {
                $MySugAffinity [$key]  ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $val ['Member'] ['big'], $val['Member'] ['photo_updated'] );
            } else {
                $sexpic = 2;
                if ($MySugAffinity [$key]  ['Member'] ['sex'] == 'f') {
                    $sexpic = 3;
                }
        
                $MySugAffinity [$key]  ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
            }
        }
                          
          $this->_apiOK($MySugAffinity); 
                   
    }
    
}