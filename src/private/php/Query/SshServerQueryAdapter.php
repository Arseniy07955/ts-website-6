<?php

namespace Wruczek\TSWebsite\Query;

/**
 * ServerQuery adapter that uses SshTransport instead of the raw TCP one
 */
class SshServerQueryAdapter extends \TeamSpeak3_Adapter_ServerQuery {

    protected function syn() {
        $this->initTransport($this->options, SshTransport::class);
        $this->transport->setAdapter($this);

        \TeamSpeak3_Helper_Profiler::init(spl_object_hash($this));

        $rdy = $this->getTransport()->readLine();

        // TS3 greets with "TS3", be lenient about what TS6 sends before the MOTD
        if (!$rdy->startsWith(\TeamSpeak3::TS3_PROTO_IDENT)
            && !$rdy->startsWith(\TeamSpeak3::TEA_PROTO_IDENT)
            && !$rdy->startsWith(\TeamSpeak3::TS3_MOTD_PREFIX)
            && !$rdy->contains("TeamSpeak", false)
        ) {
            throw new \TeamSpeak3_Adapter_Exception("invalid reply from the server (" . $rdy . ")");
        }

        \TeamSpeak3_Helper_Signal::getInstance()->emit("serverqueryConnected", $this);
    }
}
