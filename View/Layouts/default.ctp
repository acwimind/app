<!DOCTYPE html>
<html>

	<head>
		<?php echo $this->Html->charset(); ?>
		<title>
			<?php echo $title_for_layout; ?> &minus; <?php echo __('Haamble'); ?>
		</title>
                <!--[if lt IE 9]><?php echo $this->Html->css(array('ie8.css'));?><![endif]-->
                <!--[if IE 7]><?php echo $this->Html->css(array('ie7.css'));?><![endif]-->
		<?php
			echo $this->Html->meta('icon');
			echo $this->fetch('meta');

			echo $this->Html->css(array('bootstrap', /*'bootstrap-responsive.min',*/ 'datepicker', 'timePicker', 'core', 'colorbox/colorbox', 'style'));
			echo $this->fetch('css');

			//echo $this->Html->script('//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js')	//do we really ned this new jQuery version? timepicker is not working with it
			echo $this->Html->script('//ajax.googleapis.com/ajax/libs/jquery/1.7.0/jquery.min.js');
			echo $this->Html->script(array('bootstrap.min', 'bootstrap-datepicker', 'jquery.timePicker.min', 'jquery.form-replacement', 'jquery.colorbox-min', 'sessionstorage.1.4', 'signal', 'script'));
			echo $this->fetch('script');

			echo $this->fetch('uploader_head');	//JS and CSS files for uploader (only if there is an uploader on the page)

			//chat
			if(isset($logged) && $logged) {
				echo $this->Html->css(array('jquery-ui.css', 'jquery.ui.chatbox')); /*//ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/ui-lightness/*/
				echo $this->Html->script(array('//ajax.googleapis.com/ajax/libs/jqueryui/1.9.1/jquery-ui.min.js', 'jquery.ui.chatbox', 'chat'));
			}

		?>
            <!--[if lt IE 9]><?php echo $this->Html->script(array('html5.js')); ?><![endif]-->
            <!--[if (gte IE 6)&(lte IE 8)]><?php echo $this->Html->script(array('selectivizr-min.js')); ?><![endif]-->

	</head>

	<body>
	
<?php include_once("analyticstracking.php") ?>
		<div id="main-container" class="w990">

			<!--
			<div id="header" class="container">
				<?php //echo $this->element('menu/front_top' . ($logged ? '_logged' : '')); ?>
			</div> #header .container
			-->

            <header id="<?php if(empty($logged)) echo "home-header"; else echo "page-header";?>">
                <?php echo $this->element('menu/front_top' . ((isset($logged) && $logged) ? '_logged' : '')); ?>
            </header>

            <?php if(!empty($logged)): ?>

            <section id="breadcrumbs"><?php
                if(!empty($logged)):
                    echo $this->element('menu/breadcrumbs');
                else:
                	// ...
                endif;
			?></section>

	        <?php else: ?>
            <?php endif; ?>

			<section id="content_wrapper" class="clearfix">

				<?php echo $this->Session->flash(); ?>

                <!-- left sidebar -->
                <?php if(isset($categories) && !empty($categories)): ?>
                <div class="sidebar sleft"><?php

                	echo $this->element('sidebar/filter_sidebar', array(
                		'categories' => $categories,
                		'category_id' => isset($category_id) ? $category_id : null,
                		'listing' => isset($listing) && $listing == true ? true : false,
                		'pars' =>$pars,
                	));

                	if (isset($listing) && $listing==true) {
                		echo $this->element('sidebar/filter_sidebar_listing');
                	} else {
                		echo $this->element('sidebar/filter_sidebar_map');
                	}

                	// If categories are showed, sidebar should be under them
                	echo $this->element('sidebar/places_right', array('places' => $sidebar_places));

                ?></div>
				<?php endif; ?>

                <!-- content-wrapper -->
                <div class="<?php if(!empty($logged)){echo "main-content"; if (isset($sidebar_places) && !empty($sidebar_places)) {echo " has_sidebar_right"/*class if we have right sidebar*/; } elseif(isset($categories) && !empty($categories)){echo " has_sidebar_left";} }elseif(empty($logged)) echo "home-content";?>">
					<?php echo $this->fetch('content'); ?>
				</div>

				<!-- right sidebar -->
				<?php if (isset($sidebar_places) && !empty($sidebar_places) && empty($categories)): ?>
				<div class="sidebar sright">
                    <?php echo $this->element('sidebar/places_right', array('places' => $sidebar_places)); ?>
                </div>
				<?php endif; ?>

			</section>

			<footer><?php echo $this->element('menu/footer'); ?></footer>

			<div class="container well">
				<?php echo $this->element('sql_dump'); ?>
			</div>

		</div><!-- #main-container -->

<?php if (isset($logged) && $logged): ?>

		<?php echo $this->element('chat');	//chat element ?>

		<div id="dialog-form" title="Select reason for reporting" style="display:none;">
			<form id="report_form">
				<fieldset>
					<label for="reason">Reason</label>
					<select name="reason" id="reason">
					<?php foreach (Defines::$flag_types as $key => $flgt):?>
					  <option value="<?php echo $key;?>" <?php echo ($key == 0) ? 'selected' : ''; ?>><?php echo $flgt; ?></option>
					  <?php endforeach;?>
					</select>
				</fieldset>
			</form>
			<p id="rep_result" style="display:none;"></p>
		</div>

		<script>var current_member_big = <?php echo $logged['Member']['big']; ?>;</script>

<?php endif; ?>

	</body>

</html>