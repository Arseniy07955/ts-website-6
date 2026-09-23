<?php
if(!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\ServerIconCache;
use Wruczek\TSWebsite\Utils\ApiUtils;
use Wruczek\TSWebsite\Utils\TeamSpeakUtils;

if (!empty($_POST)) {
    $queryhostname = trim($_POST["queryhostname"]);
    $queryport = trim($_POST["queryport"]);
    $queryserverport = trim($_POST["queryserverport"]);
    $queryusername = trim($_POST["queryusername"]);
    $querypassword = trim($_POST["querypassword"]);
    $querydisplayip = trim($_POST["querydisplayip"]);
    $querymode = in_array($_POST["querymode"] ?? "", ["raw", "ssh", "http", "https"], true) ? $_POST["querymode"] : "raw";

    // WebQuery authenticates with the API key only
    if (($querymode === "http" || $querymode === "https") && empty($queryusername)) {
        $queryusername = "serveradmin";
    }

    if (!empty($queryhostname) && !empty($queryport)
        && !empty($queryserverport) && !empty($queryusername)
        && !empty($querypassword) && !empty($querydisplayip)
    ) {
        require_once __PRIVATE_DIR . "/vendor/autoload.php";

        try {
            $tsNodeHost = TeamSpeakUtils::connect($querymode, $queryhostname, (int) $queryport, $queryusername, $querypassword);
            $tsServer = $tsNodeHost->serverGetByPort($queryserverport);

            if(is_array($tsServer->getInfo())) {
                $tsVersion = $tsServer->getInfo()["virtualserver_version"];
                $tsBuildNo = $tsVersion->section("[", 1)->filterDigits()->toInt();
                $isTs6OrNewer = (int) $tsVersion->filterDigits()->substr(0, 1)->toString() >= 6;

                if (!$isTs6OrNewer && $tsBuildNo < 1564054246) {
                    $errormessage =
                        'Your TeamSpeak server version is not supported.<br>' .
                        'Current version: ' . TeamSpeak3_Helper_Convert::versionShort($tsVersion) . ' (build ' . $tsBuildNo . ')' . '<br>' .
                        'Supported versions: 3.10.0 (build 1564054246) and newer';
                } else {
                    $configdata = [
                        "query_hostname" => $queryhostname,
                        "query_port" => $queryport,
                        "query_mode" => $querymode,
                        "tsserver_port" => $queryserverport,
                        "query_username" => $queryusername,
                        "query_password" => $querypassword,
                        "query_displayip" => $querydisplayip,
                    ];

                    foreach ($configdata as $key => $value) {
                        try {
                            Config::i()->setValue($key, $value);
                        } catch (\Exception $e) {
                            die("Error while updating config in database, at " . htmlspecialchars($key) . " => " . htmlspecialchars($value));
                        }
                    }

                    $cacheIcons = true;
                }
            } else {
                $errormessage = 'Cannot retrieve server information';
            }
        } catch (\Throwable $e) {
            $errormessage = htmlspecialchars("Error " . $e->getCode() . ": " . $e->getMessage());

            if($e->getCode() === 520) {
                $errormessage .= '<br>You have entered wrong username and/or password. Please check it and try again.';
            }

            if($e->getCode() === 2568) {
                $errormessage .= '<br>Query account does not have permissions.';

                if (!empty($GLOBALS["__REQUIRED_QUERY_PERMS"])) {
                    $errormessage .= ' Click <a href="#" data-toggle="modal" data-target="#queryperms">here</a> to view required permissions list.';
                }
            }
        }
    }
}

// Keep what was typed when the form comes back with an error (never the password)
function queryField(string $name, string $default = ""): string {
    return htmlspecialchars(isset($_POST[$name]) ? trim((string) $_POST[$name]) : $default);
}

$selectedMode = in_array($_POST["querymode"] ?? "", ["raw", "ssh", "http", "https"], true) ? $_POST["querymode"] : "raw";

// The permission list only exists when a build defines it, so link to it only then
$requiredPerms = $GLOBALS["__REQUIRED_QUERY_PERMS"] ?? [];

if (isset($_GET["syncicons"])) {
    require_once __PRIVATE_DIR . "/vendor/autoload.php";

    set_time_limit(0); // this might take a while

    // Throwable: a connection that dropped since the previous request surfaces as an Error
    // inside the icon cache, and the page should show its message instead of an HTTP 500
    try {
        ServerIconCache::syncIcons();
        ApiUtils::jsonSuccess();
    } catch (\Throwable $e) {
        ApiUtils::jsonError($e->getMessage(), $e->getCode());
    }

    exit;
}
?>

<?php if ($requiredPerms) { ?>
<div class="modal fade" id="queryperms" tabindex="-1" role="dialog" aria-labelledby="queryperms-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="queryperms-title">Query permissions required by TS-website</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <?= installerIcon("x") ?>
                </button>
            </div>
            <div class="modal-body">
                <ul class="mb-0">
                    <?php foreach ($requiredPerms as $perm) { ?>
                        <li><code><?= htmlspecialchars($perm) ?></code></li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?php
// Names of the connection types, for the form, the facts and the notes
$queryModes = [
    "raw" => ["Raw query", "TeamSpeak 3 only, with a query username and password", "10011"],
    "ssh" => ["SSH query", "TeamSpeak 3 and 6, with a query username and password", "10022"],
    "http" => ["HTTP WebQuery", "TeamSpeak 3 and 6, with an API key in the password field", "10080"],
    "https" => ["HTTPS WebQuery", "The same over an encrypted connection", "10443"],
];
?>

<?php if(isset($cacheIcons)) {
    // The version check above rewrites the version string in place (filterDigits), so read the
    // server's answer once more for the facts; the connection is open, and they are optional
    try {
        $serverInfo = $tsServer->getInfo();
    } catch (\Throwable $e) {
        $serverInfo = [];
    }
    ?>

    <header class="installer-head reveal">
        <?= $stepChip ?>
        <h1 class="page-title">Caching server icons</h1>
        <p class="page-sub">Connected to the TeamSpeak server. Downloading its group and channel icons, this can take a moment.</p>
    </header>

    <dl class="cells installer-facts is-server is-pairs reveal" style="--i: 1">
        <div class="cell">
            <dt><?= installerIcon("headset", "i-sm") ?>Server</dt>
            <dd><?= htmlspecialchars((string) ($serverInfo["virtualserver_name"] ?? "")) ?></dd>
        </div>
        <?php if (isset($serverInfo["virtualserver_version"])) { ?>
            <div class="cell cell-fit">
                <dt><?= installerIcon("hard-drives", "i-sm") ?>Version</dt>
                <dd><span class="cell-text"><?= htmlspecialchars((string) TeamSpeak3_Helper_Convert::versionShort($serverInfo["virtualserver_version"])) ?></span></dd>
                <dd class="cell-sub"><?= htmlspecialchars((string) ($serverInfo["virtualserver_platform"] ?? "")) ?></dd>
            </div>
        <?php } ?>
        <div class="cell cell-fit">
            <dt><?= installerIcon("plugs-connected", "i-sm") ?>Connection</dt>
            <dd><span class="cell-text"><?= htmlspecialchars($queryModes[$querymode][0]) ?></span></dd>
            <dd class="cell-sub mono"><?= htmlspecialchars($queryhostname . ":" . $queryport) ?></dd>
        </div>
        <div class="cell">
            <dt><?= installerIcon("broadcast", "i-sm") ?>Voice port</dt>
            <dd><span class="cell-text"><?= htmlspecialchars($queryserverport) ?></span></dd>
            <dd class="cell-sub mono"><?= htmlspecialchars($querydisplayip) ?></dd>
        </div>
    </dl>

    <div class="installer-body">
        <div class="sync-state reveal" style="--i: 2" id="sync-state" role="status">
            <span class="loader" aria-hidden="true"></span>
            <span>Caching icons, please wait</span>
        </div>

        <div class="alert alert-warning has-icon" id="sync-error" role="alert" hidden>
            <?= installerIcon("warning-circle", "alert-icon") ?>
            The icons could not be cached: <span id="sync-error-text"></span>.
            The website tries again on its own later, you can continue.
        </div>

        <div class="installer-actions" id="sync-actions" hidden>
            <a href="?step=5" class="btn btn-primary">Next<?= installerIcon("arrow-right", "i-arrow") ?></a>
        </div>
    </div>

    <?php ob_start(); ?>
    <section class="side-block reveal" style="--i: 1" aria-labelledby="notes-title">
        <div class="side-head">
            <h2 class="side-title" id="notes-title">About the icons</h2>
        </div>

        <ul class="howto">
            <li>
                <span class="howto-icon" aria-hidden="true"><?= installerIcon("folder-simple") ?></span>
                <div>
                    <p class="howto-title">Saved next to the website</p>
                    <p class="howto-sub"><code>private/cache/servericons</code>, so visitors never load them from the TeamSpeak server</p>
                </div>
            </li>
            <li>
                <span class="howto-icon" aria-hidden="true"><?= installerIcon("arrow-clockwise") ?></span>
                <div>
                    <p class="howto-title">Kept up to date</p>
                    <p class="howto-sub">The website checks for new icons on its own every few minutes</p>
                </div>
            </li>
        </ul>
    </section>
    <?php $pageNotes = ob_get_clean(); ?>

<?php } else { ?>

    <header class="installer-head reveal">
        <?= $stepChip ?>
        <h1 class="page-title">TeamSpeak query</h1>
        <p class="page-sub">TS-website reads the server, its channels and clients through the query interface.</p>
    </header>

    <div class="installer-body">
        <?php if(!empty($errormessage)) { ?>
            <div class="alert alert-danger has-icon reveal" style="--i: 1" role="alert">
                <?= installerIcon("warning-circle", "alert-icon") ?>
                <?= $errormessage ?>
            </div>
        <?php } ?>

        <form id="tsform" method="post" action="<?= "?step=$stepNumber" ?>" class="reveal" style="--i: 2" data-busy-form>
            <div class="form-group">
                <label for="querymode">Connection</label>
                <select class="custom-select" name="querymode" id="querymode" aria-describedby="querymode-help">
                    <option value="raw"<?= $selectedMode === "raw" ? " selected" : "" ?>>Raw query (TeamSpeak 3)</option>
                    <option value="ssh"<?= $selectedMode === "ssh" ? " selected" : "" ?>>SSH query (TeamSpeak 3 and 6)</option>
                    <option value="http"<?= $selectedMode === "http" ? " selected" : "" ?>>HTTP WebQuery (TeamSpeak 3 and 6)</option>
                    <option value="https"<?= $selectedMode === "https" ? " selected" : "" ?>>HTTPS WebQuery (TeamSpeak 3 and 6)</option>
                </select>
                <p class="form-text" id="querymode-help">
                    TeamSpeak 6 has no raw query anymore, use SSH or WebQuery. WebQuery signs in with an API key
                    (<code>TSSERVER_QUERY_ADMIN_API_KEY</code>) instead of a username and password.
                </p>
            </div>

            <div class="field-row">
                <div class="form-group">
                    <label for="queryhostname">Host</label>
                    <input class="form-control" id="queryhostname" name="queryhostname" value="<?= queryField("queryhostname") ?>"
                           placeholder="127.0.0.1" required autofocus autocomplete="off" spellcheck="false" aria-describedby="queryhostname-help">
                    <p class="form-text" id="queryhostname-help">The TeamSpeak server address without a port. Use <code>127.0.0.1</code> if it runs on this machine.</p>
                </div>

                <div class="form-group">
                    <label for="queryport">Query port</label>
                    <input type="number" class="form-control" id="queryport" name="queryport" value="<?= queryField("queryport") ?>"
                           placeholder="10011" min="1" max="65535" required autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label for="queryserverport">Voice server port</label>
                <input type="number" class="form-control" id="queryserverport" name="queryserverport" value="<?= queryField("queryserverport") ?>"
                       placeholder="9987" min="1" max="65535" required autocomplete="off" aria-describedby="queryserverport-help">
                <p class="form-text" id="queryserverport-help">The port people connect to, 9987 by default.</p>
            </div>

            <div class="field-collapse" id="queryusername-field">
                <div>
                    <div class="form-group">
                        <label for="queryusername">Query username</label>
                        <input class="form-control" id="queryusername" name="queryusername" value="<?= queryField("queryusername") ?>"
                               placeholder="serveradmin" required autocomplete="off" spellcheck="false" aria-describedby="queryusername-help">
                        <p class="form-text" id="queryusername-help">A dedicated query account is safer than serveradmin.</p>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="querypassword" id="querypassword-label">Query password</label>
                <input type="password" class="form-control" id="querypassword" name="querypassword" required autocomplete="off">
            </div>

            <div class="form-group">
                <label for="querydisplayip">Displayed address</label>
                <input class="form-control" id="querydisplayip" name="querydisplayip" value="<?= queryField("querydisplayip") ?>"
                       placeholder="ts.example.com" required autocomplete="off" spellcheck="false" aria-describedby="querydisplayip-help">
                <p class="form-text" id="querydisplayip-help">The friendly address visitors see and connect to, for example <code>ts.myclan.net</code>.</p>
            </div>

            <?php if ($requiredPerms) { ?>
                <p class="mb-0">
                    <a href="#" data-toggle="modal" data-target="#queryperms">Query permissions required by TS-website</a>
                </p>
            <?php } ?>

            <div class="installer-actions">
                <button type="submit" class="btn btn-primary">
                    Connect and continue<?= installerIcon("arrow-right", "i-arrow") ?><?= installerIcon("circle-notch", "i-busy") ?>
                </button>
            </div>
        </form>
    </div>

    <?php ob_start(); ?>
    <?php // The connection picked in the form is marked, and the mark follows the select ?>
    <section class="side-block reveal" style="--i: 1" aria-labelledby="modes-title">
        <div class="side-head">
            <h2 class="side-title" id="modes-title">Which connection to pick</h2>
        </div>

        <ul class="mode-list">
            <?php foreach ($queryModes as $mode => $modeInfo) { ?>
                <li data-mode="<?= $mode ?>"<?= $mode === $selectedMode ? ' class="is-current"' : "" ?>>
                    <p class="mode-name"><?= $modeInfo[0] ?><span class="mode-port">port <?= $modeInfo[2] ?></span></p>
                    <p class="mode-meta"><?= $modeInfo[1] ?></p>
                </li>
            <?php } ?>
        </ul>
    </section>

    <section class="side-block reveal" style="--i: 2" aria-labelledby="ts-notes-title">
        <div class="side-head">
            <h2 class="side-title" id="ts-notes-title">Before you connect</h2>
        </div>

        <ul class="howto">
            <li>
                <span class="howto-icon" aria-hidden="true"><?= installerIcon("shield-check") ?></span>
                <div>
                    <p class="howto-title">Allowlist this machine</p>
                    <p class="howto-sub">
                        If the TeamSpeak server is not hosted here and you have access to its files, add the IP of
                        this machine to <code>query_ip_allowlist.txt</code> and restart the TeamSpeak server.
                        Otherwise TS&#8209;website might get rate-limited and periodically stop working.
                    </p>
                </div>
            </li>
            <?php if ($requiredPerms) { ?>
                <li>
                    <span class="howto-icon" aria-hidden="true"><?= installerIcon("key") ?></span>
                    <div>
                        <p class="howto-title">Query permissions</p>
                        <a class="howto-link" href="#" data-toggle="modal" data-target="#queryperms">The list TS-website needs</a>
                    </div>
                </li>
            <?php } ?>
        </ul>
    </section>
    <?php $pageNotes = ob_get_clean(); ?>

<?php } ?>

<script>
    // WebQuery signs in with the API key only: fold the username away and relabel the password
    var queryPorts = {raw: "10011", ssh: "10022", http: "10080", https: "10443"}

    $("#querymode").change(function () {
        var webQuery = this.value === "http" || this.value === "https"

        $("#queryusername").prop("required", !webQuery)
        $("#queryusername-field").toggleClass("is-hidden", webQuery).prop("inert", webQuery)
        $("#querypassword-label").text(webQuery ? "API key" : "Query password")
        $("#queryport").attr("placeholder", queryPorts[this.value])
        $(".mode-list li").removeClass("is-current").filter('[data-mode="' + this.value + '"]').addClass("is-current")
    })

    // Apply the state of a mode kept after an error without animating it
    $("#queryusername-field").css("transition", "none")
    $("#querymode").trigger("change")
    $("#queryusername-field")[0] && $("#queryusername-field")[0].offsetHeight
    $("#queryusername-field").css("transition", "")

    // Connecting can take up to ten seconds: show it and block a second submit
    $("[data-busy-form]").on("submit", function (e) {
        var button = $(this).find('button[type="submit"]')

        if (button.hasClass("is-busy")) {
            e.preventDefault()
            return
        }

        button.addClass("is-busy").attr("aria-disabled", "true")
    })
</script>

<?php if(isset($cacheIcons)) { ?>
    <script>
        $.ajax({
            data: { syncicons: 1 },
            success: function (res) {
                if (!res.success) {
                    showSyncError(res.message || JSON.stringify(res))
                    return
                }

                location = "?step=5"
            },
            error: function (xhr) {
                showSyncError(xhr.status ? "HTTP " + xhr.status : "no answer from the server")
            }
        })

        // The website retries the sync by itself later, so a failure here does not stop the installation
        function showSyncError(message) {
            $("#sync-state").prop("hidden", true)
            $("#sync-error-text").text(message)
            $("#sync-error, #sync-actions").prop("hidden", false)
        }
    </script>
<?php } ?>
