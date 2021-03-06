<?php

namespace Battlegrounds\arenas;

use pocketmine\scheduler\Task;
use pocketmine\tile\Sign;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use Battlegrounds\arenas\Arena;

class ArenaScheduler extends Task {
    
    private $mainTime;
    private $time = 0;
    private $startTime;
    private $updateTime = 0;
    private $ending = false;
    private $mins = 9;
    private $secs = 60;
	private $gameStart = 10;
    private $arenaResetTime = 5;
	
    private $forcestart = false;
    
    private $arena;
    
    # Line of Signs
    private $level;
    private $line1;
    private $line2;
    private $line3;
    private $line4;
    
    public function __construct(Arena $arena) {
        $this->arena = $arena;
        $this->startTime = $this->arena->data['arena']['starting_time'];
        $this->mainTime = $this->arena->data['arena']['max_game_time'];
        $this->line1 = str_replace("&", "§", $this->arena->data['signs']['status_line_1']);
        $this->line2 = str_replace("&", "§", $this->arena->data['signs']['status_line_2']);
        $this->line3 = str_replace("&", "§", $this->arena->data['signs']['status_line_3']);
        $this->line4 = str_replace("&", "§", $this->arena->data['signs']['status_line_4']);
        if(!$this->arena->plugin->getServer()->isLevelGenerated($this->arena->data['signs']['join_sign_world'])){
            $this->arena->plugin->getServer()->generateLevel($this->arena->data['signs']['join_sign_world']);
            $this->arena->plugin->getServer()->loadLevel($this->arena->data['signs']['join_sign_world']);
        }
        if(!$this->arena->plugin->getServer()->isLevelLoaded($this->arena->data['signs']['join_sign_world'])){
            $this->arena->plugin->getServer()->loadLevel($this->arena->data['signs']['join_sign_world']);
        }
    }
    
    public function onRun(int $currentTick){
		
        if(strtolower($this->arena->data['signs']['enable_status']) === 'true'){
            $this->updateTime++;
            if($this->updateTime >= $this->arena->data['signs']['sign_update_time']){
                $vars = ['%alive', '%dead', '%status', '%max', '&'];
                $replace = [count(array_merge($this->arena->ingamep, $this->arena->lobbyp)), count($this->arena->deads), $this->arena->getStatus(), $this->arena->getMaxPlayers(), "Β§"];

                $tile = $this->arena->plugin->getServer()->getLevelByName($this->arena->data['signs']['join_sign_world'])->getTile(new Vector3($this->arena->data['signs']['join_sign_x'], $this->arena->data['signs']['join_sign_y'], $this->arena->data['signs']['join_sign_z']));
                if($tile instanceof Sign){
                    $tile->setText(str_replace($vars, $replace, $this->line1), str_replace($vars, $replace, $this->line2), str_replace($vars, $replace, $this->line3), str_replace($vars, $replace, $this->line4));
                }
                $this->updateTime = 0;
            }
        }
        
        if($this->arena->game === 0){
            if(count($this->arena->lobbyp) >= $this->arena->getMinPlayers() || $this->forcestart === true){
				
                $this->startTime--;
				$this->mainTime = $this->arena->data['arena']['max_game_time'];
				$this->gameStart = 10;
				$this->mins = 9;
				$this->secs = 60;
				
				foreach($this->arena->lobbyp as $p){
					
					if($this->startTime >= 4){	
						$p->sendPopup(str_replace("%1", $this->startTime, $this->arena->plugin->getMsg('starting')));
					}

					if($this->startTime == 3){
						$p->addTitle("3", "", 20, 20, 20);
					}
					if($this->startTime == 2){
						$p->addTitle("2", "", 20, 20, 20);
					}
					if($this->startTime == 1){
						$p->addTitle("1", "", 20, 20, 20);
					}
				}

                if($this->startTime <= 0){
                    if(count($this->arena->lobbyp) >= $this->arena->getMinPlayers() || $this->forcestart === true){
                        $this->arena->startGame();
                        $this->startTime = $this->arena->data['arena']['starting_time'];
                        $this->forcestart = false;
                    }
                }
            }
        }
		
        if($this->arena->game === 1){
            $this->startTime = $this->arena->data['arena']['starting_time'];
			$this->gameStart--;
            $this->mainTime--;
            $this->secs--;

            foreach($this->arena->ingamep as $p){ 
                $p->sendPopup(TextFormat::GREEN . "\n[" . $this->mins . ":" . $this->secs . "]\nMagnetic Field: " . $this->arena->magneticFieldX . "x" . $this->arena->magneticFieldZ);
            }
			
			foreach($this->arena->spec as $p){
                $p->sendPopup(TextFormat::GREEN . "\n[Spectator]");
            }

			if($this->gameStart >= 2){
				foreach($this->arena->lobbyp as $p){
					$p->addTitle(TextFormat::GREEN . "Prepare to Jump", TextFormat::RED . $this->gameStart, 20, 20, 20);
				}
		    }
			
			if($this->gameStart == 1){
				foreach($this->arena->lobbyp as $p){
					$p->addTitle(TextFormat::GREEN . "Jump Now", "", 20, 20, 20);
				}
		    }
			
			if($this->gameStart == 0){
				$this->arena->setIngame();
			}

            if($this->secs == 0){
                $this->secs = 60;
                $this->mins--;
            }
			if($this->mins == -1){
                $this->mins = 9;
            }
			
			if($this->mainTime === 570){
				$this->arena->removeSpawnBlocks();
			}
			
			// 8 minutes task \\
			if($this->mainTime === 480){
				$this->arena->magneticFieldX = 101;
				$this->arena->magneticFieldZ = 101;
			}
			
			// 5 minutes task \\
			if($this->mainTime === 300){
				$this->arena->magneticFieldX = 51;
				$this->arena->magneticFieldZ = 51;
			}
			
			// 2 minutes task \\
			if($this->mainTime === 120){
				$this->arena->magneticFieldX = 31;
				$this->arena->magneticFieldZ = 31;
			}
			
            if($this->mainTime === 0){
 
                    foreach($this->arena->ingamep as $p){                      
						$p->addTitle("Oh no, there are no winners in this game!", "", 20, 40, 20);
                    }
                $this->arena->stopGame();
               
            }
        }
		
		if($this->arena->arenaReset === 1){
			$this->arenaResetTime--;
		
			if($this->arenaResetTime === 3){
				$this->arena->unsetAllPlayers();
			}
		
			if($this->arenaResetTime === 0){
				$this->arena->reloadmap();
				$this->arenaResetTime = 5;
				$this->arena->game = 0;
				$this->arena->arenaReset = 0;
				$this->arena->magneticFieldX = 150;
				$this->arena->magneticFieldZ = 150;
				
			}
		}
    }
}
