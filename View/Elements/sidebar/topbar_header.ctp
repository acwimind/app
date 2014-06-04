<div class="topbar_header">
	<h2><?php echo $category_name; ?></h2>
	<ul class="nav_tabs">
		<li<?php if ($this->params->action=='index') echo ' class="current"'; ?>><?php 
			echo $this->Html->link(__('List'), array('action' => 'index')); 
		?></li>
		<li<?php if ($this->params->action=='map') echo ' class="current"'; ?>><?php 
			echo $this->Html->link(__('Map'), array('action' => 'map')); 
		?></li>
	</ul>
</div>