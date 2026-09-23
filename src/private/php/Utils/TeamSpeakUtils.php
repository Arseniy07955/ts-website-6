<?php

namespace Wruczek\TSWebsite\Utils;

use Wruczek\TSWebsite\Config;
use Wruczek\TSWebsite\Query\HttpServerQueryAdapter;
use Wruczek\TSWebsite\Query\SshServerQueryAdapter;

/**
 * Class TeamSpeakUtils
 * @package Wruczek\TSWebsite\Utils
 * @author Wruczek 2017
 */
class TeamSpeakUtils {

    use SingletonTait;

    protected $configUtils;
    protected $tsNodeHost;
    protected $tsNodeServer;
    protected $exceptionsList = [];

    private function __construct() {
        $this->configUtils = Config::i();
    }

    /**
     * Returns TeamSpeak3_Node_Host object created using
     * data from config database
     * @return \TeamSpeak3_Node_Host|null
     */
    public function getTSNodeHost(): ?\TeamSpeak3_Node_Host {
        if($this->tsNodeHost === null) {
            $hostname = $this->configUtils->getValue("query_hostname");
            $queryport = $this->configUtils->getValue("query_port");
            $username = $this->configUtils->getValue("query_username");
            $password = $this->configUtils->getValue("query_password");
            $mode = $this->configUtils->getValue("query_mode", "raw");

            try {
                $this->tsNodeHost = self::connect($mode, $hostname, $queryport, $username, $password, 3);
            } catch (\Exception $e) {
                $this->addExceptionToExceptionsList($e);
            }
        }

        return $this->tsNodeHost;
    }

    /**
     * Connects and logs in to the ServerQuery.
     * @param string $mode "raw" - raw TCP query (TeamSpeak 3, default port 10011),
     *                     "ssh" - SSH query (TeamSpeak 3/6, default port 10022),
     *                     "http"/"https" - WebQuery (TeamSpeak 3/6, default port 10080/10443),
     *                     $password is the API key then and $username is ignored
     * @throws \Exception when connection or login fails
     */
    public static function connect(string $mode, string $hostname, int $queryport, string $username,
                                   string $password, int $timeout = 10): \TeamSpeak3_Node_Host {
        // registers the framework autoloader, TeamSpeak3::factory() does this
        // on its own but the SSH and HTTP adapters are created directly
        \TeamSpeak3::init();

        switch ($mode) {
            case "raw":
                $tsNodeHost = \TeamSpeak3::factory("serverquery://$hostname:$queryport/?timeout=$timeout");
                $tsNodeHost->login($username, $password);
                return $tsNodeHost;

            case "ssh":
                $adapter = new SshServerQueryAdapter([
                    "host" => $hostname,
                    "port" => $queryport,
                    "timeout" => $timeout,
                    "blocking" => 1,
                    "username" => $username,
                    "password" => $password,
                ]);

                $tsNodeHost = $adapter->getHost();

                // SSH already authenticated us, but login again so the framework
                // stores the credentials. Some servers refuse a second login, that's fine.
                try {
                    $tsNodeHost->login($username, $password);
                } catch (\TeamSpeak3_Adapter_ServerQuery_Exception $e) {}

                return $tsNodeHost;

            case "http":
            case "https":
                $adapter = new HttpServerQueryAdapter([
                    "host" => $hostname,
                    "port" => $queryport,
                    "timeout" => $timeout,
                    "blocking" => 1,
                    "https" => $mode === "https",
                    "apikey" => $password,
                ]);

                return $adapter->getHost();

            default:
                throw new \InvalidArgumentException("Unknown query mode: $mode");
        }
    }

    /**
     * Returns TeamSpeak3_Node_Server object created
     * using getTSNodeHost() method.
     * @return \TeamSpeak3_Node_Server|null
     */
    public function getTSNodeServer(): ?\TeamSpeak3_Node_Server {
        // Don't continue if TSNodeHost is NULL (not working / not initialised)
        if($this->tsNodeServer === null && $this->getTSNodeHost()) {
            $port = $this->configUtils->getValue("tsserver_port");

            try {
                $this->tsNodeServer = $this->getTSNodeHost()->serverGetByPort($port);

                $newNickname = Config::get("query_nickname");

                // if available, set the query nickname. add random numbers to the end, so
                // the bot will work even with a user/query of the same nickname online
                if (isset($newNickname)) {
                    // try 5 times to change the nickname if the previous is already in use
                    for($i = 0; $i < 5; $i++) {
                        try {
                            $this->tsNodeServer->selfUpdate(["client_nickname" => $newNickname]);
                            break; // success - we have set the nickname
                        } catch (\TeamSpeak3_Exception $e) {
                            // error nickname in use
                            if ($e->getCode() === 513) {
                                // add something random to the name and try again
                                $newNickname .= mt_rand(0, 9);
                            } else {
                                // if thats other error than nickname in use, re-throw it
                                throw $e;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->addExceptionToExceptionsList($e);
            }
        }

        return $this->tsNodeServer;
    }

    /**
     * Tries to download file from the TS3 server. It might be an actual file,
     * icon or avatar. Returns downloaded data. Might throw exceptions when filetransfer fails.
     * @param string $filename
     * @param int $cid Channel Id (defaults to 0 - server)
     * @param string $cpw Channel password (defaults to empty)
     * @return \TeamSpeak3_Helper_String
     * @throws \TeamSpeak3_Adapter_ServerQuery_Exception|\TeamSpeak3_Exception
     */
    public function ftDownloadFile(string $filename, int $cid = 0, string $cpw = ""): \TeamSpeak3_Helper_String {
        if (!$this->checkTSConnection()) {
            throw new \TeamSpeak3_Exception("Cannot connect to the TeamSpeak server");
        }

        $dl = $this->getTSNodeServer()->transferInitDownload(mt_rand(0x0000, 0xFFFF), $cid, $filename, $cpw);

        $host = (string) $dl["host"];

        // the server may report its bind address (TS6 does by default), use the query host then
        if (in_array($host, ["", "0.0.0.0", "::", "[::]"], true)) {
            $host = (string) $this->configUtils->getValue("query_hostname");
        }

        // wrap host in brackets if it contains a colon (is a IPv6)
        $host = (false !== strpos($host, ":") ? "[" . $host . "]" : $host);

        $filetransfer = \TeamSpeak3::factory("filetransfer://$host:" . $dl["port"]);

        return $filetransfer->download($dl["ftkey"], $dl["size"]);
    }

    /**
     * Resets current connection, forces to reconnect to the TeamSpeak server
     * next time you call getTSNodeHost or getTSNodeServer
     */
    public function reset(): void {
        $this->tsNodeHost = null;
        $this->tsNodeServer = null;
    }

    /**
     * Checks TeamSpeak server connection
     * Warning: it will connect to the TeamSpeak server to check the status.
     *          Use it just before accessing the server, preferably after checking cache.
     * @return bool true if TeamSpeak connection succeeded, false otherwise
     */
    public function checkTSConnection(): bool {
        return $this->getTSNodeHost() !== null
            && $this->getTSNodeServer() !== null
            && empty($this->getExceptionsList());
    }

    /**
     * Adds exception to the exceptions list
     * @param \Exception $exception
     */
    public function addExceptionToExceptionsList(\Exception $exception): void {
        $this->exceptionsList[$exception->getCode()] = $exception;
    }

    /**
     * Returns array filled with connection exceptions collected
     * when calling getTSNodeServer(), getTSNodeServer() and other methods
     * @return array Array filled with exceptions. Empty if no exceptions where thrown.
     */
    public function getExceptionsList(): array {
        return $this->exceptionsList;
    }
}
