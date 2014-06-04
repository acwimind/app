<div class="room" id="room">
<a href="#" id="refresh-room">refresh list of members</a>
<div></div>
</div>

<div class="chats" id="chats"></div>


<?php $this->Html->css('http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/ui-lightness/jquery-ui.css', null, array('inline' => false)); ?>
<?php $this->Html->css('jquery.ui.chatbox', null, array('inline' => false)); ?>
<style type="text/css">
.room {width:300px;border:1px #000 solid;padding:10px;float:left;}
.chats {float:left;}
.chat {width:300px;border:1px #000 solid;float:left;margin-left:20px;}
.chat b {display:block;background:#000;color:#fff;padding:5px 0;text-align:center;}
</style>

<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js"></script>
<script src="/js/jquery.ui.chatbox.js"></script>
<script type="text/javascript">
$(function() { // isolate from rest of javascript AND launch on READY!

var me = "<?php echo $logged['Member']['name'] . ' ' . $logged['Member']['surname']; ?>";
var chat_url = '/app/webroot/chat/?q=';

var ajaxes = [null];

// list of all opened boxes
var boxList = new Array();
// list of boxes shown on the page
var showList = new Array();

var nameHash = {};

var config = {
	width : 300, //px
	gap : 20,
	maxBoxes : 3
};

var getOffsetFor = function(idx) {	
	if (idx >= config.maxBoxes) idx = config.maxBoxes - 1;
	return (config.width + config.gap) * idx;
};


var boxClosedCallback = function(id) {
	var chid = 'chat-'+id;
	// close button in the titlebar is clicked
	var idx = showList.indexOf(chid);	
	if(idx != -1) 
	{
	    showList.splice(idx, 1);
	    for(var i = 0; i < showList.length; i++) 
		{
			//offset = $("#" + showList[i]).chatbox("option", "offset");
			$("#" + showList[i]).chatbox("option", "offset", getOffsetFor(i));
	    }
	}
	else 
	{
	    console.log("should not happen: " + chid);
	}
};


var abortXhr = function(xhr) {
	if (xhr) {                        
		try { 
			if (xhr.readyState != XMLHttpRequest.DONE)
				xhr.abort();                                       
		} 
		catch(e) {}
	}    
}

function refresh_room() {
	$.ajax({
		url: chat_url + 'list'
	}).done(function(data){
		
		var data = $.parseJSON(data);
		fill_room(data.joined, data.history)

	});
	return false;
}

function fill_room(joined, history) {
	
	var room_elm = $('#room > div');

	if ( room_elm.html().length == 0 ) {	//init room html
		
		room_elm.html('<b>Joined members</b><ul class="joined"></ul><b>Old conversations</b><ul class="history"></ul>');

	}

	$('ul.joined', room_elm).append( fill_list(joined, room_elm) );
	$('ul.history', room_elm).append( fill_list(history, room_elm) );

}

function fill_list(users, room_elm) {
	
	var html = '';

	for(var i=0; i<users.length; i++) {

		var user_elm = $('ul li[data-id=' + users[i].big + ']', room_elm);
		if (user_elm.length == 0) {
			html += '<li data-id="' + users[i].big + '"><a href="#">' + users[i].name + '</a> (' + users[i].status + ', last seen <span class="seen">' + Date() + '</span>)</li>';
		} else {
			$('span.seen', user_elm).html( Date() );
		}

	}

	return html;

}

function get_name_for_big(big) {
	var el = $('#room ul li[data-id='+big+'] a');
	if (el.length) {
		return el.html();
	}
	return null;
}

function open_chat(big, name, autoopen) {

	var chid = 'chat-'+big;
	
	var idx1 = showList.indexOf(chid);
	var idx2 = boxList.indexOf(chid);
	
	if(idx1 != -1) {
	    // found one in show box, bring to front
		var $chbx = $("#"+chid);
		
		if (idx1 >= config.maxBoxes-1) {
			var $cbp = $chbx.closest('.ui-chatbox');
			var $par = $cbp.parent();

			$cbp.detach().appendTo($par);	
			$chbx.chatbox("option", "offset", getOffsetFor(idx1));
		}		
			    
	    var manager = $chbx.chatbox("option", "boxManager");
		
	    manager.highlightBox();	    
	}
	else if(idx2 != -1) {
	    // exists, but hidden
	    // show it and put it back to showList
		var $chbx = $("#"+chid);
		var $cbp = $chbx.closest('.ui-chatbox');
		var $par = $cbp.parent();
		
		$cbp.detach().appendTo($par);
		
	    $chbx.chatbox("option", "offset", getOffsetFor(showList.length));
	    var manager = $chbx.chatbox("option", "boxManager");
		
	    manager.toggleBox();
	    showList.push(chid);
	}
	else {
		$chbx = $('<div id="chat-'+big+'"></div>');
		$("#chats").append($chbx);
		
	    nameHash[big] = name;

		$chbx.chatbox({
			id : big,
			title : "Chat with "+name,
			user : name,
			boxClosed : boxClosedCallback,
			hidden: false,
			width : config.width,
			offset : getOffsetFor(showList.length),
			
			messageSent: function(id, user, msg){
				var tsid = (new Date()).getTime(), ts = 0;

				var box = this.boxManager.elem.uiChatboxLog;
				var pivot = box.children(':last');
				if (pivot.length > 0) {
					var p = pivot.get(0);
					if (p) ts = 0.1 + Number(p.getAttribute('msgts'));				
				}			 

				this.boxManager.addMsgUniqueOrdered(me, msg, 'sent-' + tsid, ts, false);

				$.ajax({
					url: chat_url + 'send',
					type: 'post',
					data: {msg: msg, to: id}
				});
			}
		});
		
		boxList.push(chid);
	    showList.push(chid);
		

		$.ajax({
			url: chat_url + 'init',
			data: {to: big}
		}).done(function(data){

			//TODO: use timestamp returned by response, not timestamp from JS (browser)
			var data = $.parseJSON(data);
			var time = 0;
			console.log(data);

			if (data.length > 0) {

				for(var len=data.length,i=len-1; i>=0; i--) {
					var obj = data[i];
					var sender;

					if (obj.sent == true) {
						sender = me;
					} else {
						sender = name;
					}
					var t = Number(obj.time);

					$('#chat-'+big).chatbox("option", "boxManager").addMsgUniqueOrdered(
						sender, 
						t + ' / ' + obj.msg,
						obj.id,
						t,
						autoopen ? false : true);
				}

				time = data[0].time;

			}
			start_polling( time, time );
		});
	}
	
	return false;
}

is_polling = false;

function start_polling(last_timestamp, last_list_timestamp) {

	if (is_polling == true) {
		return false;
	}
	is_polling = true;

	var ts = Math.round((new Date()).getTime() / 1000);
	
	if (ajaxes[0]) {
		abortXhr(ajaxes[0]);
	}

	ajaxes[0] = $.ajax({
		url: chat_url + 'poll',
		timeout: 60000,
		data: {last: last_timestamp, last_list: last_list_timestamp, foo: ts}
	}).done(function(data){
		if (is_polling) is_polling = false;
		
		var data = $.parseJSON(data);
		var time = last_timestamp;
		var list_time = last_list_timestamp;

		if (data != null) {

			//new messages in all chats
			if (data['msgs'] != null) {

				var msgs = data['msgs'];

				for(var big in msgs) {
					for(var len=msgs[big].length, i=len-1; i>=0; i--) {
						var obj = msgs[big][i];
						var t=Number(obj.time);
					
						var chbx = $('#chat-'+big);
						var name;
						
						if (t > time) time = t;
						
						if (chbx.length == 0) {
							
							console.log('chat not found, opening a new one');
							
							name = get_name_for_big(big);
							if (name == null) {
								name = obj.name;
								refresh_room();
							}
							
							console.log('name: '+name);
							
							if (name == null) continue;
							
							open_chat(big, name, true);											
							
							chbx = $('#chat-'+big);
						}
						else {					
							name = chbx.chatbox("option", "user");
						}
						var manager = chbx.chatbox("option", "boxManager");
						
						if (!manager.elem.uiChatboxContent.is(":visible")) {
							manager.elem.uiChatboxContent.toggle();
						}
						
						manager.addMsgUniqueOrdered(
							name, 
							obj.msg + ' / ' + t,
							obj.id,
							obj.time,
							false
							);


						
					}
				}			
			}	//end new messages in all chats

		}	//if (data != null)


		
		ajaxes[0] = null;
		setTimeout(function(){ start_polling( time, list_time ); }, 1000);
		
	}).fail(function() {
		ajaxes[0] = null;
		setTimeout(function(){ start_polling( last_timestamp, last_list_timestamp ); }, 1000);
		if (is_polling) is_polling = false;
	});
		
}

//refresh_room();

$('a#refresh-room').click(refresh_room);

$('#room').on('click', 'ul li a', function(){
	return open_chat( $(this).parent('li').data('id'), $(this).html(), false );
});

// start polling at load
start_polling( <?php echo time(); ?>, 0 );

}); 
</script>


