<?php
class Handler {
    private $clients = [];

	/**
	 * Summary. Registro de los clientes conectados en memoria mientras corra el servidor websocket.
	 * Se consume en php-socket.php (el archivo que arranca el servidor websocket).
	 * @param $socket: Recurso del socket del cliente.
	 * @param $ip: IP del cliente.
	 * @return void
	 */
	public function registerClient($socket, $ip) {
        $this->clients[] = [
            'socket' => $socket,
            'ip' => $ip,
            'user_type' => null, // Tipo de usuario... podemos cambiarlo por otra cosa o eliminar esto.
            'user_id' => uniqid(), // ID's temporales en memoria.
        ];
    }

	/**
	 * Summary. Quitado de los clientes conectados en memoria mientras corra el servidor websocket.
	 * Se consume en php-socket.php (el archivo que arranca el servidor websocket).
	 * @param $socket: Recurso del socket del cliente.
	 * @param $ip: IP del cliente.
	 * @return void
	 */
	public function unregisterClient($socket) {
      $clients = $this->getClients();
	  foreach ($clients as $key => $client) {
		  if ($client['socket'] === $socket) {
			  unset($this->clients[$key]);
			  break;
		  }
	  }
    }

	/**
	 * Summary. Getter de los clientes conectados.
	 */
	public function getClients() {
		//print_r($this->clients);	
        return $this->clients;
    }

	/**
	 * Summary. Busca un cliente por su objeto socket.
	 * @param $socket: Recurso del socket del cliente.
	 * @return array $client: Cliente encontrado.
	 * @return null: Si no se encuentra el cliente.
	 */
    public function getClientBySocket($socket):?array {
        foreach ($this->clients as $client) {
            if ($client['socket'] === $socket) {
                return $client;
            }
        }
        return null;
    }

	/**
	 * Summary. Actualiza la metadata del array de clientes para un cliente.
	 * @param $socket: Recurso del socket del cliente.
	 * @param $key: Llave de la metadata a actualizar.
	 * @param $value: Valor de la metadata a actualizar.
	 * @return void
	 */
	function updateClientMetadata($socket, $key, $value) {
		//echo "updating client metadata\n";
        foreach ($this->clients as &$client) {
            if ($client['socket'] === $socket) {
                $client[$key] = $value;
                break;
            }
        }
		$this->getClients();
    }

	/**
	 * Summary. Envia un mensaje a todos los clientes conectados.
	 * @param $message: Mensaje a enviar.
	 * @param bool $receptor (A modo de prueba y opcional), si es true, se envia el mensaje a "operadores".
	 * Esto para simular que la solicitud no debe ir a usuarios clientes (en nuestro caso, vendedores).
	 * Porque, en cualquier tenant, el vendedor no acepta la solicitud, sinó el operador con permisos para ello.
	 */
	function send($message, $receptor = null) {
		global $clientSocketArray;
		$clients = $this->getClients();
		$messageLength = strlen($message);

		foreach($clients as $client){
			if ($client['user_type'] == 'operator') {
				@socket_write($client['socket'], $message, $messageLength);
			}
		}
		return true;
	}

	/**
	 * Summary. Desencripta los mensajes recibidos.
	 * @param $socketData: Mensaje a desencriptar.
	 * @return string $socketData: Mensaje desencriptado.
	 */
	function unseal($socketData) {
		$length = ord($socketData[1]) & 127;
		if($length == 126) {
			$masks = substr($socketData, 4, 4);
			$data = substr($socketData, 8);
		}
		elseif($length == 127) {
			$masks = substr($socketData, 10, 4);
			$data = substr($socketData, 14);
		}
		else {
			$masks = substr($socketData, 2, 4);
			$data = substr($socketData, 6);
		}
		$socketData = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$socketData .= $data[$i] ^ $masks[$i%4];
		}
		return $socketData;
	}

	/**
	 * Summary. Encripta los mensajes a enviar.
	 * @param $socketData: Mensaje a encriptar.
	 * @return string $header.$socketData: Mensaje encriptado.
	 */
	function seal($socketData) {
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($socketData);
		
		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header.$socketData;
	}

	/**
	 * Summary. Realiza el handshake con el cliente.
	 * @param $received_header: Header recibido del cliente.
	 * @param $client_socket_resource: Recurso del socket del cliente.
	 * @param $host_name: Nombre del host.
	 * @param $port: Puerto del host.
	 * @return void
	 */
	function doHandshake($received_header, $client_socket_resource, $host_name, $port) {
		$headers = array();
		$lines = preg_split("/\r\n/", $received_header);
		foreach($lines as $line) {
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
				$headers[$matches[1]] = $matches[2];
			}
		}
		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

		// Extract query parameters from the WebSocket URL
		if (preg_match('/GET (.*) HTTP/', $received_header, $matches)) {
			$url = $matches[1];
			$parsed_url = parse_url($url);
			$queryParams = [];
			if (isset($parsed_url['query'])) {
				parse_str($parsed_url['query'], $queryParams);
			}
		}

		$userId = $queryParams['userId'] ?? null;
		$userType = $queryParams['userType'] ?? null;

		// Proceed with handshake response
		$buffer  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
			"Upgrade: websocket\r\n" .
			"Connection: Upgrade\r\n" .
			"WebSocket-Origin: $host_name\r\n" .
			"WebSocket-Location: ws://$host_name:$port/demo/shout.php\r\n".
			"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
		socket_write($client_socket_resource, $buffer, strlen($buffer));

		socket_getpeername($client_socket_resource, $client_ip_address);
		$this->registerClient($client_socket_resource, $client_ip_address);
		$this->updateClientMetadata($client_socket_resource, 'user_id', $userId);
		$this->updateClientMetadata($client_socket_resource, 'user_type', $userType);
	}

	/**
	 * Summary. Mensaje de confirmación de conexión.
	 * @param $client_ip_address: IP del cliente.
	 * @return string $ACK: Mensaje de confirmación de conexión.
	 */
	function newConnectionACK($client_ip_address) {
		$message = 'New client ' . $client_ip_address.' joined';
		$messageArray = array('message'=>$message,'message_type'=>'connection-ack');
		$ACK = $this->seal(json_encode($messageArray));
		return $ACK;
	}
	
	/**
	 * Summary. Mensaje de confirmación de desconexión.
	 * @param $client_ip_address: IP del cliente.
	 * @return string $ACK: Mensaje de confirmación de desconexión.
	 */
	function connectionDisconnectACK($client_ip_address) {
		$message = 'Client ' . $client_ip_address.' disconnected';
		$messageArray = array('message'=>$message,'message_type'=>'connection-ack');
		$ACK = $this->seal(json_encode($messageArray));
		return $ACK;
	}
	

	/**
	 * Summary. Resetea la instancia de la clase.
	 */
	function resetInstance()
	{
		$this->clients = [];
	}

}
?>