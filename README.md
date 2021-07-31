# CustomSound
This plugin will create a resource pack that stores your audio files!

## How to use:
Put your audio files in `plugin_data/CustomSound/sounds` folder. Then restart your server

To play the sound, use: `/playsound <soundName> <target> <volume> <pitch>`

To stop the sound, use: `stopsound <target> <soundName>`

If you have made some changes to the `plugin_data/CustomSound/sounds` folder. Use `/reloadsound` then restart your server.

To add your icon put your icon file (128x128, must have .png file extension) in `plugin_data/CustomSound` folder, then rename it to `pack_icon.png`.

**NOTE!** 
- Only work with `.ogg` audio files
- Sounds can be overridden (with default minecraft sounds too, list: https://minecraft.fandom.com/wiki/Sounds.json#Bedrock_Edition_values)

## API:
Play sound:
```php
/**
 * @param string $soundName
 * @param Player|Player[] $target
 * @param Vector3 $position
 * @param float $volume
 * @param float $pitch
 */
CustomSound::playSound(string $soundName, $target, Vector3 $position, ?float $volume = 1, ?float $pitch = 1)
```

Stop sound:
```php
/**
 * @param Player|Player[] $target
 * @param string $soundName
 * @param bool $stopAll
 */
CustomSound::stopSound($target, string $soundName, ?bool $stopAll = false)
```
