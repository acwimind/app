<div class="content-header">
	<h2><?php echo $member['Member']['name'] . ( !empty($member['Member']['middle_name']) ? ' ' . mb_substr($member['Member']['middle_name'], 0, 1) . '. ' : ' ') . mb_substr($member['Member']['surname'], 0, 1) . '.'; ?></h2>
    <?php
        if ($logged['Member']['big'] == $member['Member']['big']) {
            echo $this->Html->link(
                __('Edit'), 
                array('controller' => 'members', 'action' => 'edit'), 
                array('class' => 'button')
            );
        }
    ?>
</div>
<div class="profile-wrapper">
        <div class="profile-left">
        <?php 

            echo $this->Html->image( $this->Img->profile_picture($member['Member']['big'], $member['Member']['photo_updated'], 104, 104) );
           

            if (isset($place['Place']['name'])) {

                echo '<p class="checked_01">';
                echo ($checkin['Checkin']['physical'] ? __('Checked-in') : __('Joined')) . ': </p>';  

                if (isset($checkin['Event']['name']) && $checkin['Event']['type']!=2 && $checkin['Event']['status']!=0) {
                    echo '<p class="checked_02">'. __(
                        '%s at %s',
                        $this->Html->link($checkin['Event']['name'], array('controller' => 'events', 'action' => 'detail', $checkin['Event']['big'], $checkin['Event']['slug'])),
                        $this->Html->link($place['Place']['name'], array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']))
                    ). '</p>';
                } else {
                    echo '<p class="checked_02">'. $this->Html->link($place['Place']['name'], array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug'])).'</p>';
                }

                /*echo $this->Html->link(
                    __('Leave'), 
                    array('controller' => 'checkins', 'action' => 'out'), 
                    array('class' => 'button')
                );

                echo '</p>';*/

            } else {

                echo '<p class="checked_03">' . __('Currently not checked in or joined anywhere.') . '</p>';

            }
        ?>
        </div>
<?php if ($logged['Member']['big'] != $member['Member']['big']): ?>
        <div class="profile-right">
<?php   if ($is_ignored): ?>
            <?php echo $this->Html->link(__('Remove from ignore list'), array('controller' => 'ignores', 'action' => 'remove', $member['Member']['big']), array('class' => 'button')); ?>
<?php   else: ?>
            <?php echo $this->Html->link(__('Add to ignore list'), array('controller' => 'ignores', 'action' => 'add', $member['Member']['big']), array('class' => 'button')); ?>
            <?php echo $this->Html->link(__('Send a chat message'), '#', array('class' => 'button open-chat', 'data-big' => $member['Member']['big'], 'data-name' => $member['Member']['name'].' '.mb_substr($member['Member']['surname'], 0, 1))); ?>
<?php   endif; ?>
        </div>
<?php endif; ?>
</div>
<div class="profile-tabs">
    <div class="content"><?php

        $tabs = array(
            __('Uploaded Photos')   => array('action' => 'gallery', $member['Member']['big']),
            __('Attended Events')   => array('action' => 'events', $member['Member']['big']),
            __('Visited Places')   => array('action' => 'places', $member['Member']['big']),
        );

        if ($logged['Member']['big'] == $member['Member']['big']) {
            $tabs += array(
                __('Ignore List') => array('controller' => 'ignores', 'action' => 'index'),
                __('Bookmarks')   => array('controller' => 'bookmarks', 'action' => 'index'),
            );
        }

        echo $this->element('subpages/tabs', array('tabs' => $tabs));
        
    ?></div>
	
</div>