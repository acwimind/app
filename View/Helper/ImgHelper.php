<?php

class ImgHelper extends AppHelper {
	
	public $helpers = array('Html');

	/**
	 * call any of other functions with prefix "thumb_" to get a thumbnail linked to original image
	 * @return html code
	 */
	public function __call($function_name, $arguments) {

		if (preg_match('/thumb_([a-zA-Z_]+)/', $function_name, $match)) {

			$call_function = $match[1];

			$alt = $arguments[0];
			$options = $arguments[1];
			unset($arguments[0]);
			unset($arguments[1]);

			$display_img = call_user_func_array(array($this, $call_function), $arguments);

			if (empty($display_img)) {
				return '';
			}

			if (in_array($call_function, array('event_photo', 'place_photo'))) {
				unset($arguments[6]);
				unset($arguments[7]);
			} else {
				unset($arguments[3]);
				unset($arguments[4]);
			}

			$link_img = call_user_func_array(array($this, $call_function), $arguments);

			$html = $this->Html->link(
				$this->Html->image( $display_img ),
				$link_img,
				array(
					'escape' => false, 
					'class' => 'zoom-image ' . (isset($options['class']) ? $options['class'] : ''), 
					'alt' => $alt
				) + $options
			);

			return $html;

		} else {

			throw new Exception('Call to non-existing function '.$function_name.' on class ImgHelper');

		}



	}
	
	public function profile_picture($big, $updated, $width=0, $height=0, $enlarge=false, $crop=true) {
		
		$path = MEMBERS_UPLOAD_PATH . $big . '.jpg';
		if (!is_file($path)) {
			$path = MEMBERS_UPLOAD_PATH . 'dummy_avatar_profile.jpg';
		}

		$path = $this->get_resized($path, $width, $height, $enlarge, $crop);
		if ($path != false) {
			return preg_replace('/^'.preg_quote(APP.WEBROOT_DIR, '/').'/', '', $path) . '?upd='.strtotime($updated);
		}
		
	}
	
	public function category_picture($id, $width=0, $height=0, $enlarge=false, $crop=true) {
	
		$path = CATEGORIES_UPLOAD_PATH . $id . '.png';
		if (is_file($path)) {
			$path = $this->get_resized($path, $width, $height, $enlarge, $crop);
			if ($path != false) {
				return preg_replace('/^'.preg_quote(APP.WEBROOT_DIR, '/').'/', '', $path);
			}
		}
		return '';
	
	}
	
	public function event_photo($event_big, $gallery_big, $photo_big, $ext, $width=0, $height=0, $enlarge=false, $crop=true) {
		
		$path = EVENTS_UPLOAD_PATH . $event_big . '/' . $gallery_big . '/' . $photo_big . '.' . $ext;
		
		if ($width != 0 || $height != 0) {
			$path = $this->get_resized($path, $width, $height, $enlarge, $crop);
		}

		if ($path != false) {
			return preg_replace('/^'.preg_quote(APP.WEBROOT_DIR, '/').'/', '', $path);
		} else {
			return '';
		}
		
	}
	
	public function place_photo($place_big, $gallery_big, $photo_big, $ext, $width=0, $height=0, $enlarge=false, $crop=true) {
	
		$path = PLACES_UPLOAD_PATH . $place_big . '/' . $gallery_big . '/' . $photo_big . '.' . $ext;
		if ($width != 0 || $height != 0) {
			$path = $this->get_resized($path, $width, $height, $enlarge, $crop);
		}
	
		if ($path != false) {
			return preg_replace('/^'.preg_quote(APP.WEBROOT_DIR, '/').'/', '', $path);
		} else {
			return '';
		}
	
	}
	
	public function place_default_photo($category_id, $width=0, $height=0, $enlarge=false, $crop=true) {
	
		$path = PLACES_DEFAULT_UPLOAD_PATH . $category_id . '.jpg';
		if ($width != 0 || $height != 0) {
			$path = $this->get_resized($path, $width, $height, $enlarge, $crop);
		}
	
		if ($path != false) {
			return preg_replace('/^'.preg_quote(APP.WEBROOT_DIR, '/').'/', '', $path);
		} else {
			return '';
		}
	
	}
	
	public function advert_picture($big, $updated, $extension, $width=0, $height=0, $enlarge=false, $crop=true) {
		
		$path = ADVERTS_UPLOAD_PATH . $big . '.' . $extension;

// Some default picture ???		
//		if (!is_file($path)) {
//			$path = MEMBERS_UPLOAD_PATH . 'dummy_avatar_profile.jpg';
//		}

		$path = $this->get_resized($path, $width, $height, $enlarge, $crop);
		if ($path != false) {
			return preg_replace('/^'.preg_quote(APP.WEBROOT_DIR, '/').'/', '', $path) . '?upd='.strtotime($updated);
		}
		
	}
	
