DESCRIPCION:
Conjunto de archivos para un chat/envío de mensajes a través de websockets.
Socket original por: (link acá)

php-socket.php -> Inicia el servidor websocket (ejecutar con php [nombreArchivo])

class.handler.php -> Clase para el manejo de: Conexión y desconexión de usuarios,
Mensajes, usuarios conectados (en memoria).

La idea original de esto es entablar un websocket entre 2 usuarios para:
A) En el journey del préstamo, un usuario vendedor solicita un préstamo.
B) Un usuario operador/responsable/con otro cargo acepta/rechaza el préstamo.
C) El usuario vendedor recibe esta respuesta y continua/finaliza el préstamo.

Estoy trabajando sobre una base de chat. pero es adaptable a envío/recepción de mensajes a través del servidor.
En la rama principal haré el chat.
En la rama journey lo adaptaré a un simulador del journey que necesitamos.

Consideraciones del chat:
-Hay DOS vistas.
-una de operador, otra de cliente.
-el servidor de websocket DEBE estar encendido.

El chat funciona de la siguiente forma:
A)Cliente se conecta al chat cuando ingresa a /clientes.php 
B)Cliente envía mensaje en general (todos los operadores lo reciben)
C)Operador contesta mensaje de un cliente clickeando en "responder" o algo por el estilo.
D)Cliente puede seguir hablando con el Operador.
E)Operador puede enviar mensajes generales (a todos los Clientes)

Por el momento el servidor websocket es g e n e r a l... por lo que no "iniciamos" y "terminamos" chats...
Lo ideal sería finalizarle la conversación al usuario que está en el array $clients en memoria en el handler
y avisarle de eso.


¿Progreso?
El websocket es funcional.
Se pueden entablar mensajes entre operadores/usuarios.

