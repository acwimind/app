<?php

echo '<h1>' . ( isset($this->data['Event']) && $this->data['Event']['big']>0 ? __('Edit Event %s', $this->data['Event']['name']) : __('New Event') ) . '</h1>';

echo $this->AdvForm->create('Event');

echo $this->AdvForm->hidden('Event.big');

echo $this->AdvForm->hidden('Place.big');

echo $this->AdvForm->inputs(array(
	'legend' => __('Basic Info'),
	'' => array(
		'label' => __('Associated Place'), 
		'type' => 'text', 
		'value' => $place['Place']['name'], 
		'disabled' => true, 
		'after' => isset($this->data['Event']) && $this->data['Event']['big']>0 ? '' : $this->Html->link(__('Pick another place'), array('controller' => 'events', 'action' => 'add'))
	),
	'Event.name' => array('label' => __('Name'), 'type' => 'text'),
	'Event.slug' => array('label' => __('Slug (for URL)'), 'type' => 'text'),
	'Event.short_desc' => array('label' => __('Short Description')),
	'Event.long_desc' => array('label' => __('Full Description')),
	'Event.status' => array('label' => __('Event status'), 'options' => array(
		ACTIVE		=> __('Active'), 
		INACTIVE	=> __('Inactive (will not be activated and displayed to users)'), 
	)),
	'Event.photos' => array(
		'label' => __('Upload Photos'),
		'uploader' => array(
			'data-preview' => true,		//show preview of uploaded images
			'data-multiple' => true,	//allow upload of multiple files
		),
	),
));

if (isset($this->data['Gallery'][0]['big']) && $this->data['Gallery'][0]['big']>0) {
	echo $this->Html->link(__('Display gallery'), array('controller' => 'galleries', 'action' => 'index', $this->data['Gallery'][0]['big']));
} else {
	echo __('No photos');
}

if (isset($this->data['Event']) && $this->data['Event']['big']>0) {
	$fields = array(
		'legend' => __('Event Type'),
		'Event.type' => array('type' => 'hidden'),
		'Event.type_disabled' => array('label' => __('Event Type'), 'disabled' => isset($this->data['Event']) && $this->data['Event']['big']>0, 'options' => array(
			EVENT_TYPE_NORMAL	=> __('Normal event'),
			EVENT_TYPE_DEFAULT	=> __('Default event'),
		), 'after' => '<p>'.__('Only one default event per place allowed. Once you create event, you cannot change its type.').'</p>'),
	);
} else {
	$fields = array(
		'legend' => __('Event Type'),
		'Event.type' => array('label' => __('Event Type'), 'disabled' => isset($this->data['Event']) && $this->data['Event']['big']>0, 'options' => array(
			EVENT_TYPE_NORMAL	=> __('Normal event'),
			EVENT_TYPE_DEFAULT	=> __('Default event'),
		), 'after' => '<p>'.__('Only one default event per place allowed. Once you create event, you cannot change its type.').'</p>'),
	);
}


echo $this->AdvForm->inputs($fields);

if (!isset($this->data['Event']) || empty($this->data['Event']['big']) || $this->data['Event']['type'] != EVENT_TYPE_DEFAULT) {
	
	//TODO: date + time picker
	echo $this->AdvForm->inputs(array(
		'legend' => __('Dates and Times'),
		'Event.start_date' => array('label' => __('Start On'), 'picker' => 'datetime'),
		'Event.end_date' => array('label' => __('End On'), 'picker' => 'datetime'),
		'Event.daily_start' => array('label' => __('Daily Start On'), 'picker' => 'time'),
		'Event.daily_end' => array('label' => __('Daily End On'), 'picker' => 'time'),
	));

}

echo $this->AdvForm->submit('Save');

echo $this->AdvForm->end();