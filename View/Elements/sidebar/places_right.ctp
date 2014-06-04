<?php

echo '<ul class="adverts">';

foreach($places as $place) {
/*	
	echo '<li>';
	
        if ($place['DefaultPhoto']['big'] > 0) { 
		echo '<p>' . $this->Html->image(
			$this->Img->place_photo($place['Place']['big'], $place['DefaultPhoto']['gallery_big'], $place['DefaultPhoto']['big'], $place['DefaultPhoto']['original_ext'], 100, 100)
		) . '</p>';
	} else {
		echo '<p>' . $this->Html->image(
			$this->Img->place_default_photo($place['Place']['category_id'], 100, 100)
		) . '</p>';
	}
        
	echo $this->Html->link($place['Place']['name'], array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']),array('class' => 'place-title'));
	
        if (!empty($place['Place']['url'])): ?>
			<?php
				if (strpos($place['Place']['url'], 'http://') === FALSE)
					$url = 'http://' . $place['Place']['url'];
				else 
					$url = $place['Place']['url']; 
				echo  $this->Html->link($place['Place']['url'], $url, array('class' => 'website', 'target' => '_blank')); 
        endif;
        
	//echo '<p class="desc">' . $place['Place']['short_desc'] . '</p>';
	
	echo '</li>';
*/	
	echo '<li>';
	
    	echo '<p>' . $this->Html->image(
			$this->Img->advert_picture($place['Advert']['id'], $place['Advert']['photo_updated'], $place['Advert']['photo_ext'], 100, 100)
		) . '</p>';
	    
		
        if (!empty($place['Advert']['url'])):
			if (strpos($place['Advert']['url'], 'http://') === FALSE && strpos($place['Advert']['url'], 'https://') === FALSE)
				$url = 'http://' . $place['Advert']['url'];
			else 
				$url = $place['Advert']['url']; 
			echo $this->Html->link($place['Advert']['heading'], $url, array('class' => 'place-title', 'target' => '_blank'));
			echo $this->Html->link($place['Advert']['url'], $url, array('class' => 'website', 'target' => '_blank')); 
		else:
			echo '<p>' . $place['Advert']['heading'] . '</p>';
        endif;
        
//		echo '<p class="desc">' . $place['Advert']['text'] . '</p>';
	
	echo '</li>';
	
}

echo '</ul>';