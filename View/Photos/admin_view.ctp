<div class="admin_photo">
<?php 
	if (!empty($photo['Gallery']['event_big'])):

		$img = $this->Img->event_photo(
			$photo['Gallery']['event_big'], 
			$photo['Photo']['gallery_big'],
			$photo['Photo']['big'], 
			$photo['Photo']['original_ext'], 
			640, 480
		);

		echo $this->Html->image($img);
		echo '<br />';
		echo $this->Html->link('Go to event', array('controller' => 'events', 'action' => 'detail', $photo['Gallery']['event_big'], 'admin' => false));

	else: 

		$img = $this->Img->place_photo(
			$photo['Gallery']['place_big'], 
			$photo['Photo']['gallery_big'], 
			$photo['Photo']['big'], 
			$photo['Photo']['original_ext'],
			640, 480
		);

		echo $this->Html->image($img);
		echo '<br />';
		echo $this->Html->link('Go to place', array('controller' => 'places', 'action' => 'detail', $photo['Gallery']['place_big'], 'admin' => false));

	endif;
?> 
</div>