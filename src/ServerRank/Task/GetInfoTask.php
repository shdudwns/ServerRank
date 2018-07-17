<?php

namespace ServerRank\Task;

use pocketmine\scheduler\AsyncTask;

use pocketmine\Server;

use ServerRank\Loader;

class GetInfoTask extends AsyncTask{

	/** @var string */
	private $hostname;
	/** @var int */
	private $port;

	public function __construct(string $hostname, int $port){
		$this->hostname = $hostname;
		$this->port = $port;
	}

	public function onRun(){
		$this->setResult(gethostbyname($this->hostname));
	}

	public function onCompletion(Server $server){
		Loader::getInstance()->getHostByNameCallback($this->hostname, $this->getResult(), $this->port);
	}
}
