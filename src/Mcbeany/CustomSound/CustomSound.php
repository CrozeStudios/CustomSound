<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */


declare(strict_types=1);

namespace Mcbeany\CustomSound;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\StopSoundPacket;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\UUID;

class CustomSound extends PluginBase
{

    /**
     * @param string $soundName
     * @param Player|Player[] $target
     * @param Vector3 $position
     * @param float $volume
     * @param float $pitch
     */
    public static function playSound(string $soundName, $target, Vector3 $position, ?float $volume = 1, ?float $pitch = 1)
    {
        $target = is_array($target) ? $target : array($target);
        $pk = new PlaySoundPacket();
        $pk->soundName = $soundName;
        $pk->x = $position->x;
        $pk->y = $position->y;
        $pk->z = $position->z;
        $pk->volume = $volume;
        $pk->pitch = $pitch;
        Server::getInstance()->broadcastPacket($target, $pk);
    }

    /**
     * @param Player|Player[] $target
     * @param string $soundName
     * @param bool $stopAll
     */
    public static function stopSound($target, string $soundName, ?bool $stopAll = false)
    {
        $target = is_array($target) ? $target : array($target);
        $pk = new StopSoundPacket();
        $pk->soundName = $soundName;
        $pk->stopAll = $stopAll;
        Server::getInstance()->broadcastPacket($target, $pk);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        switch (strtolower($command->getName())) {
            case "playsound":
                if (count($args) > 1) {
                    $soundName = $args[0];
                    $target = null;
                    if (isset($args[1])) {
                        $target = $this->getServer()->getPlayer($args[1]);
                    } elseif ($sender instanceof Player) {
                        if (!$sender->hasPermission("customsound.commands")) return false;
                        $target = $sender;
                    }
                    if ($target !== null) {
                        $targetName = $target->getName();
                        $volume = isset($args[2]) ? floatval($args[2]) : 1;
                        $pitch = isset($args[3]) ? floatval($args[3]) : 1;
                        self::playSound($soundName, $target, $target, $volume, $pitch);
                        if ($sender instanceof Player) {
                            $sender->sendMessage("Played sound '$soundName' to $targetName");
                        } else {
                            $this->getLogger()->info("Played sound '$soundName' to $targetName");
                        }
                    } else {
                        $str = $args[1];
                        if ($sender instanceof Player) {
                            $sender->sendMessage("Player $str not found!");
                        } else {
                            $this->getLogger()->error("Player $str not found!");
                        }
                    }
                } else {
                    return false;
                }
                break;
            case "stopsound":
                if (count($args) > 1) {
                    $target = $this->getServer()->getPlayer($args[0]);
                    $soundName = $args[1];
                    if ($target !== null) {
                        $targetName = $target->getName();
                        self::stopSound($target, $soundName);
                        if ($sender instanceof Player) {
                            if (!$sender->hasPermission("customsound.commands")) return false;
                            $sender->sendMessage("Stopped sound '$soundName' for $targetName");
                        } else {
                            $this->getLogger()->info("Stopped sound '$soundName' for $targetName");
                        }
                    } else {
                        $str = $args[0];
                        if ($sender instanceof Player) {
                            $sender->sendMessage("Player $str not found!");
                        } else {
                            $this->getLogger()->error("Player $str not found!");
                        }
                    }
                } else {
                    return false;
                }
                break;
            case "reloadsound":
                if (!$sender instanceof Player) {
                    $files = glob($this->getDataFolder() . "sounds/*.ogg");
                    if (!empty($files)) {
                        if (is_file($this->getServer()->getDataPath() . "resource_packs/CustomSound.zip")) {
                            unlink($this->getServer()->getDataPath() . "resource_packs/CustomSound.zip");
                        }
                        $this->create();
                        $this->getLogger()->info("Restart your server!");
                    }
                }
                break;
        }
        return true;
    }

    private function create()
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->getServer()->getDataPath() . "resource_packs/CustomSound.zip", \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
            $protocolInfo = explode(".", ProtocolInfo::MINECRAFT_VERSION_NETWORK);
            $manifest = [
                "format_version" => 2,
                "header" => [
                    "description" => $this->getDescription()->getDescription(),
                    "name" => $this->getDescription()->getName(),
                    "uuid" => UUID::fromRandom()->toString(),
                    "version" => [0, 0, 1],
                    "min_engine_version" => [(int) $protocolInfo[0], (int) $protocolInfo[1], (int) $protocolInfo[2]],
                ],
                "modules" => [
                    [
                        "description" => $this->getDescription()->getDescription(),
                        "type" => "resources",
                        "uuid" => UUID::fromRandom()->toString(),
                        "version" => [0, 0, 1]
                    ]
                ]
            ];
            if (is_file($this->getDataFolder() . "pack_icon.png")) $zip->addFile($this->getDataFolder() . "pack_icon.png", "pack_icon.png");
            $zip->addFromString("manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
            $sound_definitions = [];
            foreach (glob($this->getDataFolder() . "sounds/*.ogg") as $file) {
                $sound = basename($file, ".ogg");
                $zip->addFile($file, "sounds/custom/" . $sound . ".ogg");
                $sound_definitions = array_merge($sound_definitions, [
                    $sound => [
                        "sounds" => [
                            "sounds/custom/" . $sound
                        ]
                    ]
                ]);
            }
            $zip->addFromString("sounds/sound_definitions.json", json_encode($sound_definitions, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
        }
        $zip->close();

        $config = new Config($this->getServer()->getDataPath() . "resource_packs/resource_packs.yml", Config::YAML);
        $config->set("force_resources", true);
        $resource_stack = $config->get("resource_stack", null);
        $pack = "CustomSound.zip";
        if (!isset($pack, $resource_stack)) $resource_stack[] = $pack;
        $config->set("resource_stack", $resource_stack);
        $config->save();
    }

    // Something

    public function onEnable()
    {
        if (!is_dir($this->getDataFolder() . "sounds")) mkdir($this->getDataFolder() . "sounds");
        $files = glob($this->getDataFolder() . "sounds/*.ogg");
        if (!empty($files)) {
            foreach ($files as $file) {
                $this->saveResource($file);
            }
            if (!is_file($this->getServer()->getDataPath() . "resource_packs/CustomSound.zip")) {
                $this->create();
            }
        }
    }

}
