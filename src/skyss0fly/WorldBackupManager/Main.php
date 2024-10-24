<?php
namespace skyss0fly\WorldBackupManager;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;

class Main extends PluginBase {

    private $config;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $interval = $this->getConfig()->get("backup-interval", 3600); // Default to 1 hour

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() : void {
            $this->backupWorld(null); // Automatic backup
        }), $interval * 20); // Convert seconds to ticks

        $this->getLogger()->info("WorldBackupManager has been enabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() === "backup") {
            $this->backupWorld($sender);
            return true;
        } elseif ($command->getName() === "restore" && isset($args[0])) {
            $this->restoreBackup($sender, $args[0]); // Restore a specific backup
            return true;
        }
        return false;
    }

    private function backupWorld(?CommandSender $sender): void {
        $worldName = $this->getServer()->getWorldManager()->getDefaultWorld()->getFolderName();
        $backupDir = $this->getDataFolder() . "backups/" . date("Y-m-d_H-i-s") . "/";

        @mkdir($backupDir, 0777, true);
        $this->recurseCopy($this->getServer()->getDataPath() . "worlds/" . $worldName, $backupDir);

        $this->zipBackup($backupDir, $this->getDataFolder() . "backups/" . date("Y-m-d_H-i-s") . ".zip");

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

    private function zipBackup(string $source, string $destination): void {
        if (!extension_loaded('zip') || !file_exists($source)) {
            return;
        }

        $zip = new \ZipArchive();
        if ($zip->open($destination, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::LEAVES_ONLY);

            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($source) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();
        }
    }

    private function restoreBackup(CommandSender $sender, string $backupName): void {
        $backupZip = $this->getDataFolder() . "backups/" . $backupName . ".zip";
        $worldName = $this->getServer()->getWorldManager()->getDefaultWorld()->getFolderName();
        $worldDir = $this->getServer()->getDataPath() . "worlds/" . $worldName . "/";

        // Check if the backup exists
        if (!file_exists($backupZip)) {
            $sender->sendMessage("Backup not found: $backupName");
            return;
        }

        // Unzip the backup
        $zip = new \ZipArchive();
        if ($zip->open($backupZip) === true) {
            $this->recurseDelete($worldDir); // Clear the current world directory
            $zip->extractTo($worldDir); // Extract backup files to the world directory
            $zip->close();
            $sender->sendMessage("World restored from backup: $backupName");
        } else {
            $sender->sendMessage("Failed to open backup: $backupName");
        }
    }

    private function recurseDelete(string $dir): void {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recurseDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}
