<?php

chdir(__DIR__);
ini_set('max_execution_time', 28800);
ini_set('memory_limit','64M');

define('IN_SITE', true);
$rootPath = $root_path = './../';
$phpEx = 'php';
include($rootPath.'inc/common.php');

include($rootPath.'lib/scan/scanner.php');
include($rootPath.'lib/scan/player.php');
include($rootPath.'lib/scan/city.php');
include($rootPath.'lib/scan/allianceChange.php');
include($rootPath.'lib/scan/nameChange.php');

$user->session_begin();
$user->setup();

//creates context
$context = new ZMQContext();
//create ROUTER socket, http://api.zeromq.org/2-1:zmq-socket#toc7
$socket = new ZMQSocket($context, ZMQ::SOCKETROUTER);
//worker binds
$socket->bind('tcp://*:15000');
//poll the socket, like event dispatcher
$poll = new ZMQPoll();
$poll->add($socket, ZMQ::POLLIN);
$readable = $writeable = array();
while(true){
	$events = $poll->poll($readable, $writeable, 1000);
	foreach($readable as $s){
		//When there is incoming message, deal with it
		$message = $socket->recvmulti();
		list($scanType, $domain) = explode('-', $message[1]);
		
		$start = time();
		new scanner($domain);
		scanner::load();

		echo 'Starting '.$domain.' at '.date('M-d-Y h:i a', $start)."<br /><br />";

		$uParams = array(
			'page' => 1,
			'perPage' => 100,
			'type' => 'might',
		);
		$result = comms::sendRequest('getUserLeaderboard', $uParams);

		$totalPages = $result['totalPages'];
		foreach($result['results'] as $data){
			if($data['userId']){
				$player = new player($data);
				$player->process();
			}else{
				continue;
			}
		}

		for($page = 2; $page < $totalPages+1; $page++){
			$uParams = array(
				'page' => 1,
				'perPage' => 100,
				'type' => 'might',
			);
			$result = comms::sendRequest('getUserLeaderboard', $uParams);
			
			foreach($result['results'] as $data){
				if($data['userId']){
					$player = new player($data);
					$player->process();
				}else{
					continue;
				}
			}
		}
		echo 'Done at '.date('M-d-Y h:i a', time());
	}
}


?>