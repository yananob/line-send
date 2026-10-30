<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use App\AppConfig;
use App\CFUtils;
use App\Line;
use eftec\bladeone\BladeOne;
use Google\CloudFunctions\FunctionsFramework;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;

FunctionsFramework::http('main_http', 'main_http');

function main_http(ServerRequestInterface $request): string
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

    $lineConfig = AppConfig::getLineConfig();
    $tokens = $lineConfig["tokens"];
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
