<?php

namespace Redist\url;

include 'puser.php';
include 'static_url.php';

class pURL implements pUser {

	static $ch;
	static $user;
	static $users;
	// Required in REQUEST	//
	static $server;		//
	static $fields;
	// Required in REQUEST	//
	static $session;	//
	static $handles;
	// DO NOT PUT IN REQUEST
	static $refer_by;	//
	static $relative;	//
	static $from_addr;	//
	// DO NOT PUT IN REQUEST//
	static $path_user;
	static $path_server;
	static $opt_ssl;
	static $page_contents;
	static $percent_diff;
	// Set for MAX delay in microseconds
	static $delay;
	// Set for MAX of history length of users
	static $max_history;
	static $content_type;
	static $timer;
	static $request;
	static $files;
	static $setup;
	static $method;

	public static function create() {
		
		self::$files = new \Redist\files\filemngr();
		self::$setup = new \Redist\setup\pConfig();
		self::$request = array();
		self::$request['cookie_sheet'] = array();
		if (self::$files->get_user_log() != false) {
			$request = self::$files->get_user_log();
		}
		echo "$ ";
		self::$request['cookie_sheet']['session'] = $_SERVER['REMOTE_ADDR'];

	// Get query string in either GET or POST
		if (isset($_SERVER['HTTP_REFERER']))
			self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['data'] = ($_SERVER['REQUEST_METHOD'] == "GET") ? ($_GET) : ($_POST);
		else {
			self::$request['cookie_sheet']['direct']['data'] = ($_SERVER['REQUEST_METHOD'] == "GET") ? ($_GET) : ($_POST);
			if (isset(self::$request['cookie_sheet']['direct']['self']) && is_int(self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['self']))
				self::$request['cookie_sheet']['direct']['self'] += 1;
			else
				self::$request['cookie_sheet']['direct']['self'] = 1;
		}
	// Get incoming address for relations to other IP class visitors
		self::$request['cookie_sheet']['user_addr'] = $_SERVER['REMOTE_ADDR'];
	// Let's setup the Cookie Sheets so each address is accommodated for
		if (isset($_COOKIE) && count($_COOKIE) > 0 // The target_pg, below, is for the redirtect
			&& isset($_SERVER['HTTP_REFERER'])
			&& isset(self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['target_pg'])
			&& isset($_COOKIE))
			self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['cookies'] = $_COOKIE;
		else if (isset($_SERVER['HTTP_REFERER']) && !isset(self::$request->cookie_sheet->$_SERVER['HTTP_REFERER']->target_pg))
			self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['self'] = 'localhost';
	// There are a couple things we use in pUrl to look at our users //
		if (isset($_SERVER['HTTP_REFERER']))
			self::$request['cookie_sheet']['refer_by'][] = $_SERVER['HTTP_REFERER'];
		self::$request['cookie_sheet']['relative'] = [];	// 
		if (isset($_SERVER['HTTP_REFERER']))
			self::add_referer();
	// This is for listing all users in the queue
		self::$users = [];
		echo "$ ";
	// Default is to turn off HTTPS:// but the program figures it out itself
	// for the most part, but if you do run into trouble, just run this static function
		self::option_ssl(false);
	// Percent of equal critical data points before return in self::$users
	// Change at any time
		self::$percent_diff = 0.75;
	// microsecond delay in wave static function
		self::$delay = 1175;
		self::$max_history = 10;
		self::$timer = time();
		self::$content_type = 'application/x-www-form-urlencoded';
	}

	public static function trace($var) {
		echo '<pre>';
		print_r($var);
	}

	// input the query string
	public static function get_servers() {
		if (!isset(self::$request['cookie_sheet']['server']))
			return null;
		self::$servers = self::$request['cookie_sheet']['server'];
		return self::$request['cookie_sheet']['server'];
	}

	// input the query string
	public static function get_sessions() {
		
		if (!isset(self::$request['cookie_sheet']['session']))
			return null;
		return self::$request['cookie_sheet']['session'];
	}

	// return the number of users present
	// and committed to sending info of.
	public static function user_count() {
		if (is_array(self::$users))
			return sizeof(self::$users);
		self::$users = [];
		return 0;
	}

	// make sure there was a request
	public static function validate_request() {
		
		if (self::$request != null && sizeof(self::$request) != 1)
			return true;
		return false;
	}

	public static function send_request() {
		if (self::$files->find_user_queue(self::$users[0]) == false)
			return false;
		$req = [];
		self::$files->get_user_log(self::$users[0]);
		$options = array(
		  'http' => array(
			'header'  => array("Content-type: self::content_type"),
		        $_SERVER['HTTP_REFERER']  => 'POST',
		        'content' => http_build_query((array)self::$user)
		        )
		);
		array_shift(self::$users);
		
		file_put_contents(self::$setup->path_server . "/users.conf", json_encode(self::$users));
		$context = stream_context_create($options);
		$url = self::opt_ssl . self::$user->cookie_sheet->server;
		self::$page_contents = file_get_contents($url, false, $context);
		return true;
	}

