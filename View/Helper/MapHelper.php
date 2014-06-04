<?php

/**
 * Helper for Google Map
 */
class MapHelper extends AppHelper {
	
	public $helpers = array('Html', 'Img');
	
	private $maps_count=0;
	
	/**
	 * Return static image with map
	 * @param string $lower_left lon,lat value
	 * @param string $top_right lon,lat value
	 * @param number $zoom
	 * @param string $size of the image
	 * @return HTML image
	 */
	public function static_region($lower_left, $top_right, $zoom=6, $size='200x100') {
		
		if (empty($lower_left) || empty($top_right))
		{
			return null;
		}
		
		//data in lonlat format (as stored in our DB)
		$lower_left = explode(',', substr($lower_left, 1, -1));
		$top_right = explode(',', substr($top_right, 1, -1));
		
		//center in latlon format (as required by Google Maps)
		$center = array(
			0 => $lower_left[1] + (($top_right[1] - $lower_left[1]) / 2),
			1 => $lower_left[0] + (($top_right[0] - $lower_left[0]) / 2),
		);
		$center = implode(',', $center);
		
		$path = array(
			'color:0xff0000ff|weight:1|fillcolor:0xffcccc88',
			$lower_left[1] . ',' . $lower_left[0],
			$lower_left[1] . ',' . $top_right[0],
			$top_right[1] . ',' . $top_right[0],
			$top_right[1] . ',' . $lower_left[0],
			$lower_left[1] . ',' . $lower_left[0],
		);
		
		$options = array(
			'center' => $center,
			'path' => implode('|', $path),
			'zoom' => $zoom,
			'size' => $size,
			'sensor' => 'false',
			'key' => GOOGLE_API_KEY_BROWSER,
		);
		
		$url = 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query($options);
		
		return $this->Html->image($url); 
		//$this->Html->link(__('Show on full map'), 'https://maps.google.com/?q=' . $center . '&z=' . ($zoom+4));
		
	}
	
	public function events_markers($div_options=array()) {
		return $this->places_markers($div_options, 'events');
	}
	
	public function places_markers($div_options=array(), $show='places') {
		
		$this->maps_count++;
		
	//	$this->Html->script('https://maps.googleapis.com/maps/api/js?sensor=false&key=' . GOOGLE_API_KEY_BROWSER, array('block' => 'script'));
	$this->Html->script('https://maps.googleapis.com/maps/api/js?sensor=false', array('block' => 'script'));

		$this->Html->script('map', array('block' => 'script'));
		
		ob_start();
		
		if (isset($div_options['div_list'])) {
			$div_list_options = $div_options['div_list'];
			unset($div_options['div_list']);
		}
		
		if (!isset($div_options['id'])) {
			$div_options['id'] = 'map-canvas-' . $this->maps_count;
		}
		
		if (!isset($div_list_options['id'])) {
			$div_list_options['id'] = 'map-list-' . $this->maps_count;
		}
		
		echo $this->Html->div(isset($div_options['class']) ? $div_options['class'] : 'google-map', '', $div_options);
		echo $this->Html->div(isset($div_options['class']) ? $div_options['class'] : 'google-map', '', $div_list_options);
?>
<style type="text/css">
#<?php echo $div_options['id']; ?> img {
	max-width: none;
	width: auto;
	display:inline;
}
.map-infobox {
	background:#fff;
	padding:20px;
	text-align:center;
}
</style>
<script type="text/javascript">
var map;

function set_map_filter(city, country) {
	console.log(city);
	console.log(country);

	geocoder = new google.maps.Geocoder();
    geocoder.geocode( { 'address': city + ', ' + country}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			map.panTo( results[0].geometry.location );
		}
    });

}

