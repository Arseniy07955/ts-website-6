<?php

namespace Wruczek\TSWebsite\Query;

/**
 * ServerQuery adapter that uses HttpTransport (WebQuery) instead of the raw TCP one
 */
class HttpServerQueryAdapter extends \TeamSpeak3_Adapter_ServerQuery {

    protected function syn() {
        $this->initTransport($this->options, HttpTransport::class);
        $this->transport->setAdapter($this);

        \TeamSpeak3_Helper_Profiler::init(spl_object_hash($this));

        // WebQuery has no greeting - check that the server answers at all
        $this->request("version");

        \TeamSpeak3_Helper_Signal::getInstance()->emit("serverqueryConnected", $this);
    }
}
