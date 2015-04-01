<?php
/**
 * This file is loaded automatically by the app/webroot/index.php file after core.php
 *
 * This file should load/create any application wide configuration settings, such as
 * Caching, Logging, loading additional configuration files.
 *
 * You should also use this file to include any files that provide global functions/constants
 * that your application uses.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2012, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Config
 * @since         CakePHP(tm) v 0.10.8.2117
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

class ErrorEx extends Exception {
	public $msg = '';
	public $user_msg = false;
	public $log = false;
	public $data = array();
}

//project available under company Gooogle account "swdevx@gmail.com"
define('GOOGLE_API_KEY', 'AIzaSyAgAyCvSVM524R7KIcIW-zk-SM7zBxLIsM');	//Key for server apps (with IP locking) 
define('GOOGLE_API_KEY_BROWSER', 'AIzaSyBWiMLeIZsBi9nlSqXI26ds76Cd3BrR5Hk');	//Key for browser apps (with referers) 

define('FACEBOOK_APP_ID', '459649744106962');
define('FACEBOOK_APP_SECRET', '20e01386f9b8b0a95c5410763f30be52');
define('FACEBOOK_REDIRECT_URL', 'http://'.$_SERVER['HTTP_HOST'].'/members/login_fb');

define('INACTIVE', 0);
define('ACTIVE', 1);
define('DELETED', 255);

define('MEMBER_MEMBER', 1);
define('MEMBER_OPERATOR', 2);
define('MEMBER_ADMIN', 3);
define('MEMBER_VIP', 4);  

define('LANG_NONE', 0);
define('LANG_EN', 1);
define('LANG_IT', 2);

define('SIGNAL_PHOTO', 2);	//2 value = value of gem type "Photo" (it must be the same!) ex 5
define('SIGNAL_CHAT', 1);	//1 value = value of gem type "Chat" (it must be the same!)
define('SIGNAL_COMMENT',3); 	//3 value = value of gem type "Comment" (it must be the same!)

define('RESIZED_IMAGES', '/resized_images/');
define('RESIZED_IMAGES_PATH', WWW_ROOT . 'resized_images' . DS);

define('FILES_UPLOAD', '/files/');
define('FILES_UPLOAD_PATH', WWW_ROOT . 'files' . DS);

define('MEMBERS_UPLOAD', FILES_UPLOAD . 'members/');
define('MEMBERS_UPLOAD_PATH',  FILES_UPLOAD_PATH . 'members' . DS);

define('CATEGORIES_UPLOAD', FILES_UPLOAD . 'categories/');
define('CATEGORIES_UPLOAD_PATH',  FILES_UPLOAD_PATH . 'categories' . DS);

define('EVENTS_UPLOAD', FILES_UPLOAD . 'events/');
define('EVENTS_UPLOAD_PATH',  FILES_UPLOAD_PATH . 'events' . DS);

define('PLACES_UPLOAD', FILES_UPLOAD . 'places/');
define('PLACES_UPLOAD_PATH',  FILES_UPLOAD_PATH . 'places' . DS);

define('PLACES_DEFAULT_UPLOAD', '/img/places_default/');
define('PLACES_DEFAULT_UPLOAD_PATH',  IMAGES . 'places_default' . DS);

define('ADVERTS_UPLOAD', FILES_UPLOAD . 'adverts/');
define('ADVERTS_UPLOAD_PATH',  FILES_UPLOAD_PATH . 'adverts' . DS);

define('CHATS_UPLOAD', FILES_UPLOAD . 'chats/');
define('CHATS_UPLOAD_PATH',  FILES_UPLOAD_PATH . 'chats' . DS);

define('CMS_SECTION_NEWS', 0);
define('CMS_SECTION_STATIC', 1);

define('GALLERY_TYPE_DEFAULT', 1);
define('GALLERY_TYPE_USERS', 2);

define('EVENT_TYPE_NORMAL', 1);
define('EVENT_TYPE_DEFAULT', 2);

define('ADMIN', 'admin');

// ----- UTENTE HAAMBLE --------
define('ID_HAAMBLE_USER',90644);
//------------------------------
define('API', 'api');
define('API_LOG_MEMBERS', '');	//coma separated list members to log all their activity via API
define('API_TOKEN_VALID', strtotime('+30 days'));	//validity of API token, prolonged on every API call

define('API_PER_PAGE', 30); // Per page count for lazy loaded lists
define('API_RETAIN_CHECK_IN_FUNC', 7); // How many days a member can use functionality (upload photo, rate) after being checked out.
define('API_CHAT_PER_PAGE', 20); // Per page count for chat lazy loaded list

define('CHECKIN_RADIUS', 0.6); // Geo-fence radius in miles used for checkin.
define('NEARBY_RADIUS', 6); // Geo-fence radius in miles used for search for nearby places.
define('API_MAP_LIMIT', 50); // Limit of places displayed on map for mobile API

define('AUTOCHECKOUT_PERIOD', 5); // How often should the app send coordinates of checked in member. In minutes
define('AUTOCHECKOUT_LIMIT', 30); // When user is outside checkin radius autocheckout will be done after this time has expired. In minutes

define('ONLINE_TIMEOUT', 1); // How long since last web or mobile activity user is considered online. In hours

// API user chat statuses
define('USER_OFFLINE', 0);
define('USER_ONLINE', 1);
define('USER_ONSITE', 2);

// Reason for flaging a chat or photo
define('FLAG_SPAM', 0);
define('FLAG_INAPPROPRIATE', 1);
define('FLAG_OFFENSIVE', 2);

define('FLAG_COOLDOWN', 5); // Signalation cooldown time in days 
define('FLAG_MSG_LIMIT', 30); // Determines how many messages will be sent to admins when signaling a conversation
define('FLAG_CHAR_LIMIT', 1000); // Determines the maximum sum of charaters sent to admins when signaling a conversation
define('FLAG_MAIL_TO', 'pippo@l.com'); // Where all signalations should be sent

// Push token platforms
define('PUSH_ANDROID', 1);
define('PUSH_IOS', 2);
define('PUSH_WINDOWS', 3);

// App Version
define('ANDROID_APP_VERSION','1.1');
define('IOS_APP_VERSION','2.1');
define('WPHONE_APP_VERSION','1');

// Type of home screen
define('HOME_CHECKED_IN', 1);
define('HOME_MATURE_USER', 2);
define('HOME_FIRST_LOGIN', 3);

define('HOME_LIST_LIMIT', 6); // Limit of last visited places/events and bookmarks listed on home page. Add +1 for blue box

// Status for chat messages
define('CHAT_JOINED', 0); // Chatting user has joined event/place
define('CHAT_CHECKED_IN', 1); // Chatting user is checked in on event/place
define('CHAT_NO_JOIN', 2); // Chatting user is not joined or checked in

// Frontend pagination
define('FRONTEND_PER_PAGE', 10); // The number of places/events displayed in the list
define('MAP_MAXIMUM_RESULTS', 100);
define('LIMIT_QUERY_CONTENT',10);
define('RATING_MAXIMUM', 5);

// Affinity Members
define('MIN_AFFINITY_MEMBERS',10); // The min number of affinity members displayed in the list

// SMS Limit
define('MAXSMSLIMIT',100);

// SOGLIA CHAT NOTIFICATION
define('SOGLIA_CHAT_NOTIFICATION',50);

// MailChimp config
define('MAILCHIMP_HAAMBLE_LIST_ID','8b7eef3ed5');
define('MAILCHIMP_API_KEY','b654ad1ba15c0d0df912c2258c9f2623-us9');

// Visibility Product
define('ID_VISIBILITY_PRODUCTS','1,2,3,4,5'); // ID prodotti sulla visibilità
define('ID_RADAR_VISIBILITY_PRODUCTS','1,2,3,4,5'); // ID prodotti sulla visibilità

class Defines {
	
	public static $languages;
	
	public static $member_types;
	
	public static $statuses;
	
	static public $gems = array(
		1	=> 'Member',
		2	=> 'Place',
		3	=> 'Event',
		4	=> 'Gallery',
		5	=> 'Photo',
		6	=> 'Checkin',
       
	);
	
	static public $external_sources;
	
	static public $countries;
	
	public static $signalations;
	
	public static $cms_sections;
	
	public static $flag_types;
	
	public static $distance;
	
	public static $order;
	
	public static $ratings;
	
}

Defines::$languages = array(
	LANG_NONE => __('None'),
	LANG_EN => __('English'),
	LANG_IT => __('Italian'),
);

Defines::$member_types = array(
	MEMBER_MEMBER => __('Member'),
	MEMBER_OPERATOR => __('Operator'),
	MEMBER_ADMIN => __('Administrator'),
);

Defines::$statuses = array(
	INACTIVE => __('Inactive'),
	ACTIVE => __('Active'),
	DELETED => __('Deleted'),
);

Defines::$external_sources = array(
	'google' => 'Google Places API',
	//'foursquare' => 'Foursquare API',
);

Defines::$countries = array(
	'IT' => __('Italy'),
);

Defines::$signalations = array(
	SIGNAL_CHAT => __('Chat'),
    SIGNAL_PHOTO => __('Photo'),
    SIGNAL_COMMENT => __('Comment'),
);

Defines::$cms_sections = array(
	CMS_SECTION_NEWS => __('News'),
	CMS_SECTION_STATIC => __('Static'),
);

Defines::$flag_types = array(
	FLAG_SPAM => __('This is a spam'),
	FLAG_INAPPROPRIATE => __('This is inappropriate content'),
	FLAG_OFFENSIVE => __('I am feeling offended by this content'),
);

Defines::$distance = array(
	'1' => '<1 km',
	'2' => '<2 km',
	'5' => '<5 km',
	'10' => '<10 km',
	'20' => '<20 km',
);

Defines::$order = array(
	'name' => 'Name',
	'rating' => 'Rating',
//	'distance' => 'Distance',
	'relevance' => 'Relevance',
);

Defines::$ratings = array(
	'1' => '1+',
	'1.5' => '1.5+',
	'2' => '2+',
	'2.5' => '2.5+',
	'3' => '3+',
	'3.5' => '3.5+',
	'4' => '4+',
	'4.5' => '4.5+',
	'5' => '5',
);

/**
 * Cache Engine Configuration
 * Default settings provided below
 *
 * File storage engine.
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'File', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'path' => CACHE, //[optional] use system tmp directory - remember to use absolute path
 * 		'prefix' => 'cake_', //[optional]  prefix every cache file with this string
 * 		'lock' => false, //[optional]  use file locking
 * 		'serialize' => true, // [optional]
 * 		'mask' => 0666, // [optional] permission mask to use when creating cache files
 *	));
 *
 * APC (http://pecl.php.net/package/APC)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Apc', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *	));
 *
 * Xcache (http://xcache.lighttpd.net/)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Xcache', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 *		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional] prefix every cache file with this string
 *		'user' => 'user', //user from xcache.admin.user settings
 *		'password' => 'password', //plaintext password (xcache.admin.pass)
 *	));
 *
 * Memcache (http://memcached.org/)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Memcache', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 * 		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 * 		'servers' => array(
 * 			'127.0.0.1:11211' // localhost, default port 11211
 * 		), //[optional]
 * 		'persistent' => true, // [optional] set this to false for non-persistent connections
 * 		'compress' => false, // [optional] compress data in Memcache (slower, but uses less memory)
 *	));
 *
 *  Wincache (http://php.net/wincache)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Wincache', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 *		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *	));
 *
 * Redis (http://http://redis.io/)
 *
 * 	 Cache::config('default', array(
 *		'engine' => 'Redis', //[required]
 *		'duration'=> 3600, //[optional]
 *		'probability'=> 100, //[optional]
 *		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 *		'server' => '127.0.0.1' // localhost
 *		'port' => 6379 // default port 6379
 *		'timeout' => 0 // timeout in seconds, 0 = unlimited
 *		'persistent' => true, // [optional] set this to false for non-persistent connections
 *	));
 */

