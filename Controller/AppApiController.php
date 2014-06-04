<?php

/**
 * This is AppController class for API requests
 */
App::uses ( 'Controller', 'Controller' );
class AppApiController extends Controller {
	public $components = array (
			'RequestHandler',
			'FileUrl',
			'Fb',
			'Uploader.Upload',
			'ChatCache',
			'Util' 
	);
	public $helpers = array ();
	public $uses = array (
			'Member' 
	);
	
	/**
	 * Functionality to be executed on every single request before functionality in controller
	 *
	 * @see Controller::beforeFilter()
	 */
	public function beforeFilter() {
		$db = $this->Member->getDataSource ();
		$db->fetchAll ( "SET timezone = 'Europe/Rome'" );
		$a = $db->fetchAll ( "SHOW timezone" );
		date_default_timezone_set ( 'Europe/Rome' );
		
		parent::beforeFilter ();
		
		$this->lang = LANG_EN; // TODO language select for API
		if (! defined ( 'CURRENT_LANG' )) {
			define ( 'CURRENT_LANG', $this->lang );
		}
		
		$this->isApi = true;
		$this->set ( 'isApi', true );
		
		$this->api = isset ( $_POST ) && ! empty ( $_POST ) ? $_POST : $_GET;
		$this->api_additional = array ();
		
		$this->_checkApiToken ();
		
		return true;
	}
	
	/**
	 * Functionality to be executed on every single request to after functionality in controller, before rendering view (JSON)
	 */
	public function beforeRender() {
		parent::beforeRender ();
		
		$this->header ( 'Content-Type: text/javascript' );
		$this->viewPath = 'Api';
		$this->layout = 'api';
		
		if ($this->name != 'CakeError') {
			$this->view = '/Api/json'; // default data format - JSON
		}
		
		$this->set ( 'debug', isset ( $this->api ['debug'] ) ? $this->api ['debug'] : 0 );
		
		if (! isset ( $this->viewVars ['data'] ) || empty ( $this->viewVars ['data'] )) { // empty response (should never happen!)
		                                                                                  // debug_print_backtrace();
			$this->_apiEr ( 'Invalid response - no data returned', false, true ); // log this error
		}
		
		// log API response for certain members
		if (isset ( $this->api ['member_big'] ) && in_array ( $this->api ['member_big'], explode ( ',', API_LOG_MEMBERS ) )) {
			ob_start ();
			echo "RESPONSE (" . $_SERVER ['REQUEST_URI'] . ") -- ";
			print_r ( $this->viewVars ['data'] );
			CakeLog::write ( 'api', ob_get_clean () );
		}
	}
	
