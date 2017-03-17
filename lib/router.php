<?php

class Router{
	public static $uri = '';
	public static $controllerPath = '';
	public static $file = '';
	public static $filePath = '';
	public static $domain = 0;
	public static $mode = '';
	public static $submode = '';
	public static $cat = '';
	public static $subcat = '';
	public static $start = 0;
	public static $parts = array();
	
	
	public function __construct($uri){
		global $rootPath;
		
		self::$uri = $uri;
		self::$controllerPath = $rootPath.'controllers/';
	}
	
	public static function parseRoute(){
		global $db, $rootPath, $template, $config, $officers, $serverList;
		
		$mode = '';
		$submode = '';
		$cat = '';
		$subcat = '';
		$start = 0;
		
		if(strpos(self::$uri, '?')){
			$uri = strstr(self::$uri, '?', true);
		}else{
			$uri = self::$uri;
		}
		self::$parts = explode('/', trim($uri, '/'));
		
		if(empty(self::$parts[0])){
			self::$file = 'index';
		}else{
			$f = self::normalize(self::$parts[0]);
			if(is_numeric($f)){
				self::$file = 'domain';
				self::$domain = $f;
			}else{
				self::$file = $f;
			}
			
			if(!empty(self::$parts[1])){
				$mode = self::normalize(self::$parts[1]);
			}
			
			if(!empty(self::$parts[2])){
				$submode = self::normalize(self::$parts[2]);
			}
			
			if(!empty(self::$parts[3])){
				$cat = self::normalize(self::$parts[3]);
			}
			
			if(!empty(self::$parts[4])){
				$subcat = self::normalize(self::$parts[4]);
			}
		}
		self::$filePath = self::$controllerPath.self::$file.'.php';
		self::$mode = $mode;
		self::$submode = $submode;
		self::$cat = $cat;
		self::$subcat = $subcat;
		
		include(self::$filePath);
		return;
	}
	
	private static function normalize($string){
		return preg_replace('[^A-Za-z0-9_-]', '', $string);
	}
}

