<?php

/**
 * Handles uploading of files to temporary dir
 * 
 * TODO: cron to remove old temporary files (lock tmp dirs for some time)
 */
class FilesController extends UploaderAppController {
	
	public $components = array('Uploader.Upload');
	private $tmp_path;
	
	public function beforeFilter() {
		
		parent::beforeFilter();
		
		$token = isset($this->params->named['token']) ? $this->params->named['token'] : 'default';	//set token
		$this->tmp_path = $this->Upload->getPathFromToken($token);	//set path based on token, used for upload or file retrieval
		
	}
	
	/**
	 * Upload a file to temporary directory, specified by token (see beforeFilter())
	 * 
	 * @return boolean file upload operation result
	 */
	public function upload() {
		
		$this->header('Content-type: text/plain');	//header - we are outputing JSON data
		
/*
		if ($this->logged == false) {	//only logged in members can upload files (this is for any upload)
			
			$this->set('result', array(
				'success' => false,
				'error' => __('Access denied'),
			));
			return false;
			
		}
*/
		
		$uploaded = isset($_FILES['qqfile']) ? $_FILES['qqfile'] : false;	//uploaded file is in "qqfile"
		
		if ($uploaded==false || $uploaded['size'] == 0) {	//check if we have file (and if it has file size > 0)
			
			$this->set('result', array(
				'success' => false,
				'error' => __('No file uploaded'),
			));
			return false;
			
		}
		
		//sanitize filename (only ascii characters, no spaces, all lowercase)
		$uploaded['name'] = iconv('UTF-8', 'ASCII//TRANSLIT', $uploaded['name']);
		$uploaded['name'] = strtolower( str_replace(' ', '_', $uploaded['name']) );
		
		if (!is_dir($this->tmp_path)) {	//create tmp directory if necessary
			mkdir($this->tmp_path, 0777, true);
		}
		
		//pick a temporary filename, check if it is unique
		do {
			$tmp_filename = uniqid() . '_' . $uploaded['name'];
		} while (is_file($this->tmp_path . $tmp_filename));
		
		//upload
		$uploaded = $this->Upload->directUpload($uploaded, $this->tmp_path . $tmp_filename);
		
		if (!$uploaded) {	//check for upload status
			
			$this->set('result', array(
				'success' => false,
				'error' => __('Internal error, unable to upload file'),
			));
			return false;
			
		}
		
		$this->set('result', array(
			'success' => true,
			'filename' => $tmp_filename,	//return temporary filename
		));

		$this->layout = 'ajax';	//set explicitly (otherwise causes problems in IE)
		
	}
	
	/**
	 * Returns the image from temporary directory
	 * @param string $tmp_filename temporary filename
	 * @return string
	 */
	public function preview($tmp_filename) {
		
		$type = mime_content_type($this->tmp_path . $tmp_filename);
		if (in_array($type, array('image/jpg', 'image/jpeg', 'image/gif', 'image/png'))) {	//if it is image file, return the image
			$this->header('Content-type: ' . $type);
			readfile($this->tmp_path . $tmp_filename);
			exit;
		} else {
			//TODO: if file is not image, return icon based on filetype (image)
			die('icon');
		}
		
	}
	
}