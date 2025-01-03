/**
 * Author: Santiago Martínez
 * Created: 2025-01-03
 * Description: Este archivo contiene la lógica para la conexión con el servidor websocket desde el lado de la solicitud(?) 
 */

var websocket;
var attempt = 0;
var max_attempts = 100000;
var retryTime = 2000;
var user_type = "user";
var message = "connect";
const url = `ws://localhost:8090/php-socket.php?usertype=${encodeURIComponent(user_type)}&message=${encodeURIComponent(message)}`;


/**Ignorar en la implementacion o reemplazar por listado.Get()*/
function showRow(messageHTML) {
    const chatBox = document.getElementById("chat-box");
    chatBox.innerHTML += messageHTML;
}
/**Ignorar en la implementacion o reemplazar por listado.Get()*/

function connect(){   
    websocket = new WebSocket(url);      
    websocket.onopen = function (event) {
        attempt = 0;
        emitPersToast('Conectado al servidor interno.','info');
    };
    websocket.onmessage = function (event) {
        const data = JSON.parse(event.data);
        console.log('Received message:', data);
        if(data.message_code == 'P001'){
            emitPersToast('Conectado al servidor interno.','info');
        }
        if(data.message_code == 'P00A'){
            emitPersToast('Prestamo aceptado.','success',data.message_extra,);
            websocket.close();
        }
        if(data.message_code == 'P00R'){
            emitPersToast("Prestamo rechazado.",'warning',data.message_extra,);
            websocket.close();
        }
    };
    
    websocket.onclose = function (event) {        
        if (event.code == 1006) {
            //Reintentar la instanciacion del websocket
            if (attempt <= max_attempts) {
                console.warn("Conexión cerrada. Código:", event.code, "Motivo:", event.reason || "No especificado");
                setTimeout(() => {
                    connect();
                    emitToastConnection(attempt,retryTime);
                }, retryTime);
            } else {
                //console.warn("No se pudo establecer la conexion con el servidor interno.");
                let title = "No se pudo establecer la conexion con el servidor interno.";
                emitToastErr(title);
            }
            console.warn("Reintentando conexión ", `(${attempt})`);
            attempt++;
            
        }else{
            //ocurrió otro error
            websocket.onerror = function (event) {
                console.error('WebSocket error:', event);
                let title = "Ha ocurrido un error.";
                emitToastErr(title);
            };
        }
    }  
}    

document.addEventListener("DOMContentLoaded", function () {
    connect();
});