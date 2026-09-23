<?php

namespace Wruczek\TSWebsite\Query;

/**
 * ServerQuery transport over HTTP WebQuery (TeamSpeak 6, TeamSpeak 3 >= 3.12).
 *
 * The TS3 framework speaks the raw query protocol, so every command line it sends
 * is translated into a WebQuery request ("clientlist -uid" -> GET /1/clientlist?-uid)
 * and the JSON reply is translated back into raw query lines ("k=v k=v|..." + "error id=0 msg=ok").
 * WebQuery is stateless and authenticated with an API key, so "login", "use" etc. are emulated here.
 */
class HttpTransport extends \TeamSpeak3_Transport_Abstract {

    /** Commands that are not bound to a virtual server */
    const INSTANCE_COMMANDS = [
        "version", "hostinfo", "instanceinfo", "instanceedit", "bindinglist", "serverlist",
        "serveridgetbyport", "servercreate", "serverdelete", "serverstart", "serverstop",
        "serverprocessstop", "gm", "logview", "permissionlist", "permidgetbyname",
        "apikeyadd", "apikeydel", "apikeylist",
    ];

    /** @var string[] reply lines waiting to be read */
    protected $lines = [];

    /** @var int selected virtual server id, 0 = none */
    protected $sid = 0;

    /** @var int selected virtual server port */
    protected $serverPort = 0;

    protected $connected = false;

    public function connect() {
        $this->connected = true;
    }

    public function disconnect() {
        if (!$this->connected) {
            return;
        }

        $this->connected = false;
        $this->lines = [];

        \TeamSpeak3_Helper_Signal::getInstance()->emit(strtolower($this->getAdapterType()) . "Disconnected");
    }

    public function isConnected() {
        return $this->connected;
    }

    public function read($length = 4096) {
        return $this->readLine();
    }

    public function readLine($token = "\n") {
        $this->connect();

        if (empty($this->lines)) {
            throw new \TeamSpeak3_Transport_Exception("no data available from server '" . $this->getBaseUrl() . "'");
        }

        $line = array_shift($this->lines);

        \TeamSpeak3_Helper_Signal::getInstance()->emit(strtolower($this->getAdapterType()) . "DataRead", $line);

        return new \TeamSpeak3_Helper_String($line);
    }

    public function send($data) {
        $this->sendLine($data);
    }

    public function sendLine($data, $separator = "\n") {
        $this->connect();

        \TeamSpeak3_Helper_Signal::getInstance()->emit(strtolower($this->getAdapterType()) . "DataSend", $data);

        list($command, $params) = $this->parseCommand(trim($data));
        $this->lines = $this->handleCommand($command, $params);
    }

    /**
     * Splits a raw query command into its name and a list of [key, value] params.
     * Flags ("-uid") have a null value.
     */
    protected function parseCommand(string $data): array {
        $cells = explode(\TeamSpeak3::SEPARATOR_CELL, $data);
        $command = strtolower(array_shift($cells));
        $params = [];

        foreach ($cells as $cell) {
            if ($cell === "") {
                continue;
            }

            // "clid=1|clid=2" - repeated parameter
            foreach (explode(\TeamSpeak3::SEPARATOR_LIST, $cell) as $pair) {
                if ($pair === "") {
                    continue;
                }

                if ($pair[0] === "-") {
                    $params[] = [$pair, null];
                    continue;
                }

                $parts = explode(\TeamSpeak3::SEPARATOR_PAIR, $pair, 2);
                $value = \TeamSpeak3_Helper_String::factory($parts[1] ?? "")->unescape()->toString();
                $params[] = [$parts[0], $value];
            }
        }

        return [$command, $params];
    }

    /**
     * @return string[] raw query reply lines
     */
    protected function handleCommand(string $command, array $params): array {
        switch ($command) {
            // authentication is done with the API key, the session is stateless
            case "login":
            case "logout":
            case "quit":
            case "clientupdate":
            case "servernotifyregister":
            case "servernotifyunregister":
                return [$this->errorLine(0, "ok")];

            case "use":
                return $this->handleUse($params);
        }

        $reply = $this->request($command, $params);

        if ($command === "whoami" && $this->sid && $reply["code"] === 0) {
            foreach ($reply["body"] as &$row) {
                $row["virtualserver_id"] = $this->sid;

                if ($this->serverPort) {
                    $row["virtualserver_port"] = $this->serverPort;
                }
            }
            unset($row);
        }

        return $this->toRawLines($reply);
    }

