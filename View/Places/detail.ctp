<?php echo $this->element('sidebar/detail_header', array(
	'name' => $place['Place']['name'], 
	'place_big' => $place['Place']['big'], 
	'joined' => $is_joined,
	'confirm' => $logged['Checkin']['big']!=null,
	'bookmarked' => $is_bookmarked,
));
$this->Html->script('https://maps.googleapis.com/maps/api/js?sensor=false', array('block' => 'script'));
$this->Html->script('map', array('block' => 'script'));
?>
<div class="profile-wrapper-place">
	<div class="gallery">
		<?php 
		if (empty($place['DefaultPhoto']['big'])) {
			echo $this->Img->thumb_place_default_photo(
				$place['Place']['name'],
				array('class' => 'main'),
				$place['Place']['category_id'], 
				262, 262
			);
		} else {
			echo $this->Img->thumb_place_photo(
				$place['Place']['name'],
				array('class' => 'main'),
				$place['Place']['big'], 
				$place['DefaultPhoto']['gallery_big'], 
				$place['DefaultPhoto']['big'], 
				$place['DefaultPhoto']['original_ext'],
				262, 262
			);
		}
		?>
		<div class="gallery-strip">
			<?php if (!empty($photos)):
				$i = 1;
				foreach ($photos as $photo): 
			?>
			<?php echo $this->Img->thumb_place_photo(
					__('Place photo'),
					array('class' => 'small_0'.$i),
					$place['Place']['big'], 
					$place['Gallery'][0]['big'], 
					$photo['Photo']['big'], 
					$photo['Photo']['original_ext'],
					50, 50
			);?>
			<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="place-details">
		<?php if (!empty($place['Region']['city'])):
			$address = '';
			if (!empty($place['Place']['address_street'])) { $address .= $place['Place']['address_street']; }
			if (!empty($place['Place']['address_street_no'])) { $address .= ' ' . $place['Place']['address_street_no']; }
			$address .= ', ' . $place['Region']['city'] . ', ' . Defines::$countries[$place['Region']['address_country']]; 
		?>
		<p>
			<span class="address"><?php echo $address; ?></span>
		</p>
		<?php endif;
		if (!empty($place['Place']['phone'])): 
		?>
		<p>
			<span class="phone"><?php echo $place['Place']['phone']; ?></span>
		</p>
		<?php endif;
		if (!empty($place['Place']['email'])):
		?>
		<p>
			<a class="mail" href="mailto:<?php echo $place['Place']['email']; ?>"><?php echo $place['Place']['email']; ?></a>
		</p>
		<?php endif;
		 if (!empty($place['Place']['url'])): ?>
		<p>
			<?php echo $this->Html->link($place['Place']['url'], $place['Place']['url']); ?>
		</p>
		<?php endif;
			if (!empty($place['Place']['rating_avg'])): 
		?>
		<div class="rating">
                    <?php  
                            $a = $place['Place']['rating_avg'];
                            
                    ?>
			<span style="width: <?php echo $this->App->rating_counter($place['Place']['rating_avg']); ?>px"></span>
		</div>
		<?php endif; 
		

		if (!empty($place['Place']['short_desc'])):
		?>
		<p class="short_desc">
			<?php echo $place['Place']['short_desc']; ?>
		</p>
		<?php endif;
		
		if (!empty($place['Place']['opening_hours'])):
		?>
		<p class="opening_hours">
			<?php echo $place['Place']['opening_hours']; ?>
		</p>
		
		<?php endif;

		if (!empty($event['Event']['short_desc'])):
		?>
		<p class="short_desc">
			<?php echo $event['Event']['short_desc']; ?>
		</p>
		<?php endif;
		
		 if (!empty($event['Event']['long_desc'])): ?>
		<p class="long_desc">
			<?php echo $event['Event']['long_desc']; ?>
		</p>
		<?php endif; ?>
		<?php //echo $this->element('social_share'); ?>
		
		
	</div>
    </div>
	<div class="profile-tabs"><?php

		echo $this->element('subpages/tabs', array(
			'tabs' => array(
				__('Recent events')		=> array('action' => 'events', $place['Place']['big'], 'recent'),
				__('Upcoming events')	=> array('action' => 'events', $place['Place']['big'], 'upcoming'),
				__('Past events')		=> array('action' => 'events', $place['Place']['big'], 'past'),
				__('News')				=> array('action' => 'news', $place['Place']['big']),
				__('Photo')				=> array('action' => 'gallery', $place['Place']['big']),
				__('People')			=> array('action' => 'people', $place['Place']['big']),
				__('Map')				=> array('action' => 'show_map', $place['Place']['big']),
			),
		));
		
	?></div>