	public static function update_queue() {
		
		self::$files->save_user_log(self::$request['cookie_sheet']['session']);
		file_put_contents(self::$setup->path_server . "/users.conf", json_encode(self::$users));
	}

	public static function disassemble_IP($host) {
		
		if ($host == "::1")
			return;
		preg_match("/.\//", $trim, $output);
		if (is_array($output))
			echo json_encode($output);
		if ($output == null)
			return;
		$ipv4 = gethostbyname($output);
		preg_match_all("/(\d{1,3}|\.{0})/", $ipv4, $ip_pieces);
		$ip_pieces = $ip_pieces[0];
		self::$request['cookie_sheet']['from_addr'] = [];
		self::$request['cookie_sheet']['from_addr']['A'] = $ip_pieces[0];
		self::$request['cookie_sheet']['from_addr']['B'] = $ip_pieces[1];
		self::$request['cookie_sheet']['from_addr']['C'] = $ip_pieces[2];
		self::$request['cookie_sheet']['from_addr']['D'] = $ip_pieces[3];
		self::make_relationships();
	}

	public static function make_relationships() {
		
		$new_relations = [];
		foreach (self::$users->cookie_sheet as $k => $v1) {
			if ($v1 != "from_addr" || $v1->cookie_sheet->session == self::$request['cookie_sheet']['session'])
				continue;
			if (self::$request['cookie_sheet']['from_addr']['A'] == $v1->cookie_sheet->A && self::$request['cookie_sheet']['from_addr']['B'] == $v1->cookie_sheet->B &&
				self::$request['cookie_sheet']['from_addr']['C'] == $v1->cookie_sheet->C)
				$new_relations[] = $v->cookie_sheet->session;
		}
		$unique = array_unique($new_relations);
		self::$request['cookie_sheet']['relative'] = $new_relations;
	}

	public static function add_referer () {
		
		if (isset(self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['target_pg'])
			&& isset(self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['cookies'][self::$request[$_SERVER['HTTP_REFERER']]])
			&& isset($_SERVER['HTTP_REFERER']))
			self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['cookies'][self::$request[$_SERVER['HTTP_REFERER']]] = $_SERVER['HTTP_REFERER'];
		else if (isset(self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['cookies']['self']) && is_int(self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['cookies']['self']))
			self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['cookies']['self'] += 1;
		else
			self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['cookies']['self'] = 1;
		self::remove_referer();
		return true;
	}

	public static function remove_referer() {
		
		if (isset(self::$request['cookie_sheet']['refer_by']) && sizeof(self::$request['cookie_sheet']['refer_by']) == self::$max_history)
			array_shift(self::$request['cookie_sheet']['refer_by']);
		else
			return 0;
		return sizeof(self::$request['cookie_sheet']['refer_by']);
	}

	//***
	public static function relative_count() {
		if (self::$users == null)
			self::$users = [];
		foreach (self::$users->cookie_sheet as $key => $val) {
			$x = self::$return_relatives($val);
			if ($x > 50) {
				self::$delay_connection();
				return true;
			}
		}
		return false;
	}

	// This is the only call you need
	// ***
	public static function parse_call() {
		
		self::spoof_check();
		if (count(self::$request) == 4)
			exit();
		if (!self::match_server()) {
			echo "Fatal Error: Your address is unknown";
			exit();
		}
		else if (!self::match_server()) {
			echo "Fatal Error: Target address unknown";
			exit();
		}
		
		$host = self::$request['cookie_sheet']['user_addr'];
		self::disassemble_IP($host);
		self::$files->get_user_queue();
		self::$users[] = self::$request['cookie_sheet']['session'];
		self::patch_connection();
	}

	// ***
	public static function spoof_check() {
		
		if (file_exists("spoof_list"))
			$pre_spoof_filter = file_get_contents("spoof_list");
		else
			return true;
		$spoof_list = json_decode($pre_spoof_filter);
		if ($spoof_list == null)
			return true;
		if (in_array(self::$request['cookie_sheet']['user_addr'],$spoof_list))
			exit();
	}

