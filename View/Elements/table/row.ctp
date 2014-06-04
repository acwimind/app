<tr><?php

echo '';

foreach($cols as $col) {
	
	//if (is_array($col)) {
	//} else
	{
		echo '<td>' . $col . '</td>';
	}
	
}

if (isset($id)) {

	echo '<td class="options">' . $this->element(
		'table/row_options', 
		array('id' => $id, 'options' => isset($options) ? $options : null, 'custom' => isset($custom) ? $custom : false, 'show_label' => isset($show_label) ? $show_label : false)
	) . '</td>';

}

?></tr>