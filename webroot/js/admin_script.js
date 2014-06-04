$(document).ready(function(){
	
	$('textarea.multiajax').textext({
		plugins : 'tags autocomplete',
	}).bind('getSuggestions', function(e, data){
		
		var multiajax = $(this);

		$.get( $(this).data('url') + '?q=' + data.query, function(result) {
			
			multiajax.trigger('setSuggestions', {
				result : $.parseJSON(result)
			});

		});
		
	});

	$('textarea.multiajax').each(function(){
		if ($(this).data('value')) {
			$(this).textext()[0].tags().addTags( $(this).data('value').split('|') );
		}
	});

});