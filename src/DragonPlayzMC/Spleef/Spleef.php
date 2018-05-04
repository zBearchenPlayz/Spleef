<?php
# plugin DragonPlayzMC
namespace DragonPlayzMC\Spleef;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as TE;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\tile\Chest;
use pocketmine\inventory\ChestInventory;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\player\PlayerQuitEvent;
use MTPlugins\Spleef\ResetMap;
use pocketmine\entity\Effect;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\block\Air;

class Spleef extends PluginBase implements Listener {

    public $prefix = TE::GRAY . "[" . TE::AQUA . TE::BOLD . "" . TE::RED . "Spleef" . TE::RESET . TE::GRAY . "]";
	public $mode = 0;
	public $arenas = array();
	public $currentLevel = "";
	
	public function onEnable()
	{
		  $this->getLogger()->info(TE::DARK_AQUA . "§cSpleef §eby §bPrinxIsLeqit");
                  
                $this->getServer()->getPluginManager()->registerEvents($this ,$this);
                $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                if(!empty($this->economy))
                {
                $this->api = EconomyAPI::getInstance();
                }
		@mkdir($this->getDataFolder());
                $config2 = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
		$config2->save();
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                if($config->get("money")==null)
                {
                   $config->set("money",500);
                }
		if($config->get("arenas")!=null)
		{
			$this->arenas = $config->get("arenas");
		}
		foreach($this->arenas as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
		$items = array(array(1,0,30),array(1,0,20),array(3,0,15),array(3,0,25),array(4,0,35),array(4,0,15),array(260,0,5),array(261,0,1),array(262,0,6),array(267,0,1),array(268,0,1),array(272,0,1),array(276,0,1),array(283,0,1),array(297,0,3),array(298,0,1),array(299,0,1),array(300,0,1),array(301,0,1),array(303,0,1),array(304,0,1),array(310,0,1),array(313,0,1),array(314,0,1),array(315,0,1),array(316,0,1),array(317,0,1),array(320,0,4),array(354,0,1),array(364,0,4),array(366,0,5),array(391,0,5));
		if($config->get("chestitems")==null)
		{
			$config->set("chestitems",$items);
		}
		$config->save();
		
		$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
		$playerlang->save();
		
		$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
		if($lang->get("en")==null)
		{
			$messages = array();
			$messages["Kill"] = "killed by";
			$messages["Cannotjoin"] = "You cannot join!";
			$messages["Seconds"] = "segundos para iniciar";
			$messages["Won"] = "§aWon in the: §b";
			$messages["deathmatchminutes"] = "minutes to DeathMatch!";
			$messages["deathmatchseconds"] = "seconds to DeathMatch!";
			$messages["chestrefill"] = "The chest have been refilled!";
			$messages["remainingminutes"] = "minutes remaining";
			$messages["remainingseconds"] = "seconds remaining";
			$messages["nowinner"] = "§cNo Winner in: §b";
			$messages["moreplayers"] = "Need more players";
			$lang->set("en",$messages);
		}
		$lang->save();
                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                $slots->save();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 10);
	}
	
	public function onDeath(PlayerDeathEvent $event){
        $jugador = $event->getEntity();
        $mapa = $jugador->getLevel()->getFolderName();
        if(in_array($mapa,$this->arenas))
		{
                if($event->getEntity()->getLastDamageCause() instanceof EntityDamageByEntityEvent)
                {
                $asassin = $event->getEntity()->getLastDamageCause()->getDamager();
                if($asassin instanceof Player){
                $event->setDeathMessage("");
                foreach($jugador->getLevel()->getPlayers() as $pl){
				$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
				$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
				$toUse = $lang->get($playerlang->get($pl->getName()));
                                $muerto = $jugador->getNameTag();
                                $asesino = $asassin->getNameTag();
				$pl->sendMessage(TE::RED . $muerto . TE::YELLOW . " " . $toUse["kill"] . " " . TE::GREEN . $asesino . TE::YELLOW . ".");
			}
                }
                }
                $jugador->setNameTag($jugador->getName());
                }
        }
	
