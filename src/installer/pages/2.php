<?php
if(!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

// if version ID constant is not defined, then it's probably PHP < 5.2.7
// don't even bother checking anything, just throw an error and die
if (!defined("PHP_VERSION_ID")) {
    die('Looks like you are using an ancient version of PHP (' . PHP_VERSION . '). Please update to something modern.');
}

if(!empty($_POST["allow-metrics-checkbox"])) {
    // set a 7 day cookie that tells us later to send the metrics
    setcookie("tsw_allow_metrics", "true", time() + (86400 * 7));
}

// Run the checks first, the summary above them depends on the result
$checksRun = 0;
$checksPassed = 0;

ob_start();
checkRequirements();
$checkResults = ob_get_clean();
?>

<header class="installer-head reveal">
    <?= $stepChip ?>
    <h1 class="page-title">Requirements check</h1>
    <p class="page-sub">PHP version, extensions and file permissions this server needs for TS-website.</p>
</header>

<?php
$osIcons = ["Linux" => "linux-logo", "Windows" => "windows-logo", "Darwin" => "apple-logo"];
$checksFailed = $checksRun - $checksPassed;

// "Apache/2.4.58 (Ubuntu)" reads as the name and version, with the build details on the line under it
$webServer = preg_split('/\s+/', trim((string) ($_SERVER["SERVER_SOFTWARE"] ?? "")), 2);
$webServerName = $webServer[0] !== "" ? $webServer[0] : "Unknown";
$webServerDetails = isset($webServer[1]) ? preg_replace('/^\((.*)\)$/', '$1', $webServer[1]) : "";
?>
<dl class="cells installer-facts reveal" style="--i: 1">
    <div class="cell">
        <dt><?= installerIcon("code", "i-sm") ?>PHP</dt>
        <dd><span class="cell-text"><?= htmlspecialchars(PHP_VERSION) ?></span></dd>
    </div>
    <div class="cell">
        <dt><?= installerIcon("globe-simple", "i-sm") ?>Web server</dt>
        <dd><span class="cell-text"><?= htmlspecialchars($webServerName) ?></span></dd>
        <?php if ($webServerDetails !== "") { ?>
            <dd class="cell-sub"><?= htmlspecialchars($webServerDetails) ?></dd>
        <?php } ?>
    </div>
    <div class="cell">
        <dt><?= installerIcon($osIcons[PHP_OS_FAMILY] ?? "desktop", "i-sm") ?>Operating system</dt>
        <dd><span class="cell-text"><?= htmlspecialchars(PHP_OS_FAMILY) ?></span></dd>
    </div>
    <div class="cell">
        <dt><?= installerIcon("list-checks", "i-sm") ?>Checks passed</dt>
        <dd>
            <span class="state-dot <?= $checksFailed ? "is-fail" : "is-ok" ?>" aria-hidden="true"></span>
            <?= $checksPassed ?><span class="of"> / <?= $checksRun ?></span>
        </dd>
        <?php if ($checksFailed) { ?>
            <dd class="cell-sub"><?= $checksFailed === 1 ? "1 problem to fix" : "$checksFailed problems to fix" ?></dd>
        <?php } ?>
    </div>
</dl>

<div class="installer-body">
    <?php if(defined("CANNOT_INSTALL")) { ?>
        <div class="alert alert-danger has-icon check-summary reveal" style="--i: 2" role="alert">
            <?= installerIcon("x-circle", "alert-icon") ?>
            This server cannot run TS-website yet. Fix the problems below and check again.
            If you are stuck, follow the installation guide in the
            <a href="https://github.com/Wruczek/ts-website/wiki" target="_blank" rel="noopener">wiki</a>.

            <?php if(defined("FILE_PERM_ERROR")) { ?>
                <p class="mt-2 mb-0">To fix the file permissions, try running:</p>
                <pre><code>sudo chown -R www-data:www-data "<?= htmlspecialchars(realpath(__BASE_DIR)) ?>"</code></pre>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="alert alert-success has-icon check-summary reveal" style="--i: 2" role="status">
            <?= installerIcon("check-circle", "alert-icon") ?>
            Everything is in place, this server can run TS-website.
        </div>

        <button class="btn btn-secondary btn-sm details-toggle reveal" style="--i: 3" type="button"
                data-toggle="collapse" data-target="#requirementsDetails" aria-expanded="false" aria-controls="requirementsDetails">
            <span class="details-toggle-label">Show details</span><?= installerIcon("caret-down", "caret") ?>
        </button>
    <?php } ?>

    <?php // Failed checks are shown right away, a clean result keeps them folded ?>
    <div class="check-details collapse<?= defined("CANNOT_INSTALL") ? " show" : "" ?>" id="requirementsDetails">
        <div class="check-details-inner">
            <?= $checkResults ?>
        </div>
    </div>

    <div class="installer-actions reveal" style="--i: 4">
        <a href="?step=<?= $stepNumber - 1 ?>" class="btn btn-ghost">
            <?= installerIcon("arrow-left") ?>Back
        </a>

        <?php if(defined("CANNOT_INSTALL")) { ?>
            <a href="?step=<?= $stepNumber ?>" class="btn btn-primary" data-busy>
                Check again<?= installerIcon("arrow-clockwise", "i-retry") ?><?= installerIcon("circle-notch", "i-busy") ?>
            </a>
        <?php } else { ?>
            <a href="?step=<?= $stepNumber + 1 ?>" class="btn btn-primary">
                Next<?= installerIcon("arrow-right", "i-arrow") ?>
            </a>
        <?php } ?>
    </div>
</div>

<?php ob_start(); ?>
<section class="side-block reveal" style="--i: 1" aria-labelledby="fix-title">
    <div class="side-head">
        <h2 class="side-title" id="fix-title">If a check fails</h2>
    </div>

    <ul class="howto">
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("lock-simple") ?></span>
            <div>
                <p class="howto-title">File permissions</p>
                <p class="howto-sub">The user the web server runs as needs to write to <code>private</code> and <code>private/cache</code></p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("plugs-connected") ?></span>
            <div>
                <p class="howto-title">PHP extensions</p>
                <p class="howto-sub">Install the missing one for this PHP version, then restart the web server or PHP-FPM</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("book-open-text") ?></span>
            <div>
                <p class="howto-title">Installation guide</p>
                <a class="howto-link" href="https://github.com/Wruczek/ts-website/wiki" target="_blank" rel="noopener">Wiki on GitHub<?= installerIcon("arrow-up-right", "i-sm") ?></a>
            </div>
        </li>
    </ul>
</section>
<?php $pageNotes = ob_get_clean(); ?>

<script>
    $("#requirementsDetails").on("show.bs.collapse hide.bs.collapse", function (e) {
        $(".details-toggle .details-toggle-label").text(e.type === "show" ? "Hide details" : "Show details")
    })

    // "Check again" reloads the page, show that it is working
    $("[data-busy]").click(function () {
        $(this).addClass("is-busy").attr("aria-disabled", "true")
    })
</script>

<?php
function checkRequirements() {
    displayCategory("PHP");

    // PHP version: 7.2 or newer
    {
        $result = PHP_VERSION_ID < 70200 ? 2 : 0;

        showCheckResult(
                "PHP 7.2 or newer",
                $result,
                "Current PHP version: " . PHP_VERSION
        );
    }

    displayCategory("Extensions");

    // Extensions check
    {
        foreach (["mbstring", "json", "pdo_mysql", "tokenizer", "curl"] as $extension) {
            $result = extension_loaded($extension);

            showCheckResult(
                "<code>$extension</code>",
                $result ? 0 : 2,
                $result ?
                    "Installed and loaded" :
                    'Please install or enable the <code>' . $extension . '</code> extension'
            );
        }
    }

    displayCategory("File and directory permissions");

    // file / directory writable checks
    {
        // path => true if file, false if directory
        $paths = array(
            __CONFIG_FILE => true,
            __INSTALLER_LOCK_FILE => true,
            __CACHE_DIR => false,
            __CACHE_DIR . "/templates" => false,
            __CACHE_DIR . "/servericons" => false,
        );

        foreach ($paths as $path => $isFile) {
            $exists = file_exists($path);

            // If file / directory doesnt exists try to create it and update the variable
            if(!$exists)
                $exists = $isFile ? @touch($path) : @mkdir($path);

            $writable = is_writable($path);
            $basename = basename($path);

            // we are using a custom method instead of realpath,
            // because it does not work with non-existing files
            $realpath = resolveFilename($path);

            $msg = "Writable";

            if(!$writable)
                $msg = "Please make <code>$realpath</code> writable";

            if(!$exists)
                $msg = ($isFile ? "File" : "Directory") . " <code>$realpath</code> does not exist, please create it";

            $success = $exists && $writable;

            if (!$success && !defined("FILE_PERM_ERROR")) {
                define("FILE_PERM_ERROR", true);
            }

            showCheckResult("<code>$basename</code>", $success ? 0 : 2, $msg);
        }
    }

    displayCategory("Miscellaneous");

    // cache test
    {
        $result = false;

        try {
            require_once __PRIVATE_DIR . "/vendor/autoload.php";
            $cache = new Wruczek\PhpFileCache\PhpFileCache();
            $teststring = "cachetest123";
            $cache->store("installertest", $teststring, 3);
            $result = $cache->retrieve("installertest") === $teststring;
            $cache->clearCache();
        } catch (Exception $e) {}

        showCheckResult(
                "Cache save and read test",
                $result ? 0 : 2,
                $result ?
                    "Save and read success" :
                    "Something went wrong! Please make sure that <code>private/cache</code> directory is writable"
        );
    }

    // template test
    {
        if($result) {
            if(extension_loaded("mbstring")) {
                $result = false;

                try {
                    $latte = new Latte\Engine();
                    $latte->setTempDirectory(__CACHE_DIR);
                    $latte->setLoader(new Latte\Loaders\StringLoader());

                    $render = @$latte->renderToString('Hello, {$test|upper}!', array("test" => "Wruczek"));

                    $result = $render === "Hello, WRUCZEK!";
                } catch (Exception $e) {}

                showCheckResult(
                    "Template render and cache test",
                    $result ? 0 : 2,
                    $result ?
                        "Render and cache success" :
                        "Something went wrong! Please make sure that <code>private/cache</code> directory is writable"
                );
            } else {
                showCheckResult("Template render and cache test", 2, "<code>mbstring</code> extension not found, cannot start the test");
            }
        } else {
            showCheckResult("Template render and cache test", 2, "<code>private/cache</code> directory is not writable, cannot start the test");
        }
    }

    // close the last category
    echo '</ul></section>';
}

// Utils

function showCheckResult($name, $state, $resulttext) {
    $GLOBALS["checksRun"]++;

    if($state === 0) {
        $GLOBALS["checksPassed"]++;
        $class = "check-ok";
        $icon = installerIcon("check-circle");
        $label = "Passed";
    } else if($state === 1) {
        $class = "check-warn";
        $icon = installerIcon("warning-circle");
        $label = "Warning";
    } else {
        $class = "check-fail";
        $icon = installerIcon("x-circle");
        $label = "Failed";

        if(!defined("CANNOT_INSTALL")) {
            define("CANNOT_INSTALL", true);
        }
    }

    ?>
    <li class="row-item">
        <p class="row-label"><?= $name ?></p>
        <p class="row-value check-state <?= $class ?>"><?= $icon ?><span><span class="sr-only"><?= $label ?>: </span><?= $resulttext ?></span></p>
    </li>
<?php }

function displayCategory($name) {
    static $open = false;

    if ($open) {
        echo '</ul></section>';
    }

    $open = true;
    echo '<section class="check-group"><h2 class="check-group-title">' . $name . '</h2><ul class="rows">';
}

// https://tomnomnom.com/posts/realish-paths-without-realpath
function resolveFilename($filename) {
    $filename = str_replace('//', '/', $filename);
    $parts = explode('/', $filename);
    $out = array();
    foreach ($parts as $part){
        if ($part === '.') continue;
        if ($part === '..') {
            array_pop($out);
            continue;
        }
        $out[] = $part;
    }
    return implode('/', $out);
}
