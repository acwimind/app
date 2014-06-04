<?php 
$linkParams = $pars;
$pars['action'] = $listing ? 'index' : 'map';
?>
<nav class="categories">
    <h3>Categories</h3>
    <ul>
        <?php
        foreach ($categories as $cat):
        ?>
        <li class="<?php if ($category_id == $cat['Category']['id']) { echo ' active'; }?>">
            <?php 
            	if ($cat['Category']['id'] == 0)
            	{
            		unset($pars['category']);
            	}
            	else 
            	{
            		$pars['category'] = $cat['Category']['id'];
            	}
            	unset($pars['offset']);
                echo $this->Html->link(
                    __($cat['Category']['name']) . ' (' . $cat['Category']['count'] . ')', 
                    $pars
                );
            ?>
        </li>
        <?php endforeach; ?>
    </ul>    
</nav>