	public function onMove(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$sofar = $config->get($level . "StartTime");
			if($sofar > 0)
			{
			}
		}
	}
	
	public function onLog(PlayerLoginEvent $event)
	{
		$player = $event->getPlayer();
		$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
		if($playerlang->get($player->getName())==null)
		{
			$playerlang->set($player->getName(),"en");
			$playerlang->save();
		}
                if(in_array($player->getLevel()->getFolderName(),$this->arenas))
		{
		$player->getInventory()->clearAll();
		$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
		$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
		$player->teleport($spawn,0,0);
                }
	}
        
        public function onQuit(PlayerQuitEvent $event)
        {
            $pl = $event->getPlayer();
            $level = $pl->getLevel()->getFolderName();
            if(in_array($level,$this->arenas))
            {
                $pl->removeAllEffects();
                $pl->getInventory()->clearAll();
                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                $pl->setNameTag($pl->getName());
                if($slots->get("slot1".$level)==$pl->getName())
                {
                    $slots->set("slot1".$level, 0);
                }
                if($slots->get("slot2".$level)==$pl->getName())
                {
                    $slots->set("slot2".$level, 0);
                }
                if($slots->get("slot3".$level)==$pl->getName())
                {
                    $slots->set("slot3".$level, 0);
                }
                if($slots->get("slot4".$level)==$pl->getName())
                {
                    $slots->set("slot4".$level, 0);
                }
                if($slots->get("slot5".$level)==$pl->getName())
                {
                    $slots->set("slot5".$level, 0);
                }
                if($slots->get("slot6".$level)==$pl->getName())
                {
                    $slots->set("slot6".$level, 0);
                }
                if($slots->get("slot7".$level)==$pl->getName())
                {
                    $slots->set("slot7".$level, 0);
                }
                if($slots->get("slot8".$level)==$pl->getName())
                {
                    $slots->set("slot8".$level, 0);
                }
                if($slots->get("slot9".$level)==$pl->getName())
                {
                    $slots->set("slot9".$level, 0);
                }
                if($slots->get("slot10".$level)==$pl->getName())
                {
                    $slots->set("slot10".$level, 0);
                }
                if($slots->get("slot11".$level)==$pl->getName())
                {
                    $slots->set("slot11".$level, 0);
                }
                if($slots->get("slot12".$level)==$pl->getName())
                {
                    $slots->set("slot12".$level, 0);
                }
                $slots->save();
            }
        }
	
	public function onBlockBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
                        $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                        if($config->get($level . "PlayTime") != null)
                        {
                                if($config->get($level . "PlayTime") > 779)
                                {
                                        $event->setCancelled(true);
                                }
                        }
		}
	}
	
	public function onBlockPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$event->setCancelled(false);
		}
	}
	
	public function onDamage(EntityDamageEvent $event)
	{
		if($event instanceof EntityDamageByEntityEvent)
		{
			$player = $event->getEntity();
			$damager = $event->getDamager();
			if($player instanceof Player)
			{
				if($damager instanceof Player)
				{
					$level = $player->getLevel()->getFolderName();
					$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($level . "PlayTime") != null)
					{
						if($config->get($level . "PlayTime") > 0)
						{
							$event->setCancelled(true);
						}
					}
				}
			}
		}
	}
	
	public function onCommand(CommandSender $player, Command $cmd, $label, array $args): bool {
		$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
        switch($cmd->getName()){
			case "sp":
				if($player->isOp())
				{
					if(!empty($args[0]))
					{
						if($args[0]=="make")
						{
							if(!empty($args[1]))
							{
								if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
								{
									$this->getServer()->loadLevel($args[1]);
									$this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
									array_push($this->arenas,$args[1]);
									$this->currentLevel = $args[1];
									$this->mode = 1;
									$player->sendMessage($this->prefix . "Touch the spawn points!");
									$player->setGamemode(1);
									$player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(),0,0);
                                                                        $name = $args[1];
                                                                        $this->zipper($player, $name);
								}
								else
								{
									$player->sendMessage("ERROR missing world.");
								}
							}
							else
							{
								$player->sendMessage("ERROR missing parameters.");
							}
						}
						else
						{
							$player->sendMessage("Invalid Command.");
						}
					}
					else
					{
					 $player->sendMessage("§7» ------------------------------ «");
                                         $player->sendMessage("§e( §c/sp make §5(World) §e) §a To create");
                                         $player->sendMessage("§e( §c/spstart §e) §aTo Start");
                                         $player->sendMessage(" §7»------------------------------ «");
					}
				}
				else
				{
				}
			return true;
			
			case "lang":
				if(!empty($args[0]))
				{
					if($lang->get($args[0])!=null)
					{
						$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
						$playerlang->set($player->getName(),$args[0]);
						$playerlang->save();
						$player->sendMessage(TE::GREEN . "Lang: " . $args[0]);
					}
					else
					{
						$player->sendMessage(TE::RED . "Language not found!");
					}
				}
			return true;
                        
                        case "startsp":
                            if($player->isOp())
				{
                                $player->sendMessage("§7Staring in 10 seconds...");
                                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                $config->set("arenas",$this->arenas);
                                foreach($this->arenas as $arena)
                                {
                                        $config->set($arena . "PlayTime", 780);
                                        $config->set($arena . "StartTime", 10);
                                }
                                $config->save();
                                }
                                return true;
                                
                        case "ranksp":
				if($player->isOp())
				{
				if(!empty($args[0]))
				{
					if(!empty($args[1]))
					{
					$rank = "";
					if($args[0]=="naruto")
					{
						$rank = "Naruto";
					}
                                        elseif($args[0]=="sasuke")
					{
						$rank = "Sasuke";
					}
                                        elseif($args[0]=="runner")
					{
						$rank = "Runner";
					}
                                        elseif($args[0]=="fighter")
					{
						$rank = "Fighter";
					}
                                        elseif($args[0]=="jumper")
					{
						$rank = "Jumper";
					}
                                        elseif($args[0]=="midas")
					{
						$rank = "Midas";
					}
                                        elseif($args[0]=="fakevip")
					{
						$rank = "Fakevip";
					}
                                        elseif($args[0]=="begginer")
					{
						$rank = "Begginer";
					}
                                        elseif($args[0]=="archer")
					{
						$rank = "Archer";
					}
                                        elseif($args[0]=="pyromaniac")
					{
						$rank = "Pyro";
					}
                                        elseif($args[0]=="warrior")
					{
						$rank = "Warrior";
					}
                                        elseif($args[0]=="vip")
					{
						$rank = "Vip";
					}
                                        else
                                        {
                                            goto end;
                                        }
                                        $config = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
					$config->set($args[1],$rank);
					$config->save();
					$player->sendMessage(TE::AQUA.$args[1].TE::GREEN." actives rank: ".TE::YELLOW.$rank);
                                        end:
                                        }
                                }
                                }
                                return true;
                                
                        case "spmoney":
                            if($player->isOp())
				{
				if(!empty($args[0]))
				{
                                    $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                    $config->set("money",$args[0]);
                                    $config->save();
                                    $player->sendMessage(TE::GREEN."Prize of Spleef is: ".TE::AQUA.$args[0]);
                                }
                                }
                                return true;
	}
        }
        
        public function setkit($p)
        {
            $config = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
            $kit = $config->get($p->getName());
            if($kit=="Runner")
            {
                $speed = Effect::getEffect(1);
                    $speed->setAmplifier(2);
                    $speed->setVisible(true);
                    $speed->setDuration(1000000);
                    $p->addEffect($speed);
                    $p->setNameTag(TE::GRAY."[".TE::GREEN."Runner".TE::GRAY."]".TE::AQUA.$p->getName());
            }
            elseif($kit=="Fighter"){
                $fuerza = Effect::getEffect(5);
                    $fuerza->setAmplifier(1.5);
                    $fuerza->setVisible(true);
                    $fuerza->setDuration(1000000);
                    $p->addEffect($fuerza);
                    $p->setNameTag(TE::GRAY."[".TE::GREEN."Fighter".TE::GRAY."]".TE::AQUA.$p->getName());
            }
            elseif($kit=="Jumper")
            {
                $salto = Effect::getEffect(8);
                    $salto->setAmplifier(3);
                    $salto->setVisible(true);
                    $salto->setDuration(1000000);
                    $p->addEffect($salto);
                    $p->setNameTag(TE::GRAY."[".TE::GREEN."Jumper".TE::GRAY."]".TE::AQUA.$p->getName());
            }
            elseif($kit=="Midas")
            {
                $p->getInventory()->setContents(array(Item::get(0, 0, 0)));
                    $p->getInventory()->setHelmet(Item::get(Item::GOLD_HELMET));
                    $p->getInventory()->setChestplate(Item::get(Item::GOLD_CHESTPLATE));
                    $p->getInventory()->setLeggings(Item::get(Item::GOLD_LEGGINGS));
                    $p->getInventory()->setBoots(Item::get(Item::GOLD_BOOTS));
                    $p->getInventory()->setItem(0, Item::get(Item::GOLD_AXE, 0, 1));
                    $p->getInventory()->setHotbarSlotIndex(0, 0);
                    $p->setNameTag(TE::GRAY."[".TE::YELLOW."Midas".TE::GRAY."]".TE::GOLD.$p->getName());
            }
            elseif($kit=="Fakevip")
            {
                $p->getInventory()->setContents(array(Item::get(0, 0, 0)));
                    $p->getInventory()->setHelmet(Item::get(Item::CHAIN_HELMET));
                    $p->getInventory()->setChestplate(Item::get(Item::CHAIN_CHESTPLATE));
                    $p->getInventory()->setLeggings(Item::get(Item::CHAIN_LEGGINGS));
                    $p->getInventory()->setBoots(Item::get(Item::CHAIN_BOOTS));
                    $p->getInventory()->setItem(0, Item::get(Item::IRON_AXE, 0, 1));
                    $p->getInventory()->setHotbarSlotIndex(0, 0);
                    $p->setNameTag(TE::GRAY."[".TE::GOLD."BIP".TE::GREEN."+".TE::GRAY."]".TE::AQUA.$p->getName());
            }
            elseif($kit=="Warrior")
            {
                $p->getInventory()->setContents(array(Item::get(0, 0, 0)));
                    $p->getInventory()->setChestplate(Item::get(Item::CHAIN_CHESTPLATE));
                    $p->getInventory()->setLeggings(Item::get(Item::CHAIN_LEGGINGS));
                    $p->getInventory()->setItem(0, Item::get(Item::IRON_AXE, 0, 1));
                    $p->getInventory()->setHotbarSlotIndex(0, 0);
                    $p->setNameTag(TE::GRAY."[".TE::RED."Warrior".TE::GRAY."]".TE::GREEN.$p->getName());
            }
            elseif($kit=="Begginer")
            {
                $p->getInventory()->setContents(array(Item::get(0, 0, 0)));
                    $p->getInventory()->setHelmet(Item::get(Item::GOLD_HELMET));
                    $p->getInventory()->setChestplate(Item::get(Item::GOLD_CHESTPLATE));
                    $p->getInventory()->setLeggings(Item::get(Item::LEATHER_PANTS));
                    $p->getInventory()->setBoots(Item::get(Item::LEATHER_BOOTS));
                    $p->getInventory()->setItem(0, Item::get(Item::IRON_AXE, 0, 1));
                    $p->getInventory()->setHotbarSlotIndex(0, 0);
                    $p->setNameTag(TE::GRAY."[".TE::BLUE."Begginer".TE::GRAY."]".TE::WHITE.$p->getName());
            }
            elseif($kit=="Archer")
            {
                $p->getInventory()->setContents(array(Item::get(0, 0, 0)));
                    $p->getInventory()->setItem(0, Item::get(Item::BOW, 0, 1));
                    $p->getInventory()->addItem(Item::get(262,0,20));
                    $p->getInventory()->setHotbarSlotIndex(0, 0);
                    $p->setNameTag(TE::GRAY."[".TE::DARK_PURPLE."Archer".TE::GRAY."]".TE::GREEN.$p->getName());
            }
            elseif($kit=="Pyro")
            {
                $p->getInventory()->setContents(array(Item::get(0, 0, 0)));
                    $p->getInventory()->setItem(0, Item::get(Item::FLINT_AND_STEEL, 0, 1));
                    $p->getInventory()->addItem(Item::get(46,0,3));
                    $p->getInventory()->setHotbarSlotIndex(0, 0);
                    $p->setNameTag(TE::GRAY."[".TE::DARK_RED."Pyro".TE::GRAY."]".TE::GOLD.$p->getName());
            }
            elseif($kit=="Sasuke")
            {
                $p->getInventory()->setContents(array(Item::get(0, 0, 0)));
                    $p->getInventory()->setItem(0, Item::get(Item::IRON_SWORD, 0, 1));
                    $p->getInventory()->setHotbarSlotIndex(0, 0);
                    $p->setNameTag(TE::GRAY."[".TE::AQUA."Sasuke".TE::GRAY."]".TE::GREEN.$p->getName());
            }
            elseif($kit=="Naruto")
            {
                $speed = Effect::getEffect(1);
                    $jump = Effect::getEffect(8);
                    $speed->setVisible(true);
                    $jump->setVisible(true);
                    $jump->setAmplifier(1.5);
                    $speed->setAmplifier(1.5);
                    $speed->setDuration(1000000);
                    $jump->setDuration(1000000);
                    $p->addEffect($speed);
                    $p->addEffect($jump);
                    $p->setNameTag(TE::GRAY."[".TE::GOLD."Naruto".TE::GRAY."]".TE::GREEN.$p->getName());
            }
            elseif($kit=="Vip")
            {
                    $p->getInventory()->setContents(array(Item::get(0, 0, 0)));
                    $p->getInventory()->setHelmet(Item::get(Item::CHAIN_HELMET));
                    $p->getInventory()->setChestplate(Item::get(Item::CHAIN_CHESTPLATE));
                    $p->getInventory()->setLeggings(Item::get(Item::CHAIN_LEGGINGS));
                    $p->getInventory()->setBoots(Item::get(Item::CHAIN_BOOTS));
                    $p->getInventory()->setItem(0, Item::get(Item::DIAMOND_AXE, 0, 1));
                    $p->getInventory()->setHotbarSlotIndex(0, 0);
                    $p->setNameTag(TE::GRAY."[".TE::GOLD."VIP".TE::GREEN."+".TE::GRAY."]".TE::AQUA.$p->getName());
            }
            else
            {
            }
        }
	
	public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		
		if($tile instanceof Sign) 
		{
			if($this->mode==26)
			{
				$tile->setText(TE::AQUA . "§7[ §fJoin §7]",TE::YELLOW  . "0 / 12","§f" . $this->currentLevel,$this->prefix);
				$this->refreshArenas();
				$this->currentLevel = "";
				$this->mode = 0;
				$player->sendMessage("Arena Registered");
			}
			else
			{
				$text = $tile->getText();
				if($text[3] == $this->prefix)
				{
					if($text[0]==TE::AQUA . "§7[ §fJoin §7]")
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                                $slots = new Config($this->getDataFolder() . "/slots.yml", Config::YAML);
                                                $namemap = str_replace("§f", "", $text[2]);
						$level = $this->getServer()->getLevelByName($namemap);
                                                if($slots->get("slot1".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn1");
                                                        $slots->set("slot1".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot2".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn2");
                                                        $slots->set("slot2".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot3".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn3");
                                                        $slots->set("slot3".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot4".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn4");
                                                        $slots->set("slot4".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot5".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn5");
                                                        $slots->set("slot5".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot6".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn6");
                                                        $slots->set("slot6".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot7".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn7");
                                                        $slots->set("slot7".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot8".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn8");
                                                        $slots->set("slot8".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot9".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn9");
                                                        $slots->set("slot9".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot10".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn10");
                                                        $slots->set("slot10".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot11".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn11");
                                                        $slots->set("slot11".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                else if($slots->get("slot12".$namemap)==null)
                                                {
                                                        $thespawn = $config->get($namemap . "Spawn12");
                                                        $slots->set("slot12".$namemap, $player->getName());
                                                        $slots->save();
                                                }
                                                $player->sendMessage("§c> §7You Entered Spleef");
                                                foreach($level->getPlayers() as $playersinarena)
                                                        {
                                                        $playersinarena->sendMessage($player->getName() . " §eHas joined the game!");
                                                        }
						$spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$level);
						$level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
						$player->teleport($spawn,0,0);
						$player->getInventory()->clearAll();
                                                $player->removeAllEffects();
                                                $player->setHealth(20);
                                                $player->setFood(20);
                                                $this->setkit($player);
					}
					else
					{
						$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
						$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
						$toUse = $lang->get($playerlang->get($player->getName()));
						$player->sendMessage($toUse["cannotjoin"]);
					}
				}
			}
		}
		else if($this->mode>=1&&$this->mode<=11)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage("Spawn " . $this->mode . " has been registered!");
			$this->mode++;
			$config->save();
		}
		else if($this->mode==12)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage("Spawn " . $this->mode . " has been registered!");
			$config->set("arenas",$this->arenas);
			$player->sendMessage("Touch Sign to register Arena!");
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
			$player->teleport($spawn,0,0);
			$config->save();
			$this->mode=26;
		}
	}
	
	public function refreshArenas()
	{
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		$config->set("arenas",$this->arenas);
		foreach($this->arenas as $arena)
		{
			$config->set($arena . "PlayTime", 780);
			$config->set($arena . "StartTime", 90);
		}
		$config->save();
	}
        
        public function zipper($player, $name)
        {
        $path = realpath($player->getServer()->getDataPath() . 'worlds/' . $name);
				$zip = new \ZipArchive;
				@mkdir($this->getDataFolder() . 'arenas/', 0755);
				$zip->open($this->getDataFolder() . 'arenas/' . $name . '.zip', $zip::CREATE | $zip::OVERWRITE);
				$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($path),
					\RecursiveIteratorIterator::LEAVES_ONLY
				);
                                foreach ($files as $datos) {
					if (!$datos->isDir()) {
						$relativePath = $name . '/' . substr($datos, strlen($path) + 1);
						$zip->addFile($datos, $relativePath);
					}
				}
				$zip->close();
				$player->getServer()->loadLevel($name);
				unset($zip, $path, $files);
        }
}

