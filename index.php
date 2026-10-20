<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\CFUtils;
use App\Line;
use eftec\bladeone\BladeOne;
use Google\CloudFunctions\FunctionsFramework;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;

FunctionsFramework::http('main', 'main');
function main(ServerRequestInterface $request): string
{
    $logger = new Logger('line-sender');
    $logger->pushHandler(new StreamHandler('php://stderr', Level::Info));

    $query = $request->getQueryParams();
    $body = $request->getParsedBody();

    // Body will be parsed as array if form-encoded or JSON with valid middleware, or null/empty
    if (!is_array($body)) {
        $parsedJson = json_decode((string) $request->getBody(), true);
        if (is_array($parsedJson)) {
            $body = $parsedJson;
        } else {
            $body = [];
        }
    }

    $logger->info(str_repeat("-", 120));
    $logger->info("Query: " . json_encode($query));
    $logger->info("Body: " . json_encode($body));

    $isLocal = CFUtils::isLocalHttp($request);
    $logger->info("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    $configPath = __DIR__ . "/configs/line.json";
    if (!file_exists($configPath)) {
        throw new \RuntimeException("Config file not found: {$configPath}");
    }
    $content = file_get_contents($configPath);
    if ($content === false) {
        throw new \RuntimeException("Failed to read config file: {$configPath}");
    }
    /** @var array<string, mixed>|null $lineConfig */
    $lineConfig = json_decode($content, true);
    if (!is_array($lineConfig)) {
        throw new \RuntimeException("Invalid JSON format in config file: {$configPath}");
    }

    if (!isset($lineConfig["tokens"]) || !is_array($lineConfig["tokens"])) {
        throw new \InvalidArgumentException("Config missing or invalid 'tokens' key.");
    }
    if (!isset($lineConfig["target_ids"]) || !is_array($lineConfig["target_ids"])) {
        throw new \InvalidArgumentException("Config missing or invalid 'target_ids' key.");
    }

    /** @var array<string, string> $tokens */
    $tokens = $lineConfig["tokens"];
    /** @var array<string, string> $targetIds */
    $targetIds = $lineConfig["target_ids"];

    $line = new Line($tokens, $targetIds);
    $views = __DIR__ . "/templates";
    $cache = sys_get_temp_dir() . "/blade_cache";
    if (!is_dir($cache)) {
        @mkdir($cache, 0777, true);
    }
    $blade = new BladeOne($views, $cache, BladeOne::MODE_AUTO);

    $baseUrl = CFUtils::getBaseUrl($isLocal, $request);
    $targets = $line->getTargets();

    if (empty($body)) {
        $logger->info("GET");
        return $blade->run('form', [
            'targets' => $targets,
            'baseUrl' => $baseUrl,
            'notification' => null,
        ]);
    } else {
        $logger->info("POST");
        if (!array_key_exists("target", $body) || !is_string($body["target"]) || $body["target"] === '') {
            throw new \InvalidArgumentException("target was not passed or invalid");
        }
        if (!array_key_exists("message", $body) || !is_string($body["message"])) {
            throw new \InvalidArgumentException("message was not passed or invalid");
        }
        $target = $body["target"];
        $message = $body["message"];

        // MEMO: UIのシンプル化のために、botとtargetが同じであることを前提にしている
        $line->sendPush(
            bot: $target,
            target: $target,
            message: $message,
        );

        if (array_key_exists("source", $body) && $body["source"] === "form") {
            return $blade->run('form', [
                'targets' => $targets,
                'baseUrl' => $baseUrl,
                'notification' => "Message was successfully sent!",
            ]);
        } else {
            return "";
        }
    }
}