if (function_exists('apc_fetch')) {
	
	Cache::config('default', array(
		'engine' => 'Apc',
		'duration'=> 3600,
		'probability'=> 100,
		'prefix' => 'haamble_',
	));

} else {
	
	Cache::config('default', array(
		'engine' => 'File',
		'duration'=> 3600,
		'probability'=> 100,
			'path' => CACHE,
			'prefix' => 'haamble_',
			'lock' => false,
			'serialize' => true,
			'mask' => 0666,
	));

}



/**
 * The settings below can be used to set additional paths to models, views and controllers.
 *
 * App::build(array(
 *     'Model'                     => array('/path/to/models', '/next/path/to/models'),
 *     'Model/Behavior'            => array('/path/to/behaviors', '/next/path/to/behaviors'),
 *     'Model/Datasource'          => array('/path/to/datasources', '/next/path/to/datasources'),
 *     'Model/Datasource/Database' => array('/path/to/databases', '/next/path/to/database'),
 *     'Model/Datasource/Session'  => array('/path/to/sessions', '/next/path/to/sessions'),
 *     'Controller'                => array('/path/to/controllers', '/next/path/to/controllers'),
 *     'Controller/Component'      => array('/path/to/components', '/next/path/to/components'),
 *     'Controller/Component/Auth' => array('/path/to/auths', '/next/path/to/auths'),
 *     'Controller/Component/Acl'  => array('/path/to/acls', '/next/path/to/acls'),
 *     'View'                      => array('/path/to/views', '/next/path/to/views'),
 *     'View/Helper'               => array('/path/to/helpers', '/next/path/to/helpers'),
 *     'Console'                   => array('/path/to/consoles', '/next/path/to/consoles'),
 *     'Console/Command'           => array('/path/to/commands', '/next/path/to/commands'),
 *     'Console/Command/Task'      => array('/path/to/tasks', '/next/path/to/tasks'),
 *     'Lib'                       => array('/path/to/libs', '/next/path/to/libs'),
 *     'Locale'                    => array('/path/to/locales', '/next/path/to/locales'),
 *     'Vendor'                    => array('/path/to/vendors', '/next/path/to/vendors'),
 *     'Plugin'                    => array('/path/to/plugins', '/next/path/to/plugins'),
 * ));
 *
 */

