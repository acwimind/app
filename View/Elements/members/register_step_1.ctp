<?php

echo $this->Form->create('Member', array('id' => 'MemberRegisterForm', 'action' => 'register', 'autocomplete' => 'off'));

echo $this->Form->input('name', array('label' => __('First Name'), 'required' => true));
echo $this->Form->input('surname', array('label' => __('Last Name'), 'required' => true));
echo $this->Form->input('email', array('label' => __('E-mail'), 'required' => true));
echo $this->Form->input('phone', array('label' => __('Phone'), 'required' => true, 'validation' => 'numeric'));
echo $this->Form->input('password', array('label' => __('Password'), 'required' => true ));
echo $this->Form->input('agreement', array(
		'label' => __('I have read and agree with Terms & Conditions and Privacy Policy.'),
		'value' => 'Y',
		'hiddenField' => false,
		'div' => 'input check_agreement',
		'class' => 'chckbx_agreement',
		'type' => 'checkbox',
//		'checked' => 'checked',
	));

//echo '<p class="agreement">By registering an account at Haamble you confirm that you accept the Terms & Conditions and the Privacy Policy.</p>';

echo $this->Form->submit(__('Register'));

echo $this->Form->end();

$this->AdvForm->initUploader();

?>
<div id="lightbox">
	<div id="register-window"></div>
</div>
<script type="text/javascript">
$(function(){
	$('form#MemberRegisterForm').submit(function(){

		$('form#MemberRegisterForm div.error-message').remove();
		$('form#MemberRegisterForm div.error').removeClass('error');

		$.post('<?php echo $form_url; ?>', $(this).serialize(), function(response){
			try {

				var r = jQuery.parseJSON( response );
				console.log(r);
				for(key in r) {
					for(var i=0; i<r[key].length; i++) {
						var inputDiv = $('form#MemberRegisterForm input[name="data[Member]['+key+']"]').closest('div.input');
						inputDiv.addClass('error');
						inputDiv.append('<div class="error-message">' + r[key][i] + '</div>');
					}
				}

			} catch (err) {

				$('#register-window').html(response);
				$('.content-header').remove();
				$('#register-window').fadeIn();
				$('#lightbox').fadeIn();

			}
		});
		return false;
	});
});
</script>
