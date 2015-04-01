<?php
class ExtraInfos extends AppModel {

	public $primaryKey = 'member_big';

	public $belongsTo = array(
		'Member' => array(
			'foreignKey' => 'member_big',
		),
		'Countries' => array(
			'foreignKey' => 'country_code'
		)
	);
	
	
	
	public function getMemberByExtraInfos($params)
	{
		$params = array(
				'conditions' => array('OR' => array ($params)),
				'recursive' => 1
		);
	
		return $this->find('all', $params);
	}
	
}