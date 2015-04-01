<?php

class IgnoresController extends AppController {
	
	var $uses = array('MemberSetting');
	
	/**
	 * Return list of ignored members
	 */
	public function api_list()
	{
		
		$memBig = $this->logged['Member']['big'];
		
		unbindAllBut($this->MemberSetting, array('Recipient'));
		
		$params = array(
			'conditions' => array(
				'MemberSetting.from_big' => $memBig,
				'MemberSetting.chat_ignore' => 1
			 ),
			 'fields' => array(
			 	'MemberSetting.id',
			 	'Recipient.big',
			 	'Recipient.name',
			 	'Recipient.middle_name',
			 	'Recipient.surname',
			 	'Recipient.photo_updated',
			 	'MemberSetting.chat_ignore',
			 )
		);
		$ignores = $this->MemberSetting->find('all', $params);
		
		// Add photos
		foreach($ignores as $key=>$val) {
			if ($val['Recipient']['photo_updated'] > 0) {
				$ignores[$key]['Recipient']['photo'] = $this->FileUrl->profile_picture($val['Recipient']['big'], $val['Recipient']['photo_updated']);
			}  else  {
			$sexpic=2;
			if($ignores[$key]['Recipient']['sex']=='f' )
			{
				$sexpic=3;
			}
				
			$ignores[$key]['Recipient']['photo'] = $this->FileUrl->profile_picture ( $sexpic );
			
		}
			unset($ignores[$key]['Recipient']['photo_updated']);
		}
		$this->Util->transform_name($ignores);
		$bms = array('ignores' => $ignores);
		
		$this->_apiOk($bms);
		
	}
	
	/**
	 * Add member to ignore list
	 */
	public function api_add() {
		
		$this->_checkVars(array('ignored_big'));
		
		try {
			
			$this->_add($this->api['ignored_big']);
			$this->_apiOk();

		} catch (ErrorEx $e) {

			$this->_apiEr( $e->getMessage() );

		}
		
	}

	private function _add($ignBig) {
		
		$memBig = $this->logged['Member']['big'];

		$isOnList = $this->MemberSetting->isOnIgnoreList($memBig, $ignBig);
		
		if ($isOnList) {
			throw new ErrorEx(__('User is already on the ignore list.'));
		}
		
		$result = $this->MemberSetting->addToIgnoreList($memBig, $ignBig);
		
		if ($result !== false) {
			return true;
		} else {
			throw new ErrorEx(__('Error occured. Member not added to ignore list.'));
		}

	}
	
	/**
	 * Remove member/s from ignore list of this member
	 */
	public function api_remove() {
		
		$this->_checkVars(array('ignored_big'));
		
		try {
			
			$this->_remove($this->api['ignored_big']);
			$this->_apiOk();

		} catch (ErrorEx $e) {

			$this->_apiEr( $e->getMessage() );

		}
		
	}

	private function _remove($ignBig) {

		$memBig = $this->logged['Member']['big'];

		// error handling for invalid format (i.e. other than id,id2,id3,...,idn)
		if (!is_numeric($ignBig)) {
			$ignBigs = explode(',', $ignBig);
			foreach ($ignBigs as $key=>$ibig) {
				if (!is_numeric($ibig)) {
					unset($ignBigs[$key]);
				}
			}
			$ignBig = $ignBigs;
		} 

		try {
			$result = $this->MemberSetting->removeFromIgnoreList($memBig, $ignBig);
		} catch (Exception $e) {
			debug($e);exit;
			$result = false;
		}

		if ($result !== false) {
			return true;
		} else {
			throw new ErrorEx(__('Error occured. Member not removed from ignore list.'));
		}

	}

	public function add($ignore_big=0) {
		
		try {
			
			$this->_add($ignore_big);
			$this->Session->setFlash(__('Utente aggiunto alla black list'), 'flash/success');

		} catch (ErrorEx $e) {

			$this->Session->setFlash( $e->getMessage(), 'flash/error' );

		}

		return $this->redirect(array('controller' => 'members', 'action' => 'public_profile', $ignore_big));
		
	}

	public function remove($ignore_big=0) {

		try {
			
			$this->_remove($ignore_big);
			$this->Session->setFlash(__('Utente rimosso dalla black list'), 'flash/success');

		} catch (ErrorEx $e) {

			$this->Session->setFlash( $e->getMessage(), 'flash/error' );

		}

		return $this->redirect(array('controller' => 'members', 'action' => 'public_profile', $ignore_big));

	}

	public function index() {
		
		unbindAllBut($this->MemberSetting, array('Recipient'));
		$ignores = $this->MemberSetting->find('all', array(
			'conditions' => array(
				'MemberSetting.from_big' => $this->logged['Member']['big'],
				'MemberSetting.chat_ignore' => 1
			 ),
			 'fields' => array(
			 	'MemberSetting.id',
			 	'Recipient.big',
			 	'Recipient.name',
			 	'Recipient.middle_name',
			 	'Recipient.surname',
			 	'Recipient.photo_updated',
			 	'MemberSetting.chat_ignore',
			 )
		));
		
		$this->set('ignores', $ignores);
		
	}
	
}