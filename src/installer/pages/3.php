<?php
if(!defined("__TSWEBSITE_VERSION")) die("Direct access not allowed");

use Medoo\Medoo;

if (!empty($_POST)) {
    $dbhostname = trim($_POST["dbhostname"]);
    $dbport = trim($_POST["dbport"] ?? "");
    $dbusername = trim($_POST["dbusername"]);
    $dbpassword = trim($_POST["dbpassword"]);
    $dbname = trim($_POST["dbname"]);
    $dbprefix = trim($_POST["dbprefix"]);
    $usingMysql = true;

    require_once __PRIVATE_DIR . "/vendor/autoload.php";

    if (empty($dbprefix)) {
        $dbprefix = "tsw_";
    }

    if ($dbport === "") {
        $dbport = "3306";
    }

    if (!preg_match("/^[0-9]{1,5}$/", $dbport) || (int) $dbport < 1 || (int) $dbport > 65535) {
        $errormessage = "Please enter a port between 1 and 65535";
    } else if (!empty($dbhostname) && !empty($dbusername) && !empty($dbname)) {
        $dbconfig = [
            "database_type" => "mysql",
            "server" => $dbhostname,
            "username" => $dbusername,
            "password" => $dbpassword,
            "database_name" => $dbname,
            "prefix" => $dbprefix,
            "port" => (int) $dbport,
            "charset" => "utf8mb4"
        ];
    } else {
        // no sqlite support for now :(
        $errormessage = "Please fill in your database details";

//        $usingMysql = false;
//        $dbconfig = [
//            "database_type" => "sqlite",
//            "database_file" => __LOCALDB_FILE
//        ];
    }

    // try to connect only if dbconfig is defined
    if (isset($dbconfig)) {
        try {
            $errmodeException = [
                "option" => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]
            ];

            // Enable DB exceptions instead of silent fails, only for the Medoo
            // object and not for the dbConfig, we dont want to get it saved
            $db = new Medoo($dbconfig + $errmodeException);

            $sqlfiles = [];

            if ($usingMysql) {
                $sqlfiles = [
                    "dbinstall_mysql",
                    "dbinstall_mysql_lang"
                ];
            } else {
                // no other option yet
            }

            foreach ($sqlfiles as $file) {
                $sqlquery = file_get_contents(__DIR__ . "/../$file.sql");

                if($sqlquery === false) {
                    throw new Exception("Cannot read SQL file: $file.sql");
                }

                $sqlquery = str_replace("DBPREFIX", $dbprefix, $sqlquery);
                $sqlresult = $db->pdo->exec($sqlquery);

                if ($sqlresult === false) {
                    throw new Exception("EXEC returned false");
                }
            }

            // if all queries succeeded, create a config file and save connection info there
            $phpcode = <<<EOT
<?php
/*
 * TS-website database config file
 * Generated at %s with TS-website %s (%s)
 */

return [
%s
];

EOT;
            $confarray = "";

            // Add all variables to the config. The values are single-quoted PHP strings, so only
            // quotes and backslashes need escaping (a password with ' used to break the file)
            foreach ($dbconfig as $key => $value) {
                $confarray .= sprintf("    '%s' => '%s'," . PHP_EOL, addcslashes($key, "'\\"), addcslashes($value, "'\\"));
            }

            // Remove semicolon and new line from the end
            $confarray = rtrim($confarray, "," . PHP_EOL);

            // Replace all variables with sprintf
            $phpcode = sprintf($phpcode, date("d-m-Y H:i:s"), __TSWEBSITE_VERSION, __TSWEBSITE_COMMIT, $confarray);

            if(file_put_contents(__CONFIG_FILE, $phpcode) === false) {
                $errormessage = "Cannot write to <code>" . __CONFIG_FILE . "</code>! Please check the file/directory permissions";
            } else {
                // redirect to next step on success
                header("Location: ?step=" . ($stepNumber + 1));
            }
        } catch (Exception $e) {
            $errormessage = htmlspecialchars("Error " . $e->getCode() . ": " . $e->getMessage());

            if($e->getCode() === 1045) {
                $errormessage .= '<br>You have entered wrong username and/or password. Please check it and try again.';
            }

            if($e->getCode() === 1049) {
                $errormessage .= '<br>Please manually create database "' . htmlspecialchars($dbname) . '" and try again.';
            }

            // PDO reports "Connection refused" with code 0, the MySQL client code is only in the message
            if(strpos($e->getMessage(), "[2002]") !== false) {
                $errormessage .= '<br>Check the host and the port, and that the database server is running.';
            }
        }
    }

}

// Keep what was typed when the form comes back with an error (never the password)
function dbField(string $name, string $default = ""): string {
    return htmlspecialchars(isset($_POST[$name]) ? trim((string) $_POST[$name]) : $default);
}
?>

<header class="installer-head reveal">
    <?= $stepChip ?>
    <h1 class="page-title">Database</h1>
    <p class="page-sub">TS-website keeps its settings, news and translations in a MySQL or MariaDB database.</p>
