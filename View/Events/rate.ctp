<?php

echo $this->element($elm, array('message' => $msg));
echo $this->element('events/rating', array('rating' => $event['Event']['rating_avg'], 'big' => $event['Event']['big']));