<?php
require_once __DIR__ . "/../private/php/constants.php";

// we need to check if the file has something in it, because its gonna be touched in
// the requirements check step to make sure we have write permissions.
// we only care about it after its filled with content in the last step of the installation process
if(file_exists(__INSTALLER_LOCK_FILE) && filesize(__INSTALLER_LOCK_FILE) > 1) {
    die('File "private/INSTALLER_LOCK" exists. Please remove it if you wish to run the installer again.');
}

if (!file_exists(__PRIVATE_DIR . "/vendor/autoload.php")) {
    die(
        '<h2>Oops! We cannot find Composer\'s autoload file.</h2>' .
        '<h2>Download TS-website from <a href="https://github.com/Wruczek/ts-website/releases">releases page</a>, not directly from GitHub.</h2>' .
        'Or, if you know what you are doing, run <code>composer update</code> in the ' .
        '<code>' . realpath(__BASE_DIR) . '</code> directory'
    );
}

// Show real errors to the person installing, but not the deprecation notices
// that vendor code raises on newer PHP versions
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
set_time_limit(0);

require_once __PRIVATE_DIR . "/vendor/autoload.php";

// The TS3 framework registers its class autoloader in init(). The website does that in
// private/php/load.php; without it the ssh and http query modes (TeamSpeak 6) fail with
// 'Class "TeamSpeak3_Adapter_ServerQuery" not found'
TeamSpeak3::init();

/**
 * Icon from the website's sprite (img/icons.svg), see TemplateUtils::icon()
 */
function installerIcon(string $name, string $class = ""): string {
    return \Wruczek\TSWebsite\Utils\TemplateUtils::icon($name, $class, "../");
}

$steps = [
    1 => "Introduction",
    2 => "Requirements",
    3 => "Database",
    4 => "TeamSpeak query",
    5 => "Web server security",
    6 => "Site settings",
    7 => "Finish",
];

$stepNumber = empty($_GET["step"]) || !file_exists(__DIR__ . "/pages/" . (int)$_GET["step"] . ".php") ? 1 : (int) $_GET["step"];

// Where the step stands, printed by every step above its title: "Step 3 of 7" and a segmented
// meter, drawn like the capacity meter on the website's home page
$stepChip = '<p class="step-chip"><span>Step ' . $stepNumber . ' of ' . count($steps) . '</span><span class="step-meter" aria-hidden="true">';
foreach ($steps as $number => $stepName) {
    $stepChip .= '<span class="' . ($number < $stepNumber ? "is-done" : ($number === $stepNumber ? "is-current" : "is-next")) . '"></span>';
}
$stepChip .= '</span></p>';

// A step prints its title (header.installer-head), optionally a row of facts (.installer-facts) and
// its form (div.installer-body) into the main column. Notes for the side column, under the list of
// steps, go into $pageNotes (each step buffers them itself)
$pageNotes = "";

ob_start();
require __DIR__ . "/pages/$stepNumber.php";
$pageContent = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#0f0f10">
    <meta name="view-transition" content="same-origin">
    <link rel="icon" type="image/png" sizes="32x32" href="../img/icons/defaulticon-32.png">

    <title><?= htmlspecialchars($steps[$stepNumber]) ?> | TS-website installer</title>

    <script>
        // Same theme resolution as the website (body.latte), dark unless the visitor chose otherwise
        (function (root) {
            root.classList.add("js")

            // Pages entered through a view transition skip their own entrance animation
            window.addEventListener("pagereveal", function (e) {
                if (e.viewTransition) {
                    root.classList.add("page-transition")
                }
            })

            var mode = "dark"
            try { mode = localStorage.getItem("tswebsite_theme") || mode } catch (e) {}
            if (mode === "auto") {
                mode = window.matchMedia && window.matchMedia("(prefers-color-scheme: light)").matches ? "light" : "dark"
            }
            root.setAttribute("data-theme", mode === "light" ? "light" : "dark")
            document.querySelector('meta[name="theme-color"]').setAttribute("content", mode === "light" ? "#ffffff" : "#0f0f10")
        })(document.documentElement)
    </script>

    <link rel="preload" href="../fonts/onest-latin.woff2" as="font" type="font/woff2" crossorigin>

    <!-- Bootstrap 4.6.0 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/css/bootstrap.min.css"
          integrity="sha256-T/zFmO5s/0aSwc6ics2KLxlfbewyRz6UNw1s3Ppf5gE=" crossorigin="anonymous">

    <!-- Website theme, then the installer's own layout -->
    <link rel="stylesheet" href="../css/tsw.css?v=<?= (int) @filemtime(__BASE_DIR . "/css/tsw.css") ?>">
    <link rel="stylesheet" href="style.css?v=<?= (int) @filemtime(__DIR__ . "/style.css") ?>">

    <!-- jQuery 3.6.0 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
            integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

    <!-- Bootstrap 4.6.0 (bundle - includes Popper.js) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.bundle.min.js"
            integrity="sha256-sCElQ8xaSgoxwbWp0eiXMmGZIRa0z94+ffzzO06BqXs=" crossorigin="anonymous"></script>
</head>
<body class="installer">

<div class="frame">

<header class="site-header">
    <div class="site-header-inner">
        <span class="brand">
            <img src="../img/icons/defaulticon-64.png" width="26" height="26" alt="">
            <span class="brand-name">TS-website</span>
        </span>

        <span class="installer-version">Installer, version <?= htmlspecialchars(__TSWEBSITE_VERSION) ?></span>
    </div>
</header>

<main class="page page--flush installer-page" id="main">
    <div class="installer-main">
        <?= $pageContent ?>
    </div>

    <aside class="installer-side">
        <?php // The whole way through: done steps carry a check, the current one a ring, the rest their number ?>
        <nav class="side-block steps-block reveal" aria-labelledby="steps-title">
            <div class="side-head">
                <h2 class="side-title" id="steps-title">Installation</h2>
                <span class="steps-count"><?= $stepNumber ?> / <?= count($steps) ?></span>
            </div>

            <ol class="stepper-list">
                <?php foreach ($steps as $number => $stepName) {
                    $state = $number < $stepNumber ? "is-done" : ($number === $stepNumber ? "is-current" : "is-next");
                    ?>
                    <li class="<?= $state ?>"<?= $number === $stepNumber ? ' aria-current="step"' : "" ?>>
                        <span class="stepper-mark"><?= $number < $stepNumber ? installerIcon("check") : $number ?></span>
                        <span class="stepper-name"><?= htmlspecialchars($stepName) ?><?= $number < $stepNumber ? '<span class="sr-only"> (done)</span>' : "" ?></span>
                        <?php if ($number === $stepNumber) { ?>
                            <span class="stepper-marker" aria-hidden="true"></span>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ol>
        </nav>

        <?= $pageNotes ?>
    </aside>
</main>

<footer class="site-footer">
    <div class="footer-bottom">
        <a href="https://github.com/Wruczek/ts-website/wiki" target="_blank" rel="noopener">Installation guide<?= installerIcon("arrow-up-right", "i-sm") ?></a>

        <span class="footer-credit">
            <a href="https://github.com/Wruczek/ts-website" target="_blank" rel="noopener">ts-website</a> v <?= htmlspecialchars(__TSWEBSITE_VERSION) ?> &middot;
            &copy; <a href="https://wruczek.tech/?source=tsw" target="_blank" rel="noopener">Wruczek</a> 2017 - 2025
        </span>
    </div>
</footer>

</div>

<script>
    $(function () {
        $('[data-toggle="tooltip"]').tooltip({"html": true})
    })
</script>

</body>
</html>
