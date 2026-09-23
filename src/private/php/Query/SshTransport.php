<?php

namespace Wruczek\TSWebsite\Query;

use phpseclib3\Net\SSH2;

/**
 * ServerQuery transport over SSH, built on phpseclib (no ext-ssh2 needed).
 * TeamSpeak 6 servers only offer SSH and HTTP query - the old raw
 * telnet query (port 10011) is gone, so this is the way to talk to them.
 * Works with TeamSpeak 3 servers that have SSH query enabled as well.
 */
class SshTransport extends \TeamSpeak3_Transport_Abstract {

    /** @var SSH2|null */
    protected $ssh;

    /** @var string|null last command sent, used to drop its echo from the pty */
    protected $lastCommand;

    public function connect() {
        if ($this->ssh !== null) {
            return;
        }

        $host = strval($this->config["host"]);
        $port = intval($this->config["port"]);
        $timeout = intval($this->config["timeout"]);

        try {
            $ssh = new SSH2($host, $port, $timeout);
            // same terminal type ext-ssh2 used in the TS3 framework
            $ssh->setTerminal("raw");
            $ssh->setTimeout($timeout);
            $loggedIn = $ssh->login(strval($this->config["username"]), strval($this->config["password"]));
        } catch (\Exception $e) {
            throw new \TeamSpeak3_Transport_Exception("failed to establish secure shell connection to server '$host:$port' (" . $e->getMessage() . ")");
        }

        if (!$loggedIn) {
            throw new \TeamSpeak3_Adapter_ServerQuery_Exception("invalid loginname or password", 0x208);
        }

        $this->ssh = $ssh;
    }

    public function disconnect() {
        if ($this->ssh === null) {
            return;
        }

        $this->ssh->disconnect();
        $this->ssh = null;

        \TeamSpeak3_Helper_Signal::getInstance()->emit(strtolower($this->getAdapterType()) . "Disconnected");
    }

    public function isConnected() {
        return $this->ssh !== null && $this->ssh->isConnected();
    }

    public function read($length = 4096) {
        $this->connect();

        $data = $this->ssh->read("", SSH2::READ_NEXT);

        if (!is_string($data)) {
            throw new \TeamSpeak3_Transport_Exception("connection to server '" . $this->config["host"] . ":" . $this->config["port"] . "' lost");
        }

        \TeamSpeak3_Helper_Signal::getInstance()->emit(strtolower($this->getAdapterType()) . "DataRead", $data);

        return new \TeamSpeak3_Helper_String($data);
    }

    public function readLine($token = "\n") {
        $this->connect();

        while (true) {
            $data = $this->readRaw($token);

            \TeamSpeak3_Helper_Signal::getInstance()->emit(strtolower($this->getAdapterType()) . "DataRead", $data);

            $line = $this->cleanLine($data);

            // skip blank lines and prompts, the reply parser does not expect them
            if ($line === "") {
                continue;
            }

            // skip the echo of our own command
            if ($this->lastCommand !== null && $line === $this->lastCommand) {
                $this->lastCommand = null;
                continue;
            }

            return new \TeamSpeak3_Helper_String($line);
        }
    }

    public function send($data) {
        $this->connect();

        $this->ssh->write($data);

        \TeamSpeak3_Helper_Signal::getInstance()->emit(strtolower($this->getAdapterType()) . "DataSend", $data);
    }

    public function sendLine($data, $separator = "\n") {
        $this->lastCommand = trim($data);
        $this->send($data . $separator);
    }

    /**
     * Reads from the shell until $token.
     * @throws \TeamSpeak3_Transport_Exception on timeout or lost connection
     */
    protected function readRaw(string $token): string {
        $data = $this->ssh->read($token);

        if (!is_string($data) || $data === "" || substr($data, -strlen($token)) !== $token) {
            if (is_string($data) && $data !== "" && !$this->ssh->isTimeout()) {
                return $data; // connection closed after a partial line
            }

            $host = $this->config["host"] . ":" . $this->config["port"];

            if ($this->ssh->isTimeout()) {
                throw new \TeamSpeak3_Transport_Exception("connection to server '$host' timed out");
            }

            throw new \TeamSpeak3_Transport_Exception("connection to server '$host' lost");
        }

        return $data;
    }

    /**
     * Removes terminal noise (ANSI sequences, "user@9987(1):online>" prompts)
     */
    protected function cleanLine(string $line): string {
        $line = preg_replace([
            '/\x1B\[[0-?]*[ -\/]*[@-~]/', // CSI
            '/\x1B\][^\x07]*\x07/',       // OSC
            '/\x1B[@-_]/',                // other ESC sequences
        ], "", $line);

        $line = trim($line, " \0\t\n\r\x0B");

        return trim(preg_replace('/^[A-Za-z0-9\-_.@()]+:[^\s>=]*>\s*/', "", $line));
    }
}
