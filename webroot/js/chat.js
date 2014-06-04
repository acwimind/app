var me = "";

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
	maxBoxes : set_max_boxes(),
	offset: 320
};

var msg_replace = {
	'<span class="smile">:)</span>': [':)', ':-)'],
	'<span class="smile wink">:)</span>': [';)', ';-)'],
	'<span class="smile grin">:)</span>': [':D', ':-D'],
	'<span class="smile tongue">:)</span>': [':P', ':-P'],
	'<span class="smile kiss">:)</span>': [':*', ':-*'],
	'<span class="smile sad">:)</span>': [':(', ':-('],
};

var resizing_id;
$(window).resize(function() {
    clearTimeout(resizing_id);
    resizing_id = setTimeout(done_resizing, 500);
    
});

function done_resizing(){
	config.maxBoxes = set_max_boxes();
	reopen_windows(true);
}

function set_me(value) {
	me = value;
}

function set_max_boxes() {
	return Math.max(1, Math.floor( ($(window).width()-50) / 310 )-1);
}

var getOffsetFor = function(idx) {
	
	if (idx >= config.maxBoxes) {
		idx = config.maxBoxes-1;
		var lastBox = showList[ showList.length-1 ];
		var lastBoxElm = $('#' + lastBox).parent('div.ui-chatbox-content').parent('div.ui-chatbox');
		$('span.ui-icon-closethick', lastBoxElm).parent('a.ui-chatbox-icon').trigger('click');
		store_window(lastBox.replace('chat-', ''), 'close');
	}

	return ( (config.width + config.gap) * idx ) + config.offset;

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
	    // console.log("should not happen: " + chid);
	}
	store_window(id, 'close');
};

var boxMinimizedCallback = function(id,visible) {
	store_window(id, visible ? 'open' : 'minimize');
}


var abortXhr = function(xhr) {
	if (xhr) {                        
		try { 
			if (xhr.readyState != XMLHttpRequest.DONE)
				xhr.abort();                                       
		} 
		catch(e) {}
	}    
}

$(window).resize(function() {
	$('div#room-lists').css('max-height', ($(window).height() - 100) + 'px');
});

function refresh_room(onlyReopenChats) {

	if (onlyReopenChats == undefined) {
		onlyReopenChats = false;
	}

	$.ajax({
		url: chat_url + 'list'
	}).done(function(data){
		
		var data = $.parseJSON(data);
		fill_room(data.joined, data.history, onlyReopenChats);

		bind_delete_chat();

		if (onlyReopenChats == true) {
			var openChatWindows = sessionStorage.getItem('openChatWindows');
			openChatWindows = $.parseJSON( openChatWindows );

			if (openChatWindows != null) {
				$.each(openChatWindows, function(big, state) {

					name = get_name_for_big(big);
					if (name == null) {
						store_window(big, 'close');
					} else {
						open_chat(big, name, true);
						if (state == 'm') {
							$('span.ui-icon.ui-icon-minusthick', $("#chat-"+big).closest('div.ui-chatbox')).parent('a').click();
						}
					}

				});	
			}
		}

	});
	return false;
}

function fill_room(joined, history, onlyReopenChats) {
	
	var room_elm = $('div#room-lists');
	if (!onlyReopenChats) {
		room_elm.show();
	}
	room_elm.css('max-height', ($(window).height() - 100) + 'px');

	if ( room_elm.html().length == 0 ) {	//init room html
		
		room_elm.html('<b>Joined members</b><ul class="joined"></ul><b>Old conversations</b><ul class="history"></ul>');

	}

	$('ul.joined', room_elm).html( fill_list(joined, room_elm, false) );
	$('ul.history', room_elm).html( fill_list(history, room_elm, true) );

}

