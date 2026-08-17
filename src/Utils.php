<?php

declare(strict_types=1);

namespace MyApp;

use Exception;

class Utils
{
    /**
     * @return array<string, mixed>
     */
    public static function getConfig(string $filepath): array
    {
        if (!file_exists($filepath)) {
            throw new Exception("Config file not found: {$filepath}");
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            throw new Exception("Failed to read config file: {$filepath}");
        }

        /** @var array<string, mixed>|null $config */
        $config = json_decode($content, true);
        if (!is_array($config)) {
            throw new Exception("Invalid JSON format in config file: {$filepath}");
        }

        return $config;
    }
}
