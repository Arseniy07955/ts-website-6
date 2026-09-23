<?php

use Wruczek\TSWebsite\CacheManager;
use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\TemplateUtils;
use Wruczek\TSWebsite\ViewerRenderer;

require_once __DIR__ . "/private/php/load.php";

$html = null;
$legend = [];
$facts = null;
$viewerRenderer = new ViewerRenderer("img/ts-icons", Config::get("viewer_hidden_channel_ids"));

if ($viewerRenderer->checkRequiredData()) {
    $html = $viewerRenderer->renderViewer();
    $legend = $viewerRenderer->getLegend();

    // Facts above the tree. js/status.js keeps the server numbers live; these are the same
    // numbers from the cache, so the cells are filled before the first status request returns
    $serverInfo = CacheManager::i()->getServerInfo();
    $occupiedChannels = $viewerRenderer->getOccupiedChannels() ?? [];
    $busiest = null;

    // The first of the channels with the most people, in tree order
    foreach ($occupiedChannels as $channel) {
        if ($busiest === null || count($channel["clients"]) > count($busiest["clients"])) {
            $busiest = $channel;
        }
    }

    $facts = [
        "occupied" => count($occupiedChannels),
        "busiest" => $busiest,
        "version" => null,
        "online" => null,
        "maxClients" => null,
        "onlineRecord" => (int) Config::get("onlinerecord_value"),
        "channelCount" => count(CacheManager::i()->getChannelList()),
    ];

    if ($serverInfo !== null) {
        $online = (int) (string) $serverInfo["virtualserver_clientsonline"] - (int) (string) $serverInfo["virtualserver_queryclientsonline"];

        $facts["version"] = (string) \TeamSpeak3_Helper_Convert::versionShort($serverInfo["virtualserver_version"]);
        $facts["online"] = $online;
        $facts["maxClients"] = (int) (string) $serverInfo["virtualserver_maxclients"];
        // The same rule as api/getstatus.php: the record is at least what is online now
        $facts["onlineRecord"] = max($facts["onlineRecord"], $online);
        $facts["channelCount"] = (int) (string) $serverInfo["virtualserver_channelsonline"];
    }
}

TemplateUtils::i()->renderTemplate("viewer", [
    "html" => $html,
    "legend" => $legend,
    "facts" => $facts,
]);
