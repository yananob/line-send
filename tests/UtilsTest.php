<?php

declare(strict_types=1);

namespace MyApp\Tests;

use MyApp\Utils;
use Exception;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testGetConfigValidFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tempFile, json_encode(['foo' => 'bar']));

        $config = Utils::getConfig($tempFile);
        unlink($tempFile);

        $this->assertEquals(['foo' => 'bar'], $config);
    }

    public function testGetConfigFileNotFound(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Config file not found");
        Utils::getConfig('/non/existent/path/config.json');
    }

    public function testGetConfigInvalidJson(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'cfg');
        file_put_contents($tempFile, "invalid json");

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid JSON format");

        try {
            Utils::getConfig($tempFile);
        } finally {
            unlink($tempFile);
        }
    }
}
