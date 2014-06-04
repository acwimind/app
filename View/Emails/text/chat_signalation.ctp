Hello,
Member <?php echo $member_name; ?> (with id <?php echo $member_big; ?>) has recently reported
a <?php echo ($type == SIGNAL_CHAT ? ' conversation with' : ' photo uploaded by'); ?> member <?php echo $flagged_name; ?> (with id <?php echo $flagged_big; ?>)
providing a reason: <?php echo $reason; ?>.
<?php 
	if ($type == SIGNAL_CHAT):
		if (empty($messages)):
			?> No messages in this conversation. <?php 
		else:
			?>
			Reported conversation follows: 
			<?php 		
			foreach ($messages as $msg):
				echo ($msg['Sender']['big'] == $member_big ? $member_name : $flagged_name); echo ' (' . date('d.m.Y H:i:s', strtotime($msg['ChatMessage']['created'])) . '):'; echo $msg['ChatMessage']['text']; 
			endforeach;
		endif;
	else:
		?>
		The photo can be found <?php echo $this->Html->link('here', $img, array('full_base'=>true))?>.
			You can delete it by clicking <?php echo $this->Html->link('here', array('controller' => 'photos', 'action' => 'delete', $photoBig, 'full_base'=>true, 'admin' => true));?>.
		<?php   
	endif; 
?>
