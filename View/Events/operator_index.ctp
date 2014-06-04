<?php

echo $this->element('menu/operator');
?>
<div class="operator_content">
    <div class="content-header">
<?php
if (isset($place)) {
	echo '<h2>' . __('List Events for %s', $place['Place']['name']) . '</h2>';
} else {
	echo '<h2>' . __('List Events') . '</h2>';
}
echo $this->Html->link('<i class="icon-plus"></i> ' . __('New Event'), array('action' => 'add'), array('class' => 'btn event_head', 'escape' => false));

?>
</div>        
<?php        

echo $this->element('table/head', array(
	'cols' => array('Event.name' => __('Name'), 'Place.name' => __('Associated Place'), 'Type', 'Event.date_start' => __('Starts On'), 'Event.date_end' => __('Ends On'), 'Event.created' => 'Created' ),
));

foreach($data as $item) {
	
	$type = __('normal');
	if ($item['Event']['type'] == EVENT_TYPE_DEFAULT) {
		if ($item['Event']['status'] == ACTIVE) {
			$type = __('default');
		} else {
			$type = __('default and hidden');
		}
	}
	
	$cols = array();

	if (1) {
		$cols = array_merge($cols, array(
			$this->Html->link($item['Event']['name'], array('controller' => 'events', 'action' => 'detail', $item['Event']['big'], $item['Event']['slug'], 'operator' => false)),
		));
	} else {
		$cols = array_merge($cols, array(
			$item['Event']['name']
		));
	}

	$cols = array_merge($cols, array(
		$this->Html->link($item['Place']['name'], array('controller' => 'places', 'action' => 'detail', $item['Place']['big'], $item['Place']['slug'], 'operator' => false)),
		$type,
	));
	
	if ($item['Event']['type'] != EVENT_TYPE_DEFAULT) {
		$cols = array_merge($cols, array(
			$this->Date->full($item['Event']['start_date']),
			$this->Date->full($item['Event']['end_date']),
		));
	} else {
		$cols = array_merge($cols, array(
			'-', 
			'-',
		));
	}
	
	$cols = array_merge($cols, array(
		date('d M Y, H:i', strtotime($item['Event']['created'])),
	));
	
	echo $this->element('table/row', array(
		'cols' => $cols,
		'id' => $item['Event']['big'],
		'options' => array('view' => false),
		'custom' => array(
			'view_gallery' => isset($item['Gallery'][0]) ? array( __('Display gallery'), array('controller' => 'galleries', 'action' => 'index', $item['Gallery'][0]['big']), 'picture' ) : false,
		),
	));
	
}

echo $this->element('table/foot');
?>
</div>
