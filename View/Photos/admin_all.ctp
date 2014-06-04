<div>
<?php 
$i = 1;
$black = 0;
$fc = 'place_photo';
foreach ( $data as $photo ) {
	
	$left = $i % 4 == 0;
	if ($left && $i != 0) {
		$black ++;
	}
	debug($photo);
	
//	$img = $this->Img->{$fc} ('1','1', 300,300 );
//	$img_full = $this->Img->{$fc} ( '1','1', $photo ['Photo'][$i]['big'], $photo['Photo'][$i] ['original_ext'],600,600 );, 500, 500
	
	$img = $this->Img->{$fc} ($photo['Gallery']['place_big'],$photo['Gallery'] ['big'], $photo['Photo']['big'], $photo['Photo']['original_ext'],300,300,true );
	
	//$img = $this->Img->{$fc} ( $owner ['big'], $data ['Gallery'] ['big'], $photo ['big'], $photo ['original_ext'], 104, 104 );
	$img_full = $this->Img->{$fc} ($photo['Gallery']['place_big'],$photo['Gallery'] ['big'], $photo['Photo']['big'], $photo['Photo']['original_ext'] );
	
	
	
	echo '<div class="gall_card' . ($black % 2 == 0 ? ' black' : '') . ($left ? ' left' : '') . '">';
	
	$icon_class = $black % 2 == 0 ? ' icon-white' : '';
	
	if (! empty ( $img )) {
		echo $this->Html->link ( $this->Html->image ( $img, array (
				'alt' => 'photo' 
		) ), ! empty ( $img_full ) ? $img_full : '#', array (
				'escape' => false,
				'class' => 'zoom-image' 
		) );
	} else {
		echo '<div class="no-photo">' . __ ( 'photo not available' ) . '</div>';
	}
	
	echo $this->Html->link ( '<i class="icon-trash' . $icon_class . '"></i> ' . __ ( 'Delete' ), array (
			'controller' => 'photos',
			'action' => 'delete',
		$photo ['Photo']['big']
	), array (
			'escape' => false,
			'confirm' => __ ( 'Are you sure?\nThis action cannot be undone!' ) 
	) );
	
	echo '</div>';
	
	$i ++;
}

?>
</div>