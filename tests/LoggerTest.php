<?php

declare(strict_types=1);

namespace MyApp\Tests;

use MyApp\Logger;
use PHPUnit\Framework\TestCase;

class LoggerTest extends TestCase
{
    public function testLogOutputsInfoMessage(): void
    {
        $logger = new Logger("test");

        ob_start();
        $logger->log("Hello Logger");
        $output = ob_get_clean();

        $this->assertStringContainsString("Hello Logger", (string) $output);
    }
}
