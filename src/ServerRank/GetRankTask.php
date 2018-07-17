<?php

namespace ServerRank\Task;

use pocketmine\scheduler\AsyncTask;

use pocketmine\utils\Utils;

use pocketmine\Server;

use ServerRank\Loader;

class GetRankTask extends AsyncTask{

	public function onRun(){
		$this->setResult(Utils::getURL('http://mcberank.kro.kr/api/online-servers'));
	}

	public function onCompletion(Server $server){
		$result = json_decode($this->getResult(), true);
		if(empty($result) or $result['status'] !== 200){
			$server->getLogger()->alert(Loader::getInstance()->prefix . 'http://mcberank.kro.kr 사이트에서 응답을 받을 수 없습니다.');
			$server->getLogger()->alert(Loader::getInstance()->prefix . '카카오톡 아이디 solo5star, 검색 후 연락 주세요.');
		}else{
			Loader::getInstance()->getServerRankTaskCallback($result['data']);
		}
	}
}
