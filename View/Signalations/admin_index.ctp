<?php

echo '<h1>' . __('List of Signalations') . '</h1>';

// Filter
echo $this->AdvForm->create('Signalation', array('action' => 'index', 'class' => 'form-horizontal', 'type' => 'get'));

$signals = Defines::$signalations;
foreach ($signals as $key=>$value)
{
	$signals[intval($key)] = $value;
}
echo '<div class="control-group">';
echo $this->AdvForm->label('gemtype', 'Filter by Reported', 'control-label');
echo $this->AdvForm->input('gemtype', array('label' => false, 'options' => array(null => 'All', '1' => 'Only Users', '5' => 'Only Photos') /* + Defines::$voucher_types_filter*/, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('sigtype', 'Filter by Type', 'control-label');
echo $this->AdvForm->input('sigtype', array('label' => false, 'options' => array(null => 'All') + $signals, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('status', 'Filter by Status', 'control-label');
echo $this->AdvForm->input('status', array('label' => false, 'options' => array(null => 'All', intval(INACTIVE) => __('Resolved'), ACTIVE => __('Pending')), 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('status', 'Search by Member', 'control-label');
echo $this->AdvForm->input('srchname', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

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

?> 
<script type="text/javascript"?>
$('input.export_date').datetimepicker({'dateFormat': 'yy-mm-dd', 'timeFormat': 'HH:mm'});
</script>
<?php 

//echo $this->Html->link('<i class="icon-plus"></i> ' . __('New Content'), array('action' => 'add'), array('class' => 'btn', 'escape' => false));

echo $this->element('table/head', array(
	'cols' => array('Gem.type' => __('Reported'), 'Member.name' => __('Member'), 'Signalation.type' => __('Type'), 'Signalation.text' => 'Text', 'Signalation.status' => 'Status', 'Signalation.created' => 'Created' ),
));

foreach($data as $item) {
//	print_r('<pre>');
//	print_r($item);
//	print_r('</pre>');
//	die();
	if ($item['Signalation']['status'] == ACTIVE)
	{
		$options = array(
			'view' => false, 
			'edit' => false,
			'solved' => array( __('Mark as solved'), array('action' => 'solved', $item['Signalation']['id']), 'ok'),
		);
	}
	else 
	{
		$options = array(
			'view' => false, 
			'edit' => false,
		);
	}
	
	$member = $item['Member']['name'] . (isset($item['Member']['middle_name']) ? ' ' . $item['Member']['middle_name'] . ' ' : ' ') . $item['Member']['surname'];
	if ($item['Gem']['type'] == 1)
	{
		// Member
		// TODO: link to member in admin section, not frontend? create member detail page in admin
		$gemLink = $this->Html->link('<i class="icon-user"></i>', array('controller' => 'members', 'action' => 'public_profile', $item['Gem']['big'], 'admin' => false), array('escape' => false));
	}
	elseif ($item['Gem']['type'] == 5)
	{
		// Photo
		// TODO: link to photo (in admin section), create photo detail page in admin
		$gemLink = $this->Html->link('<i class="icon-picture"></i>', array('controller' => 'photos', 'action' => 'view', $item['Gem']['big']), array('escape' => false));
	}
	
	echo $this->element('table/row', array(
		'cols' => array(
			$gemLink,
			$this->Html->link($member, array('controller' => 'members', 'action' => 'index', '?' => array('srchphr' => $item['Member']['email']))),
			Defines::$signalations[ $item['Signalation']['type'] ],
			$item['Signalation']['text'],
			$item['Signalation']['status']==INACTIVE ? '<sna class="label label-success">'.__('Resolved').'</span>' : '<sna class="label label-important">'.__('Pending').'</span>',
			date('d M Y, H:i', strtotime($item['Signalation']['created'])),
		),
		'id' => $item['Signalation']['id'],
		'options' => $options
	));
	
}

echo $this->element('table/foot');