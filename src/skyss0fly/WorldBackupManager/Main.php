<?php
namespace WorldBackupManager;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosingTask;

class Main extends PluginBase {

    private $config;

    public function onEnable(): void {
    // Save the default config if not already saved
    $this->saveDefaultConfig();

    // Retrieve the backup interval from config (in seconds)
    $interval = $this->getConfig()->get("backup-interval", 3600); // Default to 1 hour

    // Schedule the task to repeat based on the interval
    $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void {
        $this->backupWorld(null); // Pass null to indicate it's an automated backup
    }), $interval * 20); // Convert seconds to ticks (20 ticks = 1 second)
    
    $this->getLogger()->info("WorldBackupManager has been enabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "backup") {
            $this->backupWorld($sender);
            return true;
        }
        return false;
    }

    private function backupWorld(?CommandSender $sender): void {
    $worldName = $this->getServer()->getWorldManager()->getDefaultWorld()->getFolderName();
    $backupDir = $this->getDataFolder() . "backups/" . date("Y-m-d_H-i-s") . "/";

    @mkdir($backupDir, 0777, true);
    $this->recurseCopy($this->getServer()->getDataPath() . "worlds/" . $worldName, $backupDir);

    if ($sender !== null) {
        $sender->sendMessage("World backup completed!");
    }
}

private function recurseCopy(string $src, string $dst): void {
    $dir = opendir($src);
    @mkdir($dst);
    while (($file = readdir($dir)) !== false) {
        if (($file !== '.') && ($file !== '..')) {
            if (is_dir($src . '/' . $file)) {
                $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
            } else {
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}
}
