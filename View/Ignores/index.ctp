<?php

if (empty($ignores)):

	echo $this->element('flash/info', array('message' => __('No people in ignore list')));

endif;

$i = 0;
$black = 0;
foreach ($ignores as $item):

	$left = $i%4==0;
	if ($left && $i != 0) {
		$black++; 
	}

?>
<div class="gall_card<?php echo ($black%2==0 ? ' black' : '') . ($left ? ' left' : ''); ?>">
	<?php 

		$img = $this->Img->profile_picture($item['Recipient']['big'], $item['Recipient']['photo_updated'], 50, 50);
		
		$options = array('escape' => false, 'class' => 'card_img');
		echo $this->Html->link(
				$this->Html->image($img), 
				array('controller' => 'members', 'action' => 'public_profile', $item['Recipient']['big']),
				$options
		);

		echo $this->Html->link(
			$item['Recipient']['name'] . ' ' . ( !empty($item['Recipient']['surname']) ? mb_substr($item['Recipient']['surname'], 0, 1) . '.' : ''),
			array('controller' => 'members', 'action' => 'public_profile', $item['Recipient']['big'])
		);

		echo $this->Html->link(__('Remove'), array('controller' => 'ignores', 'action' => 'remove', $item['Recipient']['big']), array('class' => 'remove'));
	?>		
</div>
<?php 
	
	$i++;

endforeach;
?>
