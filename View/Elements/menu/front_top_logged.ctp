<div class="header-top">

	<?php 
		$links = array(
			
			__('Logged in as %s', $this->Html->link($logged['Member']['name'].' '.$logged['Member']['surname'], array('controller' => 'members', 'action' => 'my_profile', 'operator' => false))),
			$this->Html->link(__('Log Off'), array('controller' => 'members', 'action' => 'logout', 'operator' => false)),
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

		echo '<div class="profile-info">' . $this->Html->nestedList(array_merge($admin_links, $links)) . '</div>';
	?>
</div>

<div class="header-bottom">
	
	<a href="/" class="logo">haamble</a>
	
	<nav id="header-nav">
            <ul class="navigation">
		<li><?php echo $this->Html->link(__('Places'), array('controller' => 'places', 'action' => 'index', 'operator' => false)); ?></li>
		<li><?php echo $this->Html->link(__('Events'), array('controller' => 'events', 'action' => 'index', 'operator' => false)); ?></li>
            </ul>    
	</nav>

	<div style="clear:both"></div>

</div>