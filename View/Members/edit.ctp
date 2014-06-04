<?php

echo '<div class="content-header p_info"><h2>' . __('Edit profile') . '</h2></div>';

echo $this->AdvForm->create('Member');

echo $this->AdvForm->hidden('Member.big');

/*if ($register == 'register') {
	
} else {
	
}*/

echo $this->AdvForm->inputs(array(
	'legend' => false,//__('Personal Info'),
	'Member.name' => array('label' => __('First Name')),
	'Member.middle_name' => array('label' => __('Middle Name'), 'required' => false),
	'Member.surname' => array('label' => __('Last Name')),
	'Member.birth_date' => array('label' => __('Date of Birth'), 'picker' => 'date', 'required' => false, 'div' => true),
	'Member.birth_place' => array('label' => __('Place of Birth'), 'required' => false),
	'Member.sex' => array('label' => __('Gender'), 'options' => array(null => __('please select'), 'm' => __('male'), 'f' => __('female')), 'required' => false),
	'Member.phone' => array('label' => __('Phone Number'), 'required' => false),
	'Member.address_street' => array('label' => __('Street'), 'required' => false),
	'Member.address_street_no' => array('label' => __('Street Number'), 'required' => false),
	'Member.address_town' => array('label' => __('Town'), 'required' => false),
	'Member.address_province' => array('label' => __('Province'), 'required' => false),
	'Member.address_region' => array('label' => __('Region'), 'required' => false),
	'Member.address_country' => array('label' => __('Country'), 'required' => false),
	'Member.address_zip' => array('label' => __('ZIP Code'), 'required' => false, 'autocomplete' => 'off'),
		'Member.address_zip' => array('label' => __('ZIP Code'), 'required' => false, 'autocomplete' => 'off'),
));
?>

<div >
<label>SMS Verification code:</label>
<div >
<?php echo $this->Form->input('xme', array(
            'div'=>false, 'label'=>false)); ?>
    </div>
</div>

<?php
$img = '';
if (isset($this->data['Member']['big']) && isset($this->data['Member']['photo_updated'])) {
	$img = $this->Html->image(
		$this->Img->profile_picture($this->data['Member']['big'], $this->data['Member']['photo_updated'], 100, 100)
	);
}

if ($register == false) {
	
	echo $this->AdvForm->inputs(array(
		'legend' => __('Account Info'),
		'Member.password' => array('label' => __('New Password'), 'required' => false, 'after' => __('only fill in if you want to change the password'), 'autocomplete' => 'off'),
		'Member.password2' => array('label' => __('Repeat Password'), 'required' => false, 'type' => 'password',  'autocomplete' => 'off'),
		'Member.lang' => array('label' => __('Prefered Communication Language'), 'options' => Defines::$languages),
		'Member.photo' => array(
			'label' => __('Profile Picture'), 
			'uploader' => array(
				'default' => $img,
				'data-preview' => true,		//show preview of uploaded images
				'data-multiple' => false,	//allow upload of multiple files
				//'data-filetypes' => null,	//TODO: file type cannot be specified yet, only images are allowed
			),
		),
	));

	echo '<fieldset class="social-connect">';
	echo '<legend>' . __('Social Connect') . '</legend>';

	echo '<div class="social-connect-fb">';
	if (isset($fb_user)) {
		echo __('Connected to Facebook as %s %s', 
			$this->Html->image('https://graph.facebook.com/'.$fb_user['username'].'/picture?type=square'),
			$this->Html->link($fb_user['name'], $fb_user['link']));
		echo ' ' . $this->Html->link(__('Disconnect Facebook Account'), array('action' => 'unlink_fb'), array('class' => 'button'));
	} else {
		echo $this->Html->link(__('Connect with Facebook Account'), array('action' => 'login_fb'), array('class' => 'button fb-button'));
	}
	echo '</div>';

	echo '</fieldset>';
	
} else {
	
	echo $this->AdvForm->input('Member.photo', array(
		'label' => __('Profile Picture'), 
		'uploader' => array(
			'default' => $img,
			'data-preview' => true,		//show preview of uploaded images
			'data-multiple' => false,	//allow upload of multiple files
			//'data-filetypes' => null,	//TODO: file type cannot be specified yet, only images are allowed
		),
	));
	
}

echo $this->AdvForm->submit('Save');

echo $this->AdvForm->end();

?>
<script type="text/javascript">init_uploader();</script>