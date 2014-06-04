<?php

echo '<h1>' . __('List Events') . '</h1>';

// Filter
echo $this->AdvForm->create('Event', array('action' => 'index', 'class' => 'form-horizontal', 'type' => 'get'));

echo '<div class="control-group">';
echo $this->AdvForm->label('srchname', 'Search by Name', 'control-label');
echo $this->AdvForm->input('srchname', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('srchplace', 'Search by Place', 'control-label');
echo $this->AdvForm->input('srchplace', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('CreatedFrom', '<b>Created:</b> From', 'control-label');
echo $this->AdvForm->input('CreatedFrom', array('label' => false, 'picker' => 'datetime', 'div' => 'controls', 'toggle' => array('div' => 'input controls')));
echo $this->AdvForm->label('CreatedTo', 'To', 'control-label');
echo $this->AdvForm->input('CreatedTo', array('label' => false, 'picker' => 'datetime', 'div' => 'controls', 'toggle' => array('div' => 'input controls')));

echo '<div class="controls">';
echo $this->AdvForm->submit('Filter', array(
	'class' => 'submit btn btn-primary', 
	'after' =>  $has_filter ? $this->Html->link('Cancel Filter', array('?' => array('cancel_filter' => 1)), array('class' => 'btn')) : '',
));
echo '</div>';
echo '</div>';

echo $this->AdvForm->end();


echo $this->Html->link('<i class="icon-plus"></i> ' . __('New Event'), array('action' => 'add'), array('class' => 'btn', 'escape' => false));

echo $this->element('table/head', array(
	'cols' => array('Event.name' => __('Name'), 'Checkins', 'Place.name' => __('Associated Place'), 'Type', 'Event.start_date' => __('Starts On'), 'Event.end_date' => __('Ends On'), 'Event.created' => 'Created' ),
));

foreach($data as $item) {

	if (isset($checkins[ $item['Event']['big'] ])) {
		$checkins_text = __('%s checkins ', $checkins[ $item['Event']['big'] ]);
	} else {
		$checkins_text = __('no checkins');
	}

	if (isset($joins[ $item['Event']['big'] ])) {
		$checkins_text .= __(' / %s joins', $joins[ $item['Event']['big'] ]);
	} else {
		$checkins_text .= __(' / no joins');
	}
	
	$checkins_url = array('controller' => 'events', 'action' => 'detail', $item['Event']['big'], $item['Event']['slug'], 'admin' => false, '#' => 'tab-people');
	$type = __('normal');
	if ($item['Event']['type'] == EVENT_TYPE_DEFAULT) {
		if ($item['Event']['status'] == ACTIVE) {
			$type = __('default');
		} else {
			$type = __('default and hidden');
			$checkins_url = array('controller' => 'places', 'action' => 'detail', $item['Place']['big'], $item['Place']['slug'], 'admin' => false, '#' => 'tab-people');
		}
	}
	
	$cols = array(
		$item['Event']['name'],
		$this->Html->link($checkins_text, $checkins_url),
		$this->Html->link($item['Place']['name'], array('controller' => 'places', 'action' => 'index', '?' => array('srchphr' => $item['Place']['name']))),
		$type,
	);
	
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
		'options' => array(
			'view' => array( __('View Event'), array('controller' => 'events', 'action' => 'detail', $item['Event']['big'], $item['Event']['slug'], 'admin' => false), 'search' )
		),
		'custom' => array(
			'view_gallery' => isset($item['Gallery'][0]) ? array( __('Display gallery'), array('controller' => 'galleries', 'action' => 'index', $item['Gallery'][0]['big']), 'picture' ) : false,
		),
	));
	
}

echo $this->element('table/foot');