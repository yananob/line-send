<?php

declare(strict_types=1);

namespace MyApp;

use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Level;

class Logger
{
    private MonologLogger $logger;

    public function __construct(string $name = 'app')
    {
        $this->logger = new MonologLogger($name);
        $this->logger->pushHandler(new StreamHandler('php://stdout', Level::Info));
    }

    public function log(string $message): void
    {
        $this->logger->info($message);
    }
}
