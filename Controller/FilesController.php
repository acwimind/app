<?php


class FilesController extends AppController {
	
	/**
	 * return event photo
	 * @param number $event_big
	 * @param number $gallery_big
	 * @param number $photo_big
	 * @param string $ext photo extension
	 * @param string $size definition of photo resize
	 * @return boolean
	 */
	public function api_events($event_big=0, $gallery_big=0, $photo_big=0, $ext='jpg', $size=false) {
		$path = EVENTS_UPLOAD_PATH . $event_big . DS . $gallery_big . DS . $photo_big . '.' . $ext;
		return $this->_return($path, $size);
	}
	
	/**
	 * return place photo
	 * @param number $place_big
	 * @param number $gallery_big
	 * @param number $photo_big
	 * @param string $ext photo extension
	 * @param string $size definition of photo resize
	 * @return boolean
	 */
	public function api_places($place_big=0, $gallery_big=0, $photo_big=0, $ext='jpg', $size=false) {
		$path = PLACES_UPLOAD_PATH . $place_big . DS . $gallery_big . DS . $photo_big . '.' . $ext;
		return $this->_return($path, $size);
	}
	
	/**
	 * return default photo for place in a category
	 * @param number $category_id
	 * @param string $ext photo extension
	 * @param string $size definition of photo resize
	 * @return boolean
	 */
	public function api_places_default($category_id=0, $ext='jpg', $size=false) {
		$path = PLACES_DEFAULT_UPLOAD_PATH . $category_id . '.' . $ext;
		return $this->_return($path, $size);
	}
	
	/**
	 * return user profile photo
	 * @param number $member_big
	 * @param string $ext photo extension (not used, always use default - jpg)
	 * @param number $updated (any string, used for caching in mobile app)
	 * @param string $size definition of photo resize
	 * @return boolean
	 */
	public function api_members($member_big=0, $ext='jpg', $updated=null, $size=false) {
		$path = MEMBERS_UPLOAD_PATH . $member_big . '.jpg';
		return $this->_return($path, $size);
	}
	
	/**
	 * return user category photo
	 * @param number $category_id
	 * @param string $ext photo extension (not used, always use default - jpg)
	 * @param number $updated (any string, used for caching in mobile app)
	 * @param string $size definition of photo resize
	 * @return boolean
	 */
	public function api_categories($category_id=0, $ext='jpg', $updated=null, $size=false) {
		$path = CATEGORIES_UPLOAD_PATH . $category_id . '.png';
		return $this->_return($path, $size);
	}
	
	/**
	 * return any file based on full path and (optional) resize
	 * @param string $path full path to file
	 * @param string $size definition of photo resize
	 * @return boolean
	 */
	private function _return($path, $size=false) {
		
		if (is_file($path)) {
			
			if ($size !== false) {
				
				if (mb_strpos($size, 'x') > 0) {
					list($width, $height) = explode('x', $size);
				} elseif($size = intval($size)) {
					$width = $size;
					$height = 0;
				} else {
					$width = 50;
					$height = 0;
				}
				
				$view = new View($this);
				$helpers = array(
					//'html'	=> $view->loadHelper('Html'),
					'img'	=> $view->loadHelper('Img'),
				);
				$path = $helpers['img']->get_resized($path, $width, $height);
				
			}
			
			$extension = pathinfo($path, PATHINFO_EXTENSION);
		
			switch ($extension) {	//send header for the image
				case 'jpg':
				case 'jpeg':
					header('Content-type:image/jpg');
					break;
				case 'gif':
					header('Content-type:image/gif');
					break;
				default:
				case 'png':
					header('Content-type:image/png');
					break;
			}
			
			readfile($path);
			die();
				
		} else {
			return $this->_apiEr(__('File does not exist'), true);
		}
		
	}
	
}