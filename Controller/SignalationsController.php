<?php

class SignalationsController extends AppController {

	var $uses = array('Signalation', 'MemberSetting', 'ChatMessage', 'Photo', 'Gem');
	public $components = array('FileUrl');

	public function admin_index() {

		$this->_savedFilter(array('gemtype', 'sigtype', 'status', 'srchname', 'CreatedFromDate', ''));

		$conditions = array();
//		print_r('<pre>');
//		print_r($this->params->query);
//		print_r('</pre>');
//		die();

    	if (isset($this->params->query['gemtype']) && !empty($this->params->query['gemtype'])) {
    		$conditions['Gem.type'] = $this->params->query['gemtype'];
    	}
    	if (isset($this->params->query['sigtype']) && is_numeric($this->params->query['sigtype'])) {
    		$conditions['Signalation.type'] = $this->params->query['sigtype'];
    	}
    	if (isset($this->params->query['status']) && is_numeric($this->params->query['status'])) {
    		$conditions['Signalation.status'] = $this->params->query['status'];
    	}
		if (isset($this->params->query['srchname']) && !empty($this->params->query['srchname'])) {
    		$conditions['OR'] = array('Member.name ILIKE' => '%' . $this->params->query['srchname'] . '%',
    			'Member.middle_name ILIKE' => '%' . $this->params->query['srchname'] . '%',
    			'Member.surname ILIKE' => '%' . $this->params->query['srchname'] . '%') ;
    	}
		if (isset($this->params->query['CreatedFromDate']) && !empty($this->params->query['CreatedFromDate'])) {
    		$conditions['Signalation.created >='] = $this->params->query['CreatedFromDate'] . ' ' . $this->params->query['CreatedFromTime'];
    	}
    	if (isset($this->params->query['CreatedToDate']) && !empty($this->params->query['CreatedToDate'])) {
    		$conditions['Signalation.created <='] = $this->params->query['CreatedToDate'] . ' ' . $this->params->query['CreatedToTime'];
    	}

    	$this->request->data['Signalation'] = $this->params->query;

		unbindAllBut($this->Signalation, array('Member'), false);
		$this->paginate = array(
			'fields' => array(
				'Gem.*', 'Member.*', 'Signalation.*',
			),
			'joins' => array(
		        array(
		            'alias' => 'Gem',
		            'table' => 'gems',
		            'type' => 'INNER',
		            'conditions' => 'Signalation.gem_big = Gem.big'
		        )
		    ),
		    'order' => array(
		    	'Signalation.created' => 'desc'
		    )
		);
		$data = $this->paginate($this->Signalations, $conditions);
		$this->set('data', $data);

	}
	/*
	public function admin_add() {
		$this->admin_edit();
		$this->render('admin_edit');
	}

	public function admin_edit($id=0) {

		if ($this->request->is('post') || $this->request->is('put')) {

			if ($this->Signalation->saveAll($this->request->data, array('validate' => 'first'))) {
				$this->Session->setFlash(__('Signalation saved'), 'flash/success');
				return $this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('Error while saving Signalation'), 'flash/error');
			}

		} elseif ($id > 0) {

			$this->request->data = $this->Signalation->findById($id);

		}

	}
	*/
	public function admin_delete($id) {

		$this->Signalation->save(array(
			'id' => $id,
			'status' => DELETED,
		));

		$this->Session->setFlash(__('Segnalazione cancellata'), 'flash/success');
		return $this->redirect(array('action' => 'index'));

	}

	public function admin_solved($id)
	{
		$this->Signalation->save(array(
			'id' => $id,
			'status' => INACTIVE,
		));

		$this->Session->setFlash(__('Segnalazione contrassegnata come risolta'), 'flash/success');
		return $this->redirect(array('action' => 'index'));
	}

