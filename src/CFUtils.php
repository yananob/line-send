<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\ServerRequestInterface;

class CFUtils
{
    public static function isLocalHttp(ServerRequestInterface $request): bool
    {
        $serverParams = $request->getServerParams();
        $host = '';
        if (isset($serverParams['HTTP_HOST']) && is_string($serverParams['HTTP_HOST'])) {
            $host = $serverParams['HTTP_HOST'];
        } elseif (isset($serverParams['SERVER_NAME']) && is_string($serverParams['SERVER_NAME'])) {
            $host = $serverParams['SERVER_NAME'];
        } else {
            $uriHost = $request->getUri()->getHost();
            if ($uriHost !== '') {
                $host = $uriHost;
            }
        }

        if ($host !== '') {
            $hostOnly = explode(':', $host)[0];
            if (
                str_contains($host, 'localhost') ||
                str_contains($host, '127.0.0.1') ||
                $hostOnly === '::1' ||
                str_contains($host, '[::1]')
            ) {
                return true;
            }
        }

        return false;
    }

    public static function getBaseUrl(bool $isLocal, ServerRequestInterface $request): string
    {
        if ($isLocal) {
            $serverParams = $request->getServerParams();
            $host = $serverParams['HTTP_HOST'] ?? 'localhost:8080';
            return 'http://' . $host;
        }

        return (string) $request->getUri()->getPath();
    }
}
