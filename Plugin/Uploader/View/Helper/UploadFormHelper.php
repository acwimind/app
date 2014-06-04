<?php 

App::uses('AppHelper', 'View/Helper');

/**
 * Creates upload inputs and buttons required for fine uploader
 */
class UploadFormHelper extends AppHelper {
	
	public $helpers = array('Html', 'Form', 'Uploader.UploadForm');
	private $uploaders = 0;
	
	public function init() {
		
		//if there are no uploaders yet, include all necesary JS and CSS files (but only once)
		if ($this->uploaders == 0) {
		
			$this->uploaders++;
		
			$path = '/uploader/fineuploader/';
			$path_js = $path . 'js/';
				
			$js = array(
				$path_js . 'header.js',
				$path_js . 'util.js',
				$path_js . 'button.js',
				$path_js . 'ajax.requester.js',
				$path_js . 'deletefile.ajax.requester.js',
				$path_js . 'handler.base.js',
				$path_js . 'window.receive.message.js',
				$path_js . 'handler.form.js',
				$path_js . 'handler.xhr.js',
				$path_js . 'uploader.basic.js',
				$path_js . 'dnd.js',
				$path_js . 'uploader.js',
				$path_js . 'jquery-plugin.js',
				'/uploader/js/' . 'uploader.js',
			);
		
			$css = array(
				$path . 'fineuploader.css'
			);
		
			$head = '';
			$head .= $this->Html->css($css);
			$head .= $this->Html->script($js);
		
			$this->_View->assign('uploader_head', $head);	//assign to variable, we will print it in <head> of the page
		
			return true;
			
		} else {
			
			return false;
			
		}
		
		
	}
	
	/**
	 * Input for file upload
	 * 
	 * TODO: allow filetype specification (only images for now)
	 * 
	 * @param string $fieldName
	 * @param array $options
	 * @return string form inputs and divs
	 */
	public function input($fieldName, $options=array()) {

		$out = '';
		
		$this->init();
		
		if (!isset($options['div'])) {
			$options['div'] = array();
		}
		
		$div_class = 'input uploader ';	//default class for main div
		if (isset($options['div']['class'])) {
			$div_class .= $options['div']['class'];	//add custom class, if any
		}
		
		//options for uploader are sent in $options['uploader'], see below for all available values
		if (isset($options['uploader']) && is_array($options['uploader'])) {
			$uploader_options = $options['uploader'];
			unset($options['uploader']);
		} else {
			$uploader_options = array();
		}
		
		//set default options for uploader 
		$uploader_options += array(
			'data-token' => uniqid($fieldName . microtime(true)),	//token for temporary upload dir name
			'data-preview' => true,		//do we want preview of the uplaoded images?
			'data-multiple' => false,	//allow uploading of multiple files?
			'data-filetypes' => null,	//allowed file types
		);
		
		$preview = '';

		if (isset($uploader_options['default']) && !empty($uploader_options['default'])) {
			$preview .= $this->Html->div('uploader_preview_default', $uploader_options['default']);
		}

		//include preview div, if required
		if ($uploader_options['data-preview'] == true) {
			$preview .= $this->Html->div('uploader_preview', '');
		}
		
		$label = isset($options['label']) ? '<label>' . $options['label'] . '</label>' : '';
		
		$out .= $this->Html->div(
			$div_class, 
			
			$label . 
			$this->Html->div(	//upload button
				'uploader_btn', 
				'Uploader...', 
				$uploader_options
			) . 
			$preview . //preview div (if any)
			$this->Form->hidden($fieldName.'.files', array('class' => 'uploader_files')) .	//hidden input for uploaded filenames 
			$this->Form->hidden($fieldName.'.token', array('value' => $uploader_options['data-token'])),	//hidden input for token
				
			$options['div']
		) . (isset($options['after']) ? $options['after'] : '');
		
		return $out;	//return all HTML
		
	}
	
}