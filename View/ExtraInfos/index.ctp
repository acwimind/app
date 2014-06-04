<h2><?=__("Let's get to know each other");?></h2>
<?php
echo $this->Form->create('ExtraInfos', array('action' => 'index'));
echo $this->Form->input('country_code',        array('label' => __('Nationality'),
											'options' => array( $countries ),
		'empty' => __('(choose one)')

) );
echo $this->Form->input('city',               array('label' => __('City') , 'type' => 'text') );
echo $this->Form->input('occupation',         array('label' => __('Occupation'), 'type' => 'text' ));
echo $this->Form->input('music',              array('label' => __('Music'), 'type' => 'text' ));
echo $this->Form->input('food',               array('label' => __('Food'), 'type' => 'text' ));
echo $this->Form->input('fashion',            array('label' => __('Fashion'), 'type' => 'text' ));
echo $this->Form->input('primary_language',   array('label' => __('Primary language'), 'type' => 'text' ));
echo $this->Form->input('secondary_language', array('label' => __('Secondary language'), 'type' => 'text' ));
echo $this->Form->input('looking_for',        array('label' => __('Looking for'),
						'options' => array('Friendship' => __('Friendship'), 'Love' => __('Love'), 'Sex' => __('Sex')),
						'empty' => __('(choose one)')
));
echo $this->Form->submit(__('Save'));
echo $this->Form->end();
?>
