<?php

namespace ServerRank;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\Utils;

use pocketmine\Player;

use ServerRank\Listener\EventListener;
use ServerRank\Task\{
	CheckTask, GetInfoTask, GetRankTask
};

class Loader extends PluginBase{

	/** @var string */
	private $prefix = '§d<§f시스템§d>§f ';

	/** @var Loader */
	private static $instance = null;

	/** @var array */
	private $servers;

	/** @var int */
	private $requestCount = 0;

	/** @var null|int */
	private $rank = null;

	/** @var int */
	private $repeatingInterval = 1200; // 1 minutes

	/**
	* @return Loader
	*/
	public static function getInstance(): Loader {
		return self::$instance;
	}

	public function onLoad(): void {
		self::$instance = $this;

		$this->getServerRank();
	}

	public function onEnable(): void {
		$this->getScheduler()->scheduleDelayedRepeatingTask(new CheckTask($this), $this->repeatingInterval, $this->repeatingInterval);

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}

	public function getServerRank(): void {
		$this->getServer()->getAsyncPool()->submitTask(new GetRankTask());
	}

	public function getServerRankTaskCallback(array $servers): void {
		foreach($servers as $i => $server){
			$rank = $i + 1;
			$this->servers[$server['address']['host'] . ':' . $server['address']['port']] = $rank;
		}
		foreach($servers as $server){
			++$this->requestCount;
			$this->getServer()->getAsyncPool()->submitTask(new GetInfoTask($server['address']['host'], $server['address']['port']));
		}
	}

	public function getHostByNameCallback(string $hostname, string $ip, int $port): void {
		if($ip === Utils::getIP() and $port === $this->getServer()->getPort()){
			if(isset($this->servers[$hostname . ':' . $port])){
				$updatedOnce = $this->rank !== null;

				$this->rank = $this->servers[$hostname . ':' . $port];

				$this->getServer()->getLogger()->notice($this->prefix . '한국 전체 서버 중 우리 서버의 순위는 §7(MCBE RANK 기준) §d§l' . $this->rank . '§r위 입니다.');

				if(!$updatedOnce){
					foreach($this->getServer()->getOnlinePlayers() as $player){
						$this->sendRankMessage($player);
					}
				}
			}
		}else if(--$this->requestCount === 0){
			$this->getServer()->getLogger()->alert($this->prefix . 'https://mcberank.kro.kr 사이트에 서버가 등록되어 있지 않습니다.');
			$this->getServer()->getLogger()->alert($this->prefix . '카카오톡 아이디 solo5star, 검색 후 연락 주세요.');
		}
	}
}
