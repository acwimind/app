<?php

/**
 * This is AppController class for web requests (both frontend and admin backend)
 */

App::uses('Controller', 'Controller');

class AppWebController extends Controller {

	public $components = array(
		'Auth', 'Session', 'RequestHandler', 'Uploader.Upload', 'Fb',
		//'DebugKit.Toolbar', 'ChatCache',
	);
	public $helpers = array('Session', 'Html', 'Form', 'AdvForm', 'Img', 'Map');
	public $uses = array('Member', 'Operator', 'Advert');

	public $paginate = array(
		'limit' => 10,
	);

	/**
	 * Logged in or not, default false
	 *
	 * @var bool or array
	 */
	private $logged = false;

	/**
	 * Functionality to be executed on every single request
	 */
	public function beforeFilter() {
		$memberModel = $this->Member;/* @var $memberModel Member */
		$db = $memberModel->getDataSource();/* @var $db DboSource */
		$db->fetchAll("SET timezone = 'Europe/Rome'");
		$a = $db->fetchAll("SHOW timezone");
		date_default_timezone_set('Europe/Rome');

		parent::beforeFilter();

		$this->lang = LANG_EN;	//TODO language select for web
		if (!defined('CURRENT_LANG')) {
			define('CURRENT_LANG', $this->lang);
		}
		
		if ($this->Session->check('Config.language')) {
			Configure::write('Config.language', $this->Session->read('Config.language'));
		}

		$this->isApi = false;
		$this->set('isApi', false);

		if ($this->params['prefix'] == 'admin') {	//layout for backend interface
			$this->layout = 'backend';
		}

		$this->_setupAuth();

		$this->_setAjax();

		$this->_getLoggedMember();

		$this->_checkAccess();

		$this->_topMenu();

		return true;

	}

	/**
	 * Setup Auth component settings
	 * @return bool true
	 */
	private function _setupAuth() {

		$this->Auth->authenticate = array(
			'Haamble' => array(
				'userModel' => 'Member',
				'fields' => array('username' => 'email', 'password' => 'password', 'salt' => 'salt'),
				'scope' => array('Member.status' => 1),
			),
		);

		$this->Auth->loginAction = array('controller' => 'members', 'action' => 'login');
		$this->Auth->logoutAction = array('controller' => 'members', 'action' => 'logout');
		$this->Auth->loginRedirect = '/home';
		$this->Auth->logoutRedirect = '/home';

		return true;

	}

	/**
	 * Set variables to indicate if the request is done via ajax or not
	 * @return bool true
	 */
	private function _setAjax() {

		if ($this->RequestHandler->isAjax()) {	//request is via AJAX
			$this->layout = 'ajax';
			$this->isAjax = true;
			$this->set('isAjax', true);
		} else {	//not an AJAX request
			$this->isAjax = false;
			$this->set('isAjax', false);
		}

		return true;

	}

	/**
	 * Find if there is a member logged in
	 * Sets $this->logged for controllers and $logged for views
	 * @return [type] [description]
	 */
	private function _getLoggedMember() {

		$member = $this->Member;/* @var $member Member */
		$member->recursive = -1;
		$this->logged = $this->Member->findByBig( $this->Auth->user('big') );

		if (empty($this->logged) || $this->logged == false) {

			$this->logged = false;

		} else {	//current checkin of current member

			$checkinModel = $this->Member->Checkin;/* @var $checkinModel Checkin */
			$checkin = $checkinModel->find('first', array(
				'conditions' => array(
					'Checkin.member_big' => $this->logged['Member']['big'],
					//'Checkin.created <' => date('c'),
					'or' => array(
						'Checkin.checkout' => null,
						'Checkin.checkout >' => date('c'),
					),
				),
				'order' => array('Checkin.created' => 'desc'),
				'recursive' => -1,
			));

			if ($checkin != false) {
				$this->logged['Checkin'] = $checkin['Checkin'];
			} else {
				$this->logged['Checkin'] = array(
					'big' => null,
					'event_big' => null,
				);
			}

			//set last web activity time
			$member->create();
			$member->save(array(
				'big' => $this->logged['Member']['big'],
				'last_web_activity' => date('Y-m-d H:i:s'),	//DboSource::expression('now()'),
			));
			//$this->ChatCache->write($this->logged['Member']['big'].'_last_web_activity', time());

		}

		if ($this->Session->read('fb_token') != null) {
			$fb = $this->Fb;/* @var $fb FbComponent */
			$fb->_setToken( $this->Session->read('fb_token') );
		}

		$this->set('logged', $this->logged);

		return true;

	}

