<?php

chdir(__DIR__);
ini_set('max_execution_time', 28800);
ini_set('memory_limit','64M');

define('IN_SITE', true);
$rootPath = $root_path = './../';
$phpEx = 'php';
include($rootPath.'inc/common.php');

$user->session_begin();
$user->setup();

$start = time();

$domains = array(460,458,454,457,452,456,451,444,449,448,439,433,445,435,450,415,434,447,453,455,459,441,348,304);

//creates context
$context = new ZMQContext();
//create DEALER socket http://api.zeromq.org/2-1:zmq-socket#toc6
$socket = new ZMQSocket($context, ZMQ::SOCKET_DEALER);
//client connects
$socket->connect('tcp://127.0.0.1:15000');
//send 100 Hellos
foreach($domains as $domain){
	$socket->send("player-$domain");
	echo $socket->recv() . "\n";
	sleep(1);
}


?>