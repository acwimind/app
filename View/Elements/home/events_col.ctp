<?php

echo '<h3>' . __('Last Visited Events') . '</h3>';
echo '<ul class="place_list_ul">';

if (empty($events))
{
	?>
	<li class='place_01'>
		<span></span>
		<p class="mature_dummy"><strong>You haven't visited any events yet.</strong></p>
		<p class="mature_dummy_text">Find interesting places here and see what's going on there and when.</p>
	</li>
	<?php 
}
else 
{

	$i = 0;
	foreach($events as $item) {
		$i++;
		$options = array('escape' => false, 'class' => 'mature_img');
		if ($i == 1)
			unset($options['class']);
		
		echo "<li class='place_0".$i."'><span></span>";
			
			if ($item['DefaultPhoto']['big'] > 0) {
				echo $this->Html->link(	
						$this->Html->image(
						$this->Img->event_photo($item['Event']['big'], $item['DefaultPhoto']['gallery_big'], $item['DefaultPhoto']['big'], $item['DefaultPhoto']['original_ext'], 100, 100)
				), array('controller' => 'events', 'action' => 'detail', $item['Event']['big'], $item['Event']['slug']), $options); 
			} elseif (!empty($item['Place']['DefaultPhoto']) && $item['Place']['DefaultPhoto']['big'] > 0) {
				echo $this->Html->link(
					$this->Html->image(
					$this->Img->place_photo($item['Place']['big'], $item['Place']['DefaultPhoto']['gallery_big'], $item['Place']['DefaultPhoto']['big'], $item['Place']['DefaultPhoto']['original_ext'], 100, 100)
				), array('controller' => 'events', 'action' => 'detail', $item['Event']['big'], $item['Event']['slug']), $options);
			} else {
				echo $this->Html->link(
					$this->Html->image(
					$this->Img->place_default_photo($item['Place']['category_id'], 100, 100)
				), array('controller' => 'events', 'action' => 'detail', $item['Event']['big'], $item['Event']['slug']), $options);
			}
		
	        ?>
	        	<div class="rating_average">
	                        <span style="width: <?php echo $this->App->rating_counter($item['Event']['rating_avg']);?>px;"><?php echo $this->App->rating_counter($item['Event']['rating_avg']); ?></span>
				</div> 
	        <?php
	        echo $this->Html->link(
	        	$this->App->shorten($item['Event']['name'], 22), 
	        	array('controller' => 'events', 'action' => 'detail', $item['Event']['big'], $item['Event']['slug']),
	        	array('title' => $item['Event']['name']));
	        if ($i != 1):
	        ?>
	        <p class="home_desc">
		        <!-- at <br />  -->
		        <?php 
		        echo $this->Html->link(
		        	$this->App->shorten($item['Place']['name'], 22), 
		        	array('controller' => 'places', 'action' => 'detail', $item['Place']['big'], $item['Place']['slug']),
		        	array('title' => $item['Place']['name']));
		        ?>
		        </p> 
	        <?php 
	        endif;
		echo '</li>';
		
	}
	if($i >4 ){
	echo $this->Html->link(
		'<strong>'.__('Show all...').'</strong>', 
		array('controller' => 'members', 'action' => 'my_profile', '#' => 'tab-attended-events'), 
		array('class' => 'show-more', 'escape' => false)
	);
	}
}
echo '</ul>';