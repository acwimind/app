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

	if (!empty($ev['Event']['big']) && $ev['Event']['type'] != 2 && $ev['Event']['status'] != 0):
		
		$img = $this->Img->event_photo($ev['Event']['big'], $ev['DefaultPhoto']['gallery_big'], $ev['DefaultPhoto']['big'], $ev['DefaultPhoto']['original_ext'], '100', '100');
		if (empty($img))  {
			$img = $this->Img->place_default_photo($ev['Place']['category_id'], 100, 100);
		}
		
		$options = array('escape' => false, 'class' => 'card_img');
		echo $this->Html->link(
				$this->Html->image($img), 
				array('controller' => 'events', 'action' => 'detail', $ev['Event']['big'], $ev['Event']['slug']),
				$options
		);
		
		$name = $ev['Event']['name'];


		echo $this->Html->link(
						$ev['Event']['name'], 
						array('controller' => 'events', 'action' => 'detail', $ev['Event']['big'], $ev['Event']['slug'])
		); 

		/*echo '<p>' . $this->Date->time($ev['Event']['daily_start']) . ' - ' . $this->Date->time($ev['Event']['daily_end']) . '</p>';

		echo $ev['Event']['short_desc'];

		if (!empty($ev['Event']['long_desc'])):
			echo $this->Html->link(__('Show more...'), array('controller' => 'events', 'action' => 'detail', $ev['Event']['big'], $ev['Event']['slug']));
		endif;*/

	endif;

?></div>
<?php 
    $i++;
endforeach; ?>