function initialize() {

	var myLatlng = new google.maps.LatLng(41.896144,12.482414);
	var mapOptions = {
		zoom: 15,	
		center: myLatlng,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		minZoom: 12
	}

	map = new google.maps.Map(document.getElementById('<?php echo $div_options['id']; ?>'), mapOptions);

	var contentString = '';

	var infowindow = new google.maps.InfoWindow({
		content: contentString
	});

	//var bounds = new google.maps.LatLngBounds()
	
	var markers = new Array;
	
<?php
/*
$i=0;
foreach($markers as $val) {
	
	$img = $this->Img->place_photo($val['Place']['big'], $val['Gallery']['big'], $val['DefaultPhoto']['big'], $val['DefaultPhoto']['original_ext'], '100', '100');
	if (!empty($img)) {
		$img_html = $this->Html->image($img);;
	} else {
		$img_html = '';
	}
	$lonlat = explode(',', trim($val['Place']['lonlat'], '()'));
	$html = '<div>' . $img_html . $val['Place']['name'] . '</div>';

	echo '
		var latlon = new google.maps.LatLng('.$lonlat[1].','.$lonlat[0].');
		var marker = new google.maps.Marker({
			position: latlon,
			map: map,
			title: "'.addslashes($val['Place']['name']).'",
			html: "'.addslashes($html).'",
			icon: "https://cdn1.iconfinder.com/data/icons/Map-Markers-Icons-Demo-PNG/32/Map-Marker-Marker-Outside-Chartreuse.png"
		});
		markers['.$val['Place']['big'].'] = marker;
	    bounds.extend(latlon);
	';
		
}
	map.fitBounds(bounds);
	
	for (id in markers) {
		var marker = markers[id];
		var infoBox;
		google.maps.event.addListener(marker, 'mouseover', function () {
			infoBox = new InfoBox({
				latlng: this.getPosition(), 
				map: map, 
				html: this.html
			});
		});
		google.maps.event.addListener(marker, 'mouseout', function () {
			infoBox.close();
		});
	}

*/
?>
	var infoBox;

	google.maps.event.addListener(map, 'idle', function () {

		var ll = map.getBounds().getSouthWest().toUrlValue();
		var ur = map.getBounds().getNorthEast().toUrlValue();

		$('#<?php echo $div_list_options['id']; ?>').html('Loading...');
		
		$.ajax({
			url: '<?php echo $this->here; ?>/map_bounds[ll]:'+ll+'/map_bounds[ur]:'+ur
		}).done(function(data){

			var data = $.parseJSON(data);
			var items = data.items;
			var new_markers = new Array;

			$('#<?php echo $div_list_options['id']; ?>').html('');

			for(var i=0; i<items.length; i++) {

				var item = items[i];
				var lonlat = item.Place.lonlat.substring(1, item.Place.lonlat.length-1).split(',');
				var latlon = new google.maps.LatLng( lonlat[1], lonlat[0] );

				var img;
<?php if ($show == 'events'): ?>
				if (item.Event.photo.length > 0) {
					img = '<img src="' + item.Event.photo + '" alt="event photo" />';
<?php else: ?>
				if (item.Place.photo.length > 0) {
					img = '<img src="' + item.Place.photo + '" alt="place photo" />';
<?php endif; ?>
				} else {
					img = '';
				}

				var pinIcon = new google.maps.MarkerImage(
				    "/img/pins/pc" + item.Place.category_id + ".png",
				    null, /* size is determined at runtime */
				    null, /* origin is 0,0 */
				    null, /* anchor is bottom center of the scaled image */
				    new google.maps.Size(24,35)
				);
				
				var marker = new google.maps.Marker({
					position: latlon,
					map: map,
<?php if ($show == 'events'): ?>
					title: item.Event.name,
					html: img + item.Event.name + ' <?php echo __('<span>at</span>'); ?> ' + item.Place.name,
					url: "<?php echo $this->Html->url(array('controller' => 'events', 'action' => 'detail')); ?>/" + item.Event.big + '/' + item.Event.slug,
<?php else: ?>
					title: item.Place.name,
					html: img + item.Place.name,
					url: "<?php echo $this->Html->url(array('controller' => 'places', 'action' => 'detail')); ?>/" + item.Place.big + '/' + item.Place.slug,
<?php endif; ?>
					icon: pinIcon
				});
				new_markers.push(marker);



<?php if ($show == 'events'): ?>
				var html = '<div class="map-list-item' + (i%2==1 ? ' black' : '') + '">' + img + 
					'<h4><a href="<?php echo $this->Html->url(array('controller' => 'events', 'action' => 'detail')); ?>/' + item.Event.big + '/' + item.Event.slug + '">' + item.Event.name + 
					'</a> <?php echo __('<span>at</span>'); ?> <a href="<?php echo $this->Html->url(array('controller' => 'places', 'action' => 'detail')); ?>/' + item.Place.big + '/' + item.Place.slug + '">' + item.Place.name + '</a></h4></div>';
<?php else: ?>
				var html = '<div class="map-list-item' + (i%2==1 ? ' black' : '') + '">' + img + 
					'<h4><a href="<?php echo $this->Html->url(array('controller' => 'places', 'action' => 'detail')); ?>/' + item.Place.big + '/' + item.Place.slug + '">' + item.Place.name + '</a>';
				if (item.Event.name != undefined && item.Event.name.length > 0) {
					html += ' <?php echo __('presents'); ?> <a href="<?php echo $this->Html->url(array('controller' => 'events', 'action' => 'detail')); ?>/' + item.Event.big + '/' + item.Event.slug + '">' + item.Event.name + '</a>';
				}
				html += '</h4></div>';
<?php endif; ?>
				$('#<?php echo $div_list_options['id']; ?>').append(html);
				
			}

			for (var i=0; i<new_markers.length; i++) {
				var marker = new_markers[i];
				//console.log(marker);
				google.maps.event.addListener(marker, 'mouseover', function () {
					infoBox = new InfoBox({
						latlng: this.getPosition(), 
						map: map, 
						html: this.html
					});
				});
				google.maps.event.addListener(marker, 'mouseout', function () {
					infoBox.close();
				});
				google.maps.event.addListener(marker, 'mousedown', function () {
					window.location.href = this.url;
				});
			}
			for (var i=0; i<markers.length; i++) {
				markers[i].setMap(null);
			}
			markers = new_markers;
			new_markers = null;

			if (items.length < data.items_count && $('#map-msg').length == 0) {
				$('#<?php echo $div_options['id']; ?>').after('<div id="map-msg"><?php echo __('There are more than %s items. Zoom in or select category.', MAP_MAXIMUM_RESULTS); ?></div>');
			} else if ($('#map-msg').length > 0) {
				$('#map-msg').remove();
			}
			
		});
		
	});
	
}

google.maps.event.addDomListener(window, 'load', initialize);
</script>
<?php
		return ob_get_clean();
	}
	
	/**
	 * Show map function
	 * @param unknown_type $div_options
	 * @return string
	 */
	public function place_address($div_options=array(), $place) {
		
		
		$this->maps_count++;
		
		ob_start();
		
		if (isset($div_options['div_list'])) {
			$div_list_options = $div_options['div_list'];
			unset($div_options['div_list']);
		}
		
		if (!isset($div_options['id'])) {
			$div_options['id'] = 'map-canvas-' . $this->maps_count;
		}
		
		echo $this->Html->div(isset($div_options['class']) ? $div_options['class'] : 'google-map', '', $div_options);
		?>
		<style type="text/css">
		#<?php echo $div_options['id']; ?> img {
			max-width: none;
			width: auto;
			display:inline;
		}
		.map-infobox {
			background:#fff;
			padding:20px;
			text-align:center;
		}
		</style>
		<script type="text/javascript">
		var map;
		
		function set_map_filter(city, country) {
			console.log(city);
			console.log(country);
		
			geocoder = new google.maps.Geocoder();
		    geocoder.geocode( { 'address': city + ', ' + country}, function(results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					map.panTo( results[0].geometry.location );
				}
		    });
		
		}
		
		function initialize() {
			var myLatlng = new google.maps.LatLng(<?php echo $place['Place']['lonlat']; ?>);
			var mapOptions = {
				zoom: 15,	
				center: myLatlng,
				mapTypeId: google.maps.MapTypeId.ROADMAP,
				minZoom: 12
			}
		
			map = new google.maps.Map(document.getElementById('<?php echo $div_options['id']; ?>'), mapOptions);
		
			var pinIcon = new google.maps.MarkerImage(
					"/img/pins/pc" + <?php echo $place['Place']['category_id']; ?> + ".png",
				    null, /* size is determined at runtime */
				    null, /* origin is 0,0 */
				    null, /* anchor is bottom center of the scaled image */
				    new google.maps.Size(26,31)
				);
				
				var marker = new google.maps.Marker({
					position: myLatlng,
					map: map,
					title: "<?php echo htmlspecialchars($place['Place']['name']); ?>",
					icon: pinIcon
				});
		}

		$(document).ready(function(e, data){ 
		    initialize();
        }); 
		
		</script>
		<?php
		return ob_get_clean();
		
	}
	
}