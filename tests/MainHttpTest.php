<?php

declare(strict_types=1);

namespace App\Tests;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Utils;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../index.php';

class MainHttpTest extends TestCase
{
    private ?string $originalEnv;

    protected function setUp(): void
    {
        parent::setUp();
        $env = getenv('LINE_TOKENS_N_TARGETS');
        $this->originalEnv = $env !== false ? $env : null;

        $config = [
            'tokens' => [
                'my_bot' => 'test_token',
            ],
            'target_ids' => [
                'my_bot' => 'test_target_id',
            ],
        ];
        putenv('LINE_TOKENS_N_TARGETS=' . json_encode($config));
        $_ENV['LINE_TOKENS_N_TARGETS'] = json_encode($config);
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

    public function testGetRequestRendersForm(): void
    {
        $request = new ServerRequest('GET', 'http://localhost:8080/', [], null, '1.1', [
            'HTTP_HOST' => 'localhost:8080',
        ]);

        $response = main_http($request);

        $this->assertStringContainsString('<title>Line Sender</title>', $response);
        $this->assertStringContainsString('value="my_bot"', $response);
        $this->assertStringContainsString('action="http://localhost:8080"', $response);
    }

    public function testPostRequestValidationFailsWhenTargetMissing(): void
    {
        $request = (new ServerRequest('POST', 'http://localhost:8080/', ['Content-Type' => 'application/json']))
            ->withParsedBody(['message' => 'hello']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('target was not passed or invalid');

        main_http($request);
    }

    public function testPostRequestValidationFailsWhenMessageMissing(): void
    {
        $request = (new ServerRequest('POST', 'http://localhost:8080/', ['Content-Type' => 'application/json']))
            ->withParsedBody(['target' => 'my_bot']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('message was not passed or invalid');

        main_http($request);
    }

    public function testJsonBodyParsedWhenParsedBodyNull(): void
    {
        // When JSON body is passed as raw string in stream and parsedBody is null,
        // it verifies json_decode branch executes.
        // It will fail Line::sendPush because network is not mocked for default client,
        // confirming that body parsing successfully passed target & message to Line::sendPush.
        $stream = Utils::streamFor(json_encode([
            'target' => 'nonexistent_bot',
            'message' => 'hello',
        ]));
        $request = (new ServerRequest('POST', 'http://localhost:8080/'))
            ->withBody($stream);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Token for bot 'nonexistent_bot' is not configured.");

        main_http($request);
    }
}
