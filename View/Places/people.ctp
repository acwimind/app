<?php

if (empty($people)):

	echo $this->element('flash/info', array('message' => __('No people at this place')));

endif;
$isEventsCntrl = $this->params['controller'] == 'events' ? true : false;
$i = 0;

foreach ($people as $item):

	$left = $i%2==0;
	

?>
<div class="gall_card people black<?php echo ($left ? ' left' : ''); ?>">
	<?php 

		$img = $this->Img->profile_picture($item['Member']['big'], $item['Member']['photo_updated'], 104, 104);
		
		$options = array('escape' => false, 'class' => 'card_img');
		echo $this->Html->link(
				$this->Html->image($img), 
				array('controller' => 'members', 'action' => 'public_profile', $item['Member']['big']),
				$options
		);

		echo $this->Html->link(
			$item['Member']['name'] . ' ' . mb_substr($item['Member']['surname'], 0, 1) . '.',
			array('controller' => 'members', 'action' => 'public_profile', $item['Member']['big']), array('class' => 'person')
		);
		
		// Determine if this is events/people or places/people call
		if ($isEventsCntrl)
		{
			$isWhere = 'this event.';
		}
		else 
		{
			//Determine whether the user is on the event or in the place
			$isWhere = ($item['Event']['type'] == EVENT_TYPE_DEFAULT && $item['Event']['status'] == INACTIVE) ? 'this place.' : '"' . $item['Event']['name'] . '" event.' ;
		}
		echo '<p class="event">' . ($item['Checkin']['physical'] ? __('Checked in at ' . $isWhere) : __('Joined ' . $isWhere)) . '</p>';
		
		if ($logged['Member']['big'] != $item['Member']['big']) {
			echo $this->Html->link(__('Chat'), '#', array('class' => 'button grey open-chat', 'data-big' => $item['Member']['big'], 'data-name' => $item['Member']['name'].' '.mb_substr($item['Member']['surname'], 0, 1)));
		}
	?>		
</div>
<?php 
	
	$i++;

endforeach;
?>