	/**
	 * Check if the API request is authorized
	 *
	 * @return boolean whether there was correct mamber_big/token pair or email/password pair
	 */
	protected function _checkApiToken() {
		$public_api = array (
				array (
						'members',
						'api_register' 
				),
				array (
						'members',
						'api_login' 
				),
				array (
						'members',
						'api_forgot_password' 
				),
				array (
						'chat_messages',
						'api_send_push' 
				),
				array (
						'RegistrationCodes',
						'api_generateVerificationCode' 
				),
				array (
						'RegistrationCodes',
						'api_checkVerificationCode' 
				) 
		// array('members', 'api_CheckContactsprofile'),
				)
					

		// array('chat_messages', 'api_testpn'),
				;;

		foreach ( $public_api as $api ) {
			if ($this->request->params ['controller'] == $api [0] && $this->request->params ['action'] == $api [1]) { // request to public controller and action (check public API token)
			                                                                                                          // if (MemberAuthenticate::_validate_public_api_token($this->api['api_token'])) {
			                                                                                                          
				// $this->set_timezone(false, $this->Member);
				return true;
				
				// }
			}
		}
		
		/*
		 * INIZIO HACK PER GESTIONE CHIAMATE JSON $InputData = $this->request->input ( 'json_decode', true ); if (isset ( $InputData ['api_token'] ) && ! empty ( $InputData ['api_token'] )) { $this->api ['api_token'] = $InputData ['api_token']; } if (isset ( $InputData ['member_big'] ) && ! empty ( $InputData ['member_big'] )) { $this->api ['member_big'] = $InputData ['member_big']; } // * fine HACK PER GESTIONE CHIAMATE JSON debug($this->request); debug( $this->api ['api_token'] );
		 */
		if (isset ( $this->api ['api_token'] ) && ! empty ( $this->api ['api_token'] ) && isset ( $this->api ['member_big'] ) && $this->api ['member_big'] > 0) {
			
			$member = $this->Member->ApiToken->find ( 'first', array (
					'conditions' => array (
							'ApiToken.member_big' => ( int ) $this->api ['member_big'],
							'ApiToken.token' => $this->api ['api_token'],
							'ApiToken.expired >' => date ( 'Y-m-d H:i:s' )  // DboSource::expression('now()'),
										),
					'recursive' => 1 
			) );
		} elseif ((isset ( $this->api ['email'] ) && ! empty ( $this->api ['email'] ) && isset ( $this->api ['password'] ) && ! empty ( $this->api ['password'] )) || 		// auth using email/password
		(isset ( $this->api ['fb_token'] ) && ! empty ( $this->api ['fb_token'] ))) 		// auth using facebook
		{
			
			$member = $this->_apiLogin ();
			
			if ($this->request->params ['action'] != API . '_logout') {
				
				$this->_apiOk ( array (
						'Member' => $member ['Member'],
						'api_token' => $member ['ApiToken'] ['token'],
						'expired' => $member ['ApiToken'] ['expired'] 
				), 'login_data' );
			}
		}
		
		if (! empty ( $member )) {
			
			$this->logged = $member;
			
			$this->Member->ApiToken->save ( array (
					'ApiToken' => array (
							'id' => $member ['ApiToken'] ['id'],
							'expired' => date ( 'Y-m-d H:i:s', API_TOKEN_VALID ) 
					) 
			) );
			
			$this->Member->save ( array (
					'Member' => array (
							'big' => $member ['Member'] ['big'],
							'last_mobile_activity' => date ( 'Y-m-d H:i:s' )  // DboSource::expression('now()'),
										) 
			) );
			// $this->ChatCache->write($member['Member']['big'].'_last_mobile_activity', time());
			
			// $this->set_timezone(!is_null($member['Member']['timezone']) ? $member['Member']['timezone'] : false, $this->Member);
			return true;
		}
		
		// $this->set_timezone(false, $this->Member);
		
		$this->_apiEr ( 'Invalid "api_token" and / or "member_big"', __ ( 'There was an authentication error, try to log in again' ), false, array (
				'error_code' => '010' 
		) );
		return false;
	}
	
	/**
	 * Check required and optional variables in API request
	 *
	 * @param array $required
	 *        	API variables for this API endpoint
	 * @param array $optional
	 *        	API variables
	 */
	protected function _checkVars($required, $optional = array()) {
		$optional = array_merge ( $optional, array (
				'debug',
				'member_big',
				'api_token',
				'email',
				'password' 
		) );
		
		$missing = array ();
		
		foreach ( $required as $var ) {
			if (! isset ( $this->api [$var] )) {
				$missing [] = $var;
			}
		}
		
		if (! empty ( $missing )) {
			$this->_apiEr ( 'The following required API variables are missing: ' . implode ( ', ', $missing ), false, true );
		}
		
		foreach ( $this->api as $var => $val ) {
			
			if (! in_array ( $var, $required ) && ! in_array ( $var, $optional )) {
				$this->api_additional [$var] = $this->api [$var]; // unspecified variables are moved to $this->api_additional
				unset ( $this->api [$var] );
			}
		}
		
		return true;
	}
	
	/**
	 * Set data for response, continue with scriupt execution
	 *
	 * @param array $data
	 *        	array with data to return, set to false to erase all previously set data
	 * @return boolean true
	 */
	protected function _apiOk($data = array(), $key = 'data') {
		if (! isset ( $this->viewVars ['data'] )) {
			$this->viewVars ['data'] = array (
					'status' => 1,
					'data' => null 
			);
		}
		
		if ($data === false) {
			$this->viewVars ['data'] [$key] = null;
		} else {
			$this->viewVars ['data'] [$key] = isset ( $this->viewVars ['data'] [$key] ) ? array_merge ( $this->viewVars ['data'] [$key], $data ) : $data;
		}
		// $this->viewVars['data']['timestamp'] = time();
		return true;
	}
	
	/**
	 * Set error message as response, render error message imideatelly - shortcut for _apiError()
	 *
	 * @param string $msg        	
	 * @param string $user_msg        	
	 * @param boolean $log
	 *        	the error
	 * @param array $additional
	 *        	fields to add to the response
	 * @return boolean true
	 */
	protected function _apiEr($msg, $user_msg = false, $log = false, $additional = array()) {
		return $this->_apiError ( $msg, $user_msg, $log, $additional );
	}
	
