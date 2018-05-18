<?php

namespace Battlegrounds;

use pocketmine\scheduler\PluginTask;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\level\particle\AngryVillagerParticle;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\math\Vector3;

use Battlegrounds\MainBG;
use Battlegrounds\arenas\Arena;

class BattleParticle extends PluginTask {
	
	private $player;
	
  public function __construct(MainBG $plugin, Player $player){
    parent::__construct($plugin);
    $this->player = $player;
  }

  public function onRun(int $currentTick){
    $level = $this->player->getLevel();
    
    $r = rand(1,300);
    $g = rand(1,300);
    $b = rand(1,300);
    
    $x = $this->player->getX();
    $y = $this->player->getY();
    $z = $this->player->getZ();
    
    $center = new Vector3($x, $y, $z);
    
    $radius = 1;
    $count = 6;
    
	  $particle = new AngryVillagerParticle($center, $r, $g, $b, 1);
                for($yaw = 0, $y = $center->y; $y < $center->y + 4; $yaw += (M_PI * 2) / 20, $y += 1 / 20){
                  $x = -sin($yaw) + $center->x;
                  $z = cos($yaw) + $center->z;
                  $particle->setComponents($x, $y, $z);
                  $level->addParticle($particle);
          }
      }
}
