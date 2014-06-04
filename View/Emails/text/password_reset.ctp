<?php

echo __('Hello %s,', $name) . "\n";

echo __('this is password reset e-mail from Haamble.') . "\n\n";


echo __('To reset your password, please click this link:') . "\n";
echo $this->Html->url(array('controller' => 'members', 'action' => 'change_password', $member_big, $token), true) . "\n\n";

echo __('If you didn\'t request password reset, please ignore this e-mail. Password reset was requested from IP address %s.', $ip) . "\n";

