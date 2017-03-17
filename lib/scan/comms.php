<?php

class comms{
	public static $endPoint = '';
	
	public static function getData($page, $args){
		$ch = curl_init(self::$endPoint.$page.'.php');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
		curl_setopt($ch, CURLOPT_POST, 1);
		$result = curl_exec($ch);
		return json_decode($result, true);
	}
	
	public static function buildPayload($arr){
		$args = array_merge($arr, scanner::$uD);
		return http_build_query($args);
	}
	
	public static function sendRequest($page, $args){
		$query = self::buildPayload($args);
		return self::getData($page, $query);
	}
}