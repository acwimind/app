<?php

App::uses('CmsText', 'Model');

class CmsEntriesController extends AppController {

	public function beforeFilter() {

		parent::beforeFilter();

		if ($this->Auth) {
			$this->Auth->allow('home', 'display', 'm_display');
		}

	}

	public function admin_add() {
		$this->admin_edit();
		$this->render('admin_edit');
	}

	public function admin_edit($id=0) {

		if ($this->request->is('post') || $this->request->is('put')) {

//			print_r('<pre>');
//			print_r($this->request->data);
//			print_r('</pre>');
//			die();

			$data = $this->request->data;

			// Check CMS Entry
			if (empty($data['CmsEntry']['section']) && empty($data['CmsEntry']['name']))
			{
				if (!empty($data['CmsEntry']['id']))
				{
					return $this->redirect(array(
						'action' => 'delete',
						$data['CmsEntry']['id']
					));
				}
				else
				{
					$this->Session->setFlash(__('Entry non salvato'), 'flash/error');
					return;
				}
			}

			// Cycle trough Texts
			foreach ($data['CmsText'] as $key => $text)
			{
				// If main fields are empty consider this as a delete request
				if (empty($text['name']) && empty($text['text']))
				{
					if (!empty($text['id']))
					{
						// Mark as deleted
						$modelCmsText = new CmsText();
						$modelCmsText->deleteText($text['id']);
					}
					unset($data['CmsText'][$key]);
				}
			}

//			print_r('<pre>');
//			print_r($data);
//			print_r('</pre>');
//			die();


			if ($this->CmsEntry->saveAll($data, array('validate' => 'first'))) {
				$this->Session->setFlash(__('Entry salvato'), 'flash/success');
				return $this->redirect(array('action' => 'index'));
			} else {
//				debug($this->CmsEntry->validationErrors);
				$this->Session->setFlash(__('Errore durante il salvataggio'), 'flash/error');
			}

		} elseif ($id > 0) {

			$data = $this->CmsEntry->getEntriesAndTexts($id);

//			print_r('<pre>');
//			print_r($data);
//			print_r('</pre>');
//			die();

			$this->request->data = $data;

		}

	}

	public function admin_delete($id) {

		$this->CmsEntry->save(array(
			'id' => $id,
			'status' => DELETED,
		));

		$this->Session->setFlash(__('Entry cancellato'), 'flash/success');
		return $this->redirect(array('action' => 'index'));

	}

	public function admin_index() {

		$this->_savedFilter(array('sectn', 'status', 'srchphr', 'CreatedFromDate', 'CreatedToDate', 'UpdatedFromDate', 'UpdatedToDate'));

		$conditions = array();

    	if (isset($this->params->query['sectn']) && !empty($this->params->query['sectn'])) {
    		$conditions['CmsEntry.section'] = $this->params->query['sectn'];
    	}
    	if (isset($this->params->query['status']) && is_numeric($this->params->query['status'])) {
    		$conditions['CmsEntry.status'] = $this->params->query['status'];
    	}
		if (isset($this->params->query['srchphr']) && !empty($this->params->query['srchphr'])) {
    		$conditions['CmsEntry.name ILIKE'] = '%' . $this->params->query['srchphr'] . '%';
    	}
    	if (isset($this->params->query['CreatedFromDate']) && !empty($this->params->query['CreatedFromDate'])) {
			$conditions['CmsEntry.created >='] = $this->params->query['CreatedFromDate'] . ' ' . $this->params->query['CreatedFromTime'];
    	}
    	if (isset($this->params->query['CreatedToDate']) && !empty($this->params->query['CreatedToDate'])) {
    		$conditions['CmsEntry.created <='] = $this->params->query['CreatedToDate'] . ' ' . $this->params->query['CreatedToTime'];
    	}
		if (isset($this->params->query['UpdatedFromDate']) && !empty($this->params->query['UpdatedFromDate'])) {
    		$conditions['CmsEntry.updated >='] = $this->params->query['UpdatedFromDate'] . ' ' . $this->params->query['UpdatedFromTime'];
    	}
    	if (isset($this->params->query['UpdatedToDate']) && !empty($this->params->query['UpdatedToDate'])) {
    		$conditions['CmsEntry.updated <='] = $this->params->query['UpdatedToDate'] . ' ' . $this->params->query['UpdatedToTime'];
    	}

    	$this->request->data['CmsEntry'] = $this->params->query;

		$data = $this->paginate('CmsEntry', $conditions);
		$this->set('data', $data);

//		print_r('<pre>');
//		print_r($this->params->query);
//		print_r('</pre>');
	}

	public function admin_view() {

		return $this->redirect(array(
			'controller' => 'cmstexts',
			'action' => 'index'
		));

	}

	public function home() {

		if ($this->logged) {
			return $this->redirect('/');
		}

		unbindAllBut($this->CmsEntry, array('CmsTextLang'));
		$cms_home = $this->CmsEntry->find('first', array(
			'conditions' => array(
				'CmsEntry.id' => 1,
				//'CmsText.lang' => $this->lang,
			),
		));
		$this->set('title_for_layout', __('Welcome to Haamble'));
		$this->set('cms_home', $cms_home);

	}

/**
 * Displays a view
 *
 * @param mixed What page to display
 * @return void
 */
	public function display() {
		$path = func_get_args();

		$count = count($path);
		if (!$count) {
			die($count);
			$this->redirect('/');
		}
		$page = $subpage = $title_for_layout = null;

		if (!empty($path[0])) {
			$page = $path[0];
		}
		if (!empty($path[1])) {
			$subpage = $path[1];
		}
		if (!empty($path[$count - 1])) {
			$title_for_layout = Inflector::humanize($path[$count - 1]);
		}
		$this->set(compact('page', 'subpage', 'title_for_layout'));
//		$this->render(implode('/', $path));

		$cmsEntry = $this->CmsEntry;/* @var $cmsEntry CmsEntry */
		unbindAllBut($cmsEntry, array('CmsTextLang'));
		$entry = $cms_home = $cmsEntry->find('first', array(
			'conditions' => array(
				'CmsTextLang.slug' => $path[0],
			),
		));

		if (!empty($entry['CmsTextLang']['name']))
			$this->set('title_for_layout', $entry['CmsTextLang']['name']);

		$this->set('entry', $entry);
	}

/**
 * Displays a view with mobile template
 *
 * @param mixed What page to display
 * @return void
 */
	public function m_display() {
		$this->layout = 'mobile';
		$path = func_get_args();

		$count = count($path);
		if (!$count) {
			die($count);
			$this->redirect('/');
		}
		$page = $subpage = $title_for_layout = null;

		if (!empty($path[0])) {
			$page = $path[0];
		}
		if (!empty($path[1])) {
			$subpage = $path[1];
		}
		if (!empty($path[$count - 1])) {
			$title_for_layout = Inflector::humanize($path[$count - 1]);
		}
		$this->set(compact('page', 'subpage', 'title_for_layout'));
//		$this->render(implode('/', $path));

		unbindAllBut($this->CmsEntry, array('CmsTextLang'));
		$entry = $cms_home = $this->CmsEntry->find('first', array(
			'conditions' => array(
				'CmsTextLang.slug' => $path[0],
			),
		));

		if (!empty($entry['CmsTextLang']['name']))
			$this->set('title_for_layout', $entry['CmsTextLang']['name']);

		$this->set('entry', $entry);
	}

}