class RefreshSigns extends PluginTask {
    public $prefix = TE::GRAY . "[" . TE::AQUA . TE::BOLD . "" . TE::RED . "Spleef" . TE::RESET . TE::GRAY . "]";
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$allplayers = $this->plugin->getServer()->getOnlinePlayers();
		$level = $this->plugin->getServer()->getDefaultLevel();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[3]==$this->prefix)
				{
					$aop = 0;
                                        $namemap = str_replace("§f", "", $text[2]);
					foreach($allplayers as $player){if($player->getLevel()->getFolderName()==$namemap){$aop=$aop+1;}}
					$ingame = TE::AQUA . "§7[ §fJoin §7]";
					$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($namemap . "PlayTime")!=780)
					{
						$ingame = TE::DARK_PURPLE . "§7[ §cRunning §7]";
					}
					else if($aop>=12)
					{
						$ingame = TE::GOLD . "§7[ §9Full §7]";
					}
					$t->setText($ingame,TE::YELLOW  . $aop . " / 12",$text[2],$this->prefix);
				}
			}
		}
	}
}

class GameSender extends PluginTask {
    public $prefix = TE::GRAY . "[" . TE::AQUA . TE::BOLD . "" . TE::RED . "Spleef" . TE::RESET . TE::GRAY . "]";
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
        
