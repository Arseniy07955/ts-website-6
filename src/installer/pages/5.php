<?php
if(!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

// The URL path of the private directory, for the nginx example (the website may live in a subdirectory)
$privatePath = rtrim(str_replace("\\", "/", dirname(dirname($_SERVER["SCRIPT_NAME"] ?? "/installer/index.php"))), "/") . "/private/";
?>

<header class="installer-head reveal">
    <?= $stepChip ?>
    <h1 class="page-title">Web server security</h1>
    <p class="page-sub">The <code>private</code> directory holds the database password and the cache. Nobody should be able to open it from the web.</p>
</header>

<div class="installer-body">
    <?php if(!empty($errormessage)) { ?>
        <div class="alert alert-danger has-icon reveal" style="--i: 1" role="alert">
            <?= installerIcon("warning-circle", "alert-icon") ?>
            <?= $errormessage ?>
        </div>
    <?php } ?>

    <div class="alert alert-warning has-icon reveal" style="--i: 1">
        <?= installerIcon("warning-circle", "alert-icon") ?>
        Securing your web server is very important. Please read
        <a href="https://github.com/Wruczek/ts-website/wiki/%5BEN%5D-Securing-private-directory" target="_blank" rel="noopener">this guide</a>
        on how to properly isolate the <code>private</code> directory.
    </div>

    <div class="installer-actions reveal" style="--i: 2">
        <a href="?step=<?= $stepNumber + 1 ?>" class="btn btn-primary">
            Next<?= installerIcon("arrow-right", "i-arrow") ?>
        </a>
    </div>
</div>

<?php ob_start(); ?>
<section class="side-block reveal" style="--i: 1" aria-labelledby="notes-title">
    <div class="side-head">
        <h2 class="side-title" id="notes-title">Closing it off</h2>
    </div>

    <ul class="howto">
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("lock-simple") ?></span>
            <div>
                <p class="howto-title">Apache</p>
                <p class="howto-sub"><code>private/.htaccess</code> already denies every request, as long as <code>AllowOverride</code> lets Apache read it</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("terminal-window") ?></span>
            <div>
                <p class="howto-title">nginx</p>
                <p class="howto-sub">nginx ignores <code>.htaccess</code>. Deny the directory in the server block:</p>
                <pre class="note-code"><code>location ^~ <?= htmlspecialchars($privatePath) ?> {
    deny all;
}</code></pre>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("book-open-text") ?></span>
            <div>
                <p class="howto-title">The full guide</p>
                <a class="howto-link" href="https://github.com/Wruczek/ts-website/wiki/%5BEN%5D-Securing-private-directory" target="_blank" rel="noopener">Securing the private directory<?= installerIcon("arrow-up-right", "i-sm") ?></a>
            </div>
        </li>
    </ul>
</section>
<?php $pageNotes = ob_get_clean(); ?>
