<?php

declare(strict_types=1);

namespace App\Tests;

use App\AppConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AppConfigTest extends TestCase
{
    private ?string $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $env = getenv('LINE_TOKENS_N_TARGETS');
        $this->originalEnv = $env !== false ? $env : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalEnv !== null) {
            putenv("LINE_TOKENS_N_TARGETS={$this->originalEnv}");
            $_ENV['LINE_TOKENS_N_TARGETS'] = $this->originalEnv;
        } else {
            putenv('LINE_TOKENS_N_TARGETS');
            unset($_ENV['LINE_TOKENS_N_TARGETS']);
        }
        parent::tearDown();
    }

    public function testGetLineConfigSuccess(): void
    {
        $config = [
            'tokens' => [
                'bot1' => 'token1',
                'bot2' => 'token2',
            ],
            'target_ids' => [
                'bot1' => 'target1',
                'bot2' => 'target2',
            ],
        ];

        putenv('LINE_TOKENS_N_TARGETS=' . json_encode($config));
        $_ENV['LINE_TOKENS_N_TARGETS'] = json_encode($config);

        $result = AppConfig::getLineConfig();

        $this::assertEquals($config['tokens'], $result['tokens']);
        $this::assertEquals($config['target_ids'], $result['target_ids']);
    }

    public function testGetLineConfigNotSetThrowsException(): void
    {
        putenv('LINE_TOKENS_N_TARGETS');
        unset($_ENV['LINE_TOKENS_N_TARGETS']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable LINE_TOKENS_N_TARGETS is not set.');

        AppConfig::getLineConfig();
    }

    public function testGetLineConfigInvalidJsonThrowsException(): void
    {
        putenv('LINE_TOKENS_N_TARGETS={invalid_json');
        $_ENV['LINE_TOKENS_N_TARGETS'] = '{invalid_json';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Environment variable LINE_TOKENS_N_TARGETS contains invalid JSON.');

        AppConfig::getLineConfig();
    }

    public function testGetLineConfigMissingTokensThrowsException(): void
    {
        $config = [
            'target_ids' => ['bot1' => 'target1'],
        ];

        putenv('LINE_TOKENS_N_TARGETS=' . json_encode($config));
        $_ENV['LINE_TOKENS_N_TARGETS'] = json_encode($config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Environment variable LINE_TOKENS_N_TARGETS missing or invalid 'tokens' key.");

        AppConfig::getLineConfig();
    }

    public function testGetLineConfigMissingTargetIdsThrowsException(): void
    {
        $config = [
            'tokens' => ['bot1' => 'token1'],
        ];

        putenv('LINE_TOKENS_N_TARGETS=' . json_encode($config));
        $_ENV['LINE_TOKENS_N_TARGETS'] = json_encode($config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Environment variable LINE_TOKENS_N_TARGETS missing or invalid 'target_ids' key.");

        AppConfig::getLineConfig();
    }
}
