<?php

use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/loader.php";

$data = [
    "navActiveIndex" => 0,
    "paneltitle" => '<i class="fas fa-tachometer-alt"></i> Dashboard',
    "panelcontent" => "Welcome to the Admin Panel!"
];

TemplateUtils::i()->renderTemplate("admin/index", $data);
