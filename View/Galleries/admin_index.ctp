<?php

echo '<h2>' . __('Gallery for ');

if (isset($data['Event']['big']) && $data['Event']['big']>0) {
	echo __(
		'event %s at ', 
		$this->Html->link($data['Event']['name'], array('controller' => 'events', 'action' => 'index', '?' => array('srchname' => $data['Event']['name'], 'srchplace' => $data['Place']['name'])))
	);
}

echo __(
	'place %s',
	$this->Html->link($data['Place']['name'], array('controller' => 'places', 'action' => 'index', '?' => array('srchphr' => $data['Place']['name'])))
) . '</h2>';

if (isset($data['Event']['big']) && $data['Event']['big']>0) {
	echo $this->Html->link(__('Back to event edit'), array('controller' => 'events', 'action' => 'edit', $data['Event']['big']));
	$owner = $data['Event'];
	$fc = 'event_photo';
} else {
	echo $this->Html->link(__('Back to place edit'), array('controller' => 'places', 'action' => 'edit', $data['Place']['big']));
	$owner = $data['Place'];
	$fc = 'place_photo';
}


if ($fc == 'event_photo') {
	echo $this->AdvForm->create('Event', array('url' => array('controller' => 'events', 'action' => 'upload', $data['Event']['big'])));
} else {
	echo $this->AdvForm->create('Place', array('url' => array('controller' => 'places', 'action' => 'upload', $data['Place']['big'])));
}
echo $this->AdvForm->input('photos', array(
	'label' => __('Upload Photos'),
	'uploader' => array(
		'data-preview' => true,		//show preview of uploaded images
		'data-multiple' => true,	//allow upload of multiple files
	),
));
echo $this->AdvForm->submit('Save');
echo $this->AdvForm->end();


echo '<div style="width:670px;">';

$i = 0;
$black = 0;  
foreach($data['Photo'] as $photo) {

	$left = $i%4==0;
	if ($left && $i != 0) {
		$black++; 
	}

	$img = $this->Img->{$fc}($owner['big'], $data['Gallery']['big'], $photo['big'], $photo['original_ext'], 104, 104);
	$img_full = $this->Img->{$fc}($owner['big'], $data['Gallery']['big'], $photo['big'], $photo['original_ext']);
	
	echo '<div class="gall_card' . ($black%2==0 ? ' black' : '') . ($left ? ' left' : '') . '">';

	$icon_class = $black%2==0 ? ' icon-white' : '';
	
	if (!empty($img)) {
		echo $this->Html->link($this->Html->image($img, array('alt' => 'photo')), !empty($img_full) ? $img_full : '#', array('escape' => false, 'class' => 'zoom-image'));
	} else {
		echo '<div class="no-photo">'.__('photo not available').'</div>';
	}
	
	
	if ($photo['big'] == $owner['default_photo_big']) {
		echo '<a><i class="icon-star'.$icon_class.'"></i> ' . __('Default photo') . '</a>';
	//} else {
		}
		echo $this->Html->link(
			'<i class="icon-star-empty'.$icon_class.'"></i> ' . __('Set as default'),
			array('controller' => 'photos', 'action' => 'default', $photo['big']),
			array('escape' => false)
		);
		echo $this->Html->link(
			'<i class="icon-trash'.$icon_class.'"></i> ' . __('Delete'),
			array('controller' => 'photos', 'action' => 'delete', $photo['big']),
			array('escape' => false, 'confirm' => __('Are you sure?\nThis action cannot be undone!'))
		);
	
	
	echo '</div>';
	
	$i++;

}

echo '</div>';
