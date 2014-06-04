<?php

class CmsText extends AppModel {
	
	public $belongsTo = array('CmsEntry');
	
	public function deleteText($id)
	{
		
		$this->save(array(
			'id' => $id,
			'status' => DELETED,
		));
	}
	
	public $validate = array(
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