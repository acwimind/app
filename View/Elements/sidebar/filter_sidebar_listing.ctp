<?php echo $this->Form->create('', array('type' => 'post', 'id' => 'listing-filter', 'action' => 'index'));
if (isset($category_id))
{
	echo $this->Form->hidden('category', array('value' => $category_id));
}
?>
<div class="location">
    <h3>Country</h3>
    <?php
        echo $this->Form->input('country', array(
            'class' => 'styled',
            'options' => Defines::$countries,
            'label' => false,
        ));
    ?>
    <h3>City</h3>
    <?php 
        echo $this->Form->input('city', array(
            'label' => false,
            'value' => !empty($city) ? $city : null,
            'autocomplete' => 'off',
            'placeholder' => 'Enter city'
        ));
    ?>

    <h3>Distance</h3>
    <?php
        echo $this->Form->input('distance', array(
            'class' => 'styled',
            'options' => Defines::$distance,
            'label' => false,
        	'empty' => 'All',
        	'value' => !empty($distance) ? $distance : null
        ));
    ?>
    <h3>Rating</h3>
    <?php
        echo $this->Form->input('rating', array(
            'class' => 'styled',
            'options' => Defines::$ratings,
            'label' => false,
            'empty' => 'All',
        	'value' => !empty($rating) ? $rating : null
        ));
    ?>
</div>
<?php echo $this->Form->end(__('Filter')); ?>


<script type="text/javascript">
$('input#PlaceCity').keyup(function(){
    if ($(this).val().length < 2) {
        return false;
    }

    var input = $('input#PlaceCity');

    $.ajax({
        url: "<?php echo $this->Html->url(array('controller' => 'regions', 'action' => 'autocomplete')); ?>", 
        type: 'post',
        data: { city: $(this).val(), country: $('select#PlaceCountry option:selected').val() }
    }).done(function(result){
        
        var html = '<ul id="city-autocomplete">';

        var regions = $.parseJSON(result);
        for(var i=0; i<regions.length; i++) {
            html += '<li>' + regions[i] + '</li>';
        }

        html += '</ul>';

        $('ul#city-autocomplete').remove();
        input.after(html);

    });

});

$('form#listing-filter').on('click', 'ul#city-autocomplete li', function(){

    $('input#PlaceCity').val( $(this).html() );
    $('ul#city-autocomplete').remove();

});
</script>
