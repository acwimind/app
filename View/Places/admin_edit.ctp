<?php

echo '<h1>' . ( isset($this->data['Place']) && $this->data['Place']['big']>0 ? __('Edit Place %s', $this->data['Place']['name']) : __('New Place') ) . '</h1>';

echo $this->AdvForm->create('Place');

echo $this->AdvForm->hidden('Place.big');

echo $this->AdvForm->inputs(array(
	'legend' => __('Basic Info'),
	'Category.id' => array('label' => __('Category'), 'options' => $categories),
	'Place.name' => array('label' => __('Name'), 'type' => 'text'),
	'Place.slug' => array('label' => __('Slug (for URL)'), 'type' => 'text'),
	'Place.short_desc' => array('label' => __('Short Description')),
	'Place.long_desc' => array('label' => __('Full Description')),
	'Place.status' => array('label' => __('Active'), 'type' => 'checkbox'),
	'Place.photos' => array(
		'label' => __('Upload Photos'), 
		'uploader' => array(
			'data-preview' => true,		//show preview of uploaded images
			'data-multiple' => true,	//allow upload of multiple files
		),
	),
));

if (isset($this->data['Gallery'][0]['big']) && $this->data['Gallery'][0]['big']>0) {
	echo $this->Html->link(__('Display gallery'), array('controller' => 'galleries', 'action' => 'index', $this->data['Gallery'][0]['big']));
} else {
	echo __('No photos');
}

echo $this->AdvForm->inputs(array(
	'legend' => __('Contact Info'),
	'Place.url' => array('label' => __('URL'), 'type' => 'text'),
	'Place.phone' => array('label' => __('Phone')),
	'Place.email' => array('label' => __('E-mail Address')),
));

echo $this->AdvForm->inputs(array(
	'legend' => __('Region and Full Address'),
	'Region.id' => array('label' => __('Available Regions'), 'options' => $regions),
	'Place.address_street' => array('label' => __('Street')),
	'Place.address_street_no' => array('label' => __('Street Number')),
	'Place.address_zip' => array('label' => __('ZIP Code')),
	'Place.address_town' => array('label' => __('Town / City')),//, 'disabled' => true),
	'Place.address_province' => array('label' => __('Province')),//, 'disabled' => true),
	'Place.address_region' => array('label' => __('Region'),),// 'disabled' => true),
	'Place.address_country' => array('label' => __('Country'), 'default' => 'IT'),//, 'disabled' => true),
	'Place.lonlat' => array('label' => __('GPS Position'), 'after' => '<span class="help-inline">' . __('Format: (longitude,latitude)') . '</span>', 'type' => 'text'),	//TODO: gmap with pin ?
),
null,
array(
	'fieldset' => 'address',
));

echo $this->AdvForm->inputs(array(
	'legend' => __('Additional Info'),
	'Place.opening_hours' => array('label' => __('Opening Hours')),
	'Place.news' => array('label' => __('News (Text Only)')),
));

echo $this->AdvForm->inputs(array(
	'legend' => __('Operators'),
	'operators' => array(
		'label' => __('Operators'), 
		'multiajax' => array(
			'url' => array('controller' => 'members', 'action' => 'complete_operators', 'admin' => true),
			'value' => $operator_emails,
		),
	),
));

echo $this->AdvForm->submit('Save');

echo $this->AdvForm->end();

