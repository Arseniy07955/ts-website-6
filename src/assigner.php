<?php

use Wruczek\TSWebsite\Assigner;
use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\CacheManager;
use Wruczek\TSWebsite\Utils\TeamSpeakUtils;
use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/private/php/load.php";

// The template gets the same variables in every state. Without the TeamSpeak
// server nothing on this page can work, logging in included, so an outage
// shows the data problem instead of the login prompt
$data = [
    "tsAvailable" => TeamSpeakUtils::i()->checkTSConnection(),
    "isLoggedIn" => Auth::isLoggedIn(),
    "nickname" => Auth::getNickname(),
    "cldbid" => Auth::getCldbid(),
    "canUseAssigner" => false,
    "cooldownRemaining" => 0,
    "cooldownSeconds" => (int) Assigner::getCooldownSeconds(),
    "cooldownText" => null,
    // Categories with the groups that exist on the server: the form for a visitor who
    // can use it, a read-only list for everyone else. null = could not load, [] = not configured
    "assignerConfig" => null,
    "catalog" => null,
    // The visitor's own server groups as the server has them now (logged in only)
    "userGroups" => null,
    // Names of the groups that unlock the assigner (shown when the visitor has none of them)
    "requiredGroups" => [],
    // sgid => [cldbid => nickname] of the people online in every assignable group,
    // null when the server did not answer
    "groupMembers" => null,
    "membersTotal" => null,
];

// "5 min", "1 h 30 min": the two largest units of a duration
$formatDuration = function (int $seconds): string {
    $units = [[86400, "UNIT_DAYS_SHORT"], [3600, "UNIT_HOURS_SHORT"], [60, "UNIT_MINUTES_SHORT"], [1, "ASSIGNER_UNIT_SECONDS"]];
    $parts = [];

    foreach ($units as list($size, $key)) {
        if ($seconds >= $size && count($parts) < 2) {
            $parts[] = intdiv($seconds, $size) . __get($key);
            $seconds %= $size;
        }
    }

    return implode(" ", $parts);
};

if ($data["cooldownSeconds"] > 0) {
    $data["cooldownText"] = $formatDuration($data["cooldownSeconds"]);
}

if ($data["tsAvailable"]) {
    try {
        if ($data["isLoggedIn"]) {
            $data["canUseAssigner"] = Assigner::canUseAssigner();
            $data["cooldownRemaining"] = Assigner::getCooldownSecondsRemaining();

            if (isset($_POST["assigner"]) && $data["canUseAssigner"] && $data["cooldownRemaining"] <= 0) {
                $groups = array_keys($_POST["assigner"]); // get all group ids
                $groups = array_filter($groups, "is_int"); // only keep integers
                $data["groupChangeStatus"] = Assigner::changeGroups($groups);

                if ($data["groupChangeStatus"] === 0) {
                    // if groups have been successfully updated,
                    // invalidate the cache and update last use time
                    Auth::invalidateUserGroupCache();
                    Assigner::updateLastUseTime();
                    // refresh cooldown time after group change
                    $data["cooldownRemaining"] = Assigner::getCooldownSecondsRemaining();
                    // the online counts below include this visitor
                    CacheManager::i()->clearClientList();
                }
            }
        }

        $serverGroups = CacheManager::i()->getServerGroupList();

        // A failed request caches the server group list as null, which would
        // otherwise render every category without a single group
        if ($serverGroups !== null) {
            if ($data["isLoggedIn"]) {
                // A category whose groups no longer exist on the server has nothing to pick.
                // With none left the page says the assigner is not configured instead of
                // showing an empty form
                $data["assignerConfig"] = array_values(array_filter(Assigner::getAssignerArray(), function ($category) {
                    return !empty($category["groups"]);
                }));
                $data["catalog"] = $data["assignerConfig"];

                $userGroupIds = Auth::getUserServerGroupIds();
                $data["userGroups"] = array_values(array_filter($serverGroups, function ($group) use ($userGroupIds) {
                    return in_array($group["sgid"], $userGroupIds);
                }));

                foreach (Assigner::getRequiredSgids() as $sgid) {
                    if (isset($serverGroups[$sgid])) {
                        $data["requiredGroups"][] = (string) $serverGroups[$sgid]["name"];
                    }
                }
            } else {
                // Logged out: the same categories without the "assigned" state
                $data["catalog"] = [];

                foreach (Assigner::getAssignerConfig() ?: [] as $category) {
                    $groups = [];

                    foreach ($category["groups"] as $sgid) {
                        if (isset($serverGroups[$sgid])) {
                            $groups[$sgid] = $serverGroups[$sgid];
                        }
                    }

                    if ($groups) {
                        $category["groups"] = $groups;
                        $data["catalog"][] = $category;
                    }
                }
            }
        }

        if ($data["catalog"]) {
            $sgids = [];

            foreach ($data["catalog"] as $category) {
                $sgids = array_merge($sgids, array_keys($category["groups"]));
            }

            // Who is online in each group, from the client list the viewer and the home page
            // already cache: no query per group, however big the catalog is. Only decoration
            // for the lists, so without it the page works as before
            $clients = CacheManager::i()->getClientList();

            if ($clients !== null) {
                $data["groupMembers"] = array_fill_keys($sgids, []);
                $everyone = [];

                foreach ($clients as $client) {
                    // ServerQuery connections, the website's own included
                    if ($client["client_type"]) {
                        continue;
                    }

                    $cldbid = (int) $client["client_database_id"];

                    foreach (explode(",", (string) $client["client_servergroups"]) as $sgid) {
                        $sgid = (int) $sgid;

                        if (isset($data["groupMembers"][$sgid])) {
                            $data["groupMembers"][$sgid][$cldbid] = (string) $client["client_nickname"];
                            $everyone[$cldbid] = true;
                        }
                    }
                }

                $data["membersTotal"] = count($everyone);
            }
        }
    } catch (\TeamSpeak3_Exception $e) {
        // assignerConfig stays null: the page shows the data problem
    }
}

TemplateUtils::i()->renderTemplate("assigner", $data);
