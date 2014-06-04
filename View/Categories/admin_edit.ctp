<?php

echo '<h1>' . ( isset($this->data['Category']) && $this->data['Category']['id']>0 ? __('Edit Category %s', $this->data['Category']['name']) : __('New Category') ) . '</h1>';

echo $this->AdvForm->create('Category');

echo $this->AdvForm->hidden('Category.id');

echo $this->AdvForm->inputs(array(
	'legend' => __('Basic Info'),
	'Category.name' => array('label' => __('Name'), 'type' => 'text'),
	'Category.external_name' => array('label' => __('External Name'), 'type' => 'text'),
	'Category.external_source' => array('label' => __('External Source'), 'options' => Defines::$external_sources),
	'Category.picture' => array(
		'label' => __('Category Icon / Picture'),
		'after' => '('.__('PNG only').') ' . (isset($this->data['Category']['id']) ? $this->Img->category_picture($this->data['Category']['id']) : ''),
		'uploader' => array(
			'data-preview' => true,		//show preview of uploaded images
			'data-multiple' => false,	//allow upload of multiple files
			'data-filetypes' => 'png',
		),
	),
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
	$categoryId = (isset($this->data['Category']['id']) ? $this->data['Category']['id'] : '');
		
	echo $this->AdvForm->hidden('CatLang.'.$rowId.'.cat_lang_id');
	echo $this->AdvForm->hidden('CatLang.'.$rowId.'.language_id', array('value' => $langId));
	echo $this->AdvForm->hidden('CatLang.'.$rowId.'.category_id', array('value' => $categoryId));
	
	echo $this->AdvForm->inputs(array(
		'legend' => __('Category name for ' . $lang),
		'CatLang.'.$rowId.'.name' => array('label' => __('Name'), 'type' => 'text'),
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