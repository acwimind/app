<?php

if (empty($places)):

	echo $this->element('flash/info', array('message' => __('No places')));

endif;

$already_places = array();
$i = 0;
$black = 0;
foreach ($places as $place):

	$left = $i%4==0;
	if ($left && $i != 0) {
		$black++; 
	}

if (in_array($place['Place']['big'], $already_places)) {
	continue;	//do not repeat places (in case there were 2 events in 1 place)
}

$already_places[] = $place['Place']['big'];
?>
<div class="gall_card<?php echo ($black%2==0 ? ' black' : '') . ($left ? ' left' : ''); ?>"><?php 

	$img = $this->Img->place_photo($place['Place']['big'], $place['Photo']['gallery_big'], $place['Photo']['big'], $place['Photo']['original_ext'], 100, 100);
	if (empty($img)) {
		$img = $this->Img->place_default_photo($place['Place']['category_id'], 100, 100);
	}
	
	$options = array('escape' => false, 'class' => 'card_img');
	echo $this->Html->link(
			$this->Html->image($img), 
			array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']),
			$options
	);

	echo '<p>' . $this->Html->link($place['Place']['name'], array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug'])) . '</p>';

?></div>
<?php 
	$i++;
endforeach; ?>
