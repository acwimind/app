<?php

//add photos
foreach($events as $key=>$val) {
	$events[ $key ]['Event']['photo'] = $this->Img->event_photo($val['Event']['big'], $val['Gallery']['big'], $val['DefaultPhoto']['big'], $val['DefaultPhoto']['original_ext'], '50', '50');
}

$data = array(
	'items' => $events,
	'items_count' => $eventsCount,
);

echo json_encode($data);