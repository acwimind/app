<?php

echo '<h1>' . __('List Content') . '</h1>';

// Filter
echo $this->AdvForm->create('CmsEntry', array('action' => 'index', 'class' => 'form-horizontal', 'type' => 'get'));

echo '<div class="control-group">';
echo $this->AdvForm->label('sectn', 'Filter by Section', 'control-label');
echo $this->AdvForm->input('sectn', array('label' => false, 'options' => array(null => 'All', 'News' => 'News', 'Static' => 'Static') /* + Defines::$voucher_types_filter*/, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('status', 'Filter by Status', 'control-label');
echo $this->AdvForm->input('status', array('label' => false, 'options' => array(null => 'All', intval(INACTIVE) => Defines::$statuses[INACTIVE], ACTIVE => Defines::$statuses[ACTIVE]), 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('srchphr', 'Search by Name', 'control-label');
echo $this->AdvForm->input('srchphr', array('label' => false, 'class' => 'input-medium', 'div' => 'controls'));

echo $this->AdvForm->label('CreatedFrom', '<b>Created:</b> From', 'control-label');
echo $this->AdvForm->input('CreatedFrom', array('label' => false, 'picker' => 'datetime', 'toggle' => array('div' => 'input controls')));
echo $this->AdvForm->label('CreatedTo', 'To', 'control-label');
echo $this->AdvForm->input('CreatedTo', array('label' => false, 'picker' => 'datetime', 'toggle' => array('div' => 'input controls')));

echo $this->AdvForm->label('UpdatedFrom', '<b>Updated:</b> From', 'control-label');
echo $this->AdvForm->input('UpdatedFrom', array('label' => false, 'picker' => 'datetime', 'toggle' => array('div' => 'input controls')));
echo $this->AdvForm->label('UpdatedTo', 'To', 'control-label');
echo $this->AdvForm->input('UpdatedTo', array('label' => false, 'picker' => 'datetime', 'toggle' => array('div' => 'input controls')));

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

echo $this->Html->link('<i class="icon-plus"></i> ' . __('New Content'), array('action' => 'add'), array('class' => 'btn', 'escape' => false));

echo $this->element('table/head', array(
	'cols' => array('CmsEntry.section' => __('Section'), 'CmsEntry.name' => __('Name'), 'CmsEntry.status' => 'Status', 'CmsEntry.created' => 'Created', 'CmsEntry.updated' => 'Updated' ),
));

foreach($data as $item) {
	
	echo $this->element('table/row', array(
		'cols' => array(
			Defines::$cms_sections[ $item['CmsEntry']['section'] ],
			$item['CmsEntry']['name'],
			Defines::$statuses[ $item['CmsEntry']['status'] ],
			date('d M Y, H:i', strtotime($item['CmsEntry']['created'])),
			date('d M Y, H:i', strtotime($item['CmsEntry']['updated'])),
		),
		'id' => $item['CmsEntry']['id'],
		'options' => array('view' => false)
	));
	
}

echo $this->element('table/foot');