    protected function handleUse(array $params): array {
        $values = [];

        foreach ($params as list($key, $value)) {
            $values[$key] = $value;
        }

        if (isset($values["sid"])) {
            $this->sid = (int) $values["sid"];
            $this->serverPort = 0;
            return [$this->errorLine(0, "ok")];
        }

        if (isset($values["port"])) {
            $reply = $this->request("serveridgetbyport", [["virtualserver_port", $values["port"]]]);

            if ($reply["code"] !== 0) {
                return $this->toRawLines($reply);
            }

            $this->sid = (int) ($reply["body"][0]["server_id"] ?? 0);
            $this->serverPort = (int) $values["port"];

            if (!$this->sid) {
                return [$this->errorLine(1024, "invalid serverID")];
            }

            return [$this->errorLine(0, "ok")];
        }

        return [$this->errorLine(1538, "invalid parameter")];
    }

    /**
     * Sends a WebQuery request
     * @return array ["code" => int, "message" => string, "extra" => string|null, "body" => array]
     * @throws \TeamSpeak3_Transport_Exception
     */
    protected function request(string $command, array $params): array {
        $path = "/" . rawurlencode($command);

        if ($this->sid && !in_array($command, self::INSTANCE_COMMANDS, true)) {
            $path = "/" . $this->sid . $path;
        }

        $query = [];

        foreach ($params as list($key, $value)) {
            $query[] = $value === null ? rawurlencode($key) : rawurlencode($key) . "=" . rawurlencode($value);
        }

        $url = $this->getBaseUrl() . $path . (empty($query) ? "" : "?" . implode("&", $query));
        $response = $this->httpGet($url);
        $json = json_decode($response, true);

        if (!is_array($json) || !isset($json["status"]["code"])) {
            throw new \TeamSpeak3_Transport_Exception("invalid reply from WebQuery '" . $this->getBaseUrl() . "': " . substr($response, 0, 200));
        }

        $body = $json["body"] ?? [];

        return [
            "code" => (int) $json["status"]["code"],
            "message" => (string) ($json["status"]["message"] ?? ""),
            "extra" => $json["status"]["extra_message"] ?? null,
            "body" => is_array($body) ? $body : [],
        ];
    }

    protected function httpGet(string $url): string {
        $timeout = intval($this->config["timeout"]);
        $headers = ["Accept: application/json"];

        if (!empty($this->config["apikey"])) {
            $headers[] = "x-api-key: " . $this->config["apikey"];
        }

        if (function_exists("curl_init")) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                // self-signed certificates are common on query https ports
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create([
                "http" => [
                    "method" => "GET",
                    "header" => implode("\r\n", $headers),
                    "timeout" => $timeout,
                    "ignore_errors" => true, // WebQuery errors come with 4xx codes and a JSON body
                ],
                "ssl" => ["verify_peer" => false, "verify_peer_name" => false],
            ]);
            $response = @file_get_contents($url, false, $context);
            $error = error_get_last()["message"] ?? "unknown error";
        }

        if (!is_string($response) || $response === "") {
            throw new \TeamSpeak3_Transport_Exception("connection to WebQuery '" . $this->getBaseUrl() . "' failed ($error)");
        }

        return $response;
    }

    protected function getBaseUrl(): string {
        $host = strval($this->config["host"]);
        $host = strpos($host, ":") !== false ? "[$host]" : $host;
        $scheme = empty($this->config["https"]) ? "http" : "https";

        return "$scheme://$host:" . intval($this->config["port"]);
    }

    protected function toRawLines(array $reply): array {
        $lines = [];

        if ($reply["code"] === 0 && !empty($reply["body"])) {
            $rows = [];

            foreach ($reply["body"] as $row) {
                $cells = [];

                foreach ((array) $row as $key => $value) {
                    $cells[] = $value === null || $value === ""
                        ? $key
                        : $key . \TeamSpeak3::SEPARATOR_PAIR . $this->escape($value);
                }

                $rows[] = implode(\TeamSpeak3::SEPARATOR_CELL, $cells);
            }

            $lines[] = implode(\TeamSpeak3::SEPARATOR_LIST, $rows);
        }

        $lines[] = $this->errorLine($reply["code"], $reply["message"], $reply["extra"]);

        return $lines;
    }

    protected function errorLine(int $code, string $message, $extra = null): string {
        $line = "error id=$code msg=" . $this->escape($message);

        if ($extra !== null && $extra !== "") {
            $line .= " extra_msg=" . $this->escape($extra);
        }

        return $line;
    }

    protected function escape($value): string {
        if (is_bool($value)) {
            $value = $value ? "1" : "0";
        } elseif (is_array($value)) {
            $value = json_encode($value);
        }

        return \TeamSpeak3_Helper_String::factory((string) $value)->escape()->toString();
    }
}
