<section id="content">
<?php echo $this->element('sidebar/detail_header', array(
	'name' => $event['Event']['name'], 
	'event_big' => $event['Event']['big'], 
	'joined' => $event['Event']['big']==$logged['Checkin']['event_big'],
	'confirm' => $logged['Checkin']['big']!=null,
));?>
<div class="profile-wrapper-place">
	<div class="gallery">
		<?php if (empty($event['DefaultPhoto']['big'])) {
			echo $this->Img->thumb_place_default_photo(
				$event['Event']['name'],
				array('class' => 'main'),
				$event['Place']['category_id'], 
				262, 262
			);
		} else {
			echo $this->Img->thumb_event_photo(
				$event['Event']['name'],
				array('class' => 'main'),
				$event['Event']['big'], 
				$event['DefaultPhoto']['gallery_big'], 
				$event['DefaultPhoto']['big'], 
				$event['DefaultPhoto']['original_ext'],
				262, 262
			);
		}
		?>
		<div class="gallery-strip">
			<?php if (!empty($photos)):
				$i = 1;
				foreach ($photos as $photo): 
			?>
			<?php echo $this->Img->thumb_event_photo(
					__('Event photo'),
					array('class' => 'small_0'.$i),
					$event['Event']['big'], 
					$event['Gallery'][0]['big'], 
					$photo['Photo']['big'], 
					$photo['Photo']['original_ext'],
					50, 50
			);?>
			<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
	<div class="place-details">
		<!--<p>
			<?php //echo __('Event at place %s', $this->Html->link($event['Place']['name'], array('controller' => 'places', 'action' => 'detail', $event['Place']['big'], $event['Place']['slug']))); ?>
		</p>-->
		<?php if (!empty($event['Event']['start_date'])):
		?>
		<p>
			<span class="event_date">
				<?php
					echo __('The event starts on %s', $this->Date->dateTime($event['Event']['start_date']));
					echo !empty($event['Event']['end_date']) ? __(' and last until %s', $this->Date->dateTime($event['Event']['end_date'])) : '';
					echo '.';
				?>
			</span>
		</p>
		<?php endif;
		if (!empty($event['Event']['daily_start']) && !empty($event['Event']['daily_end'])): 
		?>
		<p>
			<span class="time">Monday - Friday <?php echo $this->Date->time($event['Event']['daily_start']) . ' - ' . $this->Date->time($event['Event']['daily_end']); ?></span>
		</p>
		<?php endif;
			
			if (!empty($event['Event']['rating_avg'])): 
				echo '<div id="rating-scale">' . 
						$this->element('events/rating', array('rating' => $event['Event']['rating_avg'], 'big' => $event['Event']['big'])) . 
					 '</div>';
				?>
				<script type="text/javascript">
					$('#rating-scale').on('click', 'a', function(){
						$('#rating-scale').html('<?php echo __("Rating event..."); ?>');
						$('#rating-scale').load( $(this).attr('href') );
						return false;
					});
				</script>
				<?php
			endif;

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
		<?php echo $this->element('social_share'); ?>
	</div>
    </div>
	<div class="profile-tabs"><?php

		echo $this->element('subpages/tabs', array(
			'tabs' => array(
				__('News')				=> array('controller' => 'places', 'action' => 'news', $event['Event']['place_big']),
				__('Photo')				=> array('action' => 'gallery', $event['Event']['big']),
				__('People')			=> array('action' => 'people', $event['Event']['big']),
			),
		));
		
	?></div>

</section>