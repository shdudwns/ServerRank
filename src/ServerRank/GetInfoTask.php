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
	/** @var array */
	private $servers;

	public function __construct(string $hostname, int $port, array $servers){
		$this->hostname = $hostname;
		$this->port = $port;
		$this->servers = $servers;
	}

	public function onRun(){
		$this->setResult(gethostbyname($this->hostname));
	}

	public function onCompletion(Server $server){
		Loader::getInstance()->getHostByNameCallback($this->hostname, $this->getResult(), $this->port, json_decode(json_encode($this->servers), true));
	}
}
