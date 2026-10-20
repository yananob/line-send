<?php

declare(strict_types=1);

namespace App;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Exception;

class Line
{
    /** @var array<string, string> */
    private array $tokens;

    /** @var array<string, string> */
    private array $targetIds;

    private Client $client;

    /**
     * @param array<string, string> $tokens
     * @param array<string, string> $targetIds
     * @param Client|null $client
     */
    public function __construct(array $tokens, array $targetIds, ?Client $client = null)
    {
        $this->tokens = $tokens;
        $this->targetIds = $targetIds;
        $this->client = $client ?? new Client();
    }

    /**
     * @return array<int, string>
     */
    public function getTargets(): array
    {
        return array_values(array_unique(array_merge(array_keys($this->tokens), array_keys($this->targetIds))));
    }

    public function sendPush(string $bot, string $target, string $message): void
    {
        if (!isset($this->tokens[$bot])) {
            throw new Exception("Token for bot '{$bot}' is not configured.");
        }

        $targetId = $this->targetIds[$target] ?? $target;
        $token = $this->tokens[$bot];

        try {
            $response = $this->client->post('https://api.line.me/v2/bot/message/push', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => [
                    'to' => $targetId,
                    'messages' => [
                        [
                            'type' => 'text',
                            'text' => $message,
                        ],
                    ],
                ],
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new Exception("Failed to send LINE message. Status code: " . $response->getStatusCode());
            }
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response !== null ? $response->getStatusCode() : $e->getCode();
            throw new Exception("Failed to send LINE message. Status code: {$statusCode}", 0, $e);
        } catch (GuzzleException $e) {
            throw new Exception("Failed to send LINE message. Status code: " . $e->getCode(), 0, $e);
        }
    }
}
