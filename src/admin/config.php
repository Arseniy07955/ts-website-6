<?php

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\TemplateUtils;

require_once __DIR__ . "/loader.php";

$successMessage = null;
$errorMessage = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        foreach ($_POST as $key => $value) {
            if ($key === "csrf_token") continue;

            // Handle admin_uids specifically as JSON
            if ($key === "admin_uids") {
                $uids = array_map('trim', explode(',', $value));
                Config::i()->setValue($key, $uids);
            } else {
                // Try to preserve types based on existing config
                $existing = Config::get($key);
                if (is_bool($existing)) {
                     // Bools are usually checkboxes, but if not present in POST it means false.
                     // However, since we loop over POST, we only see what's sent.
                     // A better approach is to rely on the form structure, but for now we trust the input.
                     // If it was a checkbox, the browser sends "on" or value.
                     // Let's assume text input for simplicity for now, or careful handling.
                     // The Config::setValue handles type conversion based on input type? No, it uses gettype($value).
                     // Since $_POST values are always strings, we need to cast.
                     $val = $value === "true" || $value === "1" || $value === "on";
                     Config::i()->setValue($key, $val);
                } elseif (is_int($existing)) {
                    Config::i()->setValue($key, (int)$value);
                } elseif (is_array($existing)) {
                     // JSON array
                     $json = json_decode($value, true);
                     if ($json !== null) {
                         Config::i()->setValue($key, $json);
                     } else {
                         // Fallback for simple comma separated lists if JSON fails?
                         // For now, let's just save as string if it was string.
                         // Actually Config::setValue handles array -> JSON.
                         // But we receive string from POST.
                         Config::i()->setValue($key, $json);
                     }
                } else {
                    Config::i()->setValue($key, $value);
                }
            }
        }
        $successMessage = "Configuration updated successfully.";
    } catch (Exception $e) {
        $errorMessage = "Error updating configuration: " . $e->getMessage();
    }
}

$configItems = Config::i()->getConfig();

// Sort keys
ksort($configItems);

$data = [
    "navActiveIndex" => 1,
    "paneltitle" => '<i class="fas fa-cogs"></i> Configuration',
    "configItems" => $configItems,
    "successMessage" => $successMessage,
    "errorMessage" => $errorMessage
];

TemplateUtils::i()->renderTemplate("admin/config", $data);
