<?php

class CatLang extends AppModel {
	
	public $primaryKey = 'cat_lang_id';
	
	public $belongsTo = array(
		'Category',
	);
	
}