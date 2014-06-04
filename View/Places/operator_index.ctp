<?php

echo $this->element('menu/operator');
?>
<div class="operator_content">
<div class="content-header">
<?php
echo '<h2>' . __('My Places') . '</h2>';
?>
</div>    
<?php
echo $this->element('table/head', array(
	'cols' => array('Place.name' => __('Name'), 'Checkins', 'Place.street' => 'Address' ),
));

foreach($data as $item) {
	
	if (isset($checkins[ $item['Place']['big'] ])) {
		$checkins_text = __('%s checkins by %s users', $checkins[ $item['Place']['big'] ]['checkins'], $checkins[ $item['Place']['big'] ]['members']);
	} else {
		$checkins_text = __('no checkins');
	}
	
	echo $this->element('table/row', array(
		'cols' => array(	
			$this->Html->link($item['Place']['name'], array('controller' => 'places', 'action' => 'detail', $item['Place']['big'], $item['Place']['slug'], 'operator' => false)),
			$checkins_text,
			$item['Place']['address_street'] . ', ' . $item['Region']['city'],
		),
		'id' => $item['Place']['big'],
		'show_label' => false,
		'options' => array(
			'view' => array( __('Display Events'), array('controller' => 'events', 'action' => 'index', 'place' => $item['Place']['big']), 'search' ), 
			'delete' => false
		),
		'custom' => array(
			'add_event' => array( __('New Event'), array('controller' => 'events', 'action' => 'add', $item['Place']['big']), 'glass' ),
			'view_gallery' => isset($item['Gallery'][0]) ? array( __('Display gallery'), array('controller' => 'galleries', 'action' => 'index', $item['Gallery'][0]['big']), 'picture' ) : false,
		),
	));
	
}

echo $this->element('table/foot');
?>
</div>