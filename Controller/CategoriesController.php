<?php

class CategoriesController extends AppController {
	
	public function admin_index() {
		
		$this->_savedFilter(array('srchphr'));

		$conditions = array();
	    	
    	if (isset($this->params->query['srchphr']) && !empty($this->params->query['srchphr'])) {
    		$conditions['OR'] = array('Category.name ILIKE' => '%' . $this->params->query['srchphr'] . '%') ;
    	}
		
    	$this->request->data['Category'] = $this->params->query;
    	$this->paginate['order'] = array('Category.name' => 'asc');
    	$data = $this->paginate('Category', $conditions);
		
		$this->set('data', $data);
		
	}
	
	public function admin_add() {
		$this->admin_edit();
		$this->render('admin_edit');
	}
	
	public function admin_edit($id=0) {
		
		if ($this->request->is('post') || $this->request->is('put')) {
			
			if ($this->Category->saveAll($this->request->data, array('validate' => 'first'))) {
				
				//profile picture upload
				try {
					if ( $this->_upload($this->request->data['Category']['picture'], $this->Category->id) ) {
						$this->Category->save(array('Category' => array('updated' => DboSource::expression('now()'))));
					}
				} catch (UploadException $e) {
						
				}
				
				$this->Session->setFlash(__('Category saved'), 'flash/success');
				return $this->redirect(array('action' => 'index'));
				
			} else {
				$this->Session->setFlash(__('Error while saving category'), 'flash/error');
			}
			
		} elseif ($id > 0) {
			
			$this->request->data = $this->Category->getCatLangs($id);
			
		}
		
	}
	
	private function _upload($photo, $id) {
	
		return $this->Upload->upload(
			$photo,	//data from form (temporary filenames, token)
			CATEGORIES_UPLOAD_PATH,	//path
			$id	//filename
		);
		
	}
	
	public function admin_delete($id) {
		
		$has_places = $this->Category->Place->find('count', array(
			'conditions' => array(
				'Place.category_id' => $id
			),
			'recursive' => -1,
		));
		
		if ($has_places == 0) {
			$this->Category->deleteAll(array('Category.id' => $id));
			$this->Session->setFlash(__('Category deleted'), 'flash/success');
		} else {
			$this->Session->setFlash(__('Unable to delete category, because it contains places. Please delete places first.'), 'flash/error');
		}
		return $this->redirect(array('action' => 'index'));
		
	}
	
	/**
	 * Return list of categories
	 */
	public function api_list() {
		
		unbindAllBut($this->Category);
		$cats = array('categories' => $this->Category->find('all'));
		
		$this->_apiOk($cats);
		
	}
	
}