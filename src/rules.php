<?php

use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/private/php/load.php";

$data = [
    "pagetitle" => __get("RULES_TITLE"),
    "navActiveIndex" => 4,
    "paneltitle" => __get("RULES_PANEL_TITLE"),
    // Replace this placeholder with your rules, written in HTML
    "panelcontent" => '<div class="empty-state page-placeholder">' . TemplateUtils::icon("book-open-text") .
        '<p class="empty-state-title">' . __get("RULES_PLACEHOLDER_TITLE") . '</p>' .
        '<p>' . __get("RULES_PLACEHOLDER_TEXT", "<code>rules.php</code>") . '</p></div>'
];

TemplateUtils::i()->renderTemplate("simple-page", $data);
