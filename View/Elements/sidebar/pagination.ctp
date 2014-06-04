<?php if (!empty($count) && $count > FRONTEND_PER_PAGE){
$currentPage = $offset + 1; 
$numPages = ceil($count/FRONTEND_PER_PAGE);
$pageDisplayLimit = 7;
$linkParams = $pars;
$pars['action'] = 'index';
?>
<div class="pagination">
	<ul>
		<li> 
			<span class="prev<?php if ($currentPage == 1){ echo ' disabled'; } ?>">
				<?php if ($currentPage == 1) { echo '<'; } 
					else {
						$pars['offset'] = $offset - 1;
						echo $this->Html->link('<',	$pars); 
					} ?>
			</span>
		</li>
		<?php 
		if ($numPages < $pageDisplayLimit):
			for ($i = 1; $i<$numPages + 1; $i++):
				if ($i == $currentPage):
			?>
			<li><span class="current"><?php echo $i; ?></span></li>
			<?php else: ?>
			<li><?php
			$pars['offset'] =  $i - 1;
			echo $this->Html->link($i, $pars); ?>
			</li>
			<?php endif; ?>
			<?php endfor;
		else:
		?>
		<li>
			<span <?php echo $currentPage == 1 ? 'class="current"' : null ?>>
				<?php if ($currentPage == 1) { echo 1; } 
				else {
					$pars['offset'] = 0;
				 	echo $this->Html->link('1', $pars);
				 }?>
			</span>
		</li>
		<?php
			$innerPagesCount = $pageDisplayLimit - 2; // Excluding first and last as they are always there
			$i = $currentPage - 2;
			if ($currentPage - 2 < 2)
				$i = 2;
			if ($i + $innerPagesCount > $numPages )
				$i = $numPages - $innerPagesCount;
			$forLimit = $innerPagesCount + $i;	

			// dots
			if ($i > 2) {
			?>
			<li><span class="ellipsis">...</span></li>
			<?php
			}
			for ($i; $i<$forLimit; $i++):
				if ($i == $currentPage):
			?>
			<li><span class="current"><?php echo $i; ?></span></li>
			<?php else: ?>
			<li><?php
			$pars['offset'] =  $i - 1;
			echo $this->Html->link($i, $pars); ?>
			</li>
			<?php endif; ?>
			<?php endfor;
			// dots
			if ($forLimit < $numPages) {
			?>
			<li><span class="ellipsis">...</span></li>
			<?php } ?>
			<li>
			<span <?php echo $currentPage == $numPages ? 'class="current"' : null ?>>
				<?php if ($currentPage == $numPages) { echo $numPages; } 
				else {
					$pars['offset'] = $numPages - 1;
					echo $this->Html->link($numPages, $pars);
				} ?>
			</span>
		</li>
		<?php endif; ?>
		<li> 
			<span class="next<?php if ($currentPage == $numPages){ echo ' disabled'; } ?>">
				<?php if ($currentPage == $numPages) { echo '>'; } 
				else { 
					$pars['offset'] = $offset + 1;
					echo $this->Html->link('>', $pars);
				 } ?>
			</span>
		</li>
	</ul>
</div>
<?php } ?>