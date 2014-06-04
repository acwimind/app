$(document).ready(function(){
   
	$('table.table td.options i').tooltip();

	/*$('.input.slider').each(function(){
		
		var input = $('input', $(this));
		var slidebar = $('.slidebar', $(this));
		
		slidebar.slider({
			 orientation: "horizontal",
			 range: "min",
			 min: 0,
			 max: 100,
			 step: 5,
			 value: input.val(),
			 slide: function(val) {
				 input.val( $(this).slider('value') );
			 },
			 //change: function(val) {},
	    });
		
		input.change(function(){
			slidebar.slider('value', input.val());
		});
		
	});*/
	
	
	//$(".alert").alert();
	
	initDatepicker($('body'));
	
	var togglables = new Array;
	$('.toggle').each(function(index){
		var div = $(this);
		$('input[type=checkbox]', div).change(function(){
			var input = $('div.toggled-input', div);
			if ($(this).attr('checked')) {
				$('div.toggled-input', div).html( togglables[index] );
				if (input.hasClass('date')) {
					initDatepicker(div);
				}
				input.show();
			} else {
				togglables[index] = input.html();
				input.hide();
				input.html('');
			}
		});
		$('input[type=checkbox]', div).trigger('change');
	});
	
	$('a.zoom-image').colorbox();
        
    var height = $(".content-header h2").height() / -2;
    $(".content-header h2").css("margin-top", height);

});

function initDatepicker(scope) {
	$('.input.date .datepicker', scope).datepicker({
		weekStart:1,
		dateFormat:'dd.mm.yy'
	}).on('changeDate', function(){
		$(this).trigger('change');
	});
	$('.input.date .timepicker', scope).timePicker();
}

