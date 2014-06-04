<?php

/**
 * define variable $tabs as array
 * key = string name of the link
 * value = array/string URL of the page 
 */

if (!isset($tabs)) $tabs = array();

$tab_menu = array();
$options = array('class' => 'act');

$i=0;
foreach($tabs as $name=>$link) {
	$i++;
	$options = array('data-id' => 'tab-'.preg_replace('/[^\da-z]/i', '-', mb_strtolower($name)));
	$tab_menu[] = $this->Html->link($name, $link, $options);
	$options = array();
}

?>
<header id="tab-nav">
	<h3><?php echo reset( array_keys($tabs) ); ?></h3>
	<?php
		echo $this->Html->nestedList($tab_menu, array('class' => 'nav_tabs'));
	?>
</header>

<div id="tab-content"><?php
	
		echo $this->requestAction(reset($tabs), array('return'));

?></div>

<script type="text/javascript">
$('#tab-nav ul a').click(function(){
	
	$('#tab-nav h3').html( $(this).html() );
	$('#tab-nav h3').attr('id', $(this).data('id') );
	location.hash = '#' + $(this).data('id');

	$('#tab-nav ul a').removeClass('act');
	$(this).addClass('act');

	$('#tab-content').html('Loading...');
	$('#tab-content').load( $(this).attr('href'), function(){
		
		$('a.zoom-image').colorbox();
		
		$('a.open-chat').click(function(){
			return bind_open_chat($(this));
		});

	} );

	return false;

});
var hash = location.hash.replace('#', '');
if (hash.indexOf('tab-') === 0) {
	$('#tab-nav ul a[data-id='+hash+']').click();
}
</script>
