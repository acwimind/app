<div class="navbar">
	<div class="navbar-inner">
		<div class="container">
			<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</a>
			<?php echo $this->Html->link(__('Haamble'), '/', array('class' => 'brand')); ?>
			<?php echo $this->Html->link(__('Admin'), array('controller' => 'pages', 'action' => 'display', 'home', 'admin' => true), array('class' => 'brand')); ?>
			<div class="nav-collapse">
				<ul class="nav"><?php
					foreach($top_menu as $name => $url) {
						echo '<li' . ($this->request->params['controller'] == $url['controller'] ? ' class="active"' : '') . '>' . $this->Html->link($name, $url) . '</li>';
					}
				?></ul>
				<?php if ($logged === false):?>
					<div class="pull-right"><?php
						echo $this->Html->link(__('Sign Up'), array('controller' => 'members', 'action' => 'register'), array('class' => 'btn')) . ' ';
						echo $this->Html->link(__('Sign In'), array('controller' => 'members', 'action' => 'login'), array('class' => 'btn btn-primary'));
					?></div>
				<?php else: ?>
				<div class="btn-group pull-right">
					<a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><?php echo $logged['Member']['name'] . ' ' . $logged['Member']['surname']; ?> <span class="caret"></span></a>
					<ul class="dropdown-menu">
						<li><?php echo $this->Html->link(__('Sign Out'), array('controller' => 'members', 'action' => 'logout')); ?></li>
					</ul>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>