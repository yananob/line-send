<?php

declare(strict_types=1);

namespace MyApp;

use Psr\Http\Message\ServerRequestInterface;

class CFUtils
{
    public static function isLocalHttp(ServerRequestInterface $request): bool
    {
        $serverParams = $request->getServerParams();
        if (isset($serverParams['HTTP_HOST'])) {
            $host = (string) $serverParams['HTTP_HOST'];
            if (str_contains($host, 'localhost') || str_contains($host, '127.0.0.1')) {
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
