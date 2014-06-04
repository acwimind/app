<?php

echo '<h1>' . __('List Regions') . '</h1>';

// Filter
echo $this->AdvForm->create('Region', array('action' => 'index', 'class' => 'form-horizontal', 'type' => 'get'));

echo '<div class="control-group">';
echo $this->AdvForm->label('srchphr', 'Search by Name', 'control-label');
echo $this->AdvForm->input('srchphr', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('country', 'Filter by Country', 'control-label');
echo $this->AdvForm->input('country', array('label' => false, 'options' => array(null => 'All') + Defines::$countries, 'class' => 'input-medium', 'div' => 'controls'));

echo '<div class="controls">';
echo $this->AdvForm->submit('Filter', array(
	'class' => 'submit btn btn-primary', 
	'after' =>  $has_filter ? $this->Html->link('Cancel Filter', array('?' => array('cancel_filter' => 1)), array('class' => 'btn')) : '',
));
echo '</div>';
echo '</div>';

echo $this->AdvForm->end();

echo $this->Html->link('<i class="icon-plus"></i> ' . __('New Region'), array('action' => 'add'), array('class' => 'btn', 'escape' => false));

echo $this->element('table/head', array(
	'cols' => array('Region.city' => __('City'), 'Region.country' => 'Country', 'Map',),
));

foreach($data as $item) {
	
	$lowleft = (isset($item['Region']['lonlat_lowerleft']) ? $item['Region']['lonlat_lowerleft'] : null);
	$topright = (isset($item['Region']['lonlat_topright']) ? $item['Region']['lonlat_topright'] : null);
	
	echo $this->element('table/row', array(
		'cols' => array(
			$item['Region']['city'],
			isset(Defines::$countries[ $item['Region']['country'] ]) ? Defines::$countries[ $item['Region']['country'] ] : __('Unknown') . ' (' . $item['Region']['country'] . ')',
			$this->Map->static_region($lowleft, $topright),
		),
		'id' => $item['Region']['id'],
		'options' => array('view' => false),
	));
	
}

echo $this->element('table/foot');