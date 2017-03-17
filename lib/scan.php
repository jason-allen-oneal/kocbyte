<?php

class scan {
	public function __construct($domain){
		echo 'Starting '.$domain.' at '.date('M-d-Y h:i a', time())."<br /><br />";

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