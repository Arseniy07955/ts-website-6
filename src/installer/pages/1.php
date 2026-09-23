<?php if (!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed"); ?>

<div class="modal fade" tabindex="-1" role="dialog" id="dev-release-notice" aria-labelledby="dev-release-notice-title">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="dev-release-notice-title">Welcome to the development version of TS-website 2</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <?= installerIcon("x") ?>
                </button>
            </div>
            <div class="modal-body">
                <p>
                    <b>Development version</b> is great to test and explore TS-website.
                    Remember, that this version is not finished and only intended for testing.
                    <b>We strongly advise you to NOT use it in production.</b>
                </p>

                <p><b>Continue only if you:</b></p>

                <ul>
                    <li class="mb-2">
                        <b>Want to try out development version of TS-website</b>
                    </li>
                    <li class="mb-2">
                        <b>Understand how websites work</b> and will be able to fix common problems with PHP, your web server and your database
                    </li>
                </ul>

                <p><b>Things that you might not like:</b></p>

                <ul class="mb-0">
                    <li class="mb-2">
                        <b>There is NO admin panel</b><br>
                        Configure it by modifying files and values in the database
                    </li>
                    <li class="mb-2">
                        <b>You break it, you fix it</b><br>
                        If something breaks, you need to read the error messages and fix the problem yourself.
                    </li>
                    <li>
                        <b>You might find bugs and problems</b><br>
                        If you do, please
                        <a href="https://github.com/Wruczek/ts-website/issues" target="_blank" rel="noopener">create an issue</a>
                        on GitHub
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">I understand</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="metrics-info" aria-labelledby="metrics-info-title">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="metrics-info-title">Metrics sent by TS-website</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <?= installerIcon("x") ?>
                </button>
            </div>
            <div class="modal-body">
                <p>
                    You might allow TS-website to send one-time metrics during the installation process.
                    The data sent contains only publicly known information, no private info is send.
                    The collected data will be used only to learn more about our users and improve TS-website.
                    We will never sell or share it to 3rd-parties. We might, however, publish some statistics
                    collected via the metrics. The published data will always be fully anonymous.
                </p>

                <p>
                    The data sent will be indexed with the sending server's IP address, to prevent abuse.
                </p>

                <p>
                    You can check all of the data sent by looking at the source code:
                    <code>installer/pages/7.php</code>
                </p>

                <p><b>Data sent by TS-website:</b></p>

                <ul class="mb-0">
                    <li class="mb-1">
                        Version of TS-website and PHP
                    </li>
                    <li class="mb-1">
                        List of loaded PHP extension names
                    </li>
                    <li class="mb-1">
                        Server identification string (contains mainly web server name and version)
                    </li>
                    <li class="mb-1">
                        Basic OS info (type, version, architecture)
                    </li>
                    <li>
                        TeamSpeak server info (version, build number, host OS name, slot count,
                        are you using serveradmin for query)
                    </li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<header class="installer-head reveal">
    <?= $stepChip ?>
    <h1 class="page-title">Welcome to TS-website</h1>
    <p class="page-sub">This wizard will guide you through the installation, step by step.</p>
</header>

<div class="installer-body">
    <?php // The requirements check touches an empty dbconfig.php, only a filled one means an earlier install ?>
    <?php if(file_exists(__CONFIG_FILE) && filesize(__CONFIG_FILE) > 0) { ?>
        <div class="alert alert-danger has-icon reveal" style="--i: 1" role="alert">
            <?= installerIcon("warning-circle", "alert-icon") ?>
            <code>dbconfig.php</code> already exists, so TS-website might have been installed before.
            If you continue, you will lose its data.
        </div>
    <?php } ?>

    <noscript>
        <div class="alert alert-warning has-icon" role="alert">
            <?= installerIcon("warning-circle", "alert-icon") ?>
            Please enable JavaScript before continuing.
        </div>
    </noscript>

    <div class="reveal" style="--i: 1">
        <p>
            Version <?= htmlspecialchars(__TSWEBSITE_VERSION) ?> (<?= htmlspecialchars(__TSWEBSITE_COMMIT) ?>).
            If you run into any problems, check the
            <a href="https://github.com/Wruczek/ts-website/wiki" target="_blank" rel="noopener">wiki</a>.
        </p>
    </div>

    <form method="post" action="?step=<?= $stepNumber + 1 ?>" class="reveal" style="--i: 2">
        <div class="custom-control custom-checkbox mt-4">
            <input type="checkbox" class="custom-control-input" id="allow-metrics-checkbox" name="allow-metrics-checkbox" checked>
            <label class="custom-control-label" for="allow-metrics-checkbox">
                Send one-time statistics to help improve TS-website
                (<a href="#" data-toggle="modal" data-target="#metrics-info">learn more</a>)
            </label>
        </div>

        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="accept-license-checkbox" name="accept-license-checkbox" required>
            <label class="custom-control-label" for="accept-license-checkbox">
                I read and accept the <a href="https://github.com/Wruczek/ts-website/blob/2.0/LICENSE.txt" target="_blank" rel="noopener">license</a>
            </label>
        </div>

        <div class="installer-actions">
            <button id="nextbutton" type="submit" class="btn btn-primary">
                Start<?= installerIcon("arrow-right", "i-arrow") ?>
            </button>
        </div>
    </form>
</div>

<?php ob_start(); ?>
<section class="side-block reveal" style="--i: 1" aria-labelledby="notes-title">
    <div class="side-head">
        <h2 class="side-title" id="notes-title">You will need</h2>
    </div>

    <ul class="howto">
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("database") ?></span>
            <div>
                <p class="howto-title">A MySQL or MariaDB database</p>
                <p class="howto-sub">Its address, a user with a password and the name of the database</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("key") ?></span>
            <div>
                <p class="howto-title">Query access to the TeamSpeak server</p>
                <p class="howto-sub">TeamSpeak 3: a query login. TeamSpeak 6: an SSH login or a WebQuery API key</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("folder-simple") ?></span>
            <div>
                <p class="howto-title">Write access to <code>private</code></p>
                <p class="howto-sub">The installer saves the database settings and the cache there</p>
            </div>
        </li>
    </ul>
</section>
<?php $pageNotes = ob_get_clean(); ?>

<script>
    $("#dev-release-notice").modal("show")

    // Start stays unavailable until the license is accepted; without JavaScript "required" does the same
    $("#accept-license-checkbox").change(function () {
        $("#nextbutton").prop("disabled", !this.checked)
    }).trigger("change")
</script>
