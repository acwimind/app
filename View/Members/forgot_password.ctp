<!DOCTYPE html>
<html>
<head>
<title><?php echo __('Recupera Password'); ?></title>
<link href="../less/compiler.php" rel="stylesheet" type="text/css">
</head>

<body>


<div class="haamble-modal">
<h2>Password dimenticata</h2>
<?php 
echo $this->Form->create('Member');

echo $this->Form->input('Email', array('label' => __(' '),'placeholder' => __('Indirizzo E-mail')));

echo $this->Form->end(__('Continua'));
?>
</div>

</body>
</html>