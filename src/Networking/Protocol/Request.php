<?php
namespace ProcessControl\ProcessLauncher\Networking\Protocol;

class Request {

    //Every request must identify itself
    protected $guid;

    const SLP_REPORT = 34;
    const SLP_NC = 343;

    public function __construct($guid = null) {
        if(!is_null($guid)){
            $this->guid = $guid;
        }

        $this->guid = $this->generateGUID();


    }

    public function setMethod($method = null){
        //@todo: To be defined

    }

    public function setGUID($guid = null){
        $this->guid = $guid;
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

    public function createPayload($payload){


        $request = $this->guid . " " . self::SLP_NC;
        $request .= "\r\n";
        $request .= $payload;

        return $request;

    }

}