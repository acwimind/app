<?php

echo $this->Form->create('Member');

echo $this->Form->input('email', array('label' => __('E-mail Address')));
echo $this->Form->input('password', array('label' => __('New Password')));
echo $this->Form->input('password2', array('label' => __('Repeat Password'), 'type' => 'password'));

echo $this->Form->end(__('Change'));