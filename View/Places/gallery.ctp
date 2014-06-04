<?php
$i = 0;
$black = 0;
foreach ($photos as $photo):

$left = $i%4==0;
if ($left && $i != 0) {
	$black++; 
}

?>
<div class="gall_card<?php echo ($black%2==0 ? ' black' : '') . ($left ? ' left' : ''); ?>">
<?php /* if (isset($loggedBig) && $loggedBig != $photo['Member']['big']):?>
	<a id="<?php echo $photo['Photo']['big'];?>" class="signal_btn sign_btn" title="Report this image">Report</a>
<?php
	endif; 

	if (!empty($photo['Event']['big'])):

		if($photo['Event']['hidden'] == false)
		{
			$name = $this->Html->link($photo['Event']['name'], array('controller' => 'events', 'action' => 'detail', $photo['Event']['big'], $photo['Event']['slug']));
			$alt = __('Photo from %s at %s', $photo['Event']['name'], $photo['Place']['name']);
		}
		else
		{
			$name = $photo['Place']['name'];
			$alt = __('Photo from %s', $photo['Place']['name']);
		}
		$img = $this->Img->thumb_event_photo(
			$alt, 
			array(),
			$photo['Event']['big'], 
			$photo['Photo']['gallery_big'], 
			$photo['Photo']['big'], 
			$photo['Photo']['original_ext'], 
			100, 100
		);

		if (empty($img))  {
			echo '<div class="no-photo">'.__('photo not available').'</div>';
		} else {
			echo $img;
		}
		
		/*echo $this->Html->link(
			$this->Html->image($img, array('alt' => __('Photo from %s at %s', $photo['Event']['name'], $photo['Place']['name']))),
			$this->Img->event_photo($photo['Event']['big'], $photo['Photo']['gallery_big'], $photo['Photo']['big'], $photo['Photo']['original_ext']),
			array('escape' => false)
		);*//*


	else: 

		$img = $this->Img->thumb_place_photo(
			__('Photo at %s', $photo['Place']['name']), 
			array(),
			$photo['Place']['big'], 
			$photo['Photo']['gallery_big'], 
			$photo['Photo']['big'], 
			$photo['Photo']['original_ext'], 
			100, 100
		);
//		if (empty($img)) {
//			continue;
//		}
		
		echo $img;

		$name = $photo['Place']['name'];

	endif; 

	echo '<p>' . $name . '</p>';
*/

	if (!empty($photo['Event']['big'])):

		if($photo['Event']['hidden'] == false)
		{
			$name = $this->Html->link($photo['Event']['name'], array('controller' => 'events', 'action' => 'gallery', $photo['Event']['big']), array('class' => 'gal_btn'));
			$alt = __('Photo from %s', $photo['Event']['name']);
		}
		else
		{
			$name = $this->Html->link('Place photos', array('controller' => 'events', 'action' => 'gallery', $photo['Event']['big']), array('class' => 'gal_btn'));
			$alt = __('Photo from this place');
		}
		$img = $this->Img->thumb_event_photo(
			$alt, 
			array(),
			$photo['Event']['big'], 
			$photo['Photo']['gallery_big'], 
			$photo['Photo']['big'], 
			$photo['Photo']['original_ext'], 
			100, 100
		);

		if (empty($img))  {
			echo '<div class="no-photo">'.__('photo not available').'</div>';
		} else {
			echo $img;
		}

	else: 

		$img = $this->Img->thumb_place_photo(
			__('Photo from this place'), 
			array(),
			$placeBig, 
			$photo['Photo']['gallery_big'], 
			$photo['Photo']['big'], 
			$photo['Photo']['original_ext'], 
			100, 100
		);
//		if (empty($img)) {
//			continue;
//		}
		
		echo $img;

		$name = 'Place photos';

	endif; 

	echo '<p>' . $name . '</p>';

?></div>
<?php
	$i++;  
endforeach; ?>
<script>
$(document).ready(function(){

	$('body').on('click', '.gal_btn', function(event) {
		//console.log(event.currentTarget.href);
		$.ajax({
		     type: "GET",
		     url: event.currentTarget.href,
		     //data: "id=" + id, // appears as $_GET['id'] @ ur backend side
		     success: function(data) {
		           // data is ur summary
		          $('#tab-content').html(data);
		     }
		   });
		return false;
	});

});
</script>
