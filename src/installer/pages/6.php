<?php
if(!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

use Wruczek\TSWebsite\Config;

require_once __PRIVATE_DIR . "/../private/vendor/autoload.php";

if (!empty($_POST)) {
    $baseUrl = @$_POST["base-url"];
    $websiteName = @$_POST["website-name"];
    $timezone = @$_POST["timezone"];
    $usingCloudflare = isset($_POST["using-cloudflare"]);

    if (!in_array($timezone, timezone_identifiers_list())) {
        $errormessage = "Invalid timezone";
    } else {
        try {
            Config::i()->setValue("baseurl", $baseUrl);
            Config::i()->setValue("website_title", $websiteName);
            Config::i()->setValue("nav_brand", $websiteName);
            Config::i()->setValue("timezone", $timezone);
            Config::i()->setValue("usingcloudflare", $usingCloudflare);

            header("Location: ?step=" . ($stepNumber + 1));
        } catch (\Exception $e) {
            $errormessage = "Error saving config: " . htmlspecialchars($e->getMessage());
        }
    }
}

$defaultTimezone = date_default_timezone_get();

$defaultBase = (@$_SERVER["HTTPS"] === "on" ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
$defaultBase = dirname(dirname($defaultBase)); // get the path for the previous directory, not the installer

$displayip = Config::get("query_displayip"); // set initial website name to the TS3 IP
?>

<header class="installer-head reveal">
    <?= $stepChip ?>
    <h1 class="page-title">Site settings</h1>
    <p class="page-sub">Almost done. You can change all of this later.</p>
</header>

<div class="installer-body">
    <?php if(!empty($errormessage)) { ?>
        <div class="alert alert-danger has-icon reveal" style="--i: 1" role="alert">
            <?= installerIcon("warning-circle", "alert-icon") ?>
            <?= $errormessage ?>
        </div>
    <?php } ?>

    <form id="configureform" method="post" action="<?= "?step=$stepNumber" ?>" class="reveal" style="--i: 1">
        <div class="form-group">
            <label for="base-url">Base URL</label>
            <input class="form-control"
                   id="base-url"
                   name="base-url"
                   value="<?= htmlspecialchars($defaultBase) ?>"
                   required autofocus autocomplete="off" spellcheck="false" aria-describedby="base-url-help">
            <p class="form-text" id="base-url-help">Where TS-website is reachable, without a trailing slash.</p>
        </div>

        <div class="form-group">
            <label for="website-name">Website name</label>
            <input class="form-control"
                   id="website-name"
                   name="website-name"
                   value="<?= htmlspecialchars($displayip) ?>"
                   required autocomplete="off" aria-describedby="website-name-help">
            <p class="form-text" id="website-name-help">Shown in the header and in the browser tab.</p>
        </div>

        <div class="form-group">
            <label for="timezone">Time zone</label>
            <select class="custom-select" name="timezone" id="timezone" required>
                <!-- Set this as selected if there is no default timezone -->
                <option <?= empty($defaultTimezone) ? "selected" : "" ?> disabled value="">
                    Choose your time zone
                </option>

                <?php foreach (timezone_identifiers_list() as $timezone) {
                    $selected = $timezone === $defaultTimezone;
                    $time = (new DateTime("now", new DateTimeZone($timezone)))->format("H:i (P)");
                    ?>
                    <option <?= $selected ? "selected" : "" ?> value="<?= $timezone ?>">
                        <?= "$timezone, $time" ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="using-cloudflare" name="using-cloudflare"
                    <?= isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? "checked" : "" ?>>
                <label class="custom-control-label" for="using-cloudflare">
                    I am using Cloudflare
                    <span class="form-text">This changes how TS-website detects the visitor's IP address.</span>
                </label>
            </div>
        </div>

        <div class="installer-actions">
            <button type="submit" class="btn btn-primary">
                Save and continue<?= installerIcon("arrow-right", "i-arrow") ?>
            </button>
        </div>
    </form>
</div>

<?php ob_start(); ?>
<section class="side-block reveal" style="--i: 1" aria-labelledby="notes-title">
    <div class="side-head">
        <h2 class="side-title" id="notes-title">Where they show up</h2>
    </div>

    <ul class="howto">
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("house") ?></span>
            <div>
                <p class="howto-title">Website name</p>
                <p class="howto-sub">The header, the footer and the browser tab. It starts as the TeamSpeak address you entered</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("clock") ?></span>
            <div>
                <p class="howto-title">Time zone</p>
                <p class="howto-sub">Every date and time the website shows. PHP on this server uses <?= htmlspecialchars($defaultTimezone) ?></p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("shield-check") ?></span>
            <div>
                <p class="howto-title">Cloudflare</p>
                <p class="howto-sub">
                    Behind Cloudflare, requests arrive from Cloudflare's addresses. This reads the visitor's own IP
                    from Cloudflare's header, and the TeamSpeak login needs it to find them
                    <?php if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) { ?>(this request came through Cloudflare, so it is on)<?php } ?>
                </p>
            </div>
        </li>
    </ul>
</section>
<?php $pageNotes = ob_get_clean(); ?>
