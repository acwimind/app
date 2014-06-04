<?php

App::uses('BaseAuthenticate', 'Controller/Component/Auth');

class HaambleAuthenticate extends BaseAuthenticate {

	public function login() {
		
	}
	
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		
		$userModel = $this->settings['userModel'];	//model to authentificate on
		list($plugin, $model) = pluginSplit($userModel);

		$fields = $this->settings['fields'];
		if (empty($request->data[$model])) {	//empty data for given model
			return false;
		}
		if (
			empty($request->data[$model][$fields['username']]) ||
			empty($request->data[$model][$fields['password']])
		) {
			return false;
		}
		
		$conditions = array(	//find user by username and password (or other conditions)
			$model . '.' . $fields['username']			=> $request->data[$model][$fields['username']],
			$model . '.' . $fields['password'] . ' != ' => ''
		);
		if (!empty($this->settings['scope'])) {	//scope: filter by an additional condition
			$conditions = array_merge($conditions, $this->settings['scope']);
		}
		$result = ClassRegistry::init($userModel)->find('first', array(
			'conditions' => $conditions,
		));

		if (empty($result) || empty($result[$model])) {
			return false;
		}


		if ($fields['salt'] != false) {	//hash password if we have salt
			$password_hash = $this->hash($request->data[$model][$fields['password']], $result[$model][$fields['salt']]);
		} else {
			$password_hash = $request->data[$model][$fields['password']];
		}

		if ($result[$model][$fields['password']] != $password_hash) {	//check password hash
			return false;
		}
		
		unset($result[$model][$fields['password']]);
		return $result[$model];
	}
	
	public static function hash($password, $salt) {
		
		/*
		 * hash algorhythms (produced hash lenght: names):
		 *  128: sha512; whirlpool; salsa10; salsa20
		 *   96: sha384
		 *   80: ripemd320
		 *   64: sha256; snefru; snefru256; gost; haval256,3; haval256,4; haval256,5
		 */
		return hash(
			'sha512',
			$password . $salt . Configure::read('Security.salt')
		);
		
	}
	
	public static function generateSalt($email='') {
		
		return hash(
			'md5', 
			date('YmdhisW') . uniqid(rand()) . $email . Configure::read('Security.salt')
		);
		
	}
	
	public static function _generate_api_token($email) {
		
		return hash(
			'sha512',
			time() . uniqid(rand()) . date('WYhsimd') . $email . Configure::read('Security.salt')
		);
		
	}
	
}
