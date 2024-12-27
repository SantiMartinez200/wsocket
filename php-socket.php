<?php
define('HOST_NAME', "localhost");
define('PORT', "8090");
$null = NULL;

require_once("class.handler.php");
$handler = new Handler();

// Crear el recurso del socket
$socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

if (!$socketResource) {
    die('Error al crear el socket: ' . socket_strerror(socket_last_error()) . PHP_EOL);
}

if (!socket_set_option($socketResource, SOL_SOCKET, SO_REUSEADDR, 1)) {
    die('Error al configurar el socket: ' . socket_strerror(socket_last_error()) . PHP_EOL);
}

// Vincular el socket a todas las interfaces de red
if (!socket_bind($socketResource, '0.0.0.0', PORT)) {
    die('Error al vincular el socket: ' . socket_strerror(socket_last_error()) . PHP_EOL);
}

// Escuchar conexiones entrantes
if (!socket_listen($socketResource)) {
    die('Error al escuchar en el socket: ' . socket_strerror(socket_last_error()) . PHP_EOL);
}

$clientSocketArray = [$socketResource];

echo "Servidor WebSocket escuchando en " . HOST_NAME . ":" . PORT . PHP_EOL;

$running = true;
if (isset($_GET['shutdown'])) {
    $running = false;
}

while ($running) {
    $newSocketArray = $clientSocketArray;
    socket_select($newSocketArray, $null, $null, 0, 10);
    
    // Aceptar nuevas conexiones
    if (in_array($socketResource, $newSocketArray)) {
        $newSocket = socket_accept($socketResource);
        if ($newSocket === false) {
            echo "Error al aceptar una conexión: " . socket_strerror(socket_last_error()) . PHP_EOL;
            continue;
        }

        $clientSocketArray[] = $newSocket;
        $header = socket_read($newSocket, 1024);
        $handler->doHandshake($header, $newSocket, HOST_NAME, PORT);
		
        
        socket_getpeername($newSocket, $client_ip_address);
        $connectionACK = $handler->newConnectionACK($client_ip_address);
        echo $connectionACK . PHP_EOL;
        
        $handler->send($connectionACK);
        unset($newSocketArray[array_search($socketResource, $newSocketArray)]);
    }
    
    // Leer datos de los clientes
    foreach ($newSocketArray as $clientSocket) {
        $socketData = '';
        $bytesReceived = @socket_recv($clientSocket, $socketData, 1024, 0);

        //(se recibe un mensaje)
        if ($bytesReceived > 0) {
            $socketMessage = $handler->unseal($socketData);
            $messageObj = json_decode($socketMessage);
            //Mensaje publico
			if(isset($messageObj->chat_user, $messageObj->chat_message, $messageObj->chat_user_id)){
				$chat_box_message = $handler->createChatBoxMessage($messageObj->chat_user, $messageObj->chat_message, $messageObj->chat_user_id);

				if(gettype($messageObj->chat_user_id) == 'string' && $messageObj->chat_user_id == 'all'){
                    echo "todos";
                    $handler->sendToAll($chat_box_message);
                }else{
                    echo "uno";
                    $handler->send($chat_box_message);
                }
                
				echo "Mensaje recibido: " . $chat_box_message . PHP_EOL;
			}

            //Solictud de chat (Y/N) (esto deberia usarse???)
			if(isset($messageObj->chat_user, $messageObj->chat_ask, $messageObj->chat_user_id)){
				$chat_box_message = $handler->createChatBoxSolicitud($messageObj->chat_user, $messageObj->chat_ask, $messageObj->chat_user_id);
				$handler->send($chat_box_message);
				echo "Peticion recibido: " . $chat_box_message . PHP_EOL;
			}

			///responder a un solo usuario (porque sino se lo manda a todos)
			if(isset($messageObj->chat_user, $messageObj->chat_response, $messageObj->chat_user_id)){
				$chat_box_message = $handler->createChatBoxMessage($messageObj->chat_user, $messageObj->chat_response);
				$handler->sendTo($chat_box_message, $messageObj->chat_user_id);
				echo "respuesta para ". $messageObj->chat_user_id ." recibido: " . $chat_box_message . PHP_EOL;
			}


        } elseif ($bytesReceived === 0 || $socketData === false) {
            //Desconectar cliente
            socket_getpeername($clientSocket, $client_ip_address);
            $handler->unregisterClient($clientSocket); //lo sacamos del array de clientes
            echo "Cliente desconectado: $client_ip_address" . PHP_EOL;
            $connectionACK = $handler->connectionDisconnectACK($client_ip_address);
            $handler->send($connectionACK);

            unset($clientSocketArray[array_search($clientSocket, $clientSocketArray)]);
            socket_close($clientSocket);
        }
    }
}
echo "Servidor WebSocket detenido.\n";

$handler->resetInstance();
socket_close($socketResource);
