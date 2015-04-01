<?php

echo __('Ciao %s,', $name) . "\n";

echo __('questo è un messaggio per resettare la tua password di Haamble.') . "\n\n";


echo __('Per favore clicca qui per resettare la tua password:') . "\n";
echo $this->Html->url(array('controller' => 'members', 'action' => 'change_password', $member_big, $token), true) . "\n\n";

echo __('Se non hai richiesto di resettare la tua password ignora questa e-mail. La richiesta di resettare la password è stata fatta da questo IP %s.', $ip) . "\n";

