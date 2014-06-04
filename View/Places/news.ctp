<?php 

echo '<h3>' . __('%s presents:', $place['Place']['name']) . '</h3><div style="clear:both"></div>';

if (!empty($place['Place']['news'])) {
	
	echo $place['Place']['news'];

} else {

	echo $this->element('flash/info', array('message' => __('There are no news')));

}