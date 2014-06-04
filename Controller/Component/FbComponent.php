<?php

App::import('Vendor', 'Facebook/facebook');

class FbComponent extends Component {

	var $token = null;
	var $config = array(
		'appId' => FACEBOOK_APP_ID,
		'secret' => FACEBOOK_APP_SECRET,
		'redirectUrl' => FACEBOOK_REDIRECT_URL,
	);
	private $fb;

	public function initialize(Controller $controller) {
		$this->controller = $controller;
		parent::initialize($controller);
	}

	public function __construct() {
		Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = false;
		$this->fb = new Facebook($this->config);
	}

	public function login() {
		return $this->controller->redirect('https://www.facebook.com/dialog/oauth?' . 
			'client_id=' . $this->config['appId'] . 
			'&redirect_uri=' . $this->config['redirectUrl'] . 
			'&scope=email' . 
			'&state=' . time()
		);
	}

	public function exchangeToken($code) {
		$response = $this->_api('/oauth/access_token?' .
    		'client_id=' . $this->config['appId'] . 
   			'&redirect_uri=' . $this->config['redirectUrl'] . 
   			'&client_secret=' . $this->config['secret'] . 
   			'&code=' . $code
		);
		$this->_setToken($response);
	}
	
	public function initToken($code, $redirect_url=null) {
		return $this->_api('/oauth/access_token', 'GET', array(
			'redirect_uri' => $redirect_url!=null ? $redirect_url : FACEBOOK_REDIRECT_URL,
			'client_id' => FACEBOOK_APP_ID,
			'client_secret' => FACEBOOK_APP_SECRET,
			'code' => $code
		));
	}
	
	public function _setToken($token) {
		$this->token = $token;
		$this->fb->setAccessToken($token);
	}
	
	public function friends() {
		$user_id = $this->user_id();
		$friends = $this->_api('/' . $user_id . '/friends?limit=1000000','GET');
		return $friends['data'];
	}
	
	public function user() {
		return $this->_api('/me','GET');
	}
	
	public function user_id() {
		$user = $this->user();
		return $user['id'];
	}
	
	public function friend($profile_id) {
		return $this->_api("/$profile_id",'GET');
	}
	
	public function profile_picture($fb_id) {
		return 'http://graph.facebook.com/'.$fb_id.'/picture';
	}
	
	public function post($profile_id, $params=array()) {
		
		if (empty($params)) {
			return false;
		}
		
		return $this->_api("/$profile_id/feed", 'POST', $params);
		
	}
	
	private function _api() {
		
		$args = func_get_args();
		return call_user_func_array(array($this->fb, 'api'), $args);
		
		/*try {
			$result = call_user_func_array(array($this->fb, 'api'), func_get_args());
		} catch(FacebookApiException $e) {
			//$this->_setToken( $this->fb->getAccessToken() );
			//$result = call_user_func_array(array($this->fb, 'api'), func_get_args());
			//debug($result);
			
			$url = "https://www.facebook.com/dialog/oauth?". "client_id=" . $this->config['appId']. "&redirect_uri=" . urlencode($this->config['redirectUrl']);
			
			while($url != null) {
				echo '<p>'.$url;
				$c = curl_init();
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($c, CURLOPT_URL, $url);
				curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3");
				$content = curl_exec($c);
				$info = curl_getinfo($c);
				curl_close($c);
				
				debug($info);
				debug($content);
				
				if (!preg_match('~^'.preg_quote($this->config['redirectUrl'], '~').'~', $info['redirect_url'])) {
					$url = $info['redirect_url'];
				} else {
					$url_code = $info['redirect_url'];
				}
				
			}
			
			debug($url_code);
			exit;
		}*/
	}
	
}