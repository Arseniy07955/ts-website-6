<?php

namespace Wruczek\TSWebsite;

use TeamSpeak3;
use Wruczek\TSWebsite\Utils\TemplateUtils;
use Wruczek\TSWebsite\Utils\Utils;

class ViewerRenderer {

    // The legend next to the tree (viewer.latte) lists the flags in this order, in two groups
    const LEGEND_GROUPS = [
        "channels" => ["house", "lock-simple", "music-notes", "hand", "eye-slash", "prohibit"],
        "people" => ["moon", "speaker-slash", "microphone-slash", "megaphone-simple", "crown", "microphone", "hard-drives"],
    ];

    private $imgPath;
    private $resultHtml;

    private $serverInfo;
    private $channelList;
    private $clientList;
    private $serverGroupList;
    private $channelGroupList;

    private $renderQueryClients = false;

    private $hiddenChannelIds;

    // icon => [label => true] for every flag the tree has drawn, see getLegend()
    private $usedFlags = [];

    /**
     * @param string $imgPath path to img/ts-icons, used for icons given by file name in getIcon().
     *                        Icons from the TeamSpeak server and the interface icons are linked
     *                        relative to the page the viewer is shown on (api/geticon.php, img/icons.svg)
     * @param array $hiddenChannelIds channels rendered without their members
     */
    public function __construct(string $imgPath, array $hiddenChannelIds = []) {
        $this->imgPath = $imgPath;
        $this->hiddenChannelIds = $hiddenChannelIds;

        $cm = CacheManager::i();
        $this->serverInfo = $cm->getServerInfo();
        $this->channelList = $cm->getChannelList();
        $this->clientList = $cm->getClientList();
        $this->serverGroupList = $cm->getServerGroupList();
        $this->channelGroupList = $cm->getChannelGroupList();
    }

    /**
     * Checks if we have successfully loaded all required data from cache.
     * Loading data from CacheManager might fail for example when the server is offline,
     * or when we dont have required permissions to check for a specific item.
     * @return bool true on success, false otherwise
     */
    public function checkRequiredData(): bool {
        return isset($this->channelList, $this->clientList, $this->serverGroupList, $this->channelGroupList);
    }

    private function add(string $html, string ...$args): void {
        foreach ($args as $i => $iValue) {
            // Prevent argument placeholder injection
            $iValue = str_replace(["{", "}"], ["&#123;", "&#125;"], $iValue);

            $html = str_ireplace('{' . $i . '}', $iValue, $html);
        }

        $this->resultHtml .= $html;
    }

    public function renderViewer(): string {
        if (!$this->checkRequiredData()) {
            throw new \Exception("Failed to load required data from the cache. " .
                "Is the server online? Do we have enough permissions?");
        }

        $serverIcon = "";

        if (!empty($this->serverInfo["virtualserver_icon_id"])) {
            $serverIcon = $this->getIcon($this->serverInfo["virtualserver_icon_id"], __get("VIEWER_SERVER_ICON"));
        }

        $online = 0;

        foreach ($this->clientList as $client) {
            if ($this->renderQueryClients || !$client["client_type"]) {
                $online++;
            }
        }

        // The server is the root row: clicking it offers to connect without a channel
        $html = <<<EOD
<div class="channel-container is-server">
    <div class="channel" data-channelid="0" tabindex="0" role="button" aria-haspopup="dialog">
        <span class="channel-name">{0}<span class="channel-label">{1}</span></span>
        <span class="channel-count">{2}</span>
    </div>
</div>

EOD;

        $this->add(
            $html,
            $serverIcon,
            Utils::escape((string) ($this->serverInfo["virtualserver_name"] ?? "")),
            (string) $online
        );

        if ($online === 0) {
            $this->add('<p class="viewer-note">{0}</p>' . PHP_EOL . PHP_EOL, __get("VIEWER_NOBODY_ONLINE"));
        }

        foreach ($this->channelList as $channel) {
            // Start rendering the top channels, they are gonna
            // render all the children recursively
            if ($channel["pid"] === 0) {
                $this->renderChannel(new TeamSpeakChannel($channel));
            }
        }

        return $this->resultHtml;
    }

