<table class="table">

<thead><tr><?php

foreach($cols as $sort_key => $title) {
	
	$colspan = 0;
	
	if (is_array($title)) {
		list($title, $colspan) = $title;
	}
	
	if ($sort_key === intval($sort_key)) {
		
		echo '<th>' . $title . '</th>';
		
	} else {
		
		if (preg_match('/^(.+)\.updated$/', $sort_key, $match)) {
			$sort_key .= ', '.$match[1].'.created';
		}
		
		echo '<th'.($colspan>1 ? ' colspan="'.$colspan.'"' : '').'>' . $this->Paginator->sort($sort_key, $title) . '</th>';
		
	}
	
}

if (!isset($options) || $options == true) {
	echo '<th class="options">Options</th>';
}

?>
</tr></thead>
    