/**
 * Custom Inflector rules, can be set to correctly pluralize or singularize table, model, controller names or whatever other
 * string is passed to the inflection functions
 *
 * Inflector::rules('singular', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 * Inflector::rules('plural', array('rules' => array(), 'irregular' => array(), 'uninflected' => array()));
 *
 */

/**
 * Plugins need to be loaded manually, you can either load them one by one or all of them in a single call
 * Uncomment one of the lines below, as you need. make sure you read the documentation on CakePlugin to use more
 * advanced ways of loading plugins
 *
 * CakePlugin::loadAll(); // Loads all plugins at once
 * CakePlugin::load('DebugKit'); //Loads a single plugin named DebugKit
 *
 */
CakePlugin::load('DebugKit');
CakePlugin::load('Uploader');
//CakePlugin::load('Mandrill');

/**
 * You can attach event listeners to the request lifecyle as Dispatcher Filter . By Default CakePHP bundles two filters:
 *
 * - AssetDispatcher filter will serve your asset files (css, images, js, etc) from your themes and plugins
 * - CacheDispatcher filter will read the Cache.check configure variable and try to serve cached content generated from controllers
 *
 * Feel free to remove or add filters as you see fit for your application. A few examples:
 *
 * Configure::write('Dispatcher.filters', array(
 *		'MyCacheFilter', //  will use MyCacheFilter class from the Routing/Filter package in your app.
 *		'MyPlugin.MyFilter', // will use MyFilter class from the Routing/Filter package in MyPlugin plugin.
 * 		array('callable' => $aFunction, 'on' => 'before', 'priority' => 9), // A valid PHP callback type to be called on beforeDispatch
 *		array('callable' => $anotherMethod, 'on' => 'after'), // A valid PHP callback type to be called on afterDispatch
 *
 * ));
 */