    /**
     * Icon image: a file from img/ts-icons when $name is a string,
     * otherwise an icon id from the TeamSpeak server (user content, always 16px)
     */
    public function getIcon($name, ?string $tooltip = null, ?string $alt = null): string {
        if (is_string($name)) {
            $path = "{$this->imgPath}/$name";
        } else {
            $path = "api/geticon.php?iconid=" . (int) $name;
        }

        $ttip = $tooltip ? ' data-toggle="tooltip" title="' . Utils::escape($tooltip) . '"' : "";
        $alt = $alt ?? (string) $tooltip;

        return '<img class="icon" src="' . Utils::escape($path) . '" width="16" height="16" alt="' . Utils::escape($alt) . '"' . $ttip . '>';
    }

    /**
     * Small monochrome icon from img/icons.svg with a tooltip, for channel flags and client states
     * @param string|null $legendLabel what the legend calls the icon, when that is broader than the
     *                                 tooltip: one line for states the icon cannot tell apart
     */
    private function getFlag(string $icon, string $label, string $class = "viewer-flag", ?string $legendLabel = null): string {
        $this->usedFlags[$icon][$legendLabel ?? $label] = true;
        $label = Utils::escape($label);

        return '<span class="' . $class . '" role="img" aria-label="' . $label . '" data-toggle="tooltip" title="' . $label . '">' .
            TemplateUtils::icon($icon) . '</span>';
    }

    /**
     * The flags drawn by renderViewer(), for the legend next to the tree. One entry per icon; an icon
     * that stands for states with different legend labels carries all of them.
     * @return array ["channels" => [["icon" => string, "labels" => string[]], ...], "people" => [...]],
     *               a group is left out when none of its flags is in the tree
     */
    public function getLegend(): array {
        $legend = [];

        foreach (self::LEGEND_GROUPS as $group => $icons) {
            foreach ($icons as $icon) {
                if (isset($this->usedFlags[$icon])) {
                    $legend[$group][] = ["icon" => $icon, "labels" => array_keys($this->usedFlags[$icon])];
                }
            }
        }

        return $legend;
    }

