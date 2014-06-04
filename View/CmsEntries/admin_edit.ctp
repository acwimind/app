<?php

echo $this->AdvForm->create('CmsEntry');

echo $this->AdvForm->hidden('CmsEntry.id');

//print_r('<pre>');
//print_r($this->data);
//print_r('</pre>');
//die();

echo $this->AdvForm->inputs(array(
	'legend' => __('Content Info'),
	'CmsEntry.section' => array('label' => __('Section'), 'options' => Defines::$cms_sections),
	'CmsEntry.name' => array('label' => __('Name'), 'type' => 'text')
));

echo $this->AdvForm->input('status', array(
	'label' => 'Status',
    'options' => array('0' => 'Inactive', '1' => 'Active'),
));

?>
<ul class="nav nav-tabs" id="langs">
<?php 
	$activeli = true;
	foreach (Defines::$languages as $lang)
	{
		if ($lang == Defines::$languages[LANG_NONE])
		continue;
	
		if ($activeli)
		{
			echo '<li class="active"><a href="#' . $lang . '">' . $lang . '</a></li>';
			$activeli = false;
		}
		else
		{
			echo '<li><a href="#' . $lang . '">' . $lang . '</a></li>';
		}
	}
?>
</ul>
<div class="tab-content">
<?php
$active = true;
foreach (Defines::$languages as $lang)
{
	if ($lang == Defines::$languages[LANG_NONE])
		continue;
		
	echo '<div class="tab-pane' . ($active ? ' active' : '') . '" id="' . $lang . '">';
	$active = false;
	
	$langId = array_search($lang, Defines::$languages);
	$rowId = $langId;
//	foreach ($this->data['CmsText'] as $key => $text)
//	{
//		if ($text['lang'] == $langId)
//		{
//			$rowId = $key;
//		}
//	}
		
	echo $this->AdvForm->hidden('CmsText.'.$rowId.'.id');
	echo $this->AdvForm->hidden('CmsText.'.$rowId.'.lang', array('value' => $langId));
	
	echo $this->AdvForm->inputs(array(
		'legend' => __('Content for ' . $lang),
		'CmsText.'.$rowId.'.status' => array('label' => 'Status', 'options' => array('0' => 'Inactive', '1' => 'Active')),
		'CmsText.'.$rowId.'.name' => array('label' => __('Name'), 'type' => 'text'),
//		'CmsText.'.$rowId.'.lang' => array('label' => __('Content Language'), 'options' => Defines::$languages),
		'CmsText.'.$rowId.'.slug' => array('label' => __('Slug')),
		'CmsText.'.$rowId.'.meta_title' => array('label' => __('Meta Title')),
		'CmsText.'.$rowId.'.meta_text' => array('label' => __('Meta Text')),
		'CmsText.'.$rowId.'.meta_keywords' => array('label' => __('Meta Keywords')),
		'CmsText.'.$rowId.'.perex' => array('label' => __('Perex'), 'type' => 'wysiwyg'),
		'CmsText.'.$rowId.'.text' => array('label' => __('Text'), 'type' => 'wysiwyg'),

	));
	
	echo '</div>';
	
}

?>
</div>
<script>
$('#langs a').click(function (e) {
    e.preventDefault();
    $(this).tab('show');
    })
</script>
<?php 

echo $this->AdvForm->submit('Save');

echo $this->AdvForm->end();