<?php

namespace Wruczek\TSWebsite\Utils;

use Latte\Engine;
use Latte\Runtime\Html;
use Wruczek\TSWebsite\AdminStatus;
use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Utils\Language\LanguageUtils;

/**
 * Class TemplateUtils
 * @package Wruczek\TSWebsite\Utils
 * @author Wruczek 2017
 */
class TemplateUtils {

    use SingletonTait;

    protected $latte;
    private $oldestCache;

    private function __construct() {
        $this->latte = new Engine();
        $this->getLatte()->setTempDirectory(__CACHE_DIR . "/templates");

        // Add custom filters...

        $this->getLatte()->addFilter("fuzzyDateAbbr", function ($s) {
            $default = DateUtils::formatDatetime($s);
            return new Html('<span data-relativetime="fuzzydate" data-timestamp="' . $s . '">' . $default . '</span>');
        });

        $this->getLatte()->addFilter("fullDate", function ($s) {
            $default = DateUtils::formatDatetime($s);
            return new Html('<span data-relativetime="fulldate" data-timestamp="' . $s . '">' . $default . '</span>');
        });

        $this->getLatte()->addFilter("translate", function ($s, ...$args) {
            return new Html(__get($s, $args));
        });

        // Status labels like "Uptime:" are stored with a trailing colon, the layout adds its own separation
        $this->getLatte()->addFilter("label", function ($s) {
            return new Html(preg_replace('/(\s|&nbsp;|\xC2\xA0)*:(\s|&nbsp;|\xC2\xA0)*$/u', "", (string) $s));
        });

        $this->getLatte()->addFunction("icon", function (string $name, string $class = "") {
            return new Html(self::icon($name, $class));
        });

        $this->getLatte()->addFunction("avatar", function (string $nickname, string $class = "") {
            return new Html(self::avatar($nickname, $class));
        });
    }

    /**
     * Monogram avatar for a TeamSpeak nickname. TeamSpeak avatars are not reachable
     * over WebQuery, so every person gets their first letter on a hue derived from the name:
     * the same person always looks the same, different people rarely collide.
     */
    public static function avatar(string $nickname, string $class = ""): string {
        $letter = "?";

        if (preg_match('/[\p{L}\p{N}]/u', $nickname, $match)) {
            $letter = mb_strtoupper($match[0]);
        }

        $hue = crc32(mb_strtolower($nickname)) % 360;
        $classes = trim("avatar $class");

        return '<span class="' . Utils::escape($classes) . '" style="--h: ' . $hue . '" aria-hidden="true">' . Utils::escape($letter) . '</span>';
    }

    /**
     * Returns an inline SVG that references a symbol from img/icons.svg (Phosphor icons)
     * @param string $name icon name without the "i-" prefix, for example "copy"
     * @param string $class additional CSS classes
     * @param string $basePath path to the website root, relative to the current page
     */
    public static function icon(string $name, string $class = "", string $basePath = ""): string {
        static $version = null;

        if ($version === null) {
            $version = (int) @filemtime(__BASE_DIR . "/img/icons.svg");
        }

        $classes = trim("i $class");
        $href = $basePath . "img/icons.svg?v=$version#i-" . rawurlencode($name);

        return '<svg class="' . Utils::escape($classes) . '" aria-hidden="true" focusable="false"><use href="' . Utils::escape($href) . '"></use></svg>';
    }

    /**
     * Returns latte object
     * @return \Latte\Engine Latte object
     */
    public function getLatte(): Engine {
        return $this->latte;
    }

    /**
     * Echoes rendered template
     * @throws \Exception
     * @see renderTemplateToString
     */
    public function renderTemplate(string $templateName, array $data = [], bool $loadLangs = true): void {
        echo $this->renderTemplateToString($templateName, $data, $loadLangs);
    }

    /**
     * Renders and outputs the error template
     * @param string $errorcode Error code
     * @param string $errorname Error title
     * @param string $description Error description
     */
    public function renderErrorTemplate(?string $errorcode = null, string $errorname = "Error", ?string $description = null): void {
        $data = [
            "errorcode" => $errorcode,
            "errorname" => $errorname,
            "description" => $description
        ];

        $this->renderTemplate("errorpage", $data, false);
    }

