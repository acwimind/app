<?php

//add photos
foreach($places as $key=>$val) {
	
	if (isset($val['DefaultPhoto']['big']) && $val['DefaultPhoto']['big'] > 0) {
		$places[ $key ]['Place']['photo'] = $this->Img->place_photo($val['Place']['big'], $val['Gallery']['big'], $val['DefaultPhoto']['big'], $val['DefaultPhoto']['original_ext'], '50', '50');
	} else {
		$places[ $key ]['Place']['photo'] = $this->Img->place_default_photo($val['Place']['category_id'], '50', '50');
	}
	
}

$data = array(
	'items' => $places,
	'items_count' => $placesCount,
);

echo json_encode($data);