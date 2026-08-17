<?php

declare(strict_types=1);

namespace MyApp\Tests;

use MyApp\CFUtils;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\TestCase;

class CFUtilsTest extends TestCase
{
    public function testIsLocalHttpWithLocalhost(): void
    {
        $request = new ServerRequest('GET', 'http://localhost:8080/', [], null, '1.1', [
            'HTTP_HOST' => 'localhost:8080'
        ]);

        $this->assertTrue(CFUtils::isLocalHttp($request));
    }

    public function testIsLocalHttpWith127001(): void
    {
        $request = new ServerRequest('GET', 'http://127.0.0.1:8080/', [], null, '1.1', [
            'HTTP_HOST' => '127.0.0.1:8080'
        ]);

        $this->assertTrue(CFUtils::isLocalHttp($request));
    }

    public function testIsLocalHttpWithCloudHost(): void
    {
        $request = new ServerRequest('GET', 'https://example.run.app/', [], null, '1.1', [
            'HTTP_HOST' => 'example.run.app'
        ]);

        $this->assertFalse(CFUtils::isLocalHttp($request));
    }

    public function testGetBaseUrlLocal(): void
    {
        $request = new ServerRequest('GET', 'http://localhost:8080/foo', [], null, '1.1', [
            'HTTP_HOST' => 'localhost:8080'
        ]);

        $this->assertEquals('http://localhost:8080', CFUtils::getBaseUrl(true, $request));
    }

    public function testGetBaseUrlCloud(): void
    {
        $request = (new ServerRequest('GET', 'https://example.run.app/subpath'))
            ->withUri(new Uri('https://example.run.app/subpath'));

        $this->assertEquals('/subpath', CFUtils::getBaseUrl(false, $request));
    }
}
