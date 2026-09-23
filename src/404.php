<?php

use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/private/php/load.php";

http_response_code(404);
TemplateUtils::i()->renderErrorTemplate("404", "Page not found");
