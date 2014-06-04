<?php
class ExtraInfosController extends AppController{
	var $uses = array('ExtraInfos', 'Countries');
	public $helpers = array('Session', 'Html', 'Form');


	public function api_save()
	{
		$this->_checkVars(array('ExtraInfos'));

		try {

			$this->_add($this->api['ExtraInfos']);
			$this->_apiOk(array(
					'added' => true,
			));


		} catch (ErrorEx $e) {

			$this->_apiEr( $e->getMessage() );

		}

	}

	public function api_search()
	{
		$this->_checkVars(array('ExtraInfos'));
	
		try {
			$arra=$this->api['ExtraInfos'];
			$result = $arra['food'];
		//	$result =var_dump($this->api['ExtraInfos']);
			$result = $this->ExtraInfos->getMemberByExtraInfos($this->api['ExtraInfos']);
			//$result =$this->api['ExtraInfos'];
			
			$dbo = $this->ExtraInfos->getDatasource();
			$logs = $dbo->getLog();
			$lastLog = end($logs['log']);
			$this->_apiOk($lastLog);
	
		} catch (ErrorEx $e) {
	
			$this->_apiEr( $e->getMessage() );
	
		}
	
	}
	

	private function _add( $api_data_to_save ) {

		$api_data_to_save['member_big'] = $this->logged['Member']['big'];

		$extraInfos = $this->ExtraInfos;/* @var $extraInfos ExtraInfos */
		$result = $extraInfos->save( $api_data_to_save );
		if ($result !== false) {
		return true;
		} else {
		throw new ErrorEx(__('Error saving extra info.'));
			}

	}


	public function index()
	{
		$extraInfos = $this->ExtraInfos;/* @var $extraInfos ExtraInfos */
		$this->logged = $this->Member->findByBig( $this->Auth->user('big') );//why oh why it won't work in AppWebController


		if ( $this->request->is( 'post' ) ) {
			$extraInfos->create();
			$this->request->data['ExtraInfos']['member_big'] = $this->logged['Member']['big'];
			if ($extraInfos->save( $this->request->data )) {
				$this->Session->setFlash(__('Your information has been saved.'), 'flash/success');

				return $this->redirect ( array (
						'action' => 'index'
				) );
			}

			$this->Session->setFlash(__('Unable to add your post.'), 'flash/error');
		}

		$this->request->data = $extraInfos->findByMemberBig( $this->logged['Member']['big'] );//prefill form, after save


		$countries = $this->Countries;/* @var $countries Countries */
		$country_list = $countries->getAllCountries();

		$this->set('countries', $country_list);
	}
}