	/**
	 * Set error message as response, render error message imideatelly
	 * Does not continue with script execution past this point
	 *
	 * @param string $msg        	
	 * @param string $user_msg        	
	 * @param boolean $log        	
	 * @param array $additional
	 *        	fields to add to the response
	 * @return boolean true
	 */
	protected function _apiError($msg, $user_msg = false, $log = false, $additional = array()) {
		if ($user_msg === true) {
			$user_msg = $msg;
		}
		
		$this->set ( 'data', array (
				'status' => 0,
				'error' => array (
						'msg' => $msg,
						'user_msg' => ! empty ( $user_msg ) ? $user_msg : __ ( 'We are sorry but something failed. Our team will take care of the issue as soon as possible.' ) 
				) 
		) );
		
		if (! empty ( $additional )) {
			$this->viewVars ['data'] ['error'] += $additional;
		}
		
		// log an error (if required)
		if ($log == true) {
			ob_start ();
			echo "RESPONSE (" . $_SERVER ['REQUEST_URI'] . ") -- ";
			print_r ( $this->viewVars ['data'] );
			CakeLog::write ( 'api_error', ob_get_clean () );
		}
		
		$this->render (); // render the error immideatelly
		$this->response->send ();
		$this->_stop ();
		
		return true;
	}
	
	/**
	 * Login call for API
	 *
	 * @return array with api_token, member_big and other member information
	 */
	protected function _apiLogin() {
		$login_method = 'password';
		$member = false;
		
		if (isset ( $this->api ['email'] ) && ! empty ( $this->api ['email'] ) && isset ( $this->api ['password'] ) && ! empty ( $this->api ['password'] )) { // authenticate user account (with email and password)
			
			if (is_numeric ( $this->api ['email'] )) {
				// asume login with phone number
				$data = $this->Member->findByPhone ( $this->api ['email'] );
				$this->api ['email'] = $data ['Member'] ['email'];
			} else {
				// assume email
				// do nothing
			}
			
			$member = $this->_authLogin ( array (
					'email' => $this->api ['email'],
					'password' => $this->api ['password'] 
			) );
			
			if (! $member) {
				return $this->_apiEr ( __ ( 'Invalid email or password' ), true );
			}
		} elseif (isset ( $this->api ['fb_token'] ) && ! empty ( $this->api ['fb_token'] )) { // authenticate user account (with facebook token)
			
			$login_method = 'facebook';
			
			$this->Fb->_setToken ( $this->api ['fb_token'] );
			$fb_user = $this->Fb->user ();
			
			if (empty ( $fb_user ) || empty ( $fb_user ['id'] )) {
				return $this->_apiEr ( __ ( 'Your Facebook account could not be accessed. Check your permissions for Haamble application from your Facebook.' ), true );
			}
			
			$member = $this->_authLogin ( array (
					'fb_id' => $fb_user ['id'] 
			) );
			
			if (! $member) {
				if (! $this->_register_fb ( $fb_user )) {
					return $this->_apiEr ( __ ( 'Registration via Facebook failed' ), true );
				}
			}
		} else { // no variables to identify user
			
			return $this->_apiEr ( __ ( 'Please specify email or password' ), true );
		}
		
		return $member;
	}
	
