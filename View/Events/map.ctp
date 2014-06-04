	<div class="filter_topbar">
		<?php echo $this->element('sidebar/topbar_header', array('category_name' => $category_name)); ?>
	</div>
	<div class="list">
		<?php echo $this->Map->events_markers(array('style' => 'width:413px;height:500px;float: left;')); ?>
		<?php //echo $this->element('subpages/places_map_list', array('places' => $places));	//TODO: ajax ?>
	</div>