Configure::write('Dispatcher.filters', array(
	'AssetDispatcher',
	'CacheDispatcher'
));

/**
 * Configures default file logging options
 */
App::uses('CakeLog', 'Log');
CakeLog::config('debug', array(
	'engine' => 'FileLog',
	'types' => array('notice', 'info', 'debug'),
	'file' => 'debug',
));
CakeLog::config('error', array(
	'engine' => 'FileLog',
	'types' => array('warning', 'error', 'critical', 'alert', 'emergency'),
	'file' => 'error',
));

// Log for push notifications
CakeLog::config('notifs', array(
    'engine' => 'FileLog',
    'types' => array('info', 'error', 'warning', 'debug'),
    'scopes' => array('notifs'),
	'file' => 'notifs'
));

// Log for cron jobs
CakeLog::config('cronjobs', array(
    'engine' => 'FileLog',
    'types' => array('info', 'error', 'warning', 'debug'),
    'scopes' => array('cronjobs'),
	'file' => 'cronjobs'
));

/**
 * Remove all accociated model except listed ones
 * Shorthand to unbinding many models at once
 * @param class $model model na ktorom vykoname unbindModel()
 * @param array $keep_associations pole s nazvami modelov ktore nechame pripojene
 */
function unbindAllBut($model, $keep_associations=array(), $reset = true) {
	$all_associations = array(
		'belongsTo'				=> $model->belongsTo,
		'hasMany'				=> $model->hasMany,
		'hasAndBelongsToMany'	=> $model->hasAndBelongsToMany,
		'hasOne'				=> $model->hasOne,
	);
	foreach($all_associations as $assoc_name => $assoc_models) {
		foreach($assoc_models as $model_name => $model_data) {
			if (!in_array($model_name, $keep_associations)) {
				$model->unbindModel(array($assoc_name => array($model_name)), $reset);
			}
		}
	}
}

