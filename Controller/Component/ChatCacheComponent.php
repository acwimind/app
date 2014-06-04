<?php

include_once(WWW_ROOT . '/chat/lib/cache.php');

/**
 * Chat cache implementation for CakePHP
 * Use to create / update / delete cache data used in chat
 */
class ChatCacheComponent extends Component {
	
	public function __construct() {
		
		$config = array(
			'path' => TMP . 'cache/persistent/',
			'prefix' => 'haamble_chat_',
		);

		return ChatCache::init('File', $config);

	}

	public static function read($key) {
		return ChatCache::read($key);
	}

	public static function write($key, $data) {
		return ChatCache::write($key, $data);
	}

	public static function append($key, $data) {
		return ChatCache::write($key, ChatCache::read($key) . $data);
	}

	public static function delete($key) {
		return ChatCache::delete($key);
	}
	
}