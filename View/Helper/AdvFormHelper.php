<?php 

App::uses('FormHelper', 'View/Helper');

class AdvFormHelper extends FormHelper {

	public $helpers = array('Date', 'Html', 'Uploader.UploadForm', 'Tinymce');
	
	public function input($fieldName, $options = array()) {
		
		if (isset($options['div']) && is_string($options['div'])) {	//FIX: we will need the div as array from now on
			$options['div'] = array('class' => $options['div']);
		}
		
		//is the field togglable
		if (isset($options['toggle']) && $options['toggle'] !== false) {
			$toggle = $options['toggle'];
			unset($options['toggle']);
			$options['div']['class'] = 'input toggled-input';
		} else {
			$toggle = false;
		}
		
		
		$out = '';
		
		if( isset($options['picker']) && $options['picker'] != false ) {	//date and time picker
			
			$out .= $this->_picker($fieldName, $options);
		
		} elseif( isset($options['slider']) && $options['slider'] != false ) {	//slider
			
			$out .= $this->_slider($fieldName, $options);
			
		} elseif( isset($options['uploader']) && $options['uploader'] != false ) {	//uploader
			
			$out .= $this->_uploader($fieldName, $options);
			
		} elseif( isset($options['type']) && $options['type'] == 'wysiwyg' ) {	//wysiwyg
			
			$tinyoptions = (isset($options['tinyoptions'])) ? $options['tinyoptions'] : array(); 
			$preset = (isset($options['preset'])) ? $options['preset'] : null; 
			$out .= $this->Tinymce->input($fieldName, $options = array(), $tinyoptions, $preset);
			
		} elseif( isset($options['multiajax']) && $options['multiajax'] != false ) {

			$out .= $this->_multiajax($fieldName, $options);

		} else {	//regular fields
			
			$out .= parent::input($fieldName, $options);
			
		}
		
		if ($toggle !== false) {	//if field is togglable, add proper html code
			$out = $this->_toggle($toggle, $fieldName, $options, $out);
		}
		
		return $out;

	}

	/**
	 * Make multiple select with ajax autocomplete
	 * Requires jQuery TextExt Plugin http://textextjs.com/
	 * @param  string $fieldName of the input
	 * @param  array  $options for the input
	 * @return string html of the input field
	 */
	private function _multiajax($fieldName, $options = array()) {

		$out = '';

		$multiajax_opts = $options['multiajax'];
		unset($options['multiajax']);

		$options['type'] = 'textarea';
		if (isset($options['class'])) {
			$options['class'] .= ' multiajax';
		} else {
			$options['class'] = 'multiajax';
		}
		$options['style'] = 'width:400px';
		$options['rows'] = 1;

		$options['data-url'] = $this->Html->url($multiajax_opts['url']);
		$options['data-value'] = $multiajax_opts['value'];

		$out .= parent::input($fieldName, $options);

		return $out;

	}
	
	/**
	 * 
	 * Make any input togglable - add checkbox, this checkbox can show/hide the input
	 * @param array $options for the toggle checkbox
	 * @param string $fieldName of original input
	 * @param string $fieldOptions of original input
	 * @param string $fieldHtml of original input
	 * @return string html of the final field, including checkbox for toggle and the input itself
	 */
	private function _toggle($options, $fieldName, $fieldOptions, $fieldHtml) {
		
		$out = '';
		
		if (!is_array($options)) {
			$options = array();
		}
		$options += array(
			'label' => __('(check to enable)'),
		);
		$options['type'] = 'checkbox';
		$options['after'] = $fieldHtml;
		
		if (isset($options['div'])) {
			if (is_string($options['div'])) {
				$options['div'] = array('class' => $options['div']);
			}
			$options['div']['class'] .= ' toggle';
		} else {
			$options['div'] = 'input checkbox toggle';
		}
		
		$out .= parent::input($fieldName.'Toggle', $options);
		
		return $out;
		
	}
	
