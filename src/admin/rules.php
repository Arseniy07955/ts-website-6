<?php

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/loader.php";

$successMessage = null;
$errorMessage = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        if (isset($_POST['rules_content'])) {
            Config::i()->setValue("rules_content", $_POST['rules_content']);
            $successMessage = "Rules updated successfully.";
        }
    } catch (Exception $e) {
        $errorMessage = "Error updating rules: " . $e->getMessage();
    }
}

$rulesContent = Config::get("rules_content", "Rules in <b>HTML</b>");

$data = [
    "navActiveIndex" => 4,
    "paneltitle" => '<i class="fas fa-book"></i> Rules Management',
    "rulesContent" => $rulesContent,
    "successMessage" => $successMessage,
    "errorMessage" => $errorMessage
];

TemplateUtils::i()->renderTemplate("admin/rules", $data);
