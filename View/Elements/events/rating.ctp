<?php

if (!isset($rating)) $rating = false;
if (!isset($big)) $big = 0;

if ($rating !== false && $big > 0) {

	echo '<div class="rating">';

	for($i=1; $i<=RATING_MAXIMUM; $i++) {
                    
                $star_full = 'star_full';
                $star_empty = 'star_empty';
            
		/*$star = $i<=$rating ? '&#10029;' : ' &#10025 ';*/
                $star = $i<=$rating ? $star_full : $star_empty;

		echo $this->Html->link($star, array('controller' => 'events', 'action' => 'rate', $big, $i), array('escape' => false, 'style' => 'float:left;', 'class' => 'a'.$i)); /*'class' => $star*/

	}
        $av_rate = ($rating/5)*75;
        
        if($av_rate > 1){
	        $av_rate1 = $av_rate + 3;
	    }elseif($av_rate > 2){
	        $av_rate1 = $av_rate + 6;
	    }elseif($av_rate > 3){
	        $av_rate1 = $av_rate + 9;
	    }elseif($av_rate > 4){
	        $av_rate1 = $av_rate + 12;
	    }
         echo '<span style="width: '.$av_rate1.'px"></span>';
	/*echo ' (' . $rating . ')';*/

	echo '</div>';

}