	/**
	 * Creates a date picker and time picker pair of fields (or, if specified, one of those)
	 * @param string $fieldName
	 * @param array $options
	 * @return string input field html
	 */
	private function _picker($fieldName, $options = array()) {
		
		$div = array();
		
		if (substr_count($fieldName, '.') == 0 && !empty($this->defaultModel)) {
			$fieldName = $this->defaultModel . '.' . $fieldName;
		}
		
		if($this->requestType == 'get') {
			
			$fieldName = substr($fieldName, strrpos($fieldName, '.')+1);
			
			$value = Set::extract($this->defaultModel . '.' . $fieldName . 'Date', $this->data) .
					 ' ' . Set::extract($this->defaultModel . '.' . $fieldName . 'Time', $this->data);
			
			$fieldName = array(
				'date' => $fieldName . 'Date',
				'time' => $fieldName . 'Time',
			);
			
		} else {
			
			$value = Set::extract($fieldName, $this->data);
			
			$fieldName = array(
				'date' => $fieldName . '.date',
				'time' => $fieldName . '.time',
			);
			
		}
		if (!is_array($value)) {
			if (strpos($value, ' ') === FALSE)
			{
				if (strpos($value, ':') === FALSE)
				{
					$tmp_value['date'] = $value;
					$tmp_value['time'] = null;
				}
				else 
				{
					$tmp_value['date'] = null;
					$tmp_value['time'] = $value;
				}
			}
			else 
			{
				list($tmp_value['date'], $tmp_value['time']) = explode(' ', $value);
			}
			$value = $tmp_value;
		}
		
		$date_input = '';
		$time_input = '';
		
		if ($options['picker'] == 'datetime' || $options['picker'] == 'date') {
			$options += array(
				'type' => 'text',
				'class' => 'input-small datepicker',
				'data-date-format' => $this->Date->datepicker_format(),
				'div' => array_key_exists('div', $options) ? $options['div'] : false,
				'value' => $this->Date->date($value['date'] != null ? $value['date'] : date('Y-m-d'), true),
			); 
//			if ($options['div'] != false) {
//				$div = $options['div'];
//				$options['div'] = false;
//			}
			$date_input = parent::input($fieldName['date'], $options);
		}
			
		if ($options['picker'] == 'datetime' || $options['picker'] == 'time') {
			$time_options = array(
				'div' => false,
				'label' => $options['picker']=='time' ? $options['label'] : false,
				'class' => 'input-mini timepicker',
				'value' => $this->Date->time($value['time'] != null ? $value['time'] : date('Y-m-d H:i')),
			);
			$time_input = parent::input($fieldName['time'], $time_options);
		}
		
		$div_class = 'input date clearfix ';
		if (isset($div['class'])) {
			$div_class .= $div['class'];
		}
			
		return $this->Html->div($div_class, $date_input.$time_input, $div);
		
	}
	
	/**
	 * Creates slider field
	 * @param string $fieldName
	 * @param array $options
	 * @return string input field html
	 */
	private function _slider($fieldName, $options) {
		
		$div = array();
		
		$append = '<div class="slidebar"></div>';
		
		$originalFieldName = $fieldName;
		$value = Set::extract($fieldName, $this->data);
		
		if (isset($options['div']) && $options['div'] != false) {
			$div = $options['div'];
		}
		
		$options['div'] = array('class' => 'pull-left');
		$options['class'] = 'input-mini';
		$options['after'] = '%';
		
		$div_class = 'input slider clearfix ';
		if (isset($div['class'])) {
			$div_class .= $div['class'];
		}
		
		return $this->Html->div($div_class, parent::input($fieldName, $options) . $append, $div);
		
	}
	
	/**
	 * Creates field for file uplaod (calls Uploader plugin)
	 * @param string $fieldName
	 * @param array $options
	 * @return string upload field html
	 */
	private function _uploader($fieldName, $options=array()) {
		
		//call the function from uploader plugin
		//this helps us encapsulate the input field in AdvFormhelper and output it using inputs() / input() methods
		return $this->UploadForm->input($fieldName, $options);
		
	}
	
	/**
	 * Initialize JS for upload form without printing input
	 */
	public function initUploader() {
		 $this->UploadForm->init();
	}

}