	/**
	 * Find member based on specified conditions
	 * This function replaces $this->Auth->login() call with minor modifications
	 * Does not use session when logging in, only returns the member data
	 *
	 * @param array $search_conditions
	 *        	to use when finding member
	 * @return mixed array with member data or bool (false) on failure
	 */
	private function _authLogin($search_conditions = array()) {
		$check_password = false;
		
		if (empty ( $search_conditions )) { // conditions for finding member not specified
			return false;
		}
		
		if (isset ( $search_conditions ['password'] )) { // do not make find with password, check it later instead
			$check_password = $search_conditions ['password'];
			unset ( $search_conditions ['password'] );
		}
		
		unbindAllBut ( $this->Member, array (
				'ApiToken' 
		) );
		$this->Member->recursive = 1;
		$member = $this->Member->find ( 'first', array (
				'conditions' => $search_conditions,
			/*'fields' => array(
				'ApiToken.token', 'ApiToken.expired',
				'Member.id', 'Member.name', 'Member.surname', ''
			)*/
		) );
		
		if (empty ( $member )) { // member not found based on conditions
		                         // print_r($search_conditions);
			return false;
		}
		
		if ($check_password != false) { // check password if it was in original conditions
			App::uses ( 'HaambleAuthenticate', 'Controller/Component/Auth' );
			$password_hash = HaambleAuthenticate::hash ( $check_password, $member ['Member'] ['salt'] );
			if ($member ['Member'] ['password'] != $password_hash) {
				return false;
			}
		}
		
		// $this->set_timezone(!is_null($member['Member']['timezone']) ? $member['Member']['timezone'] : false, $this->Member);
		
		if (empty ( $member ['ApiToken'] )) { // api token not valid or not created
			
			App::uses ( 'HaambleAuthenticate', 'Controller/Component/Auth' );
			$member ['ApiToken'] = array (
					'member_big' => $member ['Member'] ['big'],
					'token' => HaambleAuthenticate::_generate_api_token ( $member ['Member'] ['email'] ),
					'expired' => date ( 'Y-m-d H:i:s', API_TOKEN_VALID ) 
			);
			
			$this->Member->ApiToken->save ( $member ['ApiToken'] );
			
			$member ['ApiToken'] ['id'] = $this->Member->ApiToken->id;
		} else {
			
			$member ['ApiToken'] = reset ( $member ['ApiToken'] );
		}
		
		unset ( $member ['Member'] ['password'] );
		unset ( $member ['Member'] ['salt'] );
		
		if ($member ['Member'] ['photo_updated'] > 0) {
			$member ['Member'] ['profile_picture'] = $this->FileUrl->profile_picture ( $member ['Member'] ['big'], $member ['Member'] ['photo_updated'] );
		} else {
			$sexpic=2;
			if($data ['Member']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$data ['Member']['profile_picture'] = $this->FileUrl->profile_picture ( $sexpic );
			
		}
		
		return $member;
	}
	
	/**
	 * Register a new user based on facebook account
	 *
	 * @param array $fb_user
	 *        	data returned by Facebook
	 * @return multitype:NULL string unknown Ambigous <NULL, unknown> Ambigous <>
	 */
	private function _register_fb($fb_user) {
		$member = array (
				'password' => null,
				'fb_id' => $fb_user ['id'],
				'tw_id' => null,
				'type' => MEMBER_MEMBER,
				'status' => ACTIVE 
		);
		
		if (isset ( $fb_user ['first_name'] )) {
			$member ['name'] = $fb_user ['first_name'];
		} else {
			$member ['name'] = '';
		}
		
		if (isset ( $fb_user ['last_name'] )) {
			$member ['surname'] = $fb_user ['last_name'];
		} else {
			$member ['surname'] = '';
		}
		
		if (isset ( $fb_user ['middle_name'] )) {
			$member ['middle_name'] = $fb_user ['middle_name'];
		} else {
			$member ['middle_name'] = null;
		}
		
		if (isset ( $fb_user ['email'] )) {
			$member ['email'] = $fb_user ['email'];
		} else {
			$member ['email'] = null;
		}
		
		/*
		 * if (isset($fb_user['timezone'])) { $member['timezone'] = $fb_user['timezone']; }
		 */
		
		if (isset ( $fb_user ['birthday'] )) {
			$bday = explode ( '/', $fb_user ['birthday'] );
			$member ['birth_date'] = $bday [2] . '-' . $bday [0] . '-' . $bday [1];
		}
		
		$location = isset ( $fb_user ['location'] ['name'] ) ? $fb_user ['location'] ['name'] : (isset ( $fb_user ['hometown'] ['name'] ) ? $fb_user ['hometown'] ['name'] : null);
		if ($location !== null) {
			$location = explode ( ', ', $location );
			$member ['address_town'] = $location [0];
		}
		
		unset ( $this->Member->validate ['password'] );
		
		$this->Member->create ();
		$this->Member->set ( array (
				'Member' => $member 
		) );
		$this->Member->save ();
		
		// handle errors
		if (! empty ( $this->Member->validationErrors )) {
			
			$errors = $this->Member->validationErrors;
			
			if (isset ( $errors ['email'] )) { // duplicate email
				return $this->_apiEr ( __ ( 'You tried to register via Facebook that is connected to an email address that already exists in our database and that email is not connected to your Facebook account in our system. Try to login with your email and password.' ), true );
			}
			
			$msg = array ();
			foreach ( $errors as $col => $er ) {
				$msg [] = $col . ': ' . implode ( ', ', $er );
			}
			
			return $this->_apiEr ( __ ( 'There was an error connecting via Facebook: %s', implode ( '; ', $msg ) ), true );
		}
		
		return $this->_authLogin ();
	}
	
	/**
	 * Add event photo URL to data
	 *
	 * @param array $data        	
	 * @return array $data with photos for events
	 */
	protected function _addEventPhotoUrls($data) {
		if (empty ( $data )) {
			return $data;
		}
		
		if (array_keys ( $data ) !== range ( 0, count ( $data ) - 1 )) {
			$data = array (
					$data 
			);
			$assoc = true;
		}
		
		foreach ( $data as $key => $val ) {
			
			if (isset ( $val ['DefaultPhoto'] ['big'] ) && $val ['DefaultPhoto'] ['big'] > 0) { // add URLs to default photos
				$data [$key] ['Event'] ['photo'] = $this->FileUrl->event_photo ( $val ['Event'] ['big'], $val ['Gallery'] [0] ['big'], $val ['DefaultPhoto'] ['big'], $val ['DefaultPhoto'] ['original_ext'] );
			} else {
				$data [$key] ['Event'] ['photo'] = null;
			}
			
			unset ( $data [$key] ['Gallery'] );
			unset ( $data [$key] ['DefaultPhoto'] );
		}
		
		// TODO: replace hidden event names with place name?
		
		if (isset ( $assoc ) && $assoc) {
			$data = reset ( $data );
		}
		
		return $data;
	}
	
	/**
	 * Add place photo URL to data
	 *
	 * @param array $data        	
	 * @return array $data with photos for places
	 */
	protected function _addPlacePhotoUrls($data) {
		if (empty ( $data )) {
			return $data;
		}
		
		if (array_keys ( $data ) !== range ( 0, count ( $data ) - 1 )) {
			$data = array (
					$data 
			);
			$assoc = true;
		}
		
		foreach ( $data as $key => $val ) {
			
			if (isset ( $val ['DefaultPhoto'] ['big'] ) && $val ['DefaultPhoto'] ['big'] > 0) { // add URLs to default photos
				if (isset ( $val ['DefaultPhoto'] ['status'] ) && $val ['DefaultPhoto'] ['status'] != DELETED) { // add URLs to default photos
					$data [$key] ['Place'] ['photo'] = $this->FileUrl->place_photo ( $val ['Place'] ['big'], $val ['Gallery'] [0] ['big'], $val ['DefaultPhoto'] ['big'], $val ['DefaultPhoto'] ['original_ext'] );
				} else {
					$data [$key] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['category_id'] );
				}
			} else {
				$data [$key] ['Place'] ['photo'] = $this->FileUrl->default_place_photo ( $val ['Place'] ['category_id'] );
			}
			
			unset ( $data [$key] ['Gallery'] );
			unset ( $data [$key] ['DefaultPhoto'] );
		}
		
		// TODO: replace hidden event names with place name?
		
		if (isset ( $assoc ) && $assoc) {
			$data = reset ( $data );
		}
		
		return $data;
	}
	
