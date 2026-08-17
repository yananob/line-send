<?php

declare(strict_types=1);

namespace MyApp\Tests;

use MyApp\Line;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use Exception;
use PHPUnit\Framework\TestCase;

class LineTest extends TestCase
{
    public function testGetTargets(): void
    {
        $tokens = ['bot1' => 'token1', 'bot2' => 'token2'];
        $targetIds = ['bot2' => 'id2', 'bot3' => 'id3'];

        $line = new Line($tokens, $targetIds);
        $targets = $line->getTargets();

        sort($targets);
        $this->assertEquals(['bot1', 'bot2', 'bot3'], $targets);
    }

    public function testSendPushSuccess(): void
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], json_encode(['message' => 'ok'])),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);
        $client = new Client(['handler' => $handlerStack]);

        $tokens = ['my_bot' => 'sample_token'];
        $targetIds = ['my_bot' => 'sample_target_id'];

        $line = new Line($tokens, $targetIds, $client);
        $line->sendPush('my_bot', 'my_bot', 'Test message');

        $this->assertCount(1, $container);
        $request = $container[0]['request'];

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('https://api.line.me/v2/bot/message/push', (string) $request->getUri());
        $this->assertEquals('Bearer sample_token', $request->getHeaderLine('Authorization'));

        $body = json_decode((string) $request->getBody(), true);
        $this->assertEquals([
            'to' => 'sample_target_id',
            'messages' => [
                [
                    'type' => 'text',
                    'text' => 'Test message',
                ],
            ],
        ], $body);
    }

    public function testSendPushMissingTokenThrowsException(): void
    {
        $line = new Line([], []);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Token for bot 'unknown_bot' is not configured.");

        $line->sendPush('unknown_bot', 'unknown_bot', 'hello');
    }

    public function testSendPushApiErrorThrowsException(): void
    {
        $mock = new MockHandler([
            new Response(400, [], json_encode(['error' => 'bad request'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $line = new Line(['bot' => 'token'], ['bot' => 'id'], $client);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Failed to send LINE message. Status code: 400");

        $line->sendPush('bot', 'bot', 'hello');
    }
}
