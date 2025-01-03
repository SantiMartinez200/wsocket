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


Consideraciones del websocket:
-Hay DOS vistas, en el caso del listado/prestamo/loquesea, una vista tiene que disparar un registro, la otra
aceptarlo o rechazarlo.
-una vista es de "operador", la otra de "cliente".
-el servidor de websocket DEBE estar encendido.

Por el momento el servidor websocket es g e n e r a l... por lo que no "iniciamos" y "terminamos" chats...

Lo ideal sería finalizarle la sesión al usuario que está en el array $clients en memoria en el handler
cuando se le acepta/rechaza el prestamo.

ACTUALIZACION 2025-01-02 ACTUALIZACION 2025-01-02 ACTUALIZACION 2025-01-02 ACTUALIZACION 2025-01-02 ACTUALIZACION 2025-01-02

Vistas basadas en tablas.
Usuario que defino como "operador" se conecta a una vista (index), acepta/rechaza el prestamo.

Usuario que defino como "cliente" se conecta a una vista (client). y cuando se conecta; automaticamente 
genera un prestamo (a fines de simulacion)

Se genera el prestamo en bd, y se dispara un mensaje por websocket para actualizar ese listado (en base a la BD)
Operador rechaza/acepta el prestamo.
y dispara lo siguiente:
    dispara mensaje que es devuelto al cliente, actualiza el estado de su vista. 
    dispara mensaje para actualizar el listado de solicitudes del operador.

El mensaje de actualización de listado es general, TODOS los usuarios lo reciben (se muestre o no)
El mensaje de aceptacion/rechazo de préstamo es "privado", lo recibe solo el usuario que disparó la solicitud
de prestamo. sinó... todos los usuarios se les cancelaria/aceptaria el prestamo (ver como darle mas seguridad a esto).