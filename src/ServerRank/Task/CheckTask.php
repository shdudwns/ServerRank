<?php

namespace ServerRank\Task;

use pocketmine\scheduler\Task;

use ServerRank\Loader;

class CheckTask extends Task{

    /** @var Main */
    private $owner;

    public function __construct(Loader $owner){
        $this->owner = $owner;
    }

    public function onRun(int $currentTick){
        $this->owner->getServerRank();
    }
}
