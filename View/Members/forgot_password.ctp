<?php

echo $this->Form->create('Member');

echo $this->Form->input('email', array('label' => __('E-mail Address')));

echo $this->Form->end(__('Continue'));