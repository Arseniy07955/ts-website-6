<?php

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\TemplateUtils;
use Wruczek\TSWebsite\Utils\Utils;
use Wruczek\TSWebsite\ViewerRenderer;

require_once __DIR__ . "/private/php/load.php";

$newsStore = Utils::getNewsStore();

$perPage = 5; // news per page can be easily changed here, the rest of the code will adapt
$page = 1;    // starting page, if none provided

if (isset($_GET["page"])) {
    $page = (int) $_GET["page"];
}

$newsCount = $newsStore->getNewsCount();
$pageCount = (int) ceil($newsCount / $perPage);
$newsList = [];

// Fetch the news if we are on page 1 or higher
// pages 0 or lower are invalid. Otherwise newsList will be NULL
// and the template will show an invalid page message
if ($page >= 1) {
    try {
        $newsList = $newsStore->getNewsList($perPage, ($page - 1) * $perPage);
    } catch (\Exception $e) {
        $newsList = false;
    }
}

// Who is in which channel right now, shown above the news: the busiest channels first,
// the full picture is one click away on the viewer page
$occupiedChannels = (new ViewerRenderer("img/ts-icons", Config::get("viewer_hidden_channel_ids")))->getOccupiedChannels();
$occupiedTotal = 0;
$onlinePeople = [];

if ($occupiedChannels !== null) {
    $occupiedTotal = count($occupiedChannels);

    // Everyone in a visible channel, for the avatar wall next to the online counter
    foreach ($occupiedChannels as $channel) {
        foreach ($channel["clients"] as $nickname) {
            $onlinePeople[] = ["name" => $nickname, "channel" => $channel["name"]];
        }
    }

    // usort is stable since PHP 8, channels with the same crowd keep their tree order
    usort($occupiedChannels, function (array $a, array $b) {
        return count($b["clients"]) <=> count($a["clients"]);
    });

    $occupiedChannels = array_slice($occupiedChannels, 0, 6);
}

TemplateUtils::i()->renderTemplate("index", [
    "occupiedChannels" => $occupiedChannels,
    "occupiedTotal" => $occupiedTotal,
    "onlinePeople" => $onlinePeople,
    "newsCount" => $newsCount,
    "pageCount" => $pageCount,
    "newsList" => $newsList,
    "currentPage" => $page,
]);
