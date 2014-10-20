<?php

App::uses('Advert', 'Model');

class AdvertsController extends AppController {
	 var $components = array('MailchimpApi');
     
     
     
	private function _upload($photo, $id, $direct=false) {
		
		if ($direct) {
			
			$extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
		
			// Remove old picture
			$exts = array('jpg', 'jpeg', 'png');
			foreach ($exts as $ext)
			{
				$path = ADVERTS_UPLOAD_PATH . $id . '.' . $ext;
				if (is_file($path))
				{
					unlink($path);
					break;
				}
			}
			
			return $this->Upload->directUpload(
				$photo,	//data from form (uploaded file)
				ADVERTS_UPLOAD_PATH . $id . '.' . $extension //. '.jpg'	//path + filename
			);
			
		} else {
			
			return $this->Upload->upload(
				$photo,	//data from form (temporary filenames, token)
				ADVERTS_UPLOAD_PATH,	//path
				$id	//filename
			);
			
		}
	}
	
	public function admin_index() {

		$this->_savedFilter(array('srchphr'));
		
		$conditions = array();
		
    	if (isset($this->params->query['srchphr']) && !empty($this->params->query['srchphr'])) {
    		$conditions['Advert.url ILIKE'] = '%' . $this->params->query['srchphr'] . '%';
    	}
    	if (isset($this->params->query['status']) && is_numeric($this->params->query['status'])) {
    		$conditions['Advert.status'] = $this->params->query['status'];
    	}
		
    	$this->request->data['Advert'] = $this->params->query;
    	
		$data = $this->paginate('Advert', $conditions);
		$this->set('data', $data);
		
	}
	
	public function admin_add() {
		$this->admin_edit();
		$this->render('admin_edit');
	}
	
    
    public function api_sendcampaign(){
        
        $this->_checkVars(array('campaignId'), array());
        
        $idReplica=$this->MailchimpApi->campaignReplicate($this->api['campaignId']);
        $result=$this->MailchimpApi->campaignSendNow($idReplica);
        //$result=$this->MailchimpApi->campaignList();
        
        $this->_apiOk ($result);
        
    }
    
    
	public function admin_edit($id=0) {
		
		if ($this->request->is('post') || $this->request->is('put')) {

			$data = $this->request->data;
			
			if ($this->Advert->saveAll($data, array('validate' => 'first'))) {
				
				//picture upload
				try {
					$photo = $this->request->data['Advert']['photo'];
					$extension = pathinfo($photo['files'], PATHINFO_EXTENSION);
					if ( $this->_upload($photo, $this->Advert->id) ) {
						$this->Advert->save(
							array(
								'Advert' => array(
									'photo_updated' => DboSource::expression('now()'),
									'photo_ext' => $extension
								)
							)
						);
					}
				} catch (UploadException $e) {
					
				}
				
				
				$this->Session->setFlash(__('Entry saved'), 'flash/success');
				return $this->redirect(array('action' => 'index'));
			} else {
//				debug($this->CmsEntry->validationErrors);
				$this->Session->setFlash(__('Error while saving advertisment'), 'flash/error');
			}
			
		} elseif ($id > 0) {

			$data = $this->Advert->findById($id);
			$this->request->data = $data;
			
		}
		
	}
	
	public function admin_delete($id) {
		
		$this->Advert->save(array(
			'id' => $id,
			'status' => DELETED,
		));
		
		$this->Session->setFlash(__('Entry deleted'), 'flash/success');
		return $this->redirect(array('action' => 'index'));
		
	}
	
}