</header>

<div class="installer-body">
    <?php if(!empty($errormessage)) { ?>
        <div class="alert alert-danger has-icon reveal" style="--i: 1" role="alert">
            <?= installerIcon("warning-circle", "alert-icon") ?>
            <?= $errormessage ?>
        </div>
    <?php } ?>

    <fieldset class="form-group reveal" style="--i: 1">
        <legend class="sr-only">Database type</legend>

        <div class="custom-control custom-radio">
            <input type="radio" id="use-mysql-db" name="dbselection" class="custom-control-input" checked>
            <label class="custom-control-label" for="use-mysql-db">MySQL or MariaDB</label>
        </div>
        <div class="custom-control custom-radio">
            <input type="radio" id="use-sqlite-db" name="dbselection" class="custom-control-input" disabled>
            <label class="custom-control-label" for="use-sqlite-db">SQLite <span class="text-muted">(not supported yet)</span></label>
        </div>
    </fieldset>

    <form id="dbform" method="post" action="<?= "?step=$stepNumber" ?>" class="reveal" style="--i: 2" data-busy-form>
        <div class="field-row">
            <div class="form-group">
                <label for="dbhostname">Host</label>
                <input class="form-control" id="dbhostname" name="dbhostname" value="<?= dbField("dbhostname") ?>"
                       placeholder="127.0.0.1" required autofocus autocomplete="off" spellcheck="false" aria-describedby="dbhostname-help">
                <p class="form-text" id="dbhostname-help">Use <code>127.0.0.1</code> when the database runs on this server.</p>
            </div>

            <div class="form-group">
                <label for="dbport">Port</label>
                <input class="form-control" id="dbport" name="dbport" value="<?= dbField("dbport", "3306") ?>"
                       inputmode="numeric" pattern="[0-9]{1,5}" required autocomplete="off">
            </div>
        </div>

        <div class="form-group">
            <label for="dbusername">Username</label>
            <input class="form-control" id="dbusername" name="dbusername" value="<?= dbField("dbusername") ?>"
                   required autocomplete="off" spellcheck="false" aria-describedby="dbusername-help">
            <p class="form-text" id="dbusername-help">A separate account for TS-website is safer than root.</p>
        </div>

        <div class="form-group">
            <label for="dbpassword">Password</label>
            <input type="password" class="form-control" id="dbpassword" name="dbpassword" autocomplete="off">
        </div>

        <div class="form-group">
            <label for="dbname">Database name</label>
            <input class="form-control" id="dbname" name="dbname" value="<?= dbField("dbname") ?>"
                   required autocomplete="off" spellcheck="false" aria-describedby="dbname-help">
            <p class="form-text" id="dbname-help">The database has to exist already. Its tables will be created now.</p>
        </div>

        <div class="form-group">
            <label for="dbprefix">Table prefix <span class="text-muted">(optional)</span></label>
            <input class="form-control" id="dbprefix" name="dbprefix" value="<?= dbField("dbprefix") ?>"
                   placeholder="tsw_" autocomplete="off" spellcheck="false">
        </div>

        <div class="installer-actions">
            <a href="?step=<?= $stepNumber - 1 ?>" class="btn btn-ghost">
                <?= installerIcon("arrow-left") ?>Back
            </a>
            <button type="submit" class="btn btn-primary">
                Connect and continue<?= installerIcon("arrow-right", "i-arrow") ?><?= installerIcon("circle-notch", "i-busy") ?>
            </button>
        </div>
    </form>
</div>

<?php ob_start(); ?>
<section class="side-block reveal" style="--i: 1" aria-labelledby="notes-title">
    <div class="side-head">
        <h2 class="side-title" id="notes-title">When you continue</h2>
    </div>

    <ul class="howto">
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("database") ?></span>
            <div>
                <p class="howto-title">The tables are created</p>
                <p class="howto-sub">config, faq, news, languages and translations, each with the table prefix in front</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("warning-circle") ?></span>
            <div>
                <p class="howto-title">Tables with the same names are replaced</p>
                <p class="howto-sub">An earlier install with this prefix loses its data. Pick another prefix to keep it</p>
            </div>
        </li>
        <li>
            <span class="howto-icon" aria-hidden="true"><?= installerIcon("floppy-disk") ?></span>
            <div>
                <p class="howto-title">The connection is saved on this server</p>
                <p class="howto-sub"><code>private/dbconfig.php</code></p>
            </div>
        </li>
    </ul>
</section>
<?php $pageNotes = ob_get_clean(); ?>

<script>
    // The server may need a while to connect and create the tables: show it and block a second submit
    $("[data-busy-form]").on("submit", function (e) {
        var button = $(this).find('button[type="submit"]')

        if (button.hasClass("is-busy")) {
            e.preventDefault()
            return
        }

        button.addClass("is-busy").attr("aria-disabled", "true")
    })
</script>