	public function api_add()
	{
        /* $member_big = big del member segnalatore
           $type = tipo di segnalazione 1=chat, 2=foto, 3=commento 
           $id_object = id del messaggio, commento o foto
           $reason = 0 = FLAG_SPAM; 1 = FLAG_INAPPROPRIATE; 2 = FLAG_OFFENSIVE; 
           */ 

		$this->_checkVars(array('type', 'id_object','reason'));
                      

		//$this->logged = $this->Member->findByBig( $this->Auth->user('big') );//don't understand why it's not already filled
		$memBig = $this->logged['Member']['big'];
        $type = $this->api['type'];
		$reason = $this->api['reason'];
        $idObj= $this->api['id_object'];
		    
       	
        // Check if reason matches predefined ones
		if (array_key_exists($reason, Defines::$flag_types))
		{
			$text = Defines::$flag_types[$reason];
		}
		else
		{
			$this->_apiEr(__('Reason errato'));
		}

		// Check if type of signalation is valid
		if (!array_key_exists($type, Defines::$signalations))
		{//TODO in bootstrap aggiungere anche Comment in signalations
			
            $this->_apiEr(__('Tipo di segnalazione errata'));
		}
        
        //Search member_big of bad member
        $BadMemBig=$this->Signalation->findBadMemBig($type,$idObj);
        if ($BadMemBig==null OR $BadMemBig=='')
        {
            $this->_apiEr(__('Identificatore autore mancante'));
        }
        
        
		// Can be this signalation added?
		$canSignal = $this->Signalation->canSignal($memBig, $BadMemBig, $type, $idObj);
		if (!$canSignal)
		{
		$this->_apiEr(__('Segnalazione non possibile. Una segnalazione con questi parametri � gi� attiva o effettuata recentemente.'));
		}
     
        
		// If signalation is made of type CHAT , add member to ignore list.
		if ($type == 1)
		{
			// Check if member exists? Not needed, the method has a try catch block so there won't be any dirty messages errored out
			$isOnList  = $this->MemberSetting->isOnIgnoreListDual($memBig, $BadMemBig);
			if (!$isOnList)
			{
				$res = $this->MemberSetting->addToIgnoreList($memBig, $BadMemBig);
				if ($res === FALSE)
				{
					$this->_apiEr(__('Errore. Utente non aggiunto alla black list.'));
				}
			}
			// If this is a chat, send last M chat messages with up to N characters
            // TODO migliorare prendendo N messaggi precedenti e N successivi a quello incriminato
			$ress = $this->ChatMessage->getMessagesForSignalation($memBig, $BadMemBig);
		}
		elseif ($type == 2)
		{
			// Signalation of type photo - add link to the photo and link to delete the photo
			if (empty($idObj))
			{
				echo json_encode('We are sorry. The report cannot be processed. The reason is: Missing photo identificator.');
				return;
			}

			$photo = $this->Photo->getSignaledPhoto($idObj);
			if (empty($photo['Gallery']['event_big']))
			{
				$img = $this->FileUrl->place_photo($photo['Gallery']['place_big'], $photo['Photo']['gallery_big'], $photo['Photo']['big'], $photo['Photo']['original_ext']);
			}
			else
			{
				$img = $this->FileUrl->event_photo($photo['Gallery']['event_big'], $photo['Photo']['gallery_big'], $photo['Photo']['big'], $photo['Photo']['original_ext']);
			}

		}
        elseif ($type == 3)
        { // Signalation of type comment
            
            if (empty($idObj))
            {
                echo json_encode('We are sorry. The report cannot be processed. The reason is: Missing comment identificator.');
                return;
            }
            
            //TODO quali dati riportiamo e cosa facciamo nel caso di commenti impropri ?
            
        }
        
        /*$this->log("membig ".$memBig);
        $this->log("badmembig ".$BadMemBig);
        $this->log("type ".$type);
        $this->log("text ".$text);//$this->log("type ".$type);
        $this->log("idObj ".$idObj);*/
        
		$result = $this->Signalation->addSignalation($memBig, $BadMemBig, $type, $text, $idObj);

		// Send email report to admins
		$member = $this->Signalation->Member->getMemberByBig($memBig);
		$flagged = $this->Signalation->Member->getMemberByBig($BadMemBig);
		$memberName = !empty($member) ? $member['Member']['name'] .
			(!empty($member['Member']['middle_name']) ? ' ' . $member['Member']['middle_name'] . ' ' : ' ') . $member['Member']['surname'] : 'Deleted member';
		$flaggedName = !empty($flagged) ? $flagged['Member']['name'] .
			(!empty($flagged['Member']['middle_name']) ? ' ' . $flagged['Member']['middle_name'] . ' ' : ' ') . $flagged['Member']['surname'] : 'Deleted member';
		$params = array(
			'reason' => $text,
			'type' => $type,
			'member_name' => $memberName,
			'member_big' => $memBig,
			'flagged_name' => $flaggedName,
			'flagged_big' => $BadMemBig,
		);
		if ($type == 1)
		{//SIGNAL_CHAT
			$params['messages'] = $ress;
		}
		else
		{
			$params['img'] = $img;
			$params['photoBig'] = $idObj;
		}
		App::uses('Emailer', 'Lib');
		Emailer::sendEmail('chat_signalation', null, $params, __('New signalation'), FLAG_MAIL_TO);


		if ($result !== false)
		{
			$this->_apiOk();
		}
		else
		{
			$this->_apiEr(__('Errore. Segnalazione non inserita.'));
		}

	}

