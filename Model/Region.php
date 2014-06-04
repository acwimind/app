<?php

class Region extends AppModel {
	
	public $hasMany = array(
		'RegionLang',
		'Place',
	);
	
	public $virtualFields = array(
		'name' => "CONCAT(Region.city, ', ', Region.country)"
	);
	
	public $displayField = 'name';
	
	public $validate = array(
		'city' => array(
			'rule' => array('minLength', 2),
			'message' => 'Please fill in region city',
		),
		'country' => array(
			'rule' => array('between', 2, 2),
			'message' => 'Please fill in country',
		),
	);
	
	public function getRegionLangs($id)
	{
		$data = $this->findById($id);
//		debug($data); die();
		$entries = $data['RegionLang'];
		unset($data['RegionLang']);
		
		foreach ($entries as $entry)
		{
			$data['RegionLang'][$entry['language_id']] = $entry;
		}
		
		return $data;
	}
	
	public function getRegionByCityAndCountry($city, $country)
	{
		$params = array(
			'conditions' => array(
				'LOWER(Region.city)' => strtolower($city),
				'LOWER(Region.country)' => strtolower($country)
			),
			'fields' => array(
				'Region.id'
			), 
			'recursive' => -1
		);
		
		return $this->find('first', $params);
	}
	
}