<?php

echo '<h1>' . __('List Categories') . '</h1>';

// Filter
echo $this->AdvForm->create('Category', array('action' => 'index', 'class' => 'form-horizontal', 'type' => 'get'));

echo '<div class="control-group">';
echo $this->AdvForm->label('srchphr', 'Search by Name', 'control-label');
echo $this->AdvForm->input('srchphr', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

echo '<div class="controls">';
echo $this->AdvForm->submit('Filter', array(
	'class' => 'submit btn btn-primary', 
	'after' =>  $has_filter ? $this->Html->link('Cancel Filter', array('?' => array('cancel_filter' => 1)), array('class' => 'btn')) : '',
));
echo '</div>';
echo '</div>';

echo $this->AdvForm->end();

echo $this->Html->link('<i class="icon-plus"></i> ' . __('New Category'), array('action' => 'add'), array('class' => 'btn', 'escape' => false));

echo $this->element('table/head', array(
	'cols' => array(
		'Category.name' => __('Name'), 
		'Category.external_name' => __('External Name'), 
		'Category.external_source' => __('External Source'),
		__('Category Picture / Icon'),
	),
));

foreach($data as $item) {
	
	echo $this->element('table/row', array(
		'cols' => array(
			$item['Category']['name'],
			$item['Category']['external_name'],
			$item['Category']['external_source'],
			(
				$this->Img->category_picture($item['Category']['id'], 0, 50) != ''
				? $this->Html->image(
					$this->Img->category_picture($item['Category']['id'], 0, 50)
				)
				: ' &minus; '
			),
		),
		'id' => $item['Category']['id'],
		'options' => array('view' => false),
	));
	
}

echo $this->element('table/foot');