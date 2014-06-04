<div class="content-header">
	<h2>My Profile</h2>
    <?php

		    echo $this->Html->link(
		    		__('More info'),
		    		array('controller' => 'extraInfos', 'action' => 'index'),
		    		array('class' => 'button')
		    );

            echo $this->Html->link(
                __('Edit'),
                array('controller' => 'members', 'action' => 'edit'),
                array('class' => 'button')
            );

            echo $this->Html->link(
                __('Unsubscribe'),
                array('controller' => 'members', 'action' => 'unsubscribe'),
                array(
                	'class' => 'button',
                	'onclick' => "return confirm('".__('Are you sure you want to unsubscribe?')."');"
                )
            );
    ?>
</div>
<div class="profile-wrapper">
        <div class="profile-left my-profile">
        <?php

            echo $this->Html->image( $this->Img->profile_picture($member['Member']['big'], $member['Member']['photo_updated'], 104, 104) );

            // User's details
            ?>
            <div class="personal_info">
	            <h4><?php echo $member['Member']['name'] . ( !empty($member['Member']['middle_name']) ? ' ' .$member['Member']['middle_name'] . ' ' : ' ') . mb_substr($member['Member']['surname'], 0, 1) . '.'; ?></h4>
	            <p>
	            	<strong><?php echo $member['Member']['email']; ?></strong><br />
	            	<?php echo date('d.m.Y', strtotime($member['Member']['birth_date'])); ?><br />
	            	<?php echo ($member['Member']['sex']=='m' ? __('Male') : ($member['Member']['sex']=='f' ? __('Female') : __('Not specified'))); ?>
	            </p>
            </div>
            <div class="checkin_options">
            <?php


            if (isset($place['Place']['name'])) { ?>
                <div class="checkin_details">
          <?php echo '<p class="checked_01">';
                echo ($checkin['Checkin']['physical'] ? __('Checked-in') : __('Joined')) . ': </p>';

                // Check if this is not a default event
                if (isset($checkin['Event']['name']) && $checkin['Event']['type']!=2 && $checkin['Event']['status']!=0) {
                    echo '<p class="checked_02">'. __(
                        '%s <br>at %s',
                        $this->Html->link($checkin['Event']['name'], array('controller' => 'events', 'action' => 'detail', $checkin['Event']['big'], $checkin['Event']['slug'])),
                        $this->Html->link($place['Place']['name'], array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug']))
                    ). '</p>';
                } else {
                    echo '<p class="checked_02">'. $this->Html->link($place['Place']['name'], array('controller' => 'places', 'action' => 'detail', $place['Place']['big'], $place['Place']['slug'])).'</p>';
                } ?></div>
                <div class="p_buttons">
                <?php echo $this->Html->link(
                    __('Leave'),
                    array('controller' => 'checkins', 'action' => 'out'),
                    array('class' => 'button')
                );

                // TODO Add rate and upload photos button
                ?>
                </div>
            <?php } else {

                echo '<p class="checked_03">' . __('Currently not checked in or joined anywhere.') . '</p>';

            }
        ?>
        </div>
        </div>
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