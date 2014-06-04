<?php

echo '<p>' . __('Hello %s,', $name) . '<br />' . 
		__('this is password reset e-mail from Haamble.') . '</p>' . "\n";

echo '<p>' . $this->Html->link(
				__('Please click here to reset your password.'), 
				$this->Html->url(array('controller' => 'members', 'action' => 'change_password', $member_big, $token), true)
	) . '</p>' . "\n";

echo '<p>' . __('If you didn\'t request password reset, please ignore this e-mail. Password reset was requested from IP address %s.', $ip) . '</p>';

