<?php

namespace Redist\files;

require_once 'files.php';
require_once 'filemngr.php';

class file_class implements files {

	static $url;
	static $setup;
	static $temp_array;
	static $redist;
	static $server;

	public static function user_log_dir() {
		// Default Directories and files for configuation in pUrl	//
		return "user_logs/";			//
	}
		
	public static function server_log_dir() {
		// Default Directories and files for configuation in pUrl	//
		return "server_logs/";			//
	}
	
	// duplicate of save_user_log
	public static function update_user() {
		self::save_user_log($_SERVER['REMOTE_ADDR']);
	}

	// duplicate of save_user_log
	public static function save_user_log() {
		$hash = hash("sha256", utf8_encode($_SERVER['REMOTE_ADDR']));
		$str_dir = self::user_log_dir();
		echo self::user_log_dir().$hash;
		self::$temp_array = \Redist\url\purl::$request;
		self::$redist = array(self::get_user_log());
		if (self::$redist == false) {
			file_put_contents(self::user_log_dir().$hash, json_encode(\Redist\url\purl::$request));
		}

		else {
			self::$temp_array = array_merge_recursive(self::$temp_array,self::$redist);
			file_put_contents(self::user_log_dir().$hash, json_encode(self::$temp_array));
		}
	}

	// For curl operations
	public static function set_content_type($type) {
		return self::$content_type = $type;
    }
    
	//save $this
	public static function save_server_log($filename = "server.conf") {

		file_put_contents(self::server_log_dir().$filename, json_encode(self));
	}

	// load everything
	public static function get_server_log($filename = "server.conf") {
		$fp = "";
		if (!file_exists(self::server_log_dir().$filename))
			return false;
		$dim = file_get_contents(self::user_log_dir().$filename);
		$decoded = json_decode($dim);
		foreach ($decoded as $k=>$v)
			self::$server->k = $v;
	}

	// load users in queue
	public static function get_user_queue($filename = "users.conf") {
		$fp = "";
		if (!file_exists($filename))
			return false;
		$dim = file_get_contents($filename);
		$users = json_decode($dim);
		$files = scandir(self::user_log_dir());
		if (sizeof((array)$users) > 0)
			self::$users = array_intersect($users, (array)$files);
	}

	// you'll find that in this file, we look
	// for SESSID a lot. It's called ['session']
	// to our script. It should be sent with the
	// incoming request.
	public static function get_user_log() {
		$hash = hash("sha256", utf8_encode($_SERVER['REMOTE_ADDR']));
		echo $hash;
		$dim = [];
		if (file_exists(self::user_log_dir().$hash)) {
			$dim = file_get_contents(self::user_log_dir().$hash);
			echo $dim;
		}
		else
			return false;
		return json_decode($dim);
	}


}