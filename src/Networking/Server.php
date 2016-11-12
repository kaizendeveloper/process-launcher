<?php
namespace ProcessControl\ProcessLauncher\Networking;
class Server {

    const MAX_RETRIES = 5;

    protected $address;
    protected $port;
    protected $socket_own;
    protected $client_socket;
    protected $stop_listening;
    protected $event_listeners;

    public function __construct($address = '127.0.0.1', $port = 19800)
    {
        $this->address = $address;
        $this->port = $port;
        $this->stop_listening = false;

        $retries = 0;

        //Creiamo il socket
        while($retries <= self::MAX_RETRIES)
        {
            $this->socket_own = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            $result = socket_bind($this->socket_own, '127.0.0.1', $this->port);

            if (!$result) {
                $errorNum = socket_last_error();
                $errorDescr = socket_strerror($errorNum);
                echo "\r\nErrore: $errorDescr ($errorNum)\r\n Riprovo ad agganciare l'indirizzo $this->address sulla porta $this->port ...\r\n";
                sleep(1);
                $retries++;
                continue;
            } else {
                echo "\r\nDelli Carpini PHP Server agganciato con successo all'indirizzo $this->address sulla porta $this->port\r\n";
                echo "Pronto all'ascolto di connessioni in ingresso...\r\n";
                return $this;
            }
        }

        if(!$result) {
            throw new Exception('Server Class non è riuscita a stabilire un socket');
        }

    }

    public function startListening() {

        while (!$this->stop_listening) {

            socket_listen($this->socket_own);

            // Tenta di ottenere la risorsa per il socket del client
            // se restituisce false vuol dire che c'è stato un problema quindi chiudiamo
            // il socket e riproviamo un'altra volta
            $this->client_socket = socket_accept($this->socket_own);

            if (!$this->client_socket) {
                socket_close($this->client_socket);
                continue;
            }

            //Leggiamo 4kb (dovrebbero essere più che sufficiente)
            $input = socket_read($this->client_socket,4096);


            //Inviamo ciò che abbiamo ricevuto ad ogni ascoltatore assegnato
            $output = $this->sendInputToListeners($input);

            //La risposta sarà inviata al client
            socket_write($this->client_socket, $output);

            //Dopodiché la connessione non servirà più
            socket_close($this->client_socket);


        }
    }

    public function addEventListener($objectOrFunction, $method = null)
    {
        $this->event_listeners[] = array('object' => $objectOrFunction, 'method' => $method);
    }

    protected function sendInputToListeners($readInfo){

        foreach($this->event_listeners as $event){
            if(is_string($event['object'])){
                $buffer = call_user_func($event['object'], $readInfo);
            }
            if(is_object($event['object']))
            {
                $buffer = call_user_func(array($event['object'], $event['method']), $readInfo);
            }
        }

        return $buffer;
    }
}