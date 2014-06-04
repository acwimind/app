<?php

if (!isset($event_big)) $event_big = 0;
if (!isset($place_big)) $place_big = 0;
if (!isset($joined)) $joined = false;
if (!isset($bookmarked)) $bookmarked = false;
if (!isset($confirm)) $confirm = false;

echo '<div class="content-header">';

echo '<h2>' . $name . '</h2>';

if ($place_big > 0) {

	if ($bookmarked) {
		echo $this->Html->link(
			__('Remove'), 
			array('controller' => 'bookmarks', 'action' => 'remove', $place_big), 
			array('class' => 'button', 'title' => 'Removes this place from Bookmarks')
		);
	} else {
		echo $this->Html->link(
			__('Bookmark'), 
			array('controller' => 'bookmarks', 'action' => 'add', $place_big), 
			array('class' => 'button')
		);
	}
}

if ($joined) {
	echo $this->Html->link(
		__('Leave'), 
		array('controller' => 'checkins', 'action' => 'out'), 
		array('class' => 'button')
	);
} else {
	echo $this->Html->link(
		__('Join'), 
		array('controller' => 'checkins', 'action' => 'in', $event_big, $place_big), 
		array('class' => 'button', 'confirm' => $confirm ? __('At the moment you are joined at other event. Do you want to leave it and join this event instead?') : false)
	);
}

echo '</div>';
