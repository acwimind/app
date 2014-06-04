<?php

$links = array(
	$this->Html->link(__('My Places'), array('controller' => 'places', 'action' => 'index', 'operator' => true)),
	$this->Html->link(__('My Events'), array('controller' => 'events', 'action' => 'index', 'operator' => true)),
);

$admin_links = array();
if ($logged['Member']['type'] == MEMBER_ADMIN) {	//administrator
	
	$admin_links = array(
		$this->Html->link(__('Admin Backend'), array('controller' => 'pages', 'action' => 'display', 'home', 'admin' => true, 'operator' => false)),
	);

} elseif ($logged['Member']['type'] == MEMBER_OPERATOR) {	//operator
	
	$admin_links = array(
		$this->Html->link(__('Operator Panel'), array('controller' => 'places', 'action' => 'index', 'operator' => true)),
	);

}

echo '<nav class="categories op_cat"><h3>OPERATOR MENU:</h3> ' . $this->Html->nestedList($links);
?>
</nav>