	public function add()
	{
		$this->autoRender = false;
		$this->logged = $this->Member->findByBig( $this->Auth->user('big') );//don't understand why it's not already filled

		$memBig = $this->logged['Member']['big'];
		$flgBig = $this->request->query['flgBig'];
		$type = isset($this->request->query['type']) ? $this->request->query['type'] : null;
		$reason = $this->request->query['reason'];
		$photo_id = $flgBig;//isset($this->request->query['photo_id']) ? $this->request->query['photo_id'] : null;

		if (empty($type)) {
			$this->Gem->recursive = -1;
			$gem = $this->Gem->findByBig($flgBig);
			$type = $gem['Gem']['type'];
		}

//		echo json_encode('Reason ' . $reason . ', type ' . $type . ', flgBig ' . $flgBig . ', memBig ' . $memBig . ' photo_id ' . $photo_id);
//		return;

		// Check if reason matches predefined ones
		if (array_key_exists($reason, Defines::$flag_types))
		{
			$text = Defines::$flag_types[$reason];
		}
		else
		{
			echo json_encode('We are sorry. The report cannot be processed. The reason is: Bad report reason value.');
			return;
		}

		// Check if type of signalation is valid
		if (!array_key_exists($type, Defines::$signalations))
		{
			echo json_encode('We are sorry. The report cannot be processed. The reason is: Bad signalation type.');
			return;
		}

		// Can be this signalation added?
		$canSignal = $this->Signalation->canSignal($memBig, $flgBig, $type);
		if (!$canSignal)
		{
			echo json_encode('We are sorry. The report cannot be processed. The reason is: A report with this parameters is already active or was added not long ago.');
			return;
		}

		// If signalation is made of type CHAT , add member to ignore list.
		if ($type == SIGNAL_CHAT)
		{
			// Check if member exists? Not needed, the method has a try catch block so there won't be any dirty messages errored out
			$memberSettings = $this->MemberSetting;/* @var $memberSettings MemberSetting */
			$isOnList  = $memberSettings->isOnIgnoreListDual($memBig, $flgBig);
			if (!$isOnList)
			{
				$res = $memberSettings->addToIgnoreList($memBig, $flgBig);
				if ($res === FALSE)
				{
					echo json_encode('We are sorry. The report cannot be processed. The reason is: Error occured when trying to add this member to ignore list.');
					return;

				}
			}
			// If this is a chat, send last M chat messages with up to N characters
			$ress = $this->ChatMessage->getMessagesForSignalation($memBig, $flgBig);
		}
		else
		{
			// Signalation of type photo - add link to the photo and link to delete the photo
			if (empty($photo_id))
			{
				echo json_encode('We are sorry. The report cannot be processed. The reason is: Missing photo identificator.');
				return;
			}

			$photo = $this->Photo->getSignaledPhoto($photo_id);
			if (empty($photo['Gallery']['event_big']))
			{
				$img = $this->FileUrl->place_photo($photo['Gallery']['place_big'], $photo['Photo']['gallery_big'], $photo['Photo']['big'], $photo['Photo']['original_ext']);
			}
			else
			{
				$img = $this->FileUrl->event_photo($photo['Gallery']['event_big'], $photo['Photo']['gallery_big'], $photo['Photo']['big'], $photo['Photo']['original_ext']);
			}

		}

		$result = $this->Signalation->addSignalation($memBig, $flgBig, $type, $text);

		// Send email report to admins
		$member = $this->Signalation->Member->getMemberByBig($memBig);
		$flagged = $this->Signalation->Member->getMemberByBig($flgBig);
		$memberName = !empty($member) ? $member['Member']['name'] .
			(!empty($member['Member']['middle_name']) ? ' ' . $member['Member']['middle_name'] . ' ' : ' ') . $member['Member']['surname'] : 'Deleted member';
		$flaggedName = !empty($flagged) ? $flagged['Member']['name'] .
			(!empty($flagged['Member']['middle_name']) ? ' ' . $flagged['Member']['middle_name'] . ' ' : ' ') . $flagged['Member']['surname'] : 'Deleted member';
		$params = array(
			'reason' => $text,
			'type' => $type,
			'member_name' => $memberName,
			'member_big' => $memBig,
			'flagged_name' => $flaggedName,
			'flagged_big' => $flgBig,
		);
		if ($type == SIGNAL_CHAT)
		{
			$params['messages'] = $ress;
		}
		else
		{
			$params['img'] = $img;
			$params['photoBig'] = $photo_id;
		}
		App::uses('Emailer', 'Lib');
		Emailer::sendEmail('chat_signalation', null, $params, __('New signalation'), FLAG_MAIL_TO);


		if ($result !== false)
		{
			echo json_encode('Report sent sucessfuly. It will be managed by admins.');
			return;
		}
		else
		{
			echo json_encode('We are sorry. The report cannot be processed. The reason is: Error occured when trying to save the report.');
			return;
		}
	}
    
