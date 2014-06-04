<div class="welcome-wrapper">
    <p class="welcome-user">
        <?php echo '' . __('Welcome %s', $logged['Member']['name']) . ' !'; ?>
    </p>    
    <p class="welcome-intro">
        Want to meet new people? Start by completing your profile here,<br>
        As soon as you join a place, you can start making new friends.
    </p>
    <div class="icons"></div>
    <div class="bubbles">
        <p id="b1"><span class="arrow"></span>Want to see<br>what's popular?<br>Browse it here</p>
        <p id="b2"><span class="arrow"></span>Are you in a cool place<br>and wanto to see<br>what's up?<br>Browse our network<br>for places or events</p>
    </div>
</div>   
<div class="most-popular">
    <?php        
    echo '<h2>' . __('Most Popular Places') . '</h2>';
    echo '<div class="places">';
            $p = 0;
            foreach($popular_places as $place) {

                    echo '<div class="place">';

                    if ($place['DefaultPhoto']['big'] > 0) {
                            echo $this->Html->image(
                                    $this->Img->place_photo($place['Place']['big'], $place['DefaultPhoto']['gallery_big'], $place['DefaultPhoto']['big'], $place['DefaultPhoto']['original_ext'], 100, 100)
                            );
                    } else {
                            echo $this->Html->image(
                                    $this->Img->place_default_photo($place['Place']['category_id'], 100, 100)
                            );
                    }
                    
                    ?>
			        	<div class="rating_average">
			                        <span style="width: <?php echo $this->App->rating_counter($place['Place']['rating_avg']);?>px;"><?php echo $this->App->rating_counter($place['Place']['rating_avg']); ?></span>
						</div> <br />
			        <?php
                    
                    echo '<b>' . $this->Html->link($place['Place']['name'], array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug'])) . '</b>';
                    $p++;
                    if($p == 4){
                        echo '</div>';
                        break;
                    }

                    echo '</div>';

            }

            echo '<div class="show-more">'
                      . $this->Html->link( 'Show all...', array('controller' => 'places')) . 
                  '</div>';
     echo '</div>'; ?>
</div>