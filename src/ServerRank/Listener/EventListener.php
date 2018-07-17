<?php

namespace ServerRank\Listener;

use pocketmine\event\Listener;

use pocketmine\event\player\PlayerJoinEvent;

use ServerRank\Loader;

class EventListener implements Listener{

    /** @var Loader */
    private $owner;

    public function __construct(Loader $owner){
        $this->owner = $owner;
    }

    public function onJoin(PlayerJoinEvent $event){
        $this->owner->sendRankMessage($event->getPlayer());
    }
}
