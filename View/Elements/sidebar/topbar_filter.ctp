<div class="topbar_filter">
<script type="text/javascript">
$(document).ready(function() {
	$("#sort").change(function () {
	    var str = "";
	    str = $("#sort option:selected").val();
		var loc = window.location.href;
		if(loc.indexOf('sort:') >= 0)
		{
			if(loc.indexOf('sort:' + str) < 0)
			{
				postfix = loc.substring(loc.indexOf('sort:'));
				loc = loc.substring(0, loc.indexOf('sort:'));
				loc = loc + 'sort:' + str;
				if(postfix.indexOf('/') >= 0)
				{
					postfix = postfix.substring(postfix.indexOf('/'));
					loc = loc + postfix;
				}
			}
		}
		else
		{
			if(loc.indexOf('/index') < 0)
			{
				loc = loc + '/index';
			}
			loc = loc + '/sort:' + str;
		}
		window.location.replace(loc);
	});

	$("#search").change(function () {
		    var phr = "";
   			phr = $("#search").val();
			var loc = window.location.href;
			if(loc.indexOf('search:') >= 0)
			{
				postfix = loc.substring(loc.indexOf('search:'));
				loc = loc.substring(0, loc.indexOf('/search:'));
				if(phr.length > 0)
				{
					loc = loc + '/search:' + phr;
				}
				if(postfix.indexOf('/') >= 0)
				{
					postfix = postfix.substring(postfix.indexOf('/'));
					loc = loc + postfix;
				}
			}
			else
			{
				if(loc.indexOf('/index') < 0)
				{
					loc = loc + '/index';
				}
				loc = loc + '/search:' + phr;
			}

			if (loc.indexOf('offset:') >= 0) 
			{
				postfix = loc.substring(loc.indexOf('offset:'));
				loc = loc.substring(0, loc.indexOf('/offset:'));
				if(postfix.indexOf('/') >= 0)
				{
					postfix = postfix.substring(postfix.indexOf('/'));
					loc = loc + postfix;
				}
			}
			window.location.replace(loc);
		});
});
</script>
    <?php
    	
    	$opts = Defines::$order;
    	$defSort = 'name';
    	if (!isset($pars['city']))
    	{
    		unset($opts['distance']);
    	}
    	else 
    	{
    		$defSort = 'distance';
    	}
    	if (!isset($pars['search']))
    	{
    		unset($opts['relevance']);
    	}
    	else 
    	{
    		$defSort = 'relevance';
    	}
    	echo $this->Form->input('sort', array(
            'class' => 'styled',
            'options' => $opts,
        	'value' => !empty($sort) ? $sort : $defSort,
            'label' => false
        ));
    ?>
    <?php 
    echo $this->Form->input('search', array(
        'label' => false,
        'placeholder' => __('Enter search phrase here...'),
        'value' => !empty($search) ? $search : null
    )); 
    echo $this->Form->submit('search', array(
        'label' => false
    )); 
       ?>
</div>
