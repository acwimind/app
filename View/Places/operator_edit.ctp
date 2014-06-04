<?php

echo $this->element('menu/operator');
?>
<div class="operator_content">
    <div class="content-header">
<?php
echo '<h2>' . ( isset($this->data['Place']) && $this->data['Place']['big']>0 ? __('Edit Place %s', $this->data['Place']['name']) : __('New Place') ) . '</h2>';

?>
 </div>   
    <?php
echo $this->AdvForm->create('Place');

echo $this->AdvForm->hidden('Place.big');

echo $this->AdvForm->inputs(array(
	'legend' => __('Basic Info'),
	'Place.name' => array('label' => __('Name'), 'type' => 'text'),
	'Place.slug' => array('label' => __('Slug (for URL)'), 'type' => 'text'),
	'Place.short_desc' => array('label' => __('Short Description')),
	'Place.long_desc' => array('label' => __('Full Description')),
	'Place.photos' => array(
		'label' => __('Upload Photos'), 
		'uploader' => array(
			'data-preview' => true,		//show preview of uploaded images
			'data-multiple' => true,	//allow upload of multiple files
		),
	),
));

if (isset($this->data['Gallery'][0]['big']) && $this->data['Gallery'][0]['big']>0) {
	echo $this->Html->link(__('Display gallery'), array('controller' => 'galleries', 'action' => 'index', $this->data['Gallery'][0]['big']));
} else {
	echo __('No photos');
}

echo $this->AdvForm->inputs(array(
	'legend' => __('Contact Info'),
	'Place.url' => array('label' => __('URL'), 'type' => 'text'),
	'Place.phone' => array('label' => __('Phone')),
	'Place.email' => array('label' => __('E-mail Address')),
));

echo $this->AdvForm->inputs(array(
	'legend' => __('Region and Full Address'),
	'Region.id' => array('label' => __('Available Regions'), 'options' => $regions),
	'Place.address_street' => array('label' => __('Street')),
	'Place.address_street_no' => array('label' => __('Street Number')),
	'Place.address_zip' => array('label' => __('ZIP Code')),
	'Place.address_town' => array('label' => __('Town / City')),//, 'disabled' => true),
	'Place.address_province' => array('label' => __('Province')),//, 'disabled' => true),
	'Place.address_region' => array('label' => __('Region'),),// 'disabled' => true),
	'Place.address_country' => array('label' => __('Country'), 'default' => 'IT'),//, 'disabled' => true),
	'Place.lonlat' => array('label' => __('GPS Position'), 'after' => '<span class="help-inline">' . __('Format: (longitude,latitude)') . '</span>'),	//TODO: gmap with pin ?
));

echo $this->AdvForm->inputs(array(
	'legend' => __('Additional Info'),
	'Place.opening_hours' => array('label' => __('Opening Hours')),
	'Place.news' => array('label' => __('News (Text Only)')),
));

echo $this->AdvForm->submit('Save');

echo $this->AdvForm->end();
?>
</div>