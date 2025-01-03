<?php
require_once("operaPrestamo.php");

class Handler {
    private $clients = [];
	public $servername = "localhost";
	public $username = "root";
	public $password = "";
	public $dbname = "websocket";
	/**
	 * Summary. Registra un préstamo en la base de datos.
	 * @return bool true: Si se registró el préstamo, false caso contrario
	 */
	function registrarPrestamo($client_id, $estado) {
		try{
			$conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//$sql = "INSERT INTO prestamos (userid, confirmado) VALUES ('$client_id', '$estado')";
			$sql = "INSERT INTO backend_solicitudes (id, estado) VALUES ('$client_id', '$estado')";
			$conn->exec($sql);
		}catch(PDOException $e){
			echo "Connection failed: " . $e->getMessage();
			return false;
		}

		return true;
	}

	function getPrestamo(){
		try{
			$conn = new PDO("mysql:host=$this->servername;dbname=$this->dbname", $this->username, $this->password);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$listado = array();	
			$sql = "SELECT * FROM prestamos";
			$exec = $conn->query($sql);
			foreach ($exec as $row) {
				$listado[] = $row;
			}
			return $listado;
		}catch(PDOException $e){
			echo "Connection failed: " . $e->getMessage();
			return false;
		}
	}


	/**
	 * Summary. Buscar un cliente por algun valor (Tener en cuenta que, esto está hecho en memoria)
	 * @param $key: Llave de la metadata a buscar.
	 * @param $value: Valor de la metadata a buscar.
	 * @return array $socketClient: Objeto Socket del cliente encontrado.
	 */

	 public function getClientBy($key, $value) {
		foreach ($this->clients as $client) {
			if ($client[$key] === $value) {
				return $client['socket'];
			}
		}
		return null;
	}

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

	function updateListadoPrestamo(){
		$prestamos = $this->getPrestamo();
		$messageArray = array('message'=>$prestamos,'message_code'=>'P002','message_type'=>'loan-list');
		$ACK = $this->seal(json_encode($messageArray));
		$this->send($ACK);
	}


	/**
	 * Summary. Actualiza la metadata del array de clientes para un cliente.
	 * @param $socket: Recurso del socket del cliente.
	 * @param $key: Llave de la metadata a actualizar.
	 * @param $value: Valor de la metadata a actualizar.
	 * @return void
	 */
	function updateClientMetadata($socket, $key, $value) {
		foreach ($this->clients as &$client) {
			if ($client['socket'] === $socket) {
				$client[$key] = $value;
				if($key === 'user_type' && $value === 'user') {
					if($this->registrarPrestamo($client['user_id'], 'HOLD')){
						$messageArray = array('message'=>'Prestamo pendiente','message_code'=>'P001','message_type'=>'pending-loan');
						$ACK = $this->seal(json_encode($messageArray));
						$this->send_to_self($ACK,$socket);
						//Si suponemos que el websocket es solamente para recibir el listado de prestamos y aceptarlos/rechazarlos
						//entonces, le mandamos el listado de prestamos a    l o s   operadores
						$prestamos = $this->getPrestamo();
						$messageArray = array('message'=>$prestamos,'message_code'=>'P002','message_type'=>'loan-list');
						$ACK = $this->seal(json_encode($messageArray));
						$this->send($ACK);

					}
				}
				if($key === 'user_type' && $value === 'oper') {
					$prestamos = $this->getPrestamo();
					$messageArray = array('message'=>$prestamos,'message_code'=>'P002','message_type'=>'loan-list');
					$ACK = $this->seal(json_encode($messageArray));
					$this->send_to_self($ACK,$socket); //porque lo tiene que recibir este operador cuando se conecte
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Summary. Envia un mensaje a todos los clientes conectados.
	 * @param $message: Mensaje a enviar.
	 * @param bool $receptor (A modo de prueba y opcional), si es true, se envia el mensaje a "operadores".
	 * Esto para simular que la solicitud no debe ir a usuarios clientes (en nuestro caso, vendedores).
	 * Porque, en cualquier tenant, el vendedor no acepta la solicitud, sinó el operador con permisos para ello.
	 */
	function send($msg) {
		global $clientSocketArray;
		//var_dump($clientSocketArray);
		foreach($clientSocketArray as $clientSocket) {
			if(@socket_write($clientSocket, $msg, strlen($msg)))
			{
				echo "msg enviado\n";
				var_dump($this->getClients());	
			}
		}

		if(is_array($msg) && array_key_exists('message_type',$msg) && $msg['message_type'] == 'loan-list'){
			//Si el mensaje es de tipo loan-list, entonces, se envia a los operadores
			foreach($clientSocketArray as $clientSocket) {
				if($this->getClientBySocket($clientSocket)['user_type'] === 'oper'){
					if(@socket_write($clientSocket, $msg['message_type'], strlen($msg['message_type'])))
					{
						echo "msg enviado a operadores\n";
					}
				}
			}
		}
		return true;
	}

/**
	 * Summary. Envia el mensaje con el estado del prestamo al cliente.
	 * @param $msg: Mensaje a enviar.
	 * @param $client_socket: socket resource del cliente.
	 * return bool true: Si se envió el mensaje, false caso contrario.
	 */
	function send_to_self($msg,$client_socket) {
		global $clientSocketArray;
		//var_dump($clientSocketArray);
		foreach($clientSocketArray as $clientSocket) {
			if($clientSocket == $client_socket){
				if(@socket_write($client_socket, $msg, strlen($msg)))
				{
					echo "\n msg enviado al cliente \n";
					return true;
				}
			}
		}
		echo "no se encontro el cliente \n";
		return false;
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
		//var_dump($received_header);
		//echo "\n received Headers \n";


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
		

		$usertype = $queryParams['usertype'] ?? 'Unknown';
		$message = $queryParams['message'] ?? 'Unknown';

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
		$this->updateClientMetadata($client_socket_resource, 'user_type', $usertype);
		$this->updateClientMetadata($client_socket_resource, 'message', $message);


		return $headers;
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