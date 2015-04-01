<!DOCTYPE html>
<html>
<head>
<title><?php echo __('Cambia Password'); ?></title>
<link href="../less/compiler.php" rel="stylesheet" type="text/css">
</head>

<body>


<div class="haamble-modal">
<?php

echo $this->Form->create('Member');

echo $this->Form->input('email', array('label' => __('Indirizzo E-mail')));
echo $this->Form->input('password', array('label' => __('Nuova Password')));
echo $this->Form->input('password2', array('label' => __('Ripeti Password'), 'type' => 'password'));

echo $this->Form->end(__('Cambia'));	
?>
</div>

</body>
</html>

