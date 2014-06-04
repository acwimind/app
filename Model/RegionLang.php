<?php

class RegionLang extends AppModel {
	
	public $primaryKey = 'region_lang_id';
	
	public $belongsTo = array(
		'Region',
	);
	
}