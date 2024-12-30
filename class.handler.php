<?php
class Handler {
    private $clients = [];
	public $message;

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
	 * Summary. Getter de los operadores conectados.
	 */
	public function getOpers() {
		$opers = [];
		foreach ($this->clients as $client){
			if ($client['user_type'] == 'operator') {
				$opers[] = $client;
			}
		}
        return $opers;
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
	 * Summary. Envia un mensaje a todos los operadores conectados.
	 * @param $message: Mensaje a enviar.
	*/
	function send($message) {
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
	 * Summary. Envia un mensaje a un cliente en especifico..
	 * @param $message: Mensaje a enviar.
	 * @param int $receptor id del cliente a enviar el mensaje.
	 */
	function sendTo($message, $receptor = null) {
	
		$clientArr = $this->getClients();
		foreach($clientArr as $client)
		{
			if ($client['user_id'] == $receptor) {				
				@socket_write($client['socket'],$message);
			}
		}
		return true;
	}

	/**
	 * Summary. Envia un mensaje a todos. (operadores y clientes, en mi caso)
	 * @param $message: Mensaje a enviar.
	 */
	function sendToAll($message) {
	
		$clientArr = $this->getClients();
		$messageLength = strlen($message);

		foreach($clientArr as $client)
		{			
			echo "\n enviando \n";
			@socket_write($client['socket'],$message, $messageLength);
		}
		return true;
	}

	/**
	 * Summary. Envia un mensaje a todos los operadores conectados (test)
	 * @param $message: Mensaje a enviar.
	 */
	function sendToMyself($message,$clientSocket) {
		global $clientSocketArray;
		foreach($clientSocketArray as $client){
					if ($client == $clientSocket) {
						@socket_write($client, $message, strlen($message));
						break;
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
	
	//esto crea mensajes... deberia ser adaptado a la solicitud de prestamo, claramente.
	function createUserMessage($chat_user,$chat_box_message,$chat_box_user_id){	
		$message = $chat_user . ": <div class='chat-box-message'>" . $chat_box_message . "
									<button type='button'  onclick='replyTo()' class='reply btn btn-outline-success' data-reply='$chat_box_user_id'><svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-send-fill' viewBox='0 0 16 16'>
  <path d='M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471z'/>
</svg>
</button>
									</div>";
		$messageArray = array('message'=>$message,'message_type'=>'chat-box-html');
		$chatMessage = $this->seal(json_encode($messageArray));
		
		return $chatMessage;
	}

	//esto crea mensajes... deberia ser adaptado a la solicitud de prestamo, claramente.
	function createOperMessage($chat_user,$chat_box_message,$chat_box_user_id){
		$message = $chat_user . ": <div class='chat-box-message'>" . $chat_box_message .
		 "<button type='button'  onclick='replyTo()' class='reply btn btn-outline-success' data-reply='$chat_box_user_id'>
		 	<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-send-fill' viewBox='0 0 16 16'>
		 	  	<path d='M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471z'/>
		 	</svg>
		 </button></div>";
		$messageArray = array('message'=>$message,'message_type'=>'chat-box-html');
		$chatMessage = $this->seal(json_encode($messageArray));
		return $chatMessage;

	}


	//esto crea mensajes... deberia ser adaptado a la solicitud de prestamo, claramente.
	function createChatBoxSolicitud($chat_user,$chat_box_message, $chat_box_user_id) {
		//echo "en handler" . $chat_box_user_id . "\n";
		$message = $chat_user . 
		": <div class='chat-box-message' style='display: flex !important; justify-content: space-between;'>" 
		. $chat_box_message . 
		"<button type='button' data-id='$chat_box_user_id' class='btnAcep'>Y</button><button type='button' data-id='$chat_box_user_id' class='btnRech'>N</button></div>";

		echo $message . "\n";
		$messageArray = array('message'=>$message,'message_type'=>'chat-box-html');

		$chatMessage = $this->seal(json_encode($messageArray));
		print_r($chatMessage);
		return $chatMessage;
	}

	//esto crea mensajes... deberia ser adaptado a la solicitud de prestamo, claramente.
	function createChatBoxMessageTo($chat_user,$chat_box_message, $optional = null) {
		if (!is_null($optional)){
			$message = $chat_user . 
			": <div class='chat-box-message' style='display: flex !important; justify-content: space-between;'>" 
			. $chat_box_message . 
			"<button type='button' class='btnAcep'>Y</button><button type='button' class='btnRech'>N</button></div>";
		}else{
			$message = $chat_user . ": <div class='chat-box-message'>" . $chat_box_message . "</div>";
		}
		$messageArray = array('message'=>$message,'message_type'=>'chat-box-html');
		$chatMessage = $this->seal(json_encode($messageArray));
		return $chatMessage;
	}

	/**
	 * Summary. Resetea la instancia de la clase.
	 */
	function resetInstance()
	{
		$this->clients = [];
	}


	/**
	 * Summary. Agrega un usuario en común a un operador (test/experimento).
	 * @param $userId: Usuario a agregar.
	 * @param $operatorId: Operador a agregar.
	 * @return void
	 */
	function appendUser($userId, $operatorId) {
		$clients = $this->getClients();
		foreach($clients as $client){
			if ($client['user_id'] == $userId) {
				$client['user_operator'] = $operatorId;
			}
		}

	}
}
?>