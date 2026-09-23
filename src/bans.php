<?php

use Latte\Runtime\Html;
use Wruczek\TSWebsite\CacheManager;
use Wruczek\TSWebsite\Utils\TemplateUtils;
use Wruczek\TSWebsite\Utils\Utils;

require_once __DIR__ . "/private/php/load.php";

$banlist = CacheManager::i()->getBanList();
$data = null;
$ipbanned = false;
$facts = null;
$typeCounts = null;
$typeLabels = null;
$staff = null;
// How many more people issued as many bans as the last one in $staff
$staffTied = 0;

if ($banlist !== null) {
    $data = [];
    $staff = [];
    $now = time();
    $typeCounts = ["ip" => 0, "uid" => 0, "name" => 0, "mytsid" => 0];
    $facts = [
        "total" => 0,
        "permanent" => 0,
        "temporary" => 0,
        // Temporary bans that end within the next 7 days, and the soonest end of any ban still running
        "expiringSoon" => 0,
        "nextExpiry" => null,
        // The most recent ban: when, and the name it is shown under
        "latest" => null,
        "latestName" => null,
    ];
    $typeLabels = [
        "ip" => "IP",
        "uid" => "UID",
        "name" => __get("BANS_TYPE_NAME"),
        "mytsid" => "MyTSID"
    ];
    $idIcons = ["ip" => "globe-simple", "uid" => "identification-card", "mytsid" => "user-circle"];

    foreach ($banlist as $ban) {
        // Bans abbreviations:
        // if we see a UID, IP or MyTSID ban, and we know
        // the nickname of the banned user, we will show
        // the user's name and then the type of ban
        // that should be enough info for most users.
        // it is possible to hover over the ban type to
        // view the exact ban target
        //
        // for example, Wruczek got banned on his UID. we know that
        // his last nickname was "Wruczek", so we simply show, that
        // the ban is issued for:
        // Wruczek (UID)
        // after hovering over the "UID", you will see the exact UID
        //
        // if we dont know the last name of the banned user, we
        // will just show the UID, IP or MyTSID

        $lastNickname = null;
        // ip, uid, name or mytsid; empty when the ban has no target we know how to show
        $type = "";
        // The exact value the ban is issued for (a censored IP)
        $value = null;

        if ($ban["lastnickname"] !== null && (string) $ban["lastnickname"] !== "") {
            $lastNickname = (string) $ban["lastnickname"];
        }

        if ($ban["ip"]) {
            $type = "ip";
            $ip = str_replace("\\", "", (string) $ban["ip"]);

            try {
                $value = Utils::censorIpAddress($ip);
            } catch (\Exception $e) {
                $value = "error"; // if not an IP - should not happen
            }

            if ($ip === Utils::getClientIp()) {
                // Invoker and reason go into translations, which the template prints as HTML
                $ipbanned = [
                    "invoker" => Utils::escape((string) $ban["invokername"]),
                    "reason" => Utils::escape((string) $ban["reason"]),
                    "created" => (int) (string) $ban["created"],
                    "duration" => (int) (string) $ban["duration"]
                ];
            }
        } else if ($ban["uid"]) {
            $type = "uid";
            $value = (string) $ban["uid"];
        } else if ($ban["name"]) {
            // A name ban is issued for the name itself (a pattern), there is no nickname to add
            $type = "name";
            $value = (string) $ban["name"];
            $lastNickname = null;
        } else if (!empty($ban["mytsid"])) { // empty, older TS servers dont have MYTS bans, so the key might not exist
            $type = "mytsid";
            $value = (string) $ban["mytsid"];
        }

        if ($lastNickname !== null && $value !== null) {
            // The ban type is quiet text under the nickname, its tooltip holds the exact value. The tooltip
            // opens below it, so it doesn't cover the name it is about
            $html = '<span class="ban-name">%s</span><span class="ban-type" tabindex="0" data-toggle="tooltip" data-placement="bottom" data-custom-class="ban-tooltip" title="%s">%s</span>';
            $name = $lastNickname;
            $avatar = TemplateUtils::avatar($lastNickname, "avatar-lg");
            $target = sprintf($html, Utils::escape($lastNickname), Utils::escape($value), Utils::escape($typeLabels[$type]));

            // make sure that the "full" data is also searchable in DataTables
            $filter = "$value $lastNickname";
        } else if ($value !== null) {
            // Addresses and IDs are real data and get the mono face, a name ban is shown as the name itself
            $name = $value;
            $avatar = $type === "name" ? TemplateUtils::avatar($value, "avatar-lg") : null;
            $target = '<span class="ban-name' . ($type !== "name" ? ' ban-id' : '') . '">' . Utils::escape($value) . '</span>' .
                '<span class="ban-type">' . Utils::escape($typeLabels[$type]) . '</span>';

            // data-filter replaces the cell text in DataTables' search, so it must never be empty
            $filter = $value;
        } else {
            $name = __get("BANS_TARGET_UNKNOWN");
            $avatar = null;
            $target = '<span class="ban-name is-unknown">' . Utils::escape($name) . '</span>';
            $filter = $name;
        }

        // Addresses and IDs have no letters worth a monogram: a quiet square with the kind of ID instead
        if ($avatar === null) {
            $avatar = '<span class="avatar avatar-lg ban-avatar-id" aria-hidden="true">' . TemplateUtils::icon($idIcons[$type] ?? "question") . '</span>';
        }

        $created = (int) (string) $ban["created"];
        $duration = (int) (string) $ban["duration"];
        $expires = $created + $duration;

        $facts["total"]++;

        if ($type !== "") {
            $typeCounts[$type]++;
        }

        if ($duration) {
            $facts["temporary"]++;

            if ($expires >= $now) {
                if ($expires <= $now + 7 * 86400) {
                    $facts["expiringSoon"]++;
                }

                if ($facts["nextExpiry"] === null || $expires < $facts["nextExpiry"]) {
                    $facts["nextExpiry"] = $expires;
                }
            }
        } else {
            $facts["permanent"]++;
        }

        if ($facts["latest"] === null || $created > $facts["latest"]) {
            $facts["latest"] = $created;
            $facts["latestName"] = $name;
        }

        // Who issued it, for the list of who issued the most bans on the list. Bans without an invoker are left out
        $invoker = (string) $ban["invokername"];

        if ($invoker !== "") {
            if (!isset($staff[$invoker])) {
                $staff[$invoker] = ["name" => $invoker, "count" => 0];
            }

            $staff[$invoker]["count"]++;
        }

        $data[] = [
            "type" => $type,
            "filter" => $filter,
            "target" => new Html($target),
            // Names the row's details button. Translations print their arguments as HTML
            "label" => Utils::escape($name),
            "avatar" => new Html($avatar),
            "reason" => (string) $ban["reason"],
            "invoker" => (string) $ban["invokername"],
            "created" => $created,
            "duration" => $duration,
            "expires" => $expires,
            "expired" => $duration && $expires < $now
        ];
    }

    if (!$data) {
        $facts = null;
    }

    // Most bans first. Equal counts are equal: they go by name, and whoever ties with the last
    // one shown is counted in $staffTied rather than left out without a word
    usort($staff, function ($a, $b) {
        return [$b["count"], mb_strtolower($a["name"])] <=> [$a["count"], mb_strtolower($b["name"])];
    });

    $staffShown = 3;

    for ($i = $staffShown; $i < count($staff) && $staff[$i]["count"] === $staff[$staffShown - 1]["count"]; $i++) {
        $staffTied++;
    }

    $staff = array_slice($staff, 0, $staffShown);
}

TemplateUtils::i()->renderTemplate("bans", [
    "banlist" => $data,
    "ipbanned" => $ipbanned,
    "facts" => $facts,
    "typeCounts" => $typeCounts,
    "typeLabels" => $typeLabels,
    "staff" => $staff,
    "staffTied" => $staffTied
]);
