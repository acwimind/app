<?php

/*
 * NOTE: Gem has to extend directly Model and not AppModel - Gem is used in AppModel and doesn't use hooks from AppModel
 */
class Gem extends Model {

    var $primaryKey = 'big';
    
    var $belongsTo = array(
    	'Member',
    	'RelatedGem' => array(
    		'className' => 'Gem',
    		'foreignKey' => 'related_big',
    	),
    );

}

