<?php

class OperatorsPlace extends AppModel {

	public $primaryKeyArray = array('operator_big', 'place_big');

	public $belongsTo = array(
		'Operator' => array(
			'foreignKey' => 'operator_big',
		),
		'Place',
	);

}