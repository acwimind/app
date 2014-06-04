<?php
class Countries extends AppModel {

	public $primaryKey = 'country_code';

	/* public $hasMany = array(
			'ExtraInfos' => array(
				'className' => 'ExtraInfos'
	)); */


	/**
	 * Return all countries with their country code, order by country name ASC, with the country name translated
	 * @return Ambigous array
	 */
	public function getAllCountries()
	{
		$country_list = $this->find('all', array(
				'fields' => array('country_code', 'country_name'),
				'order' => 'country_name ASC'
		));

		$result = array();
		foreach($country_list as $block)
		{
			$result[ $block['Countries']['country_code'] ] = __( $block['Countries']['country_name']);
		}

		return $result;
	}
}