	public function get_resized($path, $width=0, $height=0, $enlarge=false, $crop=true) {
		
		$width = round($width);
		$height = round($height);
		
		if (!is_file($path)) {
			return false;
		}
		
		$pathinfo = pathinfo($path);
		
		//path to the file
		{
			if (strpos($pathinfo['dirname'], IMAGES) !== false)  {
				$dir = str_replace(IMAGES, RESIZED_IMAGES_PATH, $pathinfo['dirname']);
			} else {
				$dir = str_replace(FILES_UPLOAD_PATH, RESIZED_IMAGES_PATH, $pathinfo['dirname']);
			}
			$new_filename = $pathinfo['filename'] . '_' . $width . 'x' . $height . '.' . $pathinfo['extension'];
			$new_path = $dir . '/' . $new_filename;
		}
		
		if (!is_file($new_path) || filemtime($new_path) < filemtime($path)) {	//file doenst exist - resize and create it

			$new_path = $this->_resize($new_path, $path, $width, $height, $enlarge, $crop);

		}	//if (!is_file($new_path))
		
		//web URL to the resized image
		return $new_path;
		
	}
	
	private function _resize($new_path, $path, $width, $height, $enlarge=false, $crop=false) {
		
		$new_pathinfo = pathinfo($new_path);
		if (!is_dir($new_pathinfo['dirname'])) {
			if (!mkdir($new_pathinfo['dirname'], 0777, true)) {
				return false;
			}
		}
		
		if ($crop==false) {
			$original_size = getimagesize($path);
			if ($original_size[1] < $height) {
				$height = 0;
			} elseif ($original_size[0] < $width) {
				$width = 0;
			}
		}
		
		if (!isset($original_size)) {
			$original_size = getimagesize($path);
		}
		
		if (($width == 0 || $original_size[0] == $width) && ($height == 0 || $original_size[1] == $height)) {
			return $path;
		}
			
		switch($original_size['mime']) {
			case 'image/jpeg':
				$create_function = 'imagecreatefromjpeg';
				$save_function = 'imagejpeg';
				$compression_level = 95;
				break;
			case 'image/png':
				$create_function = 'imagecreatefrompng';
				$save_function = 'imagepng';
				$compression_level = 9;
				break;
			case 'image/gif':
				$create_function = 'imagecreatefromgif';
				$save_function = 'imagegif';
				break;
			default:
				return false;
				break;
		}
		
		if ($height > 0 && $width > 0) {
		
			$crop_width = $width < $original_size[0] ? $width : $original_size[0];
			$crop_height = $height < $original_size[1] ? $height : $original_size[1];
		
			$height = $crop_width * ($original_size[1] / $original_size[0]);
			if ($width < $crop_width || $height < $crop_height) {
				$width = $crop_height * ($original_size[0] / $original_size[1]);
				$height = $crop_height;
			}
		
			$cropped_img = imagecreatetruecolor($crop_width, $crop_height);
		
		} elseif ($height == 0) {
		
			$height = $width * ($original_size[1] / $original_size[0]);
		
		} elseif ($width == 0) {
		
			$width = $height * ($original_size[0] / $original_size[1]);
		
		} else {
		
			$width = $original_size[0];
			$height = $original_size[1];
		
		}
			
		$width = round($width);
		$height = round($height);
		
		if (($width > $original_size[0] || $height > $original_size[1]) && !$enlarge) {	//do we enlarge pictures?
			return $path;
		}
		
		$img = $create_function($path);
		$resized_img = imagecreatetruecolor($width, $height);
			
		if ($create_function == 'imagecreatefrompng') {	//PNG transparency
			imagealphablending($img, true);
			imagealphablending($resized_img, false);
			imagesavealpha($resized_img, true);
			if (isset($cropped_img)) {
				imagealphablending($cropped_img, false);
				imagesavealpha($cropped_img, true);
			}
		}
			
		//imagecopyresized($resized_img, $img, 0, 0, 0, 0, $width, $height, $original_size[0], $original_size[1]);
		imagecopyresampled($resized_img, $img, 0, 0, 0, 0, $width, $height, $original_size[0], $original_size[1]);
			
		if (isset($cropped_img)) {
			imagecopyresampled($cropped_img, $resized_img, 0, 0, floor(($width-$crop_width)/2), floor(($height-$crop_height)/2), $crop_width, $crop_height, $crop_width, $crop_height);
			$save_img = $cropped_img;
		} else {
			$save_img = $resized_img;
		}
		
		if (isset($compression_level)) {
			$save_function($save_img, $new_path, $compression_level);
		} else {
			$save_function($save_img, $new_path);
		}
		
		return $new_path;
		
	}
	
}