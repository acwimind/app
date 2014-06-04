<?php

class ChatCache {

	public static $engine = false;

	public static function init($engine, $config=false) {
		if (!class_exists('Cache' . $engine)) {
			throw new Exception('Unknown caching engine '.$engine.'. Create static class Cache'.$engine);
		}
		self::$engine = $engine;
		call_user_func('Cache'.self::$engine.'::init', $config);
	}

	public static function read($key) {
		return call_user_func('Cache'.self::$engine.'::read', $key);
	}

	public static function write($key, $data) {
		return call_user_func('Cache'.self::$engine.'::write', $key, $data);
	}

	public static function delete($key) {
		return call_user_func('Cache'.self::$engine.'::delete', $key);
	}

}

class CacheFile {

	private static $config = false;

	public static function init($config) {
		self::$config = $config;
	}

	public static function read($key) {
		$path = self::_path($key);
		if (!is_file($path)) {
			return false;
		} else {
			$data = file_get_contents($path);
			return json_decode($data);
		}
	}

	public static function write($key, $data) {
		$path = self::_path($key);
		return (bool) file_put_contents($path, json_encode($data));
	}

	public static function delete($key) {
		$path = self::_path($key);
		if (is_file($path)) {
			return unlink($path);
		} else {
			return false;
		}
	}

	private static function _path($key) {
		return self::$config['path'] . self::$config['prefix'] . $key;
	}

}

class CacheApc {

	private static $config = false;

	public static function init($config) {
		self::$config = $config;
	}

	public static function read($key) {
		$data = apc_fetch(self::$config['prefix'] . $key);
		return json_decode($data);
	}

	public static function write($key, $data) {
		return apc_add(self::$config['prefix'] . $key, json_encode($data));
	}

	public static function delete($key) {
		return apc_delete(self::$config['prefix'] . $key);
	}

}