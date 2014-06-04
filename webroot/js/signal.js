$(document).ready(function(){

	$('body').on('click', '.signal_btn', function(event) {
		event.preventDefault();
		openSignalationDialog($(this).attr('id'));
		return false;
	});

});

function openSignalationDialog(gem_big) {

	$( "#dialog-form" ).dialog({
		autoOpen: false,
		height: 220,
		width: 300,
		modal: true,
		buttons: [
				{
				text:"Report",
				click: function() {
				$.get('/signalations/add', { flgBig: gem_big , reason: $( "#reason" ).val()},
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

	//$( "#dialog-form" ).show();
	$( "#dialog-form" ).dialog( "open" );

}