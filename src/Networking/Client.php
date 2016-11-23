<?php
namespace ProcessControl\ProcessLauncher\Networking;
class Client {

    //In the case we can't hook a socket in the first try
    const MAX_RETRIES = 5;

    //Seconds
    const SOCKET_TIMEOUT = 200;

    //Should point to server side address and port
    protected $address;
    protected $port;

    //Socket resource for the client connection
    protected $socket_own;


    public function __construct($address = '127.0.0.1', $port = 19800)
    {
        $this->address = $address;
        $this->port = $port;

    }

    /**
     * Creates the socket and saves the socket resource inside of the object
     *
     * @return $this
     * @throws \Exception
     */
    public function establishConnection() {


        $retries = 0;
        //We try until we get a connection to the server
        while($retries <= self::MAX_RETRIES)
        {
            //Create the socket
            $this->socket_own = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            //stream_socket_client($this->address .":" . $this->port, $errno, $errstr);
            $socketConnected = @socket_connect($this->socket_own, $this->address, $this->port);
            //Set socket timeout
            socket_set_timeout($this->socket_own, self::SOCKET_TIMEOUT);

            if (!$socketConnected) {
                $err = socket_last_error($this->socket_own);

                //echo "$errstr ($errno) Retrying...\r\n";
                var_dump($err);
                $retries++;
                sleep(1);
                continue;
            } else {
                return $this;
            }
        }

        throw new \Exception("Client can't connect to the server.\r\n");

        //Shutdown exit point
        die();

    }

    /**
     * Send and waits for a server response
     * @param $data
     * @return string
     */
    public function sendData($data){

        //If the connection has not been established, you know what to do
        if(!$this->socket_own) {
            $this->establishConnection();
        }

        //Send the data payload through socket
        socket_write($this->socket_own, $data, strlen($data));
        var_dump(socket_last_error($this->socket_own));
        //Read server response (4kb max)
        $serverResponse = socket_read($this->socket_own, 4096);

        //Close the connection (our connections are stateless)
        fclose($this->socket_own);

        //Wash away the resource
        $this->socket_own = null;

        return $serverResponse;

    }

}