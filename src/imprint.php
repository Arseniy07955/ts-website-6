<?php

use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/private/php/load.php";

$data = [
    "pagetitle" => __get("FOOTER_IMPRINT"),
    "paneltitle" => __get("FOOTER_IMPRINT"),
    // Replace this placeholder with your imprint, written in HTML
    "panelcontent" => '<div class="empty-state page-placeholder">' . TemplateUtils::icon("identification-card") .
        '<p class="empty-state-title">' . __get("IMPRINT_PLACEHOLDER_TITLE") . '</p>' .
        '<p>' . __get("IMPRINT_PLACEHOLDER_TEXT", ["<code>imprint.php</code>", "<code>imprint_enabled</code>"]) . '</p></div>'
];

TemplateUtils::i()->renderTemplate("simple-page", $data);
