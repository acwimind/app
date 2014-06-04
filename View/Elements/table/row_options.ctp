<?php

$options = isset($options) ? $options : array();
$custom = isset($custom) ? $custom : false;
$show_label = isset($show_label) ? $show_label : false;

//TODO: what is this? who made it? is it used? :) refactor!
/*if ($custom) {
	$options['custom'] = array( __('Custom'), array('action' => 'custom', $id), 'plus');
}*/

if (is_array($custom) && !empty($custom)) {
	foreach($custom as $key=>$item) {
		$options[ $key ] = $item;
	}
}

$options += array(
		'view'		=> array( __('View'), array('action' => 'view', $id), 'search'),	//title, URL as array, icon name
		'edit'		=> array( __('Edit'), array('action' => 'edit', $id), 'edit'),
		'delete'	=> array( __('Delete'), array('action' => 'delete', $id), 'trash'),
);

foreach($options as $type => $link) {
	
	if ($link == false) {
		continue;
	}
	
	$link_options = array('escape' => false);
	
	if ($type == 'delete') {
		$link_options['confirm'] = __('Are you sure?\nThis action cannot be undone!');
	}
	
	if ($show_label) {
		echo $this->Html->link('<i class="icon-' . $link[2] . '"></i> ' . $link[0], $link[1], $link_options) . ' ';
	} else {
		echo $this->Html->link('<i class="icon-' . $link[2] . '" data-toggle="tooltip" data-placement="bottom" title="' . $link[0] . '"></i>', $link[1], $link_options) . ' ';
	}
	
}
