<?php
namespace ProcessControl\ProcessLauncher\Networking;
class Client {

    const MAX_RETRIES = 5;
    protected $address;
    protected $port;
    protected $socket_own;


    public function __construct($address = '127.0.0.1', $port = 19800)
    {
        $this->address = $address;
        $this->port = $port;

    }

    public function establishConnection() {


        $retries = 0;

        while($retries <= self::MAX_RETRIES)
        {
            //Creiamo il socket verso il server
            $this->socket_own = stream_socket_client($this->address .":" . $this->port, $errno, $errstr);

            if (!$this->socket_own) {
                echo "$errstr ($errno) Riprovo...\n";
                $retries++;
                continue;
            } else {
                return $this;
            }
        }

        throw new \Exception('Non riesco a connettermi al server');


    }


    public function sendData($data){

        if(!$this->socket_own) {
            $this->establishConnection();
        }

        fwrite($this->socket_own, $data);
        $serverResponse = fread($this->socket_own, 4096);

        fclose($this->socket_own);
        $this->socket_own = null;

        return $serverResponse;

    }

}