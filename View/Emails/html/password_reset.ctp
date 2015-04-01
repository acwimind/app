<?php

echo '<p>' . __('Ciao %s,', $name) . '<br />' . 
		__('questo è un messaggio per resettare la tua password di Haamble.') . '</p>' . "\n";

echo '<p>' . $this->Html->link(
				__('Per favore clicca qui per resettare la tua password:'), 
				$this->Html->url(array('controller' => 'members', 'action' => 'change_password', $member_big, $token), true)
	) . '</p>' . "\n";

echo '<p>' . __('Se non hai richiesto di resettare la tua password ignora questa e-mail. La richiesta di resettare la password è stata fatta da questo IP %s.', $ip) . '</p>';

