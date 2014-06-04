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
<?php if (isset($loggedBig) && $loggedBig != $photo['Member']['big']):?>
	<a id="<?php echo $photo['Photo']['big'];?>" class="signal_btn sign_btn" title="Report this image">Report</a>
<?php
	endif; 

	if (!empty($photo['Event']['big'])):

		if($photo['Event']['hidden'] == false)
		{
			$alt = __('Photo from %s at %s', $photo['Event']['name'], $photo['Place']['name']);
		}
		else
		{
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

	endif; 

	if (empty($img))  {
		echo '<div class="no-photo">'.__('photo not available').'</div>';
	} else {
		echo $img;
	}

	$name = $this->Html->link(
		$photo['Member']['name'] . ' ' . mb_substr($photo['Member']['surname'], 0, 1) . '.',
		array('controller' => 'members', 'action' => 'public_profile', $photo['Member']['big'])
	);

	echo '<p>' . __('by %s', '<br />' . $name) . '</p>';

?></div>
<?php 
    $i++;
endforeach; ?>
