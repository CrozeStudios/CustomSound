# CustomSound
This plugin will create a resource pack that stores your audio files!

## How to use:
Put your audio files in `plugin_data/sounds` folder. The restart your server

To play the sound, use: `/playsound <soundName> <target> <volume> <pitch>`

To stop the sound, use: `stopsound <target> <soundName>`

**NOTE!** 
- Only work with `.ogg` audio files
- Sounds can be overridden with default minecraft sounds

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
    public static function playSound(string $soundName, $target, Vector3 $position, ?float $volume = 1, ?float $pitch = 1)
```

Stop sound:
```php
    /**
     * @param Player|Player[] $target
     * @param string $soundName
     * @param bool $stopAll
     */
    public static function stopSound($target, string $soundName, ?bool $stopAll = false)
```
