</table>

<div class="pagination">
	<ul><?php
		echo '<li>' . $this->Paginator->prev(' < ', array('class' => 'prev'), null, array('class' => 'prev disabled')) . '</li>';
		echo '<li>' . $this->Paginator->numbers(array('modulus' => 5, 'separator' => '</li><li>', 'currentClass' => 'current', 'first' => 1, 'last' => 1, 'ellipsis' => '<span class="ellipsis">...</span>')) . '</li>';
		echo '<li>' . $this->Paginator->next(' > ', array('class' => 'next'), null, array('class' => 'next disabled')) . '</li>';
	?></ul>
	<div class="counts"><?php echo $this->Paginator->counter(
								    'Displaying {:start} - {:end} from {:count}'
								);
	?></div>
</div>