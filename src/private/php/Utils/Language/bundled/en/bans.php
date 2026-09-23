<?php
/*
 * Ban list (bans.php). Translations stored in the database always win.
 */
return [
    "BANS_INTRO" => "Who is banned from the server, what for and until when.",
    "BANS_LOADING" => "Loading the ban list",
    "BANS_EMPTY_HINT" => "Nobody is banned from the server right now.",
    "BANS_TARGET_UNKNOWN" => "Unknown",

    // The visitor's own IP is banned
    "BANS_BANNED_ALERT_EXPIRES" => "The ban ends {0}.",
    "BANS_BANNED_ALERT_PERMANENT" => "The ban is permanent.",

    // Facts about the whole list, above the table
    "BANS_FACTS_LABEL" => "Ban list in numbers",
    "BANS_FACT_TOTAL" => "Total bans",
    "BANS_FACT_PERMANENT" => "Permanent",
    "BANS_FACT_TEMPORARY" => "Temporary: {0}",
    "BANS_FACT_EXPIRING" => "End within 7 days",
    "BANS_FACT_NEXT" => "Next one ends {0}",
    "BANS_FACT_LATEST" => "Latest ban",

    // Who issued the most of the bans still on the list (lifted bans leave it), next to the facts
    "BANS_STAFF_TITLE" => "Who issued the most bans on the list",
    "BANS_STAFF_COUNT" => "Bans issued: {0}",
    "BANS_STAFF_TIED" => "And {0} more with as many bans",

    // A timed ban whose time is up, but which the server hasn't lifted yet
    "BANS_EXPIRED" => "Ended {0}",

    // Ban types. IP, UID and MyTSID are the same in every language
    "BANS_TYPE_NAME" => "Nickname",
    "BANS_FILTER_LABEL" => "Ban type",
    "BANS_FILTER_ALL" => "All",

    "BANS_SEARCH_LABEL" => "Search bans",
    "BANS_SEARCH_CLEAR" => "Clear search",
    "BANS_SEARCH_EMPTY" => "Nothing matches “{0}”",
    "BANS_SEARCH_EMPTY_HINT" => "Search looks through nicknames, reasons and who issued the ban.",
    "BANS_SEARCH_EMPTY_FILTERED" => "Only bans of the “{0}” type were searched.",
    "BANS_FILTER_RESET" => "Search all bans",
    "BANS_DETAILS" => "Details",
    "BANS_SHOW_DETAILS_FOR" => "Ban details: {0}",

    // DataTables strings. Where this file exists for the visitor's language they replace DataTables' own
    // translation from its CDN; elsewhere they are only used when that translation can't be downloaded.
    // Not a text to translate: the language code of this file, which tells the page the strings are its own
    "BANS_TABLE_LANGUAGE" => "en",
    "BANS_TABLE_INFO" => "Showing _START_ to _END_ of _TOTAL_",
    "BANS_TABLE_INFO_ALL" => "Total bans: _TOTAL_",
    "BANS_TABLE_INFO_MATCHES" => "Matching: _TOTAL_ of _MAX_",
    "BANS_TABLE_INFO_EMPTY" => "Nothing to show",
    "BANS_TABLE_INFO_FILTERED" => "(out of _MAX_)",
    "BANS_TABLE_THOUSANDS" => ",",
    "BANS_TABLE_PREVIOUS" => "Previous page",
    "BANS_TABLE_NEXT" => "Next page",
    "BANS_TABLE_SORT_ASC" => ": sort ascending",
    "BANS_TABLE_SORT_DESC" => ": sort descending",
];
