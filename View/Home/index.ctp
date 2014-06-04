<div class="welcome-wrapper">
    <p class="welcome-user">
        <?php echo '' . __('Welcome %s', $logged['Member']['name']) . ' !'; ?>
    </p>
</div>    

<div class="columns">     

    <div class="col c03">
        <?php echo $this->element('home/places_col', array('places' => $last_places)); ?>
    </div>

    <div class="col c03">
        <?php echo $this->element('home/events_col', array('events' => $last_events)); ?>
    </div>

    <div class="col c03">
        <?php echo $this->element('home/bookmarks_col', array('bookmarks' => $bookmarks)); ?>
    </div>
</div>