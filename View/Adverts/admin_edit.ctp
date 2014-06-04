<?php

echo $this->AdvForm->create('Advert');

echo $this->AdvForm->hidden('Advert.id');

echo $this->AdvForm->inputs(array(
	'legend' => __('Advertisment Content'),
	'Advert.heading' => array('label' => __('Heading'), 'type' => 'text'),
	'Advert.url' => array('label' => __('Url'), 'type' => 'text'),
	'Advert.text' => array('label' => __('Text'), 'type' => 'text'),
));

echo $this->AdvForm->input('status', array(
	'label' => 'Status',
    'options' => array('0' => 'Inactive', '1' => 'Active'),
));

$img = '';
if (isset($this->data['Advert']['id']) && isset($this->data['Advert']['photo_ext']) && isset($this->data['Advert']['photo_updated'])) {
	$img = $this->Html->image(
		$this->Img->advert_picture($this->data['Advert']['id'], $this->data['Advert']['photo_updated'], $this->data['Advert']['photo_ext'], 100, 100),
		array('class' => 'adv_cms_img')
	);
}
echo $this->AdvForm->input('Advert.photo', array(
		'label' => __('Advertisment Picture'), 
		'uploader' => array(
			'default' => $img,
			'data-preview' => true,		//show preview of uploaded images
			'data-multiple' => false,	//allow upload of multiple files
			//'data-filetypes' => null,	//TODO: file type cannot be specified yet, only images are allowed
		),
	));

echo $this->AdvForm->submit('Save');

echo $this->AdvForm->end();