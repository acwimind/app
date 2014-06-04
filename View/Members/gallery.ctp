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
<?php if (isset($loggedBig) && $loggedBig != $photo['Photo']['member_big']):?>
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
			$name = $name = $this->Html->link($photo['Place']['name'], array('controller' => 'places', 'action' => 'detail', $photo['Place']['big'], $photo['Place']['slug']));
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
//		if (empty($img))  {
//			echo '</div>';
//			continue;
//		}
		
		echo $img;

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
//			echo '</div>';
//			continue;
//		}
		
		echo $img;

		$name = $this->Html->link($photo['Place']['name'], array('controller' => 'places', 'action' => 'detail', $photo['Place']['big'], $photo['Place']['slug']));

	endif; 

	echo '<p>' . __('at %s', '<br />'.$name) . '</p>';

?>
</div>
<?php
	$i++; 
endforeach; ?>
<script>
	$(function() {
		var reason = $( "#reason" ),
		anchor = null;
		
		$( "#dialog-form" ).dialog({
			autoOpen: false,
			height: 220,
			width: 300,
			modal: true,
			buttons: [
					{
					text:"Report",
					click: function() {
					$.get('/signalations/add', { flgBig: <?php echo $memberBig; ?> , reason: reason.val(), type: <?php echo SIGNAL_PHOTO; ?>, photo_id: anchor.attr('id')},
						    function(data){
						        //request completed
						        //now update the div with the new data
						        $('#report_form').hide();
						        $('#rep_result').text(data);
						        $('#rep_submit').hide();
						        $('#rep_cancel > .ui-button-text').text('OK');
						        $('#rep_result').show();
						        
						    },
						    'json'
						);
					},
					'id': 'rep_submit'
				},
				{
					text: "Cancel",
					click: function() {
						$( this ).dialog( "close" );
					},
					'id':'rep_cancel'
				}
			],
			close: function() {
				$('#report_form').show();
				$('#rep_submit').show();
		        $('#rep_cancel > .ui-button-text').text('Cancel');
				$('#rep_result').hide();
			}
	});

	
});
</script>