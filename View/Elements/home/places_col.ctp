<?php

echo '<h3>' . __('Last Visited Places') . '</h3>';
echo '<ul class="place_list_ul">';

if (empty($places))
{
	?>
	<li class='place_01'>
		<span></span>
		<p class="mature_dummy"><strong>You haven't visited any places yet.</strong></p>
		<p class="mature_dummy_text">Find interesting places here and see what they can offer to you.</p>
	</li>
	<?php 
}
else 
{

	$i = 0;
	foreach($places as $place) {
		$i++;
		$options = array('escape' => false, 'class' => 'mature_img');
		if ($i == 1)
			unset($options['class']);
			
		echo "<li class='place_0".$i."'><span></span>";
		
		if ($place['DefaultPhoto']['big'] > 0) { 
			echo $this->Html->link(
					$this->Html->image(
						$this->Img->place_photo($place['Place']['big'], $place['DefaultPhoto']['gallery_big'], $place['DefaultPhoto']['big'], $place['DefaultPhoto']['original_ext'], 100, 100)
				), array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']), $options);
		} else {
			echo  $this->Html->link(
					$this->Html->image(
						$this->Img->place_default_photo($place['Place']['category_id'], 100, 100)
			), array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']), $options);
		}
			?>
	        	<div class="rating_average">
	                        <span style="width: <?php echo $this->App->rating_counter($place['Place']['rating_avg']);?>px;"><?php echo $this->App->rating_counter($place['Place']['rating_avg']); ?></span>
				</div> 
	        <?php
	        echo $this->Html->link(
	        	$this->App->shorten($place['Place']['name'], 22), 
	        	array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']),
	        	array('title' => $place['Place']['name']));
	        if ($i != 1):
	        	$address = (!empty($place['Place']['address_street']) ? $place['Place']['address_street'] : '') . 
		        	' ' . (!empty($place['Place']['address_street_no']) ? $place['Place']['address_street_no'] : '') . 
		        	' ' . (!empty($place['Region']['city']) ? $place['Region']['city'] : '');
	        ?>
	        	<p class="home_desc" title="<?php echo $address; ?>">
		        <?php 
		        echo $this->App->shorten($address, 23);
		        ?>
	        	</p>
	        <?php
	        endif;
		echo '</li>';
		
	}
	if($i >4 ){
	echo $this->Html->link(
		'<strong>'.__('Show all...').'</strong>', 
		array('controller' => 'members', 'action' => 'my_profile', '#' => 'tab-visited-places'), 
		array('class' => 'show-more', 'escape' => false)
	);
	}
}
echo '</ul>';