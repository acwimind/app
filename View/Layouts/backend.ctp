<?php
/**
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       Cake.View.Layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

$cakeDescription = __d('cake_dev', 'Haamble Administration Platform');
?>
<!DOCTYPE html>
<html>
	<head>
		<?php echo $this->Html->charset(); ?>
		<title>
			<?php echo $cakeDescription ?>:
			<?php echo $title_for_layout; ?>
		</title>
		<?php
			echo $this->Html->meta('icon');
			echo $this->fetch('meta');

			echo $this->Html->css(array('bootstrap', /*'bootstrap-responsive.min',*/ 'datepicker', 'timePicker', 'core', 'colorbox/colorbox', 'style'));
			echo $this->fetch('css');
			
			echo $this->Html->script('https://ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.min.js');
			echo $this->Html->script(array('bootstrap.min', 'bootstrap-datepicker', 'jquery.timePicker.min', 'jquery.colorbox-min', 'script', 'admin_script'));

			echo $this->Html->css(array('textext.core', 'textext.plugin.arrow', 'textext.plugin.autocomplete', 'textext.plugin.clear', 'textext.plugin.focus', 'textext.plugin.prompt', 'textext.plugin.tags'));
			echo $this->Html->script(array('textext.core', 'textext.plugin.ajax', 'textext.plugin.arrow', 'textext.plugin.autocomplete', 'textext.plugin.clear', 'textext.plugin.filter', 'textext.plugin.focus', 'textext.plugin.prompt', 'textext.plugin.suggestions', 'textext.plugin.tags'));
			
			echo $this->fetch('script');
			
			echo $this->fetch('uploader_head');	//JS and CSS files for uploader (only if there is an uploader on the page)
			
		?>
	</head>

	<body>

		<div id="main-container">
		
			<div id="header" class="container">
				<?php echo $this->element('menu/backend_top', array('top_menu' => $top_menu)); ?>
			</div><!-- #header .container -->
			
			<div id="content" class="container">

				<?php echo $this->Session->flash(); ?>

				<?php echo $this->fetch('content'); ?>
			</div><!-- #header .container -->
			
			<div id="footer" class="container">
				<?php //Silence is golden ?>
			</div><!-- #footer .container -->
			
			<div class="container well">
				<?php echo $this->element('sql_dump'); ?>
			</div>
			
		</div><!-- #main-container -->
		
	</body>

</html>