        public function getResetmap() {
        Return new ResetMap($this);
        }
  
	public function onRun($tick)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$arenas = $config->get("arenas");
                $money = $config->get("money");
		if(!empty($arenas))
		{
			foreach($arenas as $arena)
			{
				$time = $config->get($arena . "PlayTime");
				$timeToStart = $config->get($arena . "StartTime");
				$levelArena = $this->plugin->getServer()->getLevelByName($arena);
				if($levelArena instanceof Level)
				{
					$playersArena = $levelArena->getPlayers();
					if(count($playersArena)==0)
					{
						$config->set($arena . "PlayTime", 780);
						$config->set($arena . "StartTime", 90);
					}
					else
					{
						if(count($playersArena)>=2)
						{
							if($timeToStart>0)
							{
								$timeToStart--;
								foreach($playersArena as $pl)
								{
									$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
									$lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
									$toUse = $lang->get($playerlang->get($pl->getName()));
									$pl->sendPopup(TE::GREEN . $timeToStart . " " . $toUse["seconds"].TE::RESET);
								}
                                                                if($timeToStart==89)
                                                                {
                                                                    $levelArena->setTime(7000);
                                                                    $levelArena->stopTime();
                                                                }
								if($timeToStart<=0)
								{
									$this->refillChests($levelArena);
                                                                        foreach($playersArena as $pl)
                                                                        {
                                                                            $pl->getInventory()->setContents(array(Item::get(0, 0, 0)));
							                                                $pl->getInventory()->setItem(0, Item::get(Item::DIAMOND_SHOVEL, 0, 1));

		                                                                    $pl->getInventory()->setHotbarSlotIndex(0, 0);	
                                                                           
                                                                        }
								}
								$config->set($arena . "StartTime", $timeToStart);
							}
							else
							{
								$aop = count($levelArena->getPlayers());
								if($aop==1)
								{
									foreach($playersArena as $pl)
									{
										$this->getOwner()->getServer()->broadcastMessage($pl->getNameTag()." §dGano §aSky§bWars §een la arena: §b".$arena);

										$pl->getInventory()->clearAll();
										$pl->removeAllEffects();
										$pl->teleport($this->getOwner()->getServer()->getDefaultLevel()->getSafeSpawn(),0,0);
                                                                                $pl->setHealth(20);
                                                                                $pl->setFood(20);
                                                                                $pl->setNameTag($pl->getName());
                                                                                if(!empty($this->plugin->api))
                                                                                {
                                                                                $this->plugin->api->addMoney($pl,$money);
                                                                                }
                                                                                $this->getResetmap()->reload($levelArena);
                                                                                }
                                                                                $config->set($arena . "PlayTime", 780);
                                                                                $config->set($arena . "StartTime", 90);
								}
                                                                if(($aop>=2))
                                                                    {
                                                                    foreach($playersArena as $pl)
                                                                        {
                                                                        $pl->sendPopup(TE::BOLD.TE::AQUA.$aop.TE::GREEN." Jugadores restantes".TE::RESET);
                                                                        }
                                                                }
								$time--;
								if($time == 779)
								{
                                                                        $slots = new Config($this->plugin->getDataFolder() . "/slots.yml", Config::YAML);
                                                                        $slots->set("slot1".$arena, 0);
                                                                        $slots->set("slot2".$arena, 0);
                                                                        $slots->set("slot3".$arena, 0);
                                                                        $slots->set("slot4".$arena, 0);
                                                                        $slots->set("slot5".$arena, 0);
                                                                        $slots->set("slot6".$arena, 0);
                                                                        $slots->set("slot7".$arena, 0);
                                                                        $slots->set("slot8".$arena, 0);
                                                                        $slots->set("slot9".$arena, 0);
                                                                        $slots->set("slot10".$arena, 0);
                                                                        $slots->set("slot11".$arena, 0);
                                                                        $slots->set("slot12".$arena, 0);
                                                                        $slots->save();
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§7» ------------------------------ «");
                                                                                $pl->sendMessage("§e>§cAtention: §cSpleef has started!");
                                                                                $pl->sendMessage("§e>§cUsed map: §b" . $arena);
                                                                                $pl->sendMessage("§e>§cHave fun!: §bDragonPlayzMC");
                                                                                $pl->sendMessage("§7» ------------------------------ «");
								}

									$this->refillChests($levelArena);
									
								}
								if($time>=300)
								{
								$time2 = $time - 180;
								$minutes = $time2 / 60;
								}
								else
								{
									$minutes = $time / 60;
									if(is_int($minutes) && $minutes>0)
									{
										foreach($playersArena as $pl)
										{
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
                                                                                        $lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($pl->getName()));
											$pl->sendMessage($minutes . " " . $toUse["remainingminutes"]);
										}
									}
									else if($time == 30 || $time == 15 || $time == 10 || $time ==5 || $time ==4 || $time ==3 || $time ==2 || $time ==1)
									{
										foreach($playersArena as $pl)
										{
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
                                                                                        $lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($pl->getName()));
											$pl->sendMessage($time . " " . $toUse["remainingseconds"]);
										}
									}
									if($time <= 0)
									{
										foreach($playersArena as $pl)
										{
											$pl->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn(),0,0);
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
                                                                                        $lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($pl->getName()));
											$pl->sendMessage($toUse["nowinner"].$arena);
											$pl->getInventory()->clearAll();
                                                                                        $pl->removeAllEffects();
                                                                                        $pl->setFood(20);
                                                                                        $pl->setHealth(20);
                                                                                        $pl->setNameTag($pl->getName());
                                                                                        $this->getResetmap()->reload($levelArena);
										}
										$time = 780;
									}
								}
								$config->set($arena . "PlayTime", $time);
							}
						}
						else
						{
							if($timeToStart<=0)
							{
								foreach($playersArena as $pl)
								{
									$this->getOwner()->getServer()->broadcastMessage($pl->getNameTag()." §fWon in: §b".$arena);
									$pl->teleport($this->getOwner()->getServer()->getDefaultLevel()->getSafeSpawn(),0,0);
									$pl->getInventory()->clearAll();
                                                                        $pl->removeAllEffects();
                                                                        $pl->setHealth(20);
                                                                        $pl->setFood(20);
                                                                        $pl->setNameTag($pl->getName());
                                                                        if(!empty($this->plugin->api))
                                                                        {
                                                                        $this->plugin->api->addMoney($pl,$money);
                                                                        }
                                                                        $this->getResetmap()->reload($levelArena);
								}
								$config->set($arena . "PlayTime", 780);
								$config->set($arena . "StartTime", 90);
							}
							else
							{
								foreach($playersArena as $pl)
								{
									$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
									$lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
									$toUse = $lang->get($playerlang->get($pl->getName()));
									$pl->sendPopup(TE::DARK_AQUA . $toUse["moreplayers"].TE::RESET);
								}
								$config->set($arena . "PlayTime", 780);
								$config->set($arena . "StartTime", 90);
							}
						}
					}
				}
			}
		}
		$config->save();
	}
	
	public function refillChests(Level $level)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Chest) 
			{
				$chest = $t;
				$chest->getInventory()->clearAll();
				if($chest->getInventory() instanceof ChestInventory)
				{
					for($i=0;$i<=26;$i++)
					{
						$rand = rand(1,3);
						if($rand==1)
						{
							$k = array_rand($config->get("chestitems"));
							$v = $config->get("chestitems")[$k];
							$chest->getInventory()->setItem($i, Item::get($v[0],$v[1],$v[2]));
						}
					}									
				}
			}
		}
	}
}
