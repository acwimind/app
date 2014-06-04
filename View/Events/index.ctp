	<div class="filter_topbar">
		<?php echo $this->element('sidebar/topbar_header', array('category_name' => $category_name)); ?>
                <?php echo $this->element('sidebar/topbar_filter', array('category_name' => $category_name)); ?>
        </div>
	<div class="list clearfix">
		<?php 
		$i = 0;
		$leftCount = 0;
		foreach ($events as $event):
			$left = $i%2 == 0;
				
		?>
		<div class="place_card<?php echo $left ? ' left' : null; ?><?php echo $leftCount%2==0 ? ' black' : null; ?>">
			<?php 
				$img = $this->Img->event_photo($event['Event']['big'], $event['Gallery']['big'], $event['DefaultPhoto']['big'], $event['DefaultPhoto']['original_ext'], 104, 104);
				if (empty($img)) {
					$img = $this->Img->place_default_photo(
						$event['Place']['category_id'], 
						104, 104
					);
				}

				 $options = array('escape' => false, 'class' => 'list_img');
                 echo  $this->Html->link('<img alt="event_photo" src="'.$img.'">',
                    array('controller' => 'events', 'action' => 'detail', $event['Event']['big'], $event['Event']['slug']), $options);
                    
			?>
			<h4 >
				<?php echo $this->Html->link(
						$this->App->shorten($event['Event']['name'], 20), 
						array('action' => 'detail', $event['Event']['big'], $event['Event']['slug']),
						array('title' => $event['Event']['name'])); ?>
			</h4>
			<p><?php echo __('at'); ?></p>
				<?php echo $this->Html->link(
						$this->App->shorten($event['Place']['name'], 20), 
						array('controller' => 'places', 'action' => 'detail', $event['Place']['big'], $event['Place']['slug']),
						array('title' => $event['Place']['name'])); ?>
			<?php $address =  (!empty($event['Place']['address_street']) ? $event['Place']['address_street'] : '') . 
									        	' ' . (!empty($event['Place']['address_street_no']) ? $event['Place']['address_street_no'] : '') . 
									        	' ' . (!empty($event['Region']['city']) ? $event['Region']['city'] : '');
			?>
				<p class="place_address" title="<?php echo $address; ?>">
                                    	<?php
                                    		  echo $this->Html->link(
					                            	 	$this->App->shorten($address, 22), 
					                                    array('controller' => 'places', 'action' => 'detail', $event['Place']['big'], $event['Place']['slug'], '#' => 'tab-map'),
					                                    array('title' => $address)
					                                 );
                                    	?>
                 </p>  
			            <div class="rating_average">
                        <span style="width: <?php echo $this->App->rating_counter($event['Event']['rating_avg']);?>px;"><?php echo $this->App->rating_counter($event['Event']['rating_avg']); ?></span>
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
	        	'count' => $eventsCount, 
	        	'offset' => $offset
        	)); ?>
