<?php

echo '<h1>' . __('List Content') . '</h1>';

// Filter
echo $this->AdvForm->create('Advert', array('action' => 'index', 'class' => 'form-horizontal', 'type' => 'get'));

echo '<div class="control-group">';

echo $this->AdvForm->label('srchphr', 'Search by Url', 'control-label');
echo $this->AdvForm->input('srchphr', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('status', 'Filter by Status', 'control-label');
echo $this->AdvForm->input('status', array('label' => false, 'options' => array(null => 'All', intval(INACTIVE) => Defines::$statuses[INACTIVE], ACTIVE => Defines::$statuses[ACTIVE]), 'class' => 'input-medium', 'div' => 'controls'));

echo '<div class="controls">';
echo $this->AdvForm->submit('Filter', array(
	'class' => 'submit btn btn-primary', 
	'after' =>  $has_filter ? $this->Html->link('Cancel Filter', array('?' => array('cancel_filter' => 1)), array('class' => 'btn')) : '',
));
echo '</div>';
echo '</div>';

echo $this->AdvForm->end();

echo $this->Html->link('<i class="icon-plus"></i> ' . __('New Content'), array('action' => 'add'), array('class' => 'btn', 'escape' => false));

echo $this->element('table/head', array(
	'cols' => array('Advert.heading' => __('Heading'), 'Advert.url' => __('URL'), 'Advert.status' => 'Status'),
));

foreach($data as $item) {
	
	echo $this->element('table/row', array(
		'cols' => array(
			$item['Advert']['heading'],
			$item['Advert']['url'],
			Defines::$statuses[ $item['Advert']['status'] ],
		),
		'id' => $item['Advert']['id'],
		'options' => array(
			'view' => false
		)
	));
	
}

echo $this->element('table/foot');