    public function api_addOLD()
    {

        $this->_checkVars(array('member_big', 'flagged_big', 'reason'));


        //$this->logged = $this->Member->findByBig( $this->Auth->user('big') );//don't understand why it's not already filled
        $memBig = $this->logged['Member']['big'];
        $flgBig = $this->api['flagged_big'];
        $type = isset($this->api['type']) ? $this->api['type'] : null;
        $reason = $this->api['reason'];
        $photo_id = $flgBig;

        if (empty($type)) {
            $this->Gem->recursive = -1;
            $gem = $this->Gem->findByBig($flgBig);
            $type = $gem['Gem']['type'];
        }

        // Check if reason matches predefined ones
        if (array_key_exists($reason, Defines::$flag_types))
        {
            $text = Defines::$flag_types[$reason];
        }
        else
        {
            $this->_apiEr(__('Reason errato'));
        }

        // Check if type of signalation is valid
        if (!array_key_exists($type, Defines::$signalations))
        {
            $this->_apiEr(__('Tipo di segnalazione errata'));
        }

        // Can be this signalation added?
        $canSignal = $this->Signalation->canSignal($memBig, $flgBig, $type);
        if (!$canSignal)
        {
            $this->_apiEr(__('Segnalazione non possibile. Una segnalazione con questi parametri � gi� attiva o effettuata recentemente.'));
        }

        // If signalation is made of type CHAT , add member to ignore list.
        if ($type == SIGNAL_CHAT)
        {
            // Check if member exists? Not needed, the method has a try catch block so there won't be any dirty messages errored out
            $isOnList  = $this->MemberSetting->isOnIgnoreListDual($memBig, $flgBig);
            if (!$isOnList)
            {
                $res = $this->MemberSetting->addToIgnoreList($memBig, $flgBig);
                if ($res === FALSE)
                {
                    $this->_apiEr(__('Errore. Utente non aggiunto alla black list'));
                }
            }
            // If this is a chat, send last M chat messages with up to N characters
            $ress = $this->ChatMessage->getMessagesForSignalation($memBig, $flgBig);
        }
        else
        {
            // Signalation of type photo - add link to the photo and link to delete the photo
            if (empty($photo_id))
            {
                echo json_encode('We are sorry. The report cannot be processed. The reason is: Missing photo identificator.');
                return;
            }

            $photo = $this->Photo->getSignaledPhoto($photo_id);
            if (empty($photo['Gallery']['event_big']))
            {
                $img = $this->FileUrl->place_photo($photo['Gallery']['place_big'], $photo['Photo']['gallery_big'], $photo['Photo']['big'], $photo['Photo']['original_ext']);
            }
            else
            {
                $img = $this->FileUrl->event_photo($photo['Gallery']['event_big'], $photo['Photo']['gallery_big'], $photo['Photo']['big'], $photo['Photo']['original_ext']);
            }

        }

        $result = $this->Signalation->addSignalation($memBig, $flgBig, $type, $text);

        // Send email report to admins
        $member = $this->Signalation->Member->getMemberByBig($memBig);
        $flagged = $this->Signalation->Member->getMemberByBig($flgBig);
        $memberName = !empty($member) ? $member['Member']['name'] .
            (!empty($member['Member']['middle_name']) ? ' ' . $member['Member']['middle_name'] . ' ' : ' ') . $member['Member']['surname'] : 'Deleted member';
        $flaggedName = !empty($flagged) ? $flagged['Member']['name'] .
            (!empty($flagged['Member']['middle_name']) ? ' ' . $flagged['Member']['middle_name'] . ' ' : ' ') . $flagged['Member']['surname'] : 'Deleted member';
        $params = array(
            'reason' => $text,
            'type' => $type,
            'member_name' => $memberName,
            'member_big' => $memBig,
            'flagged_name' => $flaggedName,
            'flagged_big' => $flgBig,
        );
        if ($type == SIGNAL_CHAT)
        {
            $params['messages'] = $ress;
        }
        else
        {
            $params['img'] = $img;
            $params['photoBig'] = $photo_id;
        }
        App::uses('Emailer', 'Lib');
        Emailer::sendEmail('chat_signalation', null, $params, __('New signalation'), FLAG_MAIL_TO);


        if ($result !== false)
        {
            $this->_apiOk();
        }
        else
        {
            $this->_apiEr(__('Errore. Segnalazione non inserita.'));
        }

    }
   
}