    /**
     * @param $templateName string Name of the template file, without path and extension
     * @param $data array Data passed to the template
     * @param bool $loadLangs true if the languages should be loaded (requires working database connection)
     * @return string Rendered template
     * @throws \Exception when we cannot get the CSRF token
     */
    public function renderTemplateToString($templateName, $data = [], $loadLangs = true): string {
        $dbutils = DatabaseUtils::i();

        if($loadLangs) {
            $langUtils = LanguageUtils::i();
            $userlang = $langUtils->getLanguageById(USER_LANGUAGE_ID);

            if ($userlang === null) {
                $userlang = $langUtils->getDefaultLanguage();
            }

            $data["languageList"] = LanguageUtils::i()->getLanguages();
            $data["userLanguage"] = $userlang;
        }

        if ($timestamp = $this->getOldestCacheTimestamp()) {
            $data["oldestTimestamp"] = $timestamp;
        }

        $data["tsExceptions"] = TeamSpeakUtils::i()->getExceptionsList();

        $data["config"] = [];
        $data["sqlCount"] = "none";

        // only fetch those when DB connection is established
        if($dbutils->isInitialised()) {
            $data["config"] = Config::i()->getConfig();
            $data["sqlCount"] = @$dbutils->getDb()->query("SHOW SESSION STATUS LIKE 'Questions'")->fetchColumn(1);

            if (Config::get("adminstatus_enabled")) {
                // The status is cached in a file that another request may be rewriting at this moment;
                // a failed read shows the admin status error instead of taking the whole page down
                try {
                    $data["adminStatus"] = AdminStatus::i()->getStatus(
                        Config::get("adminstatus_groups"),
                        Config::get("adminstatus_mode"),
                        Config::get("adminstatus_hideoffline"),
                        Config::get("adminstatus_ignoredusers")
                    );
                } catch (\Exception $e) {
                    $data["adminStatus"] = false;
                }
            }
        }

        $csrfToken = CsrfUtils::getToken();
        $data["csrfToken"] = $csrfToken;
        $data["csrfField"] = new Html('<input type="hidden" name="csrf-token" value="' . $csrfToken . '">');

        return $this->getLatte()->renderToString(__TEMPLATES_DIR . "/$templateName.latte", $data);
    }

    /**
     * Returns time elapsed from website load start until now
     * @param bool $raw If true, returns elapsed time in
     * milliseconds. Defaults to false.
     * @return string|float
     */
    public static function getRenderTime(bool $raw = false) {
        if($raw) {
            return microtime(true) - __RENDER_START;
        } else {
            return number_format(self::getRenderTime(true), 5);
        }
    }

    /**
     * Stores information about the oldest cached page element
     * for later to be displayed in a warning
     * @see getOldestCacheTimestamp
     * @param $data
     */
    public function storeOldestCache($data): void {
        if ($data["expired"] && (!$this->oldestCache || $this->oldestCache > $data["time"])) {
            $this->oldestCache = $data["time"];
        }
    }

    /**
     * @see storeOldestCache
     * @return int|null Oldest cache timestamp, null if not set
     */
    public function getOldestCacheTimestamp(): ?int {
        return $this->oldestCache;
    }

    /**
     * Outputs either script or link with all parameters needed
     * @param $resourceType string must be either "stylesheet" or "script"
     * @param $url string Relative or absolute path to the resource. {cdnjs} will be
     *        replaced with "https://cdnjs.cloudflare.com/ajax/libs"
     * @param $parameter string|bool|null If boolean, its gonna treat it as a local
     *        resource and add a version timestamp. If string, its gonna treat it as a
     *        integrity hash and add it along with crossorigin="anonymous" tag.
     */
    public static function includeResource(string $resourceType, string $url, $parameter = null): void {
        $url = str_replace('{cdnjs}', 'https://cdnjs.cloudflare.com/ajax/libs', $url);
        $attributes = "";

        if (is_bool($parameter)) {
            $filemtime = @filemtime(__BASE_DIR . "/" . $url);

            if ($filemtime !== false) {
                $url .= "?v=$filemtime";
            }
        } else if (is_string($parameter)) {
            // NEEDS to start with a space!
            $attributes = ' integrity="' . Utils::escape($parameter) . '" crossorigin="anonymous"';
        }

        if ($resourceType === "stylesheet") {
            echo '<link rel="stylesheet" href="' . Utils::escape($url) . '"' . $attributes . '>';
        } else if ($resourceType === "script") {
            echo '<script src="' . Utils::escape($url) . '"' . $attributes . '></script>';
        } else {
            throw new \InvalidArgumentException("$resourceType is not a valid resource type");
        }
    }

    /**
     * @see includeResource
     */
    public static function includeStylesheet(string $url, $parameter = null): void {
        self::includeResource("stylesheet", $url, $parameter);
    }

    /**
     * @see includeResource
     */
    public static function includeScript(string $url, $parameter = null): void {
        self::includeResource("script", $url, $parameter);
    }
}
