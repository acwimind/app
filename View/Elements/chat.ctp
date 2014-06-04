<div id="chat" class="chat-container">

	<div class="room ui-widget ui-corner-top ui-chatbox" id="room">
		
		<div class="ui-widget-header ui-corner-top ui-chatbox-titlebar ui-dialog-header">
			
			<span><a href="#" id="open-room"><?php echo __('Chat - available users'); ?></a></span>

			<a href="#" id="close-room" class="ui-corner-all ui-chatbox-icon" role="button"><span class="ui-icon ui-icon-minusthick">minimize</span></a>

		</div>

		<div id="room-lists" class="room-lists ui-widget-content ui-chatbox-content"></div>

	</div>

	<div class="chats" id="chats"></div>

	<img src="<?php echo $this->Img->profile_picture($logged['Member']['big'], $logged['Member']['photo_updated'], 28, 28); ?>" alt="" style="display:none;" id="chat-self-img" />

</div>

<script type="text/javascript">
$(function() { // isolate from rest of javascript AND launch on READY!

	var roomTimer = false;

	function keep_room_refreshed() {

		if (roomTimer == false) {
			return false;
		}
		$('a#open-room').after('<span id="open-room-loader"> (Loading...)</span>');
		refresh_room();
		$('span#open-room-loader').remove();

		roomTimer = setTimeout(keep_room_refreshed, 10000);	//refresh room periodically
		
		return false;
	}

	$('a#open-room').click(function(){

		roomTimer = true;
		return keep_room_refreshed();

	});

	$('a#close-room').click(function(){

		if (roomTimer == false) {
			$('a#open-room').click();
		} else {
			roomTimer = false;
			$('#room-lists').hide();
		}

		return false;

	});

	$('#room').on('click', 'ul li a', function(){
		return open_chat( $(this).parent('li').data('id'), $(this).html(), false );
	});

	//set current suer
	set_me("<?php echo $logged['Member']['name'] . ' ' . $logged['Member']['surname']; ?>");

	// start polling at load
	start_polling( <?php echo time(); ?>, 0 );
	
	reopen_windows();

}); 
</script>


