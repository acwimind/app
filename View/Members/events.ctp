<?php

if (empty($events)):

	echo $this->element('flash/info', array('message' => __('No events')));

endif;

$i = 0;
$black = 0;
foreach ($events as $ev):

	$left = $i%4==0;
	if ($left && $i != 0) {
		$black++; 
	} 
?>
<div class="gall_card<?php echo ($black%2==0 ? ' black' : '') . ($left ? ' left' : ''); ?>"><?php 

	if (!empty($ev['Event']['big']) && $ev['Event']['hidden'] == false):
		
		$img = $this->Img->event_photo($ev['Event']['big'], $ev['Photo']['gallery_big'], $ev['Photo']['big'], $ev['Photo']['original_ext'], 100, 100);
		if (empty($img))  {
			$img = $this->Img->place_default_photo($ev['Place']['category_id'], 100, 100);
		}

		$options = array('escape' => false, 'class' => 'card_img');
		echo $this->Html->link(
				$this->Html->image($img), 
				array('controller' => 'events', 'action' => 'detail', $ev['Event']['big'], $ev['Event']['slug']),
				$options
		);

		echo '<p>' . __(
			'%s <span>at</span> %s', 
			$this->Html->link($ev['Event']['name'], array('controller' => 'events', 'action' => 'detail', $ev['Event']['big'], $ev['Event']['slug'])), 
			$this->Html->link($ev['Place']['name'], array('controller' => 'places', 'action' => 'detail', $ev['Place']['big'], $ev['Place']['slug']))
		) . '</p>';

	else: 
		
		$img = $this->Img->place_photo($ev['Place']['big'], $ev['Photo']['gallery_big'], $ev['Photo']['big'], $ev['Photo']['original_ext'], 100, 100);
		if (empty($img)) {
			$img = $this->Img->place_default_photo($ev['Place']['category_id'], 100, 100);
		}
		
		$options = array('escape' => false, 'class' => 'card_img');
		echo $this->Html->link(
				$this->Html->image($img), 
				array('controller' => 'places', 'action' => 'detail', $ev['Place']['big'], $ev['Place']['slug']),
				$options
		);

		echo '<p>' . __(
			'<span>Event at</span><br> %s', 
			$this->Html->link($ev['Place']['name'], array('controller' => 'places', 'action' => 'detail', $ev['Place']['big'], $ev['Place']['slug']))
		) . '</p>';

	endif;

	

?></div>
<?php

 	$i++;
endforeach; ?>
