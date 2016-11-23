<?php
namespace ProcessControl\ProcessLauncher\Networking;
use ProcessControl\ProcessLauncher\Networking\Protocol\Request;

class ClientProcess {

    const MAX_RETRIES = 5;

    protected $request;
    protected $response;
    protected $client;

    //Process table
    protected $ptable;

    protected $socket_own;
    protected $client_socket;
    protected $stop_listening;
    protected $event_listeners;

    public function __construct()
    {

        $this->client = new Client();

    }



    /**
     * GUIDv4 generation
     *
     */
    public function generateGUID(){

        //Set to false if you want the "{" and "}" characters wrapping the GUID
        $trim = true;

        mt_srand((double)microtime() * 10000);
        $charid = strtolower(md5(uniqid(rand(), true)));
        $hyphen = chr(45);                  // "-"
        $lbrace = $trim ? "" : chr(123);    // "{"
        $rbrace = $trim ? "" : chr(125);    // "}"
        $guidv4 = $lbrace.
            substr($charid,  0,  8).$hyphen.
            substr($charid,  8,  4).$hyphen.
            substr($charid, 12,  4).$hyphen.
            substr($charid, 16,  4).$hyphen.
            substr($charid, 20, 12).
            $rbrace;

        return $guidv4;
    }

    public function sendData($data){

        $request = new Request();
        $p = $request->createPayload('this is a test');

        $this->client->sendData($p);


    }
}