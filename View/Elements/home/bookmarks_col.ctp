<?php

echo '<h3>' . __('My Bookmarks') . '</h3>';

echo '<ul class="place_list_ul">';

if (empty($bookmarks))
{
	?>
	<li class='place_01'>
		<span></span>
		<p class="mature_dummy"><strong>Nothing bookmarked yet.</strong></p>
		<p class="mature_dummy_text">Visit interesting places or find them here and add them to bookmarks to see what's up.</p>
	</li>
	<?php 
}
else 
{
	$i = 0;
	foreach($bookmarks as $place) {
		$i++;
		$options = array('escape' => false, 'class' => 'mature_img');
		if ($i == 1)
			unset($options['class']);
			
		echo "<li class='place_0".$i."'><span></span>";
		
		if (isset($place['Place']['DefaultPhoto']['big']) && $place['Place']['DefaultPhoto']['big'] > 0) { 
			echo $this->Html->link(
					$this->Html->image(
						$this->Img->place_photo($place['Place']['big'], $place['Place']['DefaultPhoto']['gallery_big'], $place['Place']['DefaultPhoto']['big'], $place['Place']['DefaultPhoto']['original_ext'], 100, 100)
			), array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']), $options);
		} else {
			echo $this->Html->link(
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
		        	' ' . (!empty($place['Place']['Region']['city']) ? $place['Place']['Region']['city'] : '');
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
			array('controller' => 'members', 'action' => 'my_profile', '#' => 'tab-bookmarks'), 
			array('class' => 'show-more', 'escape' => false)
		);
	}
}
echo '</ul>';