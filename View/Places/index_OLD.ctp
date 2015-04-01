   <div class="filter_topbar">
            <?php echo $this->element('sidebar/topbar_header', array('category_name' => $category_name)); ?>
            <?php echo $this->element('sidebar/topbar_filter', array('category_name' => $category_name, 'sort' => $sort, 'pars' => $pars)); ?>
    </div>
    <div class="list clearfix">
            <?php 
                    $i = 0;
                    $leftCount = 0;
                    foreach ($places as $place):
                            $left = $i%2 == 0;

                    ?>
                    <div class="place_card<?php echo $left ? ' left' : null; ?><?php echo $leftCount%2==0 ? ' black' : null; ?>">
                            <?php 
                                    $img = $this->Img->place_photo($place['Place']['big'], $place['Gallery']['big'], $place['DefaultPhoto']['big'], $place['DefaultPhoto']['original_ext'], 104, 104);
                                    if (empty($img)) {
                                            $img = $this->Img->place_default_photo($place['Place']['category_id'], 104, 104);
                                    } 
                            
                            $options = array('escape' => false, 'class' => 'list_img');
                            echo  $this->Html->link('<img alt="place_photo" src="'.$img.'">',
                            	array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']), $options);

                            	
                            echo $this->Html->link(
                            	 	$this->App->shorten($place['Place']['name'], 20), 
                                    array('action' => 'detail', $place['Place']['big'], $place['Place']['slug']),
                                    array('title' => $place['Place']['name'])
                                 ); 
                                 
                                 $address = (!empty($place['Place']['address_street']) ? $place['Place']['address_street'] : '') . 
									        	' ' . (!empty($place['Place']['address_street_no']) ? $place['Place']['address_street_no'] : '') . 
									        	' ' . (!empty($place['Region']['city']) ? $place['Region']['city'] : '');
                                 ?>
                                    <p class="place_address" title="<?php echo $address; ?>">
                                    	<?php
                                    		 echo $this->Html->link(
					                            	 	$this->App->shorten($address, 22), 
					                                    array('action' => 'detail', $place['Place']['big'], $place['Place']['slug'], '#' => 'tab-map'),
					                                    array('title' => $address)
					                                 );
                                    	?>
                                    </p>        
                            
                            <?php if ($place['Event']['big'] != 0): ?>
                            <p><?php echo __('presents'); ?></p>
                            <h5>
                                    <?php echo $this->Html->link(
                                            $this->App->shorten($place['Event']['name'], 20), 
                                            array('controller' => 'events', 'action' => 'detail', $place['Event']['big'], $place['Event']['slug']),
                                            array('title' => $place['Event']['name'])
                                         ); ?>
                            </h5>
                            <?php /*?>
                            <p><?php echo __('Daily'); ?>: <br />
                            <?php echo $this->Date->time($place['Event']['daily_start']) . ' - ' . $this->Date->time($place['Event']['daily_end']); ?>
                            </p>
                            <?php */ ?>
                            <?php endif; ?>
                            <div class="rating_average">
                                    <span style="width: <?php echo $this->App->rating_counter($place['Place']['rating_avg']); ?>px;"><?php echo $this->App->rating_counter($place['Place']['rating_avg']); ?></span>
                            </div>
                    </div>
                    <?php 
                            $i++;
                            if (!$left) 
                                    $leftCount++;
                    endforeach; 
            ?>
    </div>
    <?php echo $this->element('sidebar/pagination', array(
	    	'pars' => $pars, 
	    	'count' => $placesCount, 
	    	'offset' => $offset
    	)); ?>
