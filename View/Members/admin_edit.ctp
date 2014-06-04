<?php

echo '<h1>' . ( isset($this->data['Member']) && $this->data['Member']['big']>0 ? __('Edit User %s', $this->data['Member']['name'].' '.$this->data['Member']['surname']) : __('New User') ) . '</h1>';

echo $this->AdvForm->create('Member');

echo $this->AdvForm->hidden('Member.big');

$img = '';
if (isset($this->data['Member']['big'])) {
	$img = $this->Html->image(
		$this->Img->profile_picture($this->data['Member']['big'], $this->data['Member']['photo_updated'])
	);
}

echo $this->AdvForm->inputs(array(
	'legend' => __('Account Info'),
	'Member.email' => array('label' => __('E-mail')),
	'Member.password' => array('label' => __('Password'), 'toggle' => true),
	'Member.type' => array('label' => __('User Type'), 'options' => Defines::$member_types),
	'Member.lang' => array('label' => __('Prefered Communication Language'), 'options' => Defines::$languages),
	'Member.photo' => array(
		'label' => __('Profile Picture'), 
		'after' => '('.__('JPG only').') ',
		'uploader' => array(
			'default' => $img,
			'data-preview' => true,		//show preview of uploaded images
			'data-multiple' => false,	//allow upload of multiple files
			'data-filetypes' => 'jpg,jpeg',
		),
	),
));

echo $this->AdvForm->inputs(array(
	'legend' => __('Personal Info'),
	'Member.name' => array('label' => __('First Name')),
	'Member.middle_name' => array('label' => __('Middle Name'), 'required' => false),
	'Member.surname' => array('label' => __('Last Name')),
));

echo $this->AdvForm->inputs(array(
	'legend' => __('Operator Info'),
	'Operator.company_name' => array('label' => __('Company Name'), 'type' => 'text'),
	'Operator.address_street' => array('label' => __('Address Street'), 'required' => false),
	'Operator.address_street_no' => array('label' => __('Address Street Number'), 'required' => false),
	'Operator.address_town' => array('label' => __('Town'), 'required' => false),
	'Operator.address_province' => array('label' => __('Province'), 'required' => false),
	'Operator.address_region' => array('label' => __('Region'), 'required' => false),
	'Operator.address_country' => array('label' => __('Country'), 'required' => false),
	'Operator.address_zip' => array('label' => __('Zip'), 'required' => false),
	'Operator.vat_id' => array('label' => __('Vat ID'), 'type' => 'text'),
	'Operator.email' => array('label' => __('Email'), 'required' => false),
	'Operator.phone' => array('label' => __('Phone'), 'required' => false),
),
null,
array(
	'fieldset' => 'op_info',
)
);

echo $this->AdvForm->inputs(array(
	'legend' => __('Permissions'),
	'Member.status' => array('type' => 'checkbox', 'label' => __('Active'), 'after' => '<span class="help-block">' . __('Inactive user cannot log in to the application') . '</span>'),
	'MemberPerm.login' => array('type' => 'checkbox', 'label' => __('Can Log In'), 'after' => '<span class="help-block">' . __('This might be a duplicity (see "active" above). Remove?') . '</span>'),
	'MemberPerm.photo_upload' => array('type' => 'checkbox', 'label' => __('Can Upload Photos')),
	'MemberPerm.chat' => array('type' => 'checkbox', 'label' => __('Can Use Chat')),
	'MemberPerm.comment' => array('type' => 'checkbox', 'label' => __('Can Post Comments'), 'after' => '<span class="help-block">' . __('Comments are not implemented yet') . '</span>'),
	'MemberPerm.checkin' => array('type' => 'checkbox', 'label' => __('Can Join / Check-in To Places')),
	'MemberPerm.signal' => array('type' => 'checkbox', 'label' => __('Can Signal Content')),
));

echo $this->AdvForm->submit('Save');

echo $this->AdvForm->end();
?>
<script>
$(document).ready(function(){
	if($('#MemberType').prop("value") != 2 ) // Member type operator
	{
		$('.op_info').hide();
	}
});

$('#MemberType').change(function(ev){
	if(ev.currentTarget.value == 2)
	{
		$('.op_info').show();
	}
	else
	{
		$('.op_info').hide();
	}
});
</script>