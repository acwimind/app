<?php

echo '<h1>' . __('List Users') . '</h1>';

// Filter
echo $this->AdvForm->create('Member', array('action' => 'index', 'class' => 'form-horizontal', 'type' => 'get'));

echo '<div class="control-group">';
echo $this->AdvForm->label('lang', 'Filter by Language', 'control-label');
echo $this->AdvForm->input('lang', array('label' => false, 'options' => array(null => __('All')) + Defines::$languages, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('srchphr', 'Search', 'control-label');
echo $this->AdvForm->input('srchphr', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('type', 'Role', 'control-label');
echo $this->AdvForm->input('type', array('label' => false, 'class' => 'input-medium', 'div' => 'controls', 'options' => array(null => __('All')) + Defines::$member_types));

echo $this->AdvForm->label('RegisteredFrom', '<b>Registered:</b> From', 'control-label');
echo $this->AdvForm->input('RegisteredFrom', array('label' => false, 'picker' => 'datetime', 'toggle' => array('div' => 'input controls')));
echo $this->AdvForm->label('RegisteredTo', 'To', 'control-label');
echo $this->AdvForm->input('RegisteredTo', array('label' => false, 'picker' => 'datetime', 'toggle' => array('div' => 'input controls')));

echo '<div class="controls">';
echo $this->AdvForm->submit('Filter', array(
	'class' => 'submit btn btn-primary', 
	'after' =>  $has_filter ? $this->Html->link('Cancel Filter', array('?' => array('cancel_filter' => 1)), array('class' => 'btn')) : '',
));
echo '</div>';
echo '</div>';

echo $this->AdvForm->end();


echo $this->Html->link('<i class="icon-plus"></i> ' . __('New User'), array('action' => 'add'), array('class' => 'btn', 'escape' => false));

echo $this->element('table/head', array(
	'cols' => array(
		'Member.email' => __('E-mail'), 
		'Member.name' => __('First Name'), 
		'Member.surname' => __('Last Name'), 
		'Member.type' => __('User Role'), 
		'Member.lang' => __('Language'), 
		'Member.created' => __('Registered') 
	),
));

foreach($data as $item) {
	
	echo $this->element('table/row', array(
		'cols' => array(
			$item['Member']['email'],
			$item['Member']['name'],
			$item['Member']['surname'],
			Defines::$member_types[ $item['Member']['type'] ],
			Defines::$languages[ $item['Member']['lang'] ],
			date('d M Y, H:i', strtotime($item['Member']['created'])),
		),
		'id' => $item['Member']['big'],
	));
	
}

echo $this->element('table/foot');