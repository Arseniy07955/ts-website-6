<?php

use Wruczek\TSWebsite\AdminStatus;
use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\DatabaseUtils;
use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/private/php/load.php";

$db = DatabaseUtils::i()->getDb();
$qa = $db->select("faq", "*");

// The newest tsw_faq.lastmodify; null when unknown. Nothing in the app moves that column on an
// edit (it defaults to the insert time), so it is shown once for the whole FAQ, not per answer
$updated = null;

if (is_array($qa)) {
    foreach ($qa as $key => $entry) {
        // Questions may hold admin-written HTML; the side index lists them as plain text
        $qa[$key]["questionText"] = trim(html_entity_decode(strip_tags((string) $entry["question"]), ENT_QUOTES | ENT_HTML5, "UTF-8"));

        // The start of the answer as plain text, shown under a closed question. Ends of blocks
        // turn into spaces first, so "<p>One</p><p>Two</p>" does not run together as "OneTwo"
        $answer = preg_replace('#<(script|style)\b[^>]*>.*?</\1\s*>#is', " ", (string) $entry["answer"]);
        $answer = preg_replace('#<(br|/p|/div|/li|/h[1-6]|/tr|/td|/th|/blockquote)\b[^>]*>#i', " ", (string) $answer);
        $answer = html_entity_decode(strip_tags((string) $answer), ENT_QUOTES | ENT_HTML5, "UTF-8");
        $qa[$key]["answerPreview"] = mb_substr(trim((string) preg_replace('/[\s\x{00A0}]+/u', " ", $answer)), 0, 240);

        $modified = isset($entry["lastmodify"]) ? strtotime((string) $entry["lastmodify"]) : false;

        if ($modified !== false && ($updated === null || $modified > $updated)) {
            $updated = $modified;
        }
    }
}

// Staff members on the server right now, shown next to "ask the staff": the ones who can answer
// and, apart from them, the away ones, the way the home page's team list tells them apart.
// null: admin status is off, has no groups, or TeamSpeak could not be reached
$staff = null;
$staffGroups = Config::get("adminstatus_groups");

if (Config::get("adminstatus_enabled") && is_array($staffGroups) && $staffGroups) {
    try {
        $status = AdminStatus::i()->getStatus(
            $staffGroups,
            AdminStatus::STATUS_STYLE_LIST_ONLINE_FIRST,
            true,
            Config::get("adminstatus_ignoredusers")
        );
    } catch (\Exception $e) {
        $status = false;
    }

    if ($status !== false) {
        $people = [];

        // One entry per person, even when they are in several staff groups
        foreach ($status["data"] as $entry) {
            $client = $entry["client"];
            $people[$client["client_database_id"]] = [
                "name" => $client["client_nickname"],
                "away" => !empty($client["client_away"]),
            ];
        }

        $staff = ["online" => [], "away" => []];

        foreach ($people as $person) {
            $staff[$person["away"] ? "away" : "online"][] = $person["name"];
        }
    }
}

$data = [
    "qa" => $qa,
    "updated" => $updated,
    "staff" => $staff,
];

TemplateUtils::i()->renderTemplate("faq", $data);