	/**
	 * Add member photo URL to data
	 *
	 * @param array $data        	
	 * @return array $data with photos for members
	 */
	protected function _addMemberPhotoUrls($data) {
		if (empty ( $data )) {
			return $data;
		}
		
		if (array_keys ( $data ) !== range ( 0, count ( $data ) - 1 )) {
			$data = array (
					$data 
			);
			$assoc = true;
		}
		
		foreach ( $data as $key => $val ) {
			
			if (isset ( $val ['Photo'] ['big'] ) && $val ['Photo'] ['big'] > 0) { // add URLs to default photos
				if (empty ( $val ['Gallery'] ['event_big'] )) {
					$data [$key] ['photo'] = $this->FileUrl->place_photo ( $val ['Gallery'] ['place_big'], $val ['Gallery'] ['big'], $val ['Photo'] ['big'], $val ['Photo'] ['original_ext'] );
					$data [$key] ['photo_big'] = $val ['Photo'] ['big'];
				} else {
					$data [$key] ['photo'] = $this->FileUrl->event_photo ( $val ['Gallery'] ['event_big'], $val ['Gallery'] ['big'], $val ['Photo'] ['big'], $val ['Photo'] ['original_ext'] );
					$data [$key] ['photo_big'] = $val ['Photo'] ['big'];
				}
			} else {
				$data [$key] ['photo'] = null;
			}
			
			unset ( $data [$key] ['Gallery'] );
			unset ( $data [$key] ['Photo'] );
		}
		
		// TODO: replace hidden event names with place name?
		
		if (isset ( $assoc ) && $assoc) {
			$data = reset ( $data );
		}
		
		return $data;
	}
}
