<?php

class Operator extends AppModel {

	public $primaryKey = 'member_big';

	public $hasMany = array(
		'OperatorsPlace',
	);

	public $belongsTo = array(
		'Member' => array(
			'foreignKey' => 'big',
		),
	);

}