	//***
	public static function match_remote_server() {
		if (isset(self::$request['cookie_sheet']['user_addr']))
			$host = self::$request['cookie_sheet']['user_addr'];
		else
			return false;

		$trim = "";
		if ($host == "::1" || str_replace("localhost","",$host) == true)
			return true;
		if (($trim = str_replace("http://","",$host) == true))
			self::option_ssl(false);
		else if (($trim = str_replace("https://","",$host) == true))
			self::option_ssl(true);
		if (filter_var($host, FILTER_VALIDATE_URL) == false
			&& ($check_addr_list = gethostbynamel($host)) == false) {
			$spoof_list[] = self::$request['cookie_sheet']['user_addr'];
			$spoof_list = array_unique($spoof_list);
			file_put_contents("spoof_list", $spoof_list);
			return false;
		}
		return true;
	}

	//***
	public static function match_target_server() {
		$host = self::$request[$_SERVER['HTTP_REFERER']];
		$trim = "";
		if ($host == "::1" || str_replace("localhost","",$host) == true)
			return true;
		if (($trim = str_replace("http://","",$host) == true))
			self::option_ssl(false);
		else if (($trim = str_replace("https://","",$host) == true))
			self::option_ssl(true);
		if (filter_var($host, FILTER_VALIDATE_URL) == false
			&& ($check_addr_list = gethostbynamel($host)) == false) {
			$spoof_list[] = self::$request['cookie_sheet']['user_addr'];
			$spoof_list = array_unique($spoof_list);
			file_put_contents("spoof_list", $spoof_list);
			return false;
		}
		return true;
	}

	// ***
	public static function return_relatives($addr) {
		
		self::get_user_log($addr);
		$x = [];
		foreach (self::$user->cookie_sheet as $key) {
			if ($key != 'from_addr' || json_decode($key) == null)
				continue;
			if ($key->A == self::$request['cookie_sheet']['from_addr']['A']
				&& $key->B == self::$request['cookie_sheet']['from_addr']['B']
				&& $key->C == self::$request['cookie_sheet']['from_addr']['C'])
				$x[] = $relationships;
		}
		return $x;
	}

	// ***
	public static function delay_connection() {
		
		$x = [];
		if (sizeof(self::$users) > 2000) {
			if (self::relative_count() > 50) {
				self::$files->save_user_log($_SERVER['REMOTE_ADDR']);
				array_unique(self::$users);
				file_put_contents(self::$setup->path_server . "/users.conf", json_encode(self::$users));
				exit();
			}
		}
		array_unique(self::$users);
		if (self::$users[0] != self::$request['cookie_sheet']['session']) {
			$y = file_get_contents(self::$setup->path_server . "/users.conf");
			$x = json_decode($y);
			while ($x[0] != self::$request['cookie_sheet']['session'] && time() - self::$timer < 3000) {
				$y = file_get_contents(self::$setup->path_server . "/users.conf");
				$x = json_decode($y);	
			}
			self::$patch_connection();
		}
		array_splice(self::$users, array_search(self::$request['cookie_sheet']['session'], self::$users), 1);
		self::update_queue();
		return true;
	}

	//***
	public static function patch_connection() {
		
		if (sizeof(self::$users) > 0) {
			self::run_queue();
			self::$files->save_user_log($_SERVER['REMOTE_ADDR']);
			self::update_queue();
		}
		else if (isset(self::$request['cookie_sheet']['session'])) {
			self::$files->save_user_log($_SERVER['REMOTE_ADDR']);
			if (self::$users == null)
				self::$users = [];
			file_put_contents(self::$setup->path_server . "/users.conf", json_encode(self::$users));		
		}
		if (isset($_SERVER['HTTP_REFERER']))
			header('Location: ' . self::$opt_ssl . self::$request['cookie_sheet'][$_SERVER['HTTP_REFERER']]['data']['target_pg']);
		else
			header('Location: ' . self::$opt_ssl . self::$request['cookie_sheet']['direct']['data']['target_pg']);

	}

	public static function check_addr() {
		
		self::spoof_check();
		
		self::get_servers();
		
		if (!self::match_remote_server()) {
			echo "Fatal Error: Your address is unknown";
			exit();
		}
		else if (isset($_SERVER['HTTP_REFERER']) && isset(self::$request[$_SERVER['HTTP_REFERER']]) && !self::match_target_server(self::$opt_ssl . self::$request[$_SERVER['HTTP_REFERER']])) {
			echo "Fatal Error: Target address unknown";
			exit();
		}
		else if (isset($_SERVER['HTTP_REFERER']) && !isset(self::$request[$_SERVER['HTTP_REFERER']])) {
			echo 'No target address';
			exit();
		}
		return true;
	}
	//***
	public static function run_queue() {
		
		if (self::$files->find_user_queue(self::$request['cookie_sheet']['session']) != false)
			self::send_request();
	}

	public static function option_ssl($bool) {
		self::$opt_ssl = "https://";
		if ($bool == false)
			self::$opt_ssl = "http://";
		return $bool;
	}

	public static function print_page() {
		echo self::$page_contents;
	}

}