function fill_list(users, room_elm, can_delete) {
	
	var html = '';

	for(var i=0; i<users.length; i++) {

		var user_elm = $('ul li[data-id=' + users[i].big + ']', room_elm);
		// if (user_elm.length == 0) {
			html += '<li data-id="' + users[i].big + '">'+
					'<a href="#" class="user-chat">'+
					'<img src="' + users[i].img + '" class="img" alt="' + users[i].name + '" />'+
					'<span class="name">' + users[i].name + '</span>'+
					'<img src="/img/icon_user_' + users[i].status + '.png" class="status" alt="' + users[i].status + '" />'+
					'</a>'+
					(can_delete ? '<a href="#" class="delete-chat" title="Delete conversation">&#10005;</a>' : '')+
					'</li>';
					// , last seen <span class="seen">' + Date() + '</span>
		// } 
		// else {
		// 	$('span.seen', user_elm).html( Date() );
		// }

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

function get_img_for_big(big) {
	var el = $('#room ul li[data-id='+big+'] img.img');
	if (el.length) {
		return el.attr('src');
	}
	return null;
}

function open_chat(big, name, autoopen) {

	var chid = 'chat-'+big;
	
	var idx1 = showList.indexOf(chid);
	var idx2 = boxList.indexOf(chid);

	var name_html = $(name);
	if (name_html[1]) {
		var name = name_html[1].innerHTML;
	}

	if (name.slice(-1) != ".") {
		name += ".";
	}
	
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
		
	    //manager.highlightBox();	    
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

	    var status_img = $('div#room-lists ul li[data-id="'+big+'"] img.status').clone();
	    if (status_img) {
	    	var status_img_html = $("<div />").append(status_img).html();
	    } else {
	    	var status_img_html = '<img src="/img/icon_user_offline.png" class="status" alt="offline" />';
	    }

		$chbx.chatbox({
			id : big,
			title : '<a href="/members/public_profile/'+big+'" class="lnk">'+status_img_html+name+'</a> <a id="'+big+'" class="signal_btn chat_signal_btn" title="Report this image">Report</a>',
			user : name,
			boxClosed : boxClosedCallback,
			boxMinimized: boxMinimizedCallback,
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

				var boxManager = this.boxManager;

				$.ajax({
					url: chat_url + 'send',
					type: 'post',
					data: {msg: msg, to: id}
				}).done(function(reply){
					
					var msg_replaced = replaceMsgElms(msg);


					var reply = $.parseJSON(reply);
					if (reply['r'] == true) {
						// console.log('send reply - add msg');
						add_message(boxManager, 0, me, ts, msg_replaced, 'sent-'+tsid, false, false);
					} else if(reply['more']['error']) {
						add_error_message(boxManager, ts, 'Sending failed. Reason: '+reply['more']['error'], 'sent-'+tsid, false);
						var textarea = $('textarea', $('#chat-'+id).parent('div'));
						textarea.attr('disabled', true);
						textarea.addClass('disabled');
					} else {
						add_error_message(boxManager, ts, 'Sending failed.', 'sent-'+tsid, false);
					}

				});
			}
		});
		
		boxList.push(chid);
	    showList.push(chid);

	    $('.ui-chatbox-titlebar a.lnk').click(function(event){
	    	event.stopPropagation();
	    });

	    $('.ui-chatbox-titlebar a.signal_btn').click(function(event){
			openSignalationDialog($(this).attr('id'));
			event.stopPropagation();
		});
		

		$.ajax({
			url: chat_url + 'init',
			data: {to: big}
		}).done(function(data){

			//TODO: use timestamp returned by response, not timestamp from JS (browser)
			var data = $.parseJSON(data);
			var time = 0;

			if (data.length > 0) {

				for(var len=data.length,i=len-1; i>=0; i--) {
					var obj = data[i];
					var sender;

					if (obj.sent == true) {
						sender = me;
						sender_big = 0;
					} else {
						sender = name;
						sender_big = big;
					}
					var t = Number(obj.time);

					// console.log('init chat - add msg');
					add_message($('#chat-'+big).chatbox("option", "boxManager"), sender_big, sender, t*1000, obj.msg, 'old-'+obj.id, false, false);

				}

				time = data[0].time;

			}
			start_polling( time, time );
		});
	}

	store_window(big, 'open');
	
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

					var autoopen = false;

					for(var len=msgs[big].length, i=len-1; i>=0; i--) {
						var obj = msgs[big][i];
						var t=Number(obj.time);
					
						var chbx = $('#chat-'+big);
						var name;
						
						if (t > time) time = t;
						
						if (chbx.length == 0) {
							
							// console.log('chat not found, opening a new one');
							
							name = get_name_for_big(big);
							if (name == null) {
								name = obj.name;
								refresh_room();
							}

							if (name == null) continue;

							// console.log('name: '+name);
							
							open_chat(big, name, true);											
							
							chbx = $('#chat-'+big);
						}
						else {				
							name = chbx.chatbox("option", "user");
						}
						var manager = chbx.chatbox("option", "boxManager");
						
						// console.log('poll - new msg - add msg');
						add_message(manager, big, name, t*1000, obj.msg, 'rcv'+obj.id, false, true);

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

function add_message(manager, from_user_big, from_user_name, time_sent, message, message_id, autoopen, highlight) {

	if (highlight === undefined) {
		highlight = false;
	}

	var date_sent = new Date( time_sent );
	var formated_time =  date_sent.getHours() + ':' + (date_sent.getMinutes()<10?'0':'') + date_sent.getMinutes() + ', ' 
							+ date_sent.getDate() + '. ' + date_sent.getMonth() + '.';

	var img = from_user_big==0 ? $('img#chat-self-img').attr('src') : get_img_for_big(from_user_big);
	if (img == null) {
		img = 'xx';// '/resized_images/members/dummy_avatar_profile_28x28.jpg';
	}

	// console.log('open: ' + autoopen);

	if (!manager.elem.uiChatbox.is(":visible")) {
		manager.elem.uiChatbox.show();
		manager.elem.uiChatboxContent.show();
	}

	manager.addMsgUniqueOrdered(
		from_user_name, 
		'<img src="' + img + '" alt="" /><span class="msg-time">' + formated_time + '</span> <span class="msg-text">' + message + '</span>',
		message_id,
		time_sent,
		autoopen ? true : false
	);

	if (highlight) {
		if (!manager.elem.uiChatboxTitlebar.hasClass("ui-state-focus") && !manager.highlightLock) {
			// manager.highlightLock = true;
			manager.highlightBox();
		}
	}

}

function add_error_message(manager, time_sent, message, message_id, autoopen) {

	var date_sent = new Date( time_sent );
	var formated_time =  date_sent.getHours() + ':' + (date_sent.getMinutes()<10?'0':'') + date_sent.getMinutes() + ', ' 
							+ date_sent.getDate() + '. ' + date_sent.getMonth() + '.';

	manager.addMsgUniqueOrdered(
		'', 
		'<span class="msg-time">' + formated_time + '</span><span class="msg-error">' + escapeHTML(message) + '</span>',
		message_id,
		time_sent,
		autoopen ? true : false
	);

}

function store_window(big, action) {

	if (action != 'open' && action != 'minimize') {
		action = 'close';
	}
	
	var openChatWindows = sessionStorage.getItem('openChatWindows');
	openChatWindows = $.parseJSON( openChatWindows );

	if (openChatWindows == null) {
		openChatWindows = new Object;
	}

	if (action == 'open') {
		openChatWindows[big] = 'o';
	} else if (action == 'minimize') {
		openChatWindows[big] = 'm';
	} else if (action == 'close' && openChatWindows[big] && openChatWindows[big].length > 0) {
		delete openChatWindows[big];
	}

	sessionStorage.setItem('openChatWindows', JSON.stringify(openChatWindows));

}

function reopen_windows() {

	refresh_room(true);

}

function escapeHTML(input) {
    return input.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function replaceMsgElms(input) {
	var output = escapeHTML(input);

	for(var replace_text in msg_replace) { 
		if (msg_replace.hasOwnProperty(replace_text)) {
			var find_text = msg_replace[replace_text];
			for(var j=0; j<find_text.length; j++) {
				output = output.replace(find_text[j], replace_text);
				// console.log('replace ' + find_text[j] + ' with ' + replace_text);
			}
		}
	}

	return output;
}

function bind_open_chat(elm) {
	open_chat(elm.data('big'), elm.data('name'), true);
	return false;
}

function bind_delete_chat() {
	
	$('ul.history li a.delete-chat').unbind('click');
	$('ul.history li a.delete-chat').click(function(event){

		event.preventDefault();

		if (!confirm('Do you really want to delete this conversation?')) {
			return false;
		}

		var member_elm = $(this).parent('li');
		var member_big = member_elm.data('id');

		$.ajax({
			url: chat_url + 'delete',
			type: 'post',
			data: {big: member_big}
		}).done(function(data){
			member_elm.remove();
		});

		return false;

	});

}

$(document).ready(function(){
	
	$('a.open-chat').click(function(){
		return bind_open_chat($(this));
	});

	$('div#room div.ui-widget-header', $(this)).click(function(){
		$('a#close-room', $(this)).trigger('click');
	});

});