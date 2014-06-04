<p>Hello, <br />
Member <?php echo $member_name; ?> (with id <?php echo $member_big; ?>) has recently reported <br />
a <?php echo ($type == SIGNAL_CHAT ? ' conversation with' : ' photo uploaded by'); ?> member <?php echo $flagged_name; ?> (with id <?php echo $flagged_big; ?>) <br />
providing a reason: <?php echo $reason; ?>.</p>
<?php 
	if ($type == SIGNAL_CHAT):
		if (empty($messages)):
			?> <p>No messages in this conversation.</p> <?php 
		else:
			?>
			<p>Reported conversation follows:<br /> 
			<?php 		
			foreach ($messages as $msg):
				?>
				<strong>
					<?php echo ($msg['Sender']['big'] == $member_big ? $member_name : $flagged_name); echo ' (' . date('d.m.Y H:i:s', strtotime($msg['ChatMessage']['created'])) . '):'; ?>
				</strong>
				<?php echo $msg['ChatMessage']['text']; ?> <br /> 
				<?php 
			endforeach;
			?>
			</p> 
			<?php 
		endif;
	else:
	?>
	<p>The photo can be found <?php echo $this->Html->link('here', $img, array('full_base'=>true))?>.<br />
		You can delete it by clicking <?php echo $this->Html->link('here', array('controller' => 'photos', 'action' => 'delete', $photoBig, 'full_base'=>true, 'admin' => true));?>.
	</p> 
	<?php  
	endif; 
?>
