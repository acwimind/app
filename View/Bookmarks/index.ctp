<?php

if (empty($bookmarks)):

	echo $this->element('flash/info', array('message' => __('No bookmarks')));

endif;
$i = 0;
$leftCount = 0;
foreach ($bookmarks as $bm):
$left = $i%2 == 0;
?>
<div class="gall_card bookmark<?php echo $left ? ' left' : null; ?><?php echo $leftCount%2==0 ? ' black' : null; ?>"><?php 

	$img = '';
	if (isset($bm['Place']['DefaultPhoto']['big']) && $bm['Place']['DefaultPhoto']['big'] > 0) {
		$img = $this->Img->place_photo($bm['Place']['big'], $bm['Place']['DefaultPhoto']['gallery_big'], $bm['Place']['DefaultPhoto']['big'], $bm['Place']['DefaultPhoto']['original_ext'], 100, 100);
	}
	if (empty($img))  {
		$img = $this->Img->place_default_photo($bm['Place']['category_id'], 100, 100);
	}

	$options = array('escape' => false, 'class' => 'bkmrk_img');
	echo $this->Html->link(
			$this->Html->image($img), 
			array('controller' => 'places', 'action' => 'detail', $bm['Place']['big'], $bm['Place']['slug']),
			$options
	);

	echo $this->Html->link(
		$bm['Place']['name'], 
		array('controller' => 'places', 'action' => 'detail', $bm['Place']['big'], $bm['Place']['slug'])
	); 

	echo $this->Html->link(
		__('Remove Bookmark'), 
		array('controller' => 'bookmarks', 'action' => 'remove', $bm['Place']['big']),
		array('class' => 'remove')
	);

?></div>
<?php 
	$i++;
    if (!$left) 
    	$leftCount++;

	endforeach; ?>
