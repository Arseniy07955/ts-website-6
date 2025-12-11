<?php

use Wruczek\TSWebsite\Auth;
use Wruczek\TSWebsite\Config;

require_once __DIR__ . "/../private/php/load.php";

if (!Auth::isAdmin()) {
    header("Location: ../index.php");
    exit;
}
