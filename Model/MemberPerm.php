<?php

class MemberPerm extends AppModel {
	
	public $primaryKey = 'member_big';
	
	public $belongsTo = array(
		'Member',
	);
	
}