echo $this->Html->css(array('jquery-ui.css', 'jquery.ui.chatbox'));
?>
<script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<script>
$('.address').ready(function(){

	// Result limit. When number of results exceeds limit, user is required to narrow down the search
	var resultLimit = 15;

	// Geocode button
	var $geobtn = $( '<div id="geo_gps" class="btn_geo">Get Coordinates from Address</div>' );
	$geobtn.appendTo( '.address' );
	$geobtn.on( 'click', function() {
//		console.log( 'GPS clicked' );

		// Variables
		var allowedElems = new Array(
				'PlaceAddressStreet', 
				'PlaceAddressStreetNo', 
				'PlaceAddressTown', 
				'PlaceAddressZip', 
				'PlaceAddressProvince', 
				'PlaceAddressRegion', 
				'PlaceAddressCountry' 
			);
		var address = new Array();

		// Extract address fields from form
		$( '.address div input' ).each(function( index ) {
			var elem = $(this);
//			console.log(elem);
//			console.log(elem.attr('id'));
//			console.log(elem.val());
			
			if(!jQuery.isEmptyObject(elem) && jQuery.inArray(elem.attr('id'), allowedElems) != -1)
			{
//				address[elem.attr('id')] = elem.val();
				address.push(elem.val());
			}
		});
//		console.log(address);

		var addr = address.join(' ');
		var geocoder = new google.maps.Geocoder();
//		console.log(addr);
		  geocoder.geocode( { 'address': addr}, function(results, status) {
		    if (status == google.maps.GeocoderStatus.OK) {
//		      console.log(status);
//		      console.log(results);
//		      console.log(results.length);

		      	// If too many results
				if(results.length > resultLimit)
				{
					$( '#geocode_error p' ).text('Please try to narrow down the search by filling more address fields.');
			    	$( '#geocode_error' ).dialog({
				    	title: 'Too many results',
			    		height: 220,
			    		width: 300,
			    		modal: true,
			    		buttons: {
			    			 Ok: function() {
			    			 	$( this ).dialog( "close" );
			    			 }
	    				}
			    	});
			    }
				else if (results.length > 1) 
				{
					// Open dialog window
//					jQuery.each(results, function( index )
					for (i = 0; i < results.length; ++i)
							 {

							var resArr = results[i];
//							console.log(resArr);
							var addr = resArr['formatted_address'];
//							console.log(addr);
							$('#addr_sel').append('<option value="' + i + '" ' + ((i == 0) ? '"selected"' : '') + '>' + addr + '</option>');
					};
					$( '#dialog-form' ).dialog({
				    	height: 220,
			    		width: 300,
			    		modal: true,
			    		buttons: 	{
						    			'OK':	function() {
											var seld = $('#addr_sel').find(":selected").val();
//											console.log(seld);
//											console.log(results[seld]);
//											console.log(results[seld].geometry.location.lat());
											var lonlat = '(' + results[seld].geometry.location.lng() + ',' + results[seld].geometry.location.lat() + ')';
											$('#PlaceLonlat').val(lonlat);
						    				$('#addr_sel').empty(); 
						    			 	$( this ).dialog( "close" );
						    			 	$( "#PlaceLonlat" ).animate({
												 backgroundColor: "#96D7FE"
												 }, 100, function() {
													 $( "#PlaceLonlat" ).animate({
														 backgroundColor: "#FFFFFF"
														 },1500, function() {
														 
														 });
												 });
					    				},
					    				'Close': function() {
					    					$('#addr_sel').empty(); 
					    			 		$( this ).dialog( "close" );
						    			}
					    			}
			    	});
					
				}
				else
				{
					// Put coords into the coordinates field
					var lonlat = '(' + results[0].geometry.location.lng() + ',' + results[0].geometry.location.lat() + ')';
					$('#PlaceLonlat').val(lonlat);
					 $( "#PlaceLonlat" ).animate({
						 backgroundColor: "#96D7FE"
						 }, 100, function() {
							 $( "#PlaceLonlat" ).animate({
								 backgroundColor: "#FFFFFF"
								 },1500, function() {
								 
								 });
						 });
				}
		      
		    } else {
			    // Handle error
			    var errMsg = 'Geocode was not successful for the following reason: ';
			  	switch (status) {
				case google.maps.GeocoderStatus.ZERO_RESULTS:
					errMsg += 'No results found';
					break;

				default:
					errMsg += 'Reason could not be specified. Please try again later.';
					break;
				}  
		    	$( '#geocode_error p' ).text(errMsg);
		    	$( '#geocode_error' ).dialog({
			    	title: 'Error',
		    		height: 220,
		    		width: 300,
		    		modal: true,
		    		buttons: {
		    			 Ok: function() {
		    			 	$( this ).dialog( "close" );
		    			 }
    				}
		    	});
			  
		    }
		  });
				
		
	});

	// Reverse geocode button -------------------------------------------------------------------------------
	var $revgeo = $( '<div id="rev_geo" class="btn_geo">Get Address from Coordinates</div>' );
	$revgeo.appendTo( '.address' );
	$revgeo.on( 'click', function() {
//		console.log( 'GPS clicked' );

		// Variables
		var transObj = {
				'route' : 'PlaceAddressStreet', 
				'street_number' : 'PlaceAddressStreetNo', 
				'locality' : 'PlaceAddressTown', 
				'postal_code' : 'PlaceAddressZip', 
				'administrative_area_level_2' : 'PlaceAddressProvince', 
				'administrative_area_level_1' : 'PlaceAddressRegion', 
				'country' : 'PlaceAddressCountry' 
		};
		var address = new Array();

		// Extract coordinates from form
		var coords = $( '#PlaceLonlat' ).val();
//		console.log(coords.length);

		// Coords shorter than 5 - (1,1) - makes no sense 
		if(coords.length < 5)
		{
	    	$( '#geocode_error p' ).text('Please provide valid coordinates into the GPS Position field.');
	    	$( '#geocode_error' ).dialog({
		    	title: 'Error',
	    		height: 220,
	    		width: 300,
	    		modal: true,
	    		buttons: {
	    			 Ok: function() {
	    			 	$( this ).dialog( "close" );
	    			 }
				}
	    	});
	    	return false;
		}

		// Prepare coordinates for processing
		var crds = coords.replace(/[\(\)]/g, '');
//		console.log(crds);
		var lonlatStr = crds.split(',', 2);
		var lng = parseFloat(lonlatStr[0]);
		var lat = parseFloat(lonlatStr[1]);
//		console.log(lat);
//		console.log(lng);
			
		var latlng = new google.maps.LatLng(lat, lng);
		var geocoder = new google.maps.Geocoder();
		  geocoder.geocode( { 'latLng': latlng}, function(results, status) {
		    if (status == google.maps.GeocoderStatus.OK) {
//		      console.log(status);
//		      console.log(results);
//		      console.log(results.length);

		      	// If too many results
				if(results.length > resultLimit)
				{
					$( '#geocode_error p' ).text('Please try to use more specific coordinates to narrow down the results.');
			    	$( '#geocode_error' ).dialog({
				    	title: 'Too many results',
			    		height: 220,
			    		width: 300,
			    		modal: true,
			    		buttons: {
			    			 Ok: function() {
			    			 	$( this ).dialog( "close" );
			    			 }
	    				}
			    	});
			    }
				else if (results.length > 1) 
				{
					// Open dialog window
//					jQuery.each(results, function( index )
					for (i = 0; i < results.length; ++i)
							 {

							var resArr = results[i];
//							console.log(resArr);
							var addr = resArr['formatted_address'];
//							console.log(addr);
							$('#addr_sel').append('<option value="' + i + '" ' + ((i == 0) ? '"selected"' : '') + '>' + addr + '</option>');
					};
					$( '#dialog-form' ).dialog({
				    	height: 220,
			    		width: 300,
			    		modal: true,
			    		buttons: 	{
						    			'OK':	function() {
							    			var animFld = new Array();
											var seld = $('#addr_sel').find(":selected").val();
//											console.log(seld);
//											console.log(results[seld]);
//											console.log(results[seld].address_components.length);
											var addrComp = results[seld].address_components;
											for (i = 0; i < addrComp.length; ++i)
											{
//												console.log(addrComp[i].types[0]);
												var fieldId = transObj[addrComp[i].types[0]];
												var value = (addrComp[i].types.indexOf('country') != -1) ? addrComp[i].short_name : addrComp[i].long_name;
//												console.log(fieldId);
//												console.log(value);
												$('#' + fieldId).val(value);
												animFld.push(fieldId);
											}
											$('#addr_sel').empty(); 
						    			 	$( this ).dialog( "close" );
//						    			 	console.log('before animate 1');
						    			 	for(j=0; j < animFld.length; j++)
						    			 	{
//							    			 	console.log(animFld[j]);
							    			 	var fld = '#' + animFld[j];
							    			 	$( fld ).animate({
													 backgroundColor: "#96D7FE"
													 }, {
														 duration : 100, 
														 complete : function() {
//															 console.log('complete callback');
															 var fldd = '#' + $(this).attr('id');
//															 console.log(fldd);
														 	$( fldd ).animate({
																backgroundColor: "#FFFFFF"
															 },
															 {
																 duration : 1500,
																 queue : false
															 });
													 	},
													 	queue : false
													 });
							    			 	
						    			 	}
					    				},
					    				'Close': function() {
					    					$('#addr_sel').empty(); 
					    			 		$( this ).dialog( "close" );
						    			}
					    			}
			    	});
					
				}
				else
				{
					// Put address into the address fields
					var animFld = new Array();
					var addrComp = results[0].address_components;
					for (i = 0; i < addrComp.length; ++i)
					{
//						console.log(addrComp[i].types[0]);
						var fieldId = transObj[addrComp[i].types[0]];
						var value = (addrComp[i].types.indexOf('country') != -1) ? addrComp[i].short_name : addrComp[i].long_name;
//						console.log(fieldId);
//						console.log(value);
						$('#' + fieldId).val(value);
						animFld.push(fieldId);
					}
//					console.log('before animate 1');
    			 	for(j=0; j < animFld.length; j++)
    			 	{
//	    			 	console.log(animFld[j]);
	    			 	var fld = '#' + animFld[j];
	    			 	$( fld ).animate({
							 backgroundColor: "#96D7FE"
							 }, {
								 duration : 100, 
								 complete : function() {
//									 console.log('complete callback');
									 var fldd = '#' + $(this).attr('id');
//									 console.log(fldd);
								 	$( fldd ).animate({
										backgroundColor: "#FFFFFF"
									 },
									 {
										 duration : 1500,
										 queue : false
									 });
							 	},
							 	queue : false
							 });
	    			 	
    			 	}
				}
		      
		    } else {
			    // Handle error
			    var errMsg = 'Geocode was not successful for the following reason: ';
			  	switch (status) {
				case google.maps.GeocoderStatus.ZERO_RESULTS:
					errMsg += 'No results found';
					break;

				default:
					errMsg += 'Reason could not be specified. Please try again later.';
					break;
				}  
		    	$( '#geocode_error p' ).text(errMsg);
		    	$( '#geocode_error' ).dialog({
			    	title: 'Error',
		    		height: 220,
		    		width: 300,
		    		modal: true,
		    		buttons: {
		    			 Ok: function() {
		    			 	$( this ).dialog( "close" );
		    			 }
    				}
		    	});
			  
		    }
		  });
	});
		return false;
	
});
</script>

<div id="dialog-form" title="Multiple results" style="display:none;">
			<form id="geocode_pick">
				<fieldset>
					<label for="addr_sel">Please select one of the result bellow</label>
					<select name="addr_sel" id="addr_sel">
					</select>
				</fieldset>
			</form>
			<p id="geocode_result" style="display:none;"></p>
</div>
<div id="geocode_error">
	<p></p>
</div>

<?php 
//TODO: too many regions for javascript, convert to ajax call
/*
?>
<script type="text/javascript">
var region_address = jQuery.parseJSON("<?php echo json_encode( $region_address ); ?>");
console.log(region_address);
$('select#RegionId').change(function(){
	var address = region_address[ $('option:selected', $(this)).val() ];
	$('input#PlaceAddressTown').val( address.address_town );
	$('input#PlaceAddressProvince').val( address.address_province );
	$('input#PlaceAddressRegion').val( address.address_region );
	$('input#PlaceAddressCountry').val( address.address_country );
});
$('select#RegionId').trigger('change');
</script>
*/ ?>