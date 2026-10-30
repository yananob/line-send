<?php

declare(strict_types=1);

namespace App;

use RuntimeException;
use InvalidArgumentException;

class AppConfig
{
    /**
     * @return array{tokens: array<string, string>, target_ids: array<string, string>}
     */
    public static function getLineConfig(): array
    {
        $env = getenv('LINE_TOKENS_N_TARGETS');
        if ($env === false || $env === '') {
            if (isset($_ENV['LINE_TOKENS_N_TARGETS']) && is_string($_ENV['LINE_TOKENS_N_TARGETS']) && $_ENV['LINE_TOKENS_N_TARGETS'] !== '') {
                $env = $_ENV['LINE_TOKENS_N_TARGETS'];
            }
        }

        if ($env === false || $env === '' || !is_string($env)) {
            throw new RuntimeException("Environment variable LINE_TOKENS_N_TARGETS is not set.");
        }

        $lineConfig = json_decode($env, true);
        if (!is_array($lineConfig)) {
            throw new RuntimeException("Environment variable LINE_TOKENS_N_TARGETS contains invalid JSON.");
        }

        if (!isset($lineConfig['tokens']) || !is_array($lineConfig['tokens'])) {
            throw new InvalidArgumentException("Environment variable LINE_TOKENS_N_TARGETS missing or invalid 'tokens' key.");
        }

        if (!isset($lineConfig['target_ids']) || !is_array($lineConfig['target_ids'])) {
            throw new InvalidArgumentException("Environment variable LINE_TOKENS_N_TARGETS missing or invalid 'target_ids' key.");
        }

        /** @var array<string, string> $tokens */
        $tokens = $lineConfig['tokens'];
        /** @var array<string, string> $targetIds */
        $targetIds = $lineConfig['target_ids'];

        return [
            'tokens' => $tokens,
            'target_ids' => $targetIds,
        ];
    }
}