    public function renderChannel(TeamSpeakChannel $channel): void {
        $isTopLevel = !$channel->getParentId();
        $isHidden = $this->isHiddenChannel($channel);
        $isSpacer = $channel->isSpacer();
        $info = $channel->getInfo();

        $members = $isHidden ? [] : $channel->getChannelMembers($this->renderQueryClients);
        $children = $channel->getChildChannels();

        $classes = [$isTopLevel ? "no-parent" : "has-parent"];

        // "not-occupied" is what the "hide empty channels" switch hides, so a channel only counts
        // as occupied when someone is actually shown in it or somewhere below it
        if ($members) {
            $classes[] = "is-occupied";
        } else if ($this->hasVisibleMembers($children)) {
            $classes[] = "occupied-childs";
        } else {
            $classes[] = "not-occupied";
        }

        if ($members || $children) {
            $classes[] = "has-children";
        }

        // The connect dialog (js/viewer.js) says why nobody is listed in it
        if ($isHidden) {
            $classes[] = "is-hidden";
        }

        if ($isSpacer) {
            $classes[] = "is-spacer";
            $isRepeat = $channel->getSpacerAlign() === TeamSpeak3::SPACER_ALIGN_REPEAT;

            switch ($channel->getSpacerAlign()) {
                case TeamSpeak3::SPACER_ALIGN_REPEAT:
                    $classes[] = "spacer-repeat";
                    break;
                case TeamSpeak3::SPACER_ALIGN_CENTER:
                    $classes[] = "spacer-center";
                    break;
                case TeamSpeak3::SPACER_ALIGN_RIGHT:
                    $classes[] = "spacer-right";
                    break;
                case TeamSpeak3::SPACER_ALIGN_LEFT:
                    $classes[] = "spacer-left";
                    break;
            }

            // Repeated spacers ("___", "---", "...") become a single hairline, the others a quiet label
            if ($isRepeat) {
                $row = '<div class="channel" data-channelid="{1}" role="separator"></div>';
            } else {
                $row = '<div class="channel" data-channelid="{1}">' . PHP_EOL .
                       '        <span class="channel-name"><span class="channel-label">{2}</span></span>' . PHP_EOL .
                       '    </div>';
            }

            $this->add(
                '<div class="channel-container {0}">' . PHP_EOL . '    ' . $row . PHP_EOL . PHP_EOL,
                implode(" ", $classes),
                (string) $channel->getId(),
                Utils::escape($channel->getDisplayName())
            );
        } else {
            if ($info["channel_flag_password"]) {
                $classes[] = "has-password";
            }

            if ($channel->isFullyOccupied()) {
                $classes[] = "is-full";
            }

            $topic = trim((string) ($info["channel_topic"] ?? ""));

            $html = <<<EOD
<div class="channel-container {0}">
    <div class="channel" data-channelid="{1}" tabindex="0" role="button" aria-haspopup="dialog">
        <span class="channel-name"><span class="channel-label">{2}</span>{3}</span>{4}{5}
    </div>

EOD;

            $this->add(
                $html,
                implode(" ", $classes),
                (string) $channel->getId(),
                Utils::escape($channel->getDisplayName()),
                $topic !== "" ? '<span class="channel-topic">' . Utils::escape($topic) . '</span>' : "",
                $this->getChannelFlags($channel, $isHidden, (bool) $members),
                $this->getChannelCount($channel, count($members))
            );
        }

        foreach ($members as $member) {
            $this->renderClient($member);
        }

        foreach ($children as $child) {
            $this->renderChannel($child);
        }

        $this->add('</div>' . PHP_EOL . PHP_EOL);
    }