function pad($number) {
	if (strlen($number) != 2) {
		return '0' . $number;
	} else {
		return $number;
	}
}

/**
 * Convert array of date and time to timestamp
 * @param array $input
 * @return string Y-m-d H:i
 */
function convert_to_timestamp($input) {

	if (!isset($input['time'])) {
		$input['time'] = '00:00';
	}
	
	if (!isset($input['date'])) {
		$input['date'] = '';
	} else {
		$input['date'] .= ' ';
	}

	/*
	$date_tmp = explode('.', $input['date']);
	$time_tmp = explode(':', $input['time']);

	$datetime = $date_tmp[2].'-'.pad($date_tmp[1]).'-'.pad($date_tmp[0]) . ' ' . pad($time_tmp[0]).':'.pad($time_tmp[1]);
	*/
	
	$datetime = $input['date'] . $input['time'];
	
	return $datetime;

}

/**
 * Convert array of date and time to timestamp
 * @param array $input
 * @return string Y-m-d H:i
 */
function ctt($input) {
	return convert_to_timestamp($input);
}

function convert_to_date_array($input)
{
	$dt = trim($input);
	if (empty($dt))
		return null;
		
	$dateTime['date'] = substr($dt, 0, strpos($dt, ' '));
	$dateTime['time'] = substr($dt, strpos($dt, ' '));
	
	return $dateTime;
}


/**
 * Remove directory recursively
 * @param string path to directory
 * @return bool 
 */
function rmdir_r($dir) {

	if (substr($dir, -1) != '/') {
		$dir .= '/';
	}

	$mydir = opendir($dir);
	while(false !== ($file = readdir($mydir))) {
		if($file != "." && $file != "..") {

			chmod($dir . $file, 0777);
			if(is_dir($dir . $file)) {
				chdir('.');
				rmdir_r($dir . $file . '/');
				if (!@rmdir($dir . $file)) {
					return false;
				}
			} elseif(!unlink($dir . $file)) {
				return false;
			}
		}
	}

	closedir($mydir);
	rmdir($dir);

	return true;

}