<?php

namespace ServerRank;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\Task;
use pocketmine\utils\Utils;

class Main extends PluginBase implements Listener{
	
	/** @var string */
	private $prefix = '§d<§f시스템§d>§f ';
	
	/** @var Main */
	private static $instance;
	
	/** @var array */
	private $servers;
	
	/** @var int */
	private $requestCount = 0;
	
	/** @var null|int */
	private $rank = null;
	
	/** @var int */
	private $repeatingInterval = 6000; // 5 minutes

	public function onLoad(): void {
		self::$instance = $this;

		$this->getServerRank();
	}

	public function onEnable(): void {
		$this->getScheduler()->scheduleDelayedRepeatingTask(new class($this) extends Task{
			
			/** @var Main */
			private $owner;
			
			public function __construct(Main $owner){
				$this->owner = $owner;
			}

			public function onRun(int $currentTick){
				$this->owner->getServerRank();
			}
		}, $this->repeatingInterval, $this->repeatingInterval);
		
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function getServerRank(): void {
		$this->getServer()->getAsyncPool()->submitTask(new GetServerRankTask());
	}

	public function getServerRankTaskCallback(array $servers): void {
		foreach($servers as $i => $server){
			$rank = $i + 1;
			$this->servers[$server['address']['host'] . ':' . $server['address']['port']] = $rank;
		}
		foreach($servers as $server){
			++$this->requestCount;
			$this->getServer()->getAsyncPool()->submitTask(new GetHostByNameTask($server['address']['host'], $server['address']['port']));
		}
	}

	public function getHostByNameCallback(string $hostname, string $ip, int $port): void {
		if($ip === Utils::getIP() and $port === $this->getServer()->getPort()){
			if(isset($this->servers[$hostname . ':' . $port])){
				$updatedOnce = $this->rank !== null;

				$this->rank = $this->servers[$hostname . ':' . $port];

				$this->getServer()->getLogger()->notice($this->prefix . '한국 전체 서버 중 우리 서버의 순위는 §7(MCBE RANK 기준) §d§l' . $this->rank . '§r위 입니다.');

				if(!$updatedOnce) foreach($this->getServer()->getOnlinePlayers() as $player){
					$this->sendRankMessage($player);
				}
			}
		}else if(--$this->requestCount === 0){
			$this->getServer()->getLogger()->alert($this->prefix . 'https://mcberank.kro.kr 사이트에 서버가 등록되어 있지 않습니다.');
			$this->getServer()->getLogger()->alert($this->prefix . '카카오톡 아이디 solo5star, 검색 후 연락 주세요.');
		}
	}

	public function sendRankMessage(Player $player): void {
		if($this->rank !== null){
			$player->sendMessage($this->prefix . '한국 전체 서버 중 우리 서버의 순위는 §7(MCBE RANK 기준) §d§l' . $this->rank . '§r위 입니다.');
		}
	}
	
	public function onJoin(PlayerJoinEvent $event){
		$this->sendRankMessage($event->getPlayer());
	}
	
	public static function getInstance(): Main {
		return self::$instance;
	}
}

class GetServerRankTask extends AsyncTask{

	public function onRun(){
		$this->setResult(Utils::getURL('http://mcberank.kro.kr/api/online-servers'));
	}

	public function onCompletion(Server $server){
		$result = json_decode($this->getResult(), true);
		if(empty($result) or $result['status'] !== 200){
			$server->getLogger()->alert($this->prefix . 'http://mcberank.kro.kr 사이트에서 응답을 받을 수 없습니다.');
			$server->getLogger()->alert($this->prefix . '카카오톡 아이디 solo5star, 검색 후 연락 주세요.');
		}else{
			Main::getInstance()->getServerRankTaskCallback($result['data']);
		}
	}
}

class GetHostByNameTask extends AsyncTask{
	
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
		Main::getInstance()->getHostByNameCallback($this->hostname, $this->getResult(), $this->port);
	}
}
