<?php

App::uses('CmsText', 'Model');

class CmsEntry extends AppModel {
	
	public $hasMany = array('CmsText');
	
	public $hasOne = array(
		'CmsTextLang' => array(
			'className' => 'CmsText',
			'conditions' => array('CmsTextLang.lang' => CURRENT_LANG),
		),
	);
	
	public function getEntriesAndTexts($id)
	{
		$data = $this->findById($id);
		
		$entries = $data['CmsText'];
		unset($data['CmsText']);
		
		foreach ($entries as $entry)
		{
			if ($entry['status'] == DELETED)
			{
				foreach ($entry as $key => $val)
				{
					if ($key != 'id' && $key != 'lang' && $key != 'cms_entry_id')
					{
						unset($entry[$key]);
					}
				}
			}
			$data['CmsText'][$entry['lang']] = $entry;
		}
		
		return $data;
	}
	
	public $validate = array(
//		'section' => array(
//			'rule' => array('between', 2, 16),
//			'message' => 'Section name must be between 2 and 16 characters long.',
//		),
//		'name' => array(
//			'rule' => array('minLength', 2),
//			'message' => 'Please fill in entry name',
//		),
		'status' => array(
			'rule' => 'valid_status',
			'message' => 'Incorrect status',
		)
	);
	
	public function valid_status($check)
	{
		return array_key_exists($check['status'], Defines::$statuses);
	}
	
}