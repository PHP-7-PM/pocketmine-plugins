<?php
/**
 ** OVERVIEW:Basic Usage
 **
 ** COMMANDS
 **
 ** * unbreakable|breakable: Control blocks that can/cannot be broken
 **   usage: /wp  _[world]_ **breakable|unbreakable** _[block-ids]_
 **   Manages which blocks can or can not be broken in a given world.
 **   You can get a list of blocks currently set to `unbreakable`
 **   if you do not specify any _[block-ids]_.  Otherwise these are
 **   added or removed from the list.
 **/
namespace aliuly\worldprotect;

use pocketmine\plugin\PluginBase as Plugin;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class Unbreakable extends BaseWp implements Listener {
	public function __construct(Plugin $plugin) {
		parent::__construct($plugin);
		$this->owner->getServer()->getPluginManager()->registerEvents($this, $this->owner);
		$this->enableSCmd("unbreakable",["usage" => "[id] [id]",
													"help" => "Set block to unbreakable status",
													"permission" => "wp.cmd.unbreakable",
													"aliases" => ["ubab"]]);
		$this->enableSCmd("breakable",["usage" => "[id] [id]",
												 "help" => "Remove unbreakable status from block",
												 "permission" => "wp.cmd.unbreakable",
												 "aliases" => ["bab"]]);
	}

	public function onSCommand(CommandSender $c,Command $cc,$scmd,$world,array $args) {
		if ($scmd != "breakable" && $scmd != "unbreakable") return false;
		if (count($args) == 0) {
			$ids = $this->owner->getCfg($world, "unbreakable", []);
			if (count($ids) == 0) {
				$c->sendMessage("[WP] No unbreakable blocks in $world");
			} else {
				$ln  = "[WP] Blocks(".count($ids)."):";
				$q = "";
				foreach ($ids as $id=>$n) {
					$ln .= "$q $n($id)";
					$q = ",";
				}
				$c->sendMessage($ln);
			}
			return true;
		}
		$cc = 0;
		$ids = $this->owner->getCfg($world, "unbreakable", []);
		if ($scmd == "breakable") {
			foreach ($args as $i) {
				$item = Item::fromString($i);
				if (isset($ids[$item->getId()])) {
					unset($ids[$item->getId()]);
					++$cc;
				}
			}
		} elseif ($scmd == "unbreakable") {
			foreach ($args as $i) {
				$item = Item::fromString($i);
				if (isset($ids[$item->getId()])) continue;
				$ids[$item->getId()] = $this->itemName($item);
				++$cc;
			}
		} else {
			return false;
		}
		if (!$cc) {
			$c->sendMessage("No blocks updated");
			return true;
		}
		if (count($ids)) {
			$this->owner->setCfg($world,"unbreakable",$ids);
		} else {
			$this->owner->unsetCfg($world,"unbreakable");
		}
		$c->sendMessage("Blocks changed: $cc");
		return true;
	}
	public function onBlockBreak(BlockBreakEvent $ev){
		if ($ev->isCancelled()) return;
		$bl = $ev->getBlock();
		$world = $bl->getLevel()->getName();
		if (!isset($this->wcfg[$world])) return;
		if (!isset($this->wcfg[$world][$bl->getId()])) return;
		$pl = $ev->getPlayer();
		$pl->sendMessage("It can not be broken!");
		$ev->setCancelled();
	}
}
