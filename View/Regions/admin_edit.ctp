<?php

echo '<h1>' . ( isset($this->data['Region']) && $this->data['Region']['id']>0 ? __('Edit Region %s', $this->data['Region']['name']) : __('New Region') ) . '</h1>';

echo $this->AdvForm->create('Region');

echo $this->AdvForm->hidden('Region.id');

echo $this->AdvForm->inputs(array(
	'legend' => __('Basic Info'),
	'Region.city' => array('label' => __('City'), 'type' => 'text'),
	'Region.country' => array('label' => __('Country'), 'options' => Defines::$countries),
));

echo $this->AdvForm->inputs(array(
	'legend' => __('Address'),
	'Region.address_town' => array('label' => __('Town / City'), 'type' => 'text'),
	'Region.address_province' => array('label' => __('Province')),
	'Region.address_region' => array('label' => __('Region')),
	'Region.address_country' => array('label' => __('Country'), 'options' => Defines::$countries),
	'Region.lonlat_lowerleft' => array('label' => __('Lower left corner (GPS Position)'), 'after' => '<span class="help-inline">' . __('Format: (longitude,latitude)') . '</span>', 'type' => 'text'),	//TODO: gmap with pin ?
	'Region.lonlat_topright' => array('label' => __('Top right corner (GPS Position)'), 'after' => '<span class="help-inline">' . __('Format: (longitude,latitude)') . '</span>', 'type' => 'text'),	//TODO: gmap with pin ?
));

if (isset($this->data['Region']) && isset($this->data['Region']['lonlat_lowerleft']) && isset($this->data['Region']['lonlat_topright'])) {
	echo $this->Map->static_region($this->data['Region']['lonlat_lowerleft'], $this->data['Region']['lonlat_topright'], 10, '600x300');
}

?>
<br />
<br />
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
	$regionId = (isset($this->data['Region']['id']) ? $this->data['Region']['id'] : '');
		
	echo $this->AdvForm->hidden('RegionLang.'.$rowId.'.region_lang_id');
	echo $this->AdvForm->hidden('RegionLang.'.$rowId.'.language_id', array('value' => $langId));
	echo $this->AdvForm->hidden('RegionLang.'.$rowId.'.region_id', array('value' => $regionId));
	
	echo $this->AdvForm->inputs(array(
		'legend' => __('City name for ' . $lang),
		'RegionLang.'.$rowId.'.city' => array('label' => __('City'), 'type' => 'text'),
	));
	
	echo $this->AdvForm->inputs(array(
		'legend' => __('Address for ' . $lang),
		'RegionLang.'.$rowId.'.address_town' => array('label' => __('Town / City'), 'type' => 'text'),
		'RegionLang.'.$rowId.'.address_province' => array('label' => __('Province'), 'type' => 'text'),
		'RegionLang.'.$rowId.'.address_region' => array('label' => __('Region'), 'type' => 'text'),
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