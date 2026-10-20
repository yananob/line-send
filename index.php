<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
use Smarty\Smarty;
use MyApp\Logger;
use MyApp\Line;
use MyApp\Utils;
use MyApp\CFUtils;

FunctionsFramework::http('main', 'main');
function main(ServerRequestInterface $request): string
{
    $logger = new Logger("line-sender");
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

    $logger->log(str_repeat("-", 120));
    $logger->log("Query: " . json_encode($query));
    $logger->log("Body: " . json_encode($body));

    $isLocal = CFUtils::isLocalHttp($request);
    $logger->log("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    $lineConfig = Utils::getConfig(__DIR__ . "/configs/line.json");
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
    $smarty = new Smarty();
    $smarty->setTemplateDir(__DIR__ . "/templates");
    $smarty->assign("targets", $line->getTargets());
    $smarty->assign("baseUrl", CFUtils::getBaseUrl($isLocal, $request));

    if (empty($body)) {
        $logger->log("GET");
        return $smarty->fetch('form.tpl');
    } else {
        $logger->log("POST");
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
            $smarty->assign("notification", "Message was successfully sent!");
            return $smarty->fetch('form.tpl');
        } else {
            return "";
        }
    }
}
