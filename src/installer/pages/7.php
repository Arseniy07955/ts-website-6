<?php
if(!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\TeamSpeakUtils;

$installerLockMessage =
    "After initial installation, this file prevents someone from running it again. " .
    "Deleting it will allow you to re-install TS-website.";

if(file_put_contents(__INSTALLER_LOCK_FILE, $installerLockMessage) === false) {
    die("Cannot write to <code>private/INSTALLER_LOCK</code>! Please check the file/directory permissions");
}

// If we are allowed to collect metrics
if(!empty($_COOKIE["tsw_allow_metrics"])) {
    setcookie("tsw_allow_metrics", "false", 1); // remove the cookie

    $data = [
        "tswVersion" => __TSWEBSITE_VERSION,
        "tswCommit" => __TSWEBSITE_COMMIT,
        "phpVersion" => PHP_VERSION,
        "os" => sprintf("%s %s %s %s", php_uname("s"), php_uname("r"), php_uname("v"), php_uname("m")), // no hostname
        "webServer" => $_SERVER["SERVER_SOFTWARE"],
        "loadedExtensions" => get_loaded_extensions()
    ];

    // Os details
    {
        $lsb = shell_exec('lsb_release -a | grep "Description"');

        if (strpos($lsb, "Description:") !== false) {
            // Split string by ":", get the 2nd part and trim the string
            // "Description:    Ubuntu 18.04.1 LTS" --> "Ubuntu 18.04.1 LTS"
            $osversion = trim(explode(":", $lsb, 2)[1]);
            $data["osDetails"] = $osversion;
        }
    }

    // TS info
    {
        try {
            require_once __DIR__ . "/../../private/vendor/autoload.php";
            $tsNode = TeamSpeakUtils::i()->getTSNodeHost();
            $tsAdmin = TeamSpeakUtils::i()->getTSNodeServer();

            $tsInfo = $tsAdmin->getInfo();

            $data["ts"] = [
                "version" => (string) $tsInfo["virtualserver_version"],
                "platform" => (string) $tsInfo["virtualserver_platform"],
                "slotCount" => $tsInfo["virtualserver_maxclients"],
                "usingServeradmin" => $tsNode->whoami()["client_unique_identifier"] == "serveradmin"
            ];
        } catch (\Exception $e) {}
    }

    // Send it
    $data = json_encode($data);
    $url = "https://wruczek.tech/tsw-metrics/";

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json",
            "method"  => "POST",
            "content" => $data
        ]
    ];

    $context  = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response !== "ok") {
        $metricsError = true;
    }
}
?>

<header class="installer-head reveal">
    <?= $stepChip ?>
    <h1 class="page-title">TS-website is installed</h1>
    <p class="page-sub">
        Version <?= htmlspecialchars(__TSWEBSITE_VERSION) ?> is ready. If you wish, you can remove the
        <code>installer</code> directory now.
    </p>
</header>

<?php // What the website starts with, read back from the database. In pairs; on a phone the name
      // and the address get a line each, the time zone and version share one when both fit ?>
<dl class="cells installer-facts is-pairs reveal" style="--i: 1">
    <div class="cell cell-wide">
        <dt><?= installerIcon("house", "i-sm") ?>Website</dt>
        <dd><?= htmlspecialchars((string) Config::get("website_title")) ?></dd>
    </div>
    <div class="cell cell-wide">
        <dt><?= installerIcon("headset", "i-sm") ?>TeamSpeak address</dt>
        <dd class="mono"><?= htmlspecialchars((string) Config::get("query_displayip")) ?></dd>
    </div>
    <div class="cell cell-fit">
        <dt><?= installerIcon("clock", "i-sm") ?>Time zone</dt>
        <dd><span class="cell-text"><?= htmlspecialchars((string) Config::get("timezone")) ?></span></dd>
    </div>
    <div class="cell cell-fit">
        <dt><?= installerIcon("hard-drives", "i-sm") ?>Version</dt>
        <dd><span class="cell-text"><?= htmlspecialchars(__TSWEBSITE_VERSION) ?></span></dd>
    </div>
</dl>

<div class="installer-body">
    <?php if(!empty($metricsError)) { ?>
        <div class="alert alert-warning has-icon reveal" style="--i: 2" role="alert">
            <?= installerIcon("warning-circle", "alert-icon") ?>
            The one-time statistics could not be sent. Nothing else is affected.
        </div>
    <?php } ?>

    <section class="reveal" style="--i: 2" aria-labelledby="whatnow-title">
        <div class="block-head">
            <h2 class="block-title" id="whatnow-title">What now?</h2>
        </div>

        <ul class="rows">
            <li class="row-item">
                <p class="row-label">Visit</p>
                <p class="row-value"><a href="../">Open your new website</a></p>
            </li>
            <li class="row-item">
                <p class="row-label">Join</p>
                <p class="row-value">
                    <a href="https://t.me/tswebsite" target="_blank" rel="noopener">The Telegram group</a>
                    for news, announcements and support
                </p>
            </li>
            <li class="row-item">
                <p class="row-label">Donate</p>
                <p class="row-value">To keep this project alive, send a message via Telegram or email if you would like to donate. Thanks!</p>
            </li>
            <li class="row-item">
                <p class="row-label">Spread the word</p>
                <p class="row-value">Let others know about this project</p>
            </li>
        </ul>
    </section>

    <div class="installer-actions reveal" style="--i: 3">
        <a href="../" class="btn btn-primary">
            Open the website<?= installerIcon("arrow-right", "i-arrow") ?>
        </a>
    </div>
</div>

<?php ob_start(); ?>
<section class="side-block reveal" style="--i: 1" aria-labelledby="notes-title">
    <div class="side-head">
        <h2 class="side-title" id="notes-title">Before you go</h2>
    </div>

    <ul class="howto">
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("trash") ?></span>
            <div>
                <p class="howto-title">Remove the installer</p>
                <p class="howto-sub">The website does not need the <code>installer</code> directory. <code>private/INSTALLER_LOCK</code> already keeps it from running again</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("database") ?></span>
            <div>
                <p class="howto-title">Settings live in the database</p>
                <p class="howto-sub">There is no admin panel yet: change values in the <code><?= htmlspecialchars((string) (Config::i()->getDatabaseConfig()["prefix"] ?? "")) ?>config</code> table</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("book-open-text") ?></span>
            <div>
                <p class="howto-title">Documentation</p>
                <a class="howto-link" href="https://github.com/Wruczek/ts-website/wiki" target="_blank" rel="noopener">Wiki on GitHub<?= installerIcon("arrow-up-right", "i-sm") ?></a>
            </div>
        </li>
    </ul>
</section>
<?php $pageNotes = ob_get_clean(); ?>
