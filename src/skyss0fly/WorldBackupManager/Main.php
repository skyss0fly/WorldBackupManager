<?php
namespace WorldBackupManager;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;

class Main extends PluginBase {

    private $config;

    public function onEnable(): void {
        $this->getLogger()->info("WorldBackupManager has been enabled!");
        $this->saveDefaultConfig(); // Saves the config.yml file
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "backup") {
            $this->backupWorld($sender);
            return true;
        }
        return false;
    }

    private function backupWorld(CommandSender $sender): void {
        // Implement the world backup logic here
        $sender->sendMessage("World backup started...");
    }
}