    /**
     * True when someone is shown in one of the channels or anywhere below them
     * @param TeamSpeakChannel[] $channels
     */
    private function hasVisibleMembers(array $channels): bool {
        foreach ($channels as $channel) {
            if (!$this->isHiddenChannel($channel) && $channel->getChannelMembers($this->renderQueryClients)) {
                return true;
            }

            if ($this->hasVisibleMembers($channel->getChildChannels())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Hidden channels are rendered without their members
     */
    private function isHiddenChannel(TeamSpeakChannel $channel): bool {
        return in_array($channel->getId(), $this->hiddenChannelIds, true) ||
               $channel->getInfo()["channel_needed_subscribe_power"] >= 75;
    }

    /**
     * Channels that have people in them right now, in tree order. Follows the same
     * visibility rules as the viewer, so hidden channels never expose their members.
     * @return array|null list of ["id" => int, "name" => string, "parents" => string[], "clients" => string[]],
     *                    null when the server data is not available
     */
    public function getOccupiedChannels(): ?array {
        if (!$this->checkRequiredData()) {
            return null;
        }

        $result = [];

        foreach ($this->channelList as $channel) {
            if ($channel["pid"] === 0) {
                $this->collectOccupiedChannels(new TeamSpeakChannel($channel), [], $result);
            }
        }

        return $result;
    }

    private function collectOccupiedChannels(TeamSpeakChannel $channel, array $parents, array &$result): void {
        if (!$channel->isSpacer() && !$this->isHiddenChannel($channel)) {
            $clients = [];

            foreach ($channel->getChannelMembers($this->renderQueryClients) as $member) {
                $clients[] = (string) $member["client_nickname"];
            }

            if ($clients) {
                $result[] = [
                    "id" => $channel->getId(),
                    "name" => $channel->getDisplayName(),
                    "parents" => $parents,
                    "clients" => $clients,
                ];
            }
        }

        if (!$channel->isSpacer()) {
            $parents[] = $channel->getDisplayName();
        }

        foreach ($channel->getChildChannels() as $child) {
            $this->collectOccupiedChannels($child, $parents, $result);
        }
    }

    public function renderClient(array $client): void {
        $isQuery = (bool) $client["client_type"];
        $isAway = (bool) $client["client_away"];

        $clientSGIDs = explode(",", (string) $client["client_servergroups"]);
        $clientServerGroups = [];

        $beforeName = [];
        $afterName = [];

        foreach ($this->serverGroupList as $servergroup) {
            $groupid = $servergroup["sgid"];

            if (in_array($groupid, $clientSGIDs)) {
                $clientServerGroups[$groupid] = $servergroup;

                if ($servergroup["namemode"] === TeamSpeak3::GROUP_NAMEMODE_BEFORE) {
                    $beforeName[] = "[{$servergroup["name"]}]";
                }

                if ($servergroup["namemode"] === TeamSpeak3::GROUP_NAMEMODE_BEHIND) {
                    $afterName[] = "[{$servergroup["name"]}]";
                }
            }
        }

        // Nickname in full color; group prefixes/suffixes and the away message stay quiet
        $clientName = "";

        if ($beforeName) {
            $clientName .= '<span class="client-affix">' . Utils::escape(implode(" ", $beforeName)) . '</span> ';
        }

        $clientName .= '<span class="client-nick">' . Utils::escape((string) $client["client_nickname"]) . '</span>';

        if ($afterName) {
            $clientName .= ' <span class="client-affix">' . Utils::escape(implode(" ", $afterName)) . '</span>';
        }

        if ($isAway && isset($client["client_away_message"]) && (string) $client["client_away_message"] !== "") {
            $clientName .= ' <span class="client-away">' . Utils::escape((string) $client["client_away_message"]) . '</span>';
        }

        $html = <<<EOD
<div class="client-container{0}" data-clientdbid="{1}" tabindex="0">
    {2}{3}
    <span class="client-name">{4}</span>{5}
</div>

EOD;

        $classes = ($isQuery ? " is-query" : "") . ($isAway ? " is-away" : "");
        $suffixIcons = $this->getClientSuffixIcons($client, $clientServerGroups, 0);

        $this->add(
            $html,
            $classes,
            (string) $client["client_database_id"],
            $this->getClientIcon($client),
            TemplateUtils::avatar((string) $client["client_nickname"], "viewer-avatar"),
            $clientName,
            $suffixIcons !== "" ? '<span class="client-icons">' . $suffixIcons . '</span>' : ""
        );
    }

    /**
     * Flags at the right edge of a channel row: small monochrome icons with tooltips,
     * followed by the channel icon from the TeamSpeak server
     */
    private function getChannelFlags(TeamSpeakChannel $channel, bool $isHidden, bool $isOccupied): string {
        $info = $channel->getInfo();
        $html = "";

        if ($channel->isDefaultChannel()) {
            $html .= $this->getFlag("house", __get("VIEWER_DEFAULT_CHANNEL"));
        }

        if ($info["channel_flag_password"]) {
            $html .= $this->getFlag("lock-simple", __get("VIEWER_CHANNEL_PASSWORD"));
        }

        $codec = $info["channel_codec"];
        if ($codec === TeamSpeak3::CODEC_CELT_MONO || $codec === TeamSpeak3::CODEC_OPUS_MUSIC) {
            $html .= $this->getFlag("music-notes", __get("VIEWER_CHANNEL_MUSIC_CODED"));
        }

        if ($info["channel_needed_talk_power"]) {
            $html .= $this->getFlag("hand", __get("VIEWER_CHANNEL_MODERATED"));
        }

        if ($isHidden) {
            $html .= $this->getFlag("eye-slash", __get("VIEWER_CHANNEL_UNSUB2"));
        }

        // An occupied full channel already says so with its count ("5/5")
        if (!$isOccupied && $channel->isFullyOccupied()) {
            $html .= $this->getFlag("prohibit", __get("VIEWER_CHANNEL_OCCUPIED"));
        }

        if ($info["channel_icon_id"]) {
            $html .= $this->getIcon($info["channel_icon_id"], __get("VIEWER_CHANNEL_ICON"));
        }

        return $html !== "" ? PHP_EOL . '        <span class="channel-icons">' . $html . '</span>' : "";
    }

    /**
     * Number of people shown in the channel, with the slot limit when the channel has one
     */
    private function getChannelCount(TeamSpeakChannel $channel, int $count): string {
        if ($count === 0) {
            return "";
        }

        $max = (int) $channel->getInfo()["channel_maxclients"];
        $text = $max >= 0 ? $count . '<span class="channel-count-max">/' . $max . '</span>' : (string) $count;
        $ttip = $channel->isFullyOccupied() ? ' data-toggle="tooltip" title="' . Utils::escape(__get("VIEWER_CHANNEL_OCCUPIED")) . '"' : "";

        return PHP_EOL . '        <span class="channel-count"' . $ttip . '>' . $text . '</span>';
    }

    /**
     * The one voice state that matters most, shown in a fixed slot before the name
     */
    public function getClientIcon(array $client): string {
        $icon = null;
        $label = null;
        $legendLabel = null;

        // A muted and a switched-off device look the same in the tree; the tooltip tells them
        // apart, the legend names them once
        if ($client["client_type"]) {
            $icon = "hard-drives";
            $label = "ServerQuery";
        } else if ($client["client_away"]) {
            $icon = "moon";
            $label = __get("VIEWER_CLIENT_AWAY");
        } else if (!$client["client_output_hardware"]) {
            $icon = "speaker-slash";
            $label = __get("VIEWER_CLIENT_OUTPUT_DISABLED");
            $legendLabel = __get("VIEWER_LEGEND_SOUND_OFF");
        } else if ($client["client_output_muted"]) {
            $icon = "speaker-slash";
            $label = __get("VIEWER_CLIENT_OUTPUT_MUTED");
            $legendLabel = __get("VIEWER_LEGEND_SOUND_OFF");
        } else if (!$client["client_input_hardware"]) {
            $icon = "microphone-slash";
            $label = __get("VIEWER_CLIENT_MIC_DISABLED");
            $legendLabel = __get("VIEWER_LEGEND_MIC_OFF");
        } else if ($client["client_input_muted"]) {
            $icon = "microphone-slash";
            $label = __get("VIEWER_CLIENT_MIC_MUTED");
            $legendLabel = __get("VIEWER_LEGEND_MIC_OFF");
        }

        if ($icon === null) {
            return '<span class="client-state" aria-hidden="true"></span>';
        }

        return $this->getFlag($icon, $label, "client-state", $legendLabel);
    }

    public function getClientSuffixIcons(array $client, array $groups, int $neededTalkPower): string {
        $html = "";

        if ($client["client_is_priority_speaker"]) {
            $html .= $this->getFlag("megaphone-simple", __get("VIEWER_CLIENT_PRIORITY_SPEAKER"));
        }

        if ($client["client_is_channel_commander"]) {
            $html .= $this->getFlag("crown", __get("VIEWER_CLIENT_COMMANDER"));
        }

        if ($client["client_is_talker"]) {
            $html .= $this->getFlag("microphone", __get("VIEWER_CLIENT_TALK_POWER_GRANTED"));
        } else if ($neededTalkPower && $neededTalkPower > $client["client_talk_power"]) {
            $html .= $this->getFlag("microphone-slash", __get("VIEWER_CLIENT_TALK_POWER_INSUFFICIENT"));
        }

        foreach ($groups as $group) {
            // Groups without an icon are skipped. Remove this check to show them with a "broken image" icon
            if (!$group["iconid"]) {
                continue;
            }

            $html .= $this->getIcon($group["iconid"], (string) $group["name"]);
        }

        if ($client["client_icon_id"]) {
            $html .= $this->getIcon($client["client_icon_id"], __get("VIEWER_CLIENT_ICON"));
        }

        if ($client["client_country"]) {
            // js/viewer.js puts the full country name into the tooltip
            $country = Utils::escape(strtoupper((string) $client["client_country"]));
            $html .= '<abbr class="client-country" data-toggle="tooltip" title="' . $country . '">' . $country . '</abbr>';
        }

        return $html;
    }
}
