<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Google\CloudFunctions\FunctionsFramework;
use Psr\Http\Message\ServerRequestInterface;
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
    $logger->log(str_repeat("-", 120));
    $logger->log("Query: " . json_encode($query));
    $logger->log("Body: " . json_encode($body));

    $isLocal = CFUtils::isLocalHttp($request);
    $logger->log("Running as " . ($isLocal ? "local" : "cloud") . " mode");

    $lineConfig = Utils::getConfig(__DIR__ . "/configs/line.json");
    $line = new Line($lineConfig["tokens"], $lineConfig["target_ids"]);
    $smarty = new Smarty();
    $smarty->setTemplateDir(__DIR__ . "/templates");
    $smarty->assign("targets", $line->getTargets());
    $smarty->assign("baseUrl", CFUtils::getBaseUrl($isLocal, $request));

    if (empty($body)) {
        $logger->log("GET");
        return $smarty->fetch('form.tpl');
    } else {
        $logger->log("POST");
        if (!array_key_exists("target", $body)) {
            throw new \Exception("target was not passed");
        }
        if (!array_key_exists("message", $body)) {
            throw new \Exception("message was not passed");
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
