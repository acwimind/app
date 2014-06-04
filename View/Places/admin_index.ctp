<?php

echo '<h1>' . __('List Places') . '</h1>';

// Filter
echo $this->AdvForm->create('Place', array('action' => 'index', 'class' => 'form-horizontal', 'type' => 'get'));

echo '<div class="control-group">';
echo $this->AdvForm->label('CreatedFrom', '<b>'.__('Created').':</b> '.__('From'), 'control-label');
echo '<div class="controls">';
echo $this->AdvForm->input('CreatedFrom', array('label' => false, 'picker' => 'datetime', 'toggle' => true));
echo '</div>';
echo $this->AdvForm->label('CreatedTo', __('To'), 'control-label');
echo '<div class="controls">';
echo $this->AdvForm->input('CreatedTo', array('label' => false, 'picker' => 'datetime', 'toggle' => true));
echo '</div>';

echo '<div class="control-group">';
echo $this->AdvForm->label('UpdatedFrom', '<b>'.__('Updated').':</b> '.__('From'), 'control-label');
echo '<div class="controls">';
echo $this->AdvForm->input('UpdatedFrom', array('label' => false, 'picker' => 'datetime', 'toggle' => true));
echo '</div>';
echo $this->AdvForm->label('UpdatedTo', __('To'), 'control-label');
echo '<div class="controls">';
echo $this->AdvForm->input('UpdatedTo', array('label' => false, 'picker' => 'datetime', 'toggle' => true));
echo '</div>';

//echo '<div class="control-group">';
echo $this->AdvForm->label('UpdatedBy', '<b>'.__('Updated').':</b> '.__('By'), 'control-label');
//echo '<div class="controls">';
echo $this->AdvForm->input('UpdatedBy', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));
//echo '</div>';
//echo '</div>';



echo $this->AdvForm->label('srchphr', __('Search by Name'), 'control-label');
echo $this->AdvForm->input('srchphr', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('operator', __('With operator(s)'), 'control-label');
echo $this->AdvForm->input('operator', array('type' => 'checkbox', 'label' => false, 'class' => 'input-medium', 'div' => 'controls'));

if (!empty($categories))
{
	echo $this->AdvForm->label('category', 'Filter by Category', 'control-label');
	echo $this->AdvForm->input('category', array('label' => false, 'options' => array(null => 'All') + $categories, 'class' => 'input-medium', 'div' => 'controls'));
}

echo '<div class="controls">';
echo $this->AdvForm->submit('Filter', array(
	'class' => 'submit btn btn-primary', 
	'after' =>  $has_filter ? $this->Html->link('Cancel Filter', array('?' => array('cancel_filter' => 1)), array('class' => 'btn')) : '',
));
echo '</div>';
echo '</div>';

echo $this->AdvForm->end();

echo $this->Html->link('<i class="icon-plus"></i> ' . __('New Place'), array('action' => 'add'), array('class' => 'btn', 'escape' => false));

echo $this->element('table/head', array(
	'cols' => array('Place.name' => __('Name'),__('Phone'), 'Checkins', 'Place.address_street' => 'Street', 'Region.city' => 'City', 'Place.created' => 'Created / Imported' ),
));

foreach($data as $item) {
	
//debug($item);
	if (isset($checkins[ $item['Place']['big'] ])) {
		$checkins_text = __('%s checkins ', $checkins[ $item['Place']['big'] ]);
	} else {
		$checkins_text = __('no checkins');
	}

	if (isset($joins[ $item['Place']['big'] ])) {
		$checkins_text .= __(' / %s joins', $joins[ $item['Place']['big'] ]);
	} else {
		$checkins_text .= __(' / no joins');
	}
	
	echo $this->element('table/row', array(
		'cols' => array(
			$item['Place']['name'],
			$item['Place']['phone'],
			$this->Html->link($checkins_text, array('controller' => 'places', 'action' => 'detail', $item['Place']['big'], $item['Place']['slug'], 'admin' => false, '#' => 'tab-people')),
			$item['Place']['address_street'],
			$item['Region']['city'],
			date('d M Y, H:i', strtotime($item['Place']['created'])),
		),
		'id' => $item['Place']['big'],
		'custom' => array(
			'add_event' => array( __('New Event'), array('controller' => 'events', 'action' => 'add', $item['Place']['big']), 'glass' ),
			'view_gallery' => isset($item['Gallery'][0]) ? array( __('Display gallery'), array('controller' => 'galleries', 'action' => 'index', $item['Gallery'][0]['big']), 'picture' ) : false,
			'view_events' => array( __('Display Events'), array('controller' => 'events', 'action' => 'index', '?' => array('srchplace' => $item['Place']['name'])), 'list' ), 
		),
		'options' => array(
			'view' => array( __('View Place'), array('controller' => 'places', 'action' => 'detail', $item['Place']['big'], $item['Place']['slug'], 'admin' => false), 'search' )
		),
	));
	
}

echo $this->element('table/foot');