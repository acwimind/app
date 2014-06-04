<?php

echo $this->AdvForm->create('Member', array('action' => 'login'));

echo $this->AdvForm->input('email_or_phone', array('label' => __('Email Or Phone'), 'required' => true));

echo $this->AdvForm->input('password', array( 'required' => true) );

echo $this->AdvForm->submit('Login');

echo $this->Html->link(__('Login with'), array('controller' => 'members', 'action' => 'login_fb'), array('class' => 'fb_login'));
echo $this->AdvForm->input('remember', array( 'type' => 'checkbox', 'class' => 'forgot', 'label' => 'Remember me'));
echo $this->Html->link(__('Forgot password?'), array('controller' => 'members', 'action' => 'forgot_password'), array('class' => 'forgot'));

echo $this->AdvForm->end();