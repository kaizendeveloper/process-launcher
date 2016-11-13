<?php
namespace ProcessControl\ProcessLauncher\Networking;
class Server {

    //Maximum attempts for binding the listening socket
    const MAX_RETRIES = 5;

    //Ipv4 address where to bind the socket to listen
    protected $address;
    //Port for listening given an IP address
    protected $port;
    //Resource of the server itself
    protected $socket_own;
    //Incoming connection socket
    protected $client_socket;
    //Emergency break if necessary
    protected $stop_listening;
    //Collection of callable methods where to redirect info on each input
    protected $event_listeners;
    //PHP Multithreading
    protected $forked_pid;

    /**
     *
     * Server constructor.
     * @param string $address
     * @param int $port
     * @return mixed
     * @throws \Exception
     */
    public function __construct($address = '127.0.0.1', $port = 19800)
    {
        $this->address = $address;
        $this->port = $port;
        $this->stop_listening = false;

        $retries = 0;

        //Try to hook the IP socket
        while($retries <= self::MAX_RETRIES)
        {
            //Try to create socket
            $this->socket_own = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            //Reuse address if possible
            socket_set_option($this->socket_own, SOL_TCP, SO_REUSEADDR);
            //Hook the socket
            $result = socket_bind($this->socket_own, '127.0.0.1', $this->port);

            //Check if we've succeeded
            if (!$result) {
                //No, we haven't
                $errorNum = socket_last_error();
                $errorDescr = socket_strerror($errorNum);
                //Display the error cause and try again
                echo "\r\nError: $errorDescr ErrNumber($errorNum)\r\n
                     I'll try again to hook port $this->port using this address ($this->address)...\r\n";
                sleep(1);
                $retries++;
                continue;
            } else {
                echo "\r\nDelli Carpini's PHP Server hooked successfully to port $this->port using ($this->address)\r\n";
                echo "Ready and listening to incoming connections...\r\n";
                return $this;
            }
        }

        //Instead of dying directly let's throw and exception, this shuold be useful if the whole app is wrapped to
        //an Error & Exception Handler
        if(!$result) {
            throw new \Exception("Server Class could't create a socket\r\n", 500);
        }

    }

    /**
     * Upon death kill the thread which it will otherwise remain looped
     */
    public function __destruct() {
        posix_kill($this->forked_pid, SIGKILL);
    }

    /**
     *
     */
    public function startListening() {

        //Avoid re-calling this by mistake
        if(is_null($this->forked_pid)){

            //The programs goes multithread
            $this->forked_pid = pcntl_fork();

            switch(true){
                //Error while forking
                case $this->forked_pid === -1:
                    die("Server Class: -- Could not fork --");
                    break;
                //Original program (parent) execution thread
                case (boolean)$this->forked_pid:
                    break;
                //Child (forked process) $this->forked_pid will hold the child's pid
                default:

                    //We will go on until an external force push us out of the inertia
                    while (!$this->stop_listening) {

                        //Listen to the socket
                        socket_listen($this->socket_own);


                        // Tries to obtain the incoming connection's socket, if it fails will return "false"
                        // meaning that it has been a problem, therefore will have to close the connection and
                        // retry the whole listening procedure again
                        // this is a blocking instruction
                        $this->client_socket = socket_accept($this->socket_own);

                        if (!$this->client_socket) {
                            socket_close($this->client_socket);
                            continue;
                        }


                        //Let's read 4kb at a time (shuould be more than sufficient!)
                        $input = socket_read($this->client_socket,4096);

                        //Once we have an input, send it to each registered listener
                        $output = $this->sendInputToListeners($input);

                        //Each listener will give back a response, send it back to the requester
                        socket_write($this->client_socket, $output);

                        //After serving the connection, we can close to start another one later
                        socket_close($this->client_socket);

                    }
            }
        }


    }

    /**
     * Adds a callable method upon receiving an incoming connection
     *
     * @param $objectOrFunction
     * @param null $method
     * @return $this
     */
    public function addEventListener($objectOrFunction, $method = null)
    {
        $this->event_listeners[] = array('object' => $objectOrFunction, 'method' => $method);
        return $this;
    }


    /**
     * Sends to all listeners the input received from the incoming socket
     *
     * @param $readInfo
     * @return mixed
     */
    protected function sendInputToListeners($readInfo){

        //Cicle all listeners in order to send the read info to them
        foreach($this->event_listeners as $event){
            //Check whether the listener is a simple function or a method inside an object
            //------------------------------------------------------------------------------

            //Simple function approach
            if(is_string($event['object'])){
                $buffer = call_user_func($event['object'], $readInfo);
            }
            //OOP method approach
            if(is_object($event['object']))
            {
                $buffer = call_user_func(array($event['object'], $event['method']), $readInfo);
            }
        }

        //TBD
        return $buffer;
    }

    /**
     * Meant to stop the listening loop
     */
    public function sendStopListeningSignal(){
        $this->stop_listening = true;
    }
}