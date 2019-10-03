<?php


class ClientSSL
{
    /**
     * @var EventBase
     */
    public $base;
    public $bev;

    public $host;
    public $port;
    public $ctx;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
        $this->ctx = $this->initSSL();
        $this->base = new EventBase();
        $this->bev = EventBufferEvent::sslSocket($this->base, null, $this->initSSL(),0,0 );

    }

    public function initSSL()
    {
        $local_cert = __DIR__."/cert.pem";
        $local_pk   = __DIR__."/privkey.pem";
        $ctx = new EventSslContext(3, [

            EventSslContext::OPT_ALLOW_SELF_SIGNED => true,
            EventSslContext::OPT_VERIFY_PEER => true,
            EventSslContext::OPT_CA_FILE => __DIR__."/cacert.pem",
        ]);
        return $ctx;
    }


}

$client = new ClientSSL('', '');
$client->initSSL();