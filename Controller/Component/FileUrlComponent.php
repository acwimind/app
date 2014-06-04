<?php

/**
 * Return URLs to various types of files (profile pictures, event photos, etc)
 */
class FileUrlComponent extends Component {
	
	public function profile_picture($member_big, $updated=null) {
		$exts = array('jpg', 'jpeg', 'png');
		$usedExt = '';
		foreach ($exts as $ext)
		{
			$path = MEMBERS_UPLOAD_PATH . $member_big . '.' . $ext;
			if (is_file($path))
			{
				$usedExt = $ext;
				break;
			}
		}
		if (is_file($path)) {
			return $this->_url('members', $member_big, $usedExt) . '/' . ($updated==null ? '0' : strtotime($updated));
		} else {
			return null;
		}
	}
	
	public function event_photo($event_big, $gallery_big, $photo_big, $ext) {
		$path = EVENTS_UPLOAD_PATH . $event_big . '/' . $gallery_big . '/' . $photo_big . '.' . $ext;
		if ($path != false) {
			return $this->_url('events', $event_big, $gallery_big, $photo_big, $ext);
		} else {
			return null;
		}
	}
	
	public function place_photo($place_big, $gallery_big, $photo_big, $ext) {
		$path = PLACES_UPLOAD_PATH . $place_big . '/' . $gallery_big . '/' . $photo_big . '.' . $ext;
		if ($path != false) {
			return $this->_url('places', $place_big, $gallery_big, $photo_big, $ext);
		} else {
			return null;
		}
	}
	
	public function category_picture($category_id, $updated=null) {
		$path = CATEGORIES_UPLOAD_PATH . $category_id . '.png';
		if (is_file($path)) {
			return $this->_url('categories', $category_id, 'png') . '/' . ($updated==null ? '0' : strtotime($updated));
		} else {
			return null;
		}
	}
	
	public function default_place_photo($category_id) {
		//NOTE: when changing default place image, map its name here (must be integer only because of mod_rewrite)
		$images = array(
			//1 => '100'	//example
		);
		if (isset($images[ $category_id ])) {
			$category_id = $images[ $category_id ];
		}
		return $this->_url('places_default', $category_id, 'jpg');
	}
	
	private function _url() {
		$args = func_get_args();
		return 'http://' . $_SERVER['HTTP_HOST'] . '/api/files/' . implode('/', $args);
	}
	
}