	/**
	 * Check access to current URL
	 */
	private function _checkAccess() {

		//list of URLs available to guest users, format: array(controller, action)
		//TODO: add all public URLs
		$public_pages = array(
			array('cms_entries', 'home'),
			array('cmsEntries', 'display'),
			array('cmsEntries', 'm_display'),
			array('members', 'login'),
			array('members', 'login_fb'),
			array('members', 'register'),
			array('members', 'admin_login'),
			array('members', 'forgot_password'),
			array('members', 'change_password'),
			array('members', 'registercheck'),	
				array('landing', 'index')
		);

		$here = array($this->request->controller, $this->request->action);
		//not logged
		if (!$this->logged) {

			if (!in_array($here, $public_pages)) {	//check if this is public URL
				if ($here != array('home', 'index')) {
					$this->Session->setFlash(__('Access denied, please login'), 'flash/error');
				}
				return $this->redirect(array('controller' => 'cms_entries', 'action' => 'home', 'admin' => false));
			}
		}

		//not admin
		elseif ($this->logged['Member']['type'] != MEMBER_ADMIN && $this->params['prefix'] == 'admin') {

			$this->Session->setFlash(__('Access denied, you are not administrator'), 'flash/error');
			return $this->redirect('/');

		}

		//not operator and not admin
		elseif (!in_array($this->logged['Member']['type'], array(MEMBER_OPERATOR, MEMBER_ADMIN)) && $this->params['prefix'] == 'operator') {

			$this->Session->setFlash(__('Access denied, you are not operator'), 'flash/error');
			return $this->redirect('/');

		}



	}

	/**
	 * Returns array with main menu
	 * @return array with top menu for admin
	 */
	private function _topMenu() {

		$top_menu = array();

		if ($this->logged == false) {

			$top_menu += array(

			);

		} elseif ($this->logged['Member']['type'] == MEMBER_ADMIN) {

			$top_menu += array(
				__('Users') 		=> array('controller' => 'members', 'action' => 'index', 'admin' => true),
				__('Places') 		=> array('controller' => 'places', 'action' => 'index', 'admin' => true),
				__('Events') 		=> array('controller' => 'events', 'action' => 'index', 'admin' => true),
				__('Categories') 	=> array('controller' => 'categories', 'action' => 'index', 'admin' => true),
				__('Regions') 		=> array('controller' => 'regions', 'action' => 'index', 'admin' => true),
//				__('Photos') 		=> array('controller' => 'photos', 'action' => 'index', 'admin' => true),
				__('Signalations') 	=> array('controller' => 'signalations', 'action' => 'index', 'admin' => true),
				__('Website CMS') 	=> array('controller' => 'cms_entries', 'action' => 'index', 'admin' => true),
				__('Advertisments CMS') 	=> array('controller' => 'adverts', 'action' => 'index', 'admin' => true),
			);

		} else {	//MEMBER_OPERATOR, MEMBER_MEMBER

			$top_menu += array(

			);

		}

		$this->set('top_menu', $top_menu);

		return $top_menu;

	}

	/**
	 * Select places for right sidebar in frontend (advertising)
	 * @return boolean
	 */
	protected function _sidebarPlaces() {

		$adverts = $this->Advert->find('all', array(
			'conditions' => array(
				'Advert.status ' => 1
			),
			'order' => array('random()' => 'asc'),
			'limit' => 5,
		));
		$this->set('sidebar_places', $adverts);

		return true;

	}

	protected function _checkOperatorPermissions($current_place_big=0) {

		$place_bigs = $this->Operator->OperatorsPlace->find('list', array(
			'conditions' => array(
				'OperatorsPlace.operator_big' => $this->logged['Member']['big'],
			),
			'fields' => array('OperatorsPlace.place_big', 'OperatorsPlace.place_big'),
			'recursive' => -1,
		));
		if (!in_array($current_place_big, $place_bigs)) {
			$this->Session->setFlash(__('You do not have permissions access this page'), 'flash/error');
			return $this->redirect('/');
		}

	}

	protected function _savedFilter($vars=array()) {

		if (isset($this->params->query['cancel_filter']) && $this->params->query['cancel_filter'] == true) {

			$this->Session->delete($this->params['controller'].'.admin_filter');

			return $this->redirect($this->here);

		} else {

			$filter_vars = $this->Session->read($this->params['controller'].'.admin_filter');
			$has_filter = false;

			foreach($vars as $var) {

				if (isset($this->params->query[ $var ])) {
					$filter_vars[ $var ] = $this->params->query[ $var ];
					$has_filter = true;
				} elseif (isset($filter_vars[ $var ])) {
					$this->params->query[ $var ] = $filter_vars[ $var ];
					$has_filter = true;
				}

			}

			$this->Session->write($this->params['controller'].'.admin_filter', $filter_vars);

			$this->set('has_filter', $has_filter);

		}

	}


	public function webroot() {
		chdir(WWW_ROOT . DS . 'chat' );
		return require_once  WWW_ROOT . DS . 'chat' . DS . 'index.php';
	}

}
