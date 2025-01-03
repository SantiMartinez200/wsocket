/**
 * Author: Santiago Martínez
 * Created: 2025-01-03
 * Description: Este archivo contiene la lógica para la conexión con el servidor websocket desde el lado de la mesa de
 * créditos.
 */

var websocket;
var attempt = 0; //parametrizable por json o config_parametros
var max_attempts = 100000; 
var retryTime = 2000;  //parametrizable por json o config_parametros
var user_type = "oper";
var message = "connect";
const url = `ws://localhost:8090/php-socket.php?usertype=${encodeURIComponent(user_type)}&message=${encodeURIComponent(message)}`;


/**Ignorar en la implementacion o reemplazar por listado.Get()*/
function showRow(messageHTML) {
    const chatBox = document.getElementById("chat-box");
    chatBox.innerHTML += messageHTML;
}
/**Ignorar en la implementacion o reemplazar por listado.Get()*/


function aceptar(websocket,userid){
    
    /*Esto de aca se va a cambiar por el getAjax()*/
    let chatbox = document.getElementById("chat-box");
    let inputReason = document.createElement('input');
    inputReason.type = 'text';
    inputReason.placeholder = 'Motivo';
    inputReason.id = 'reason';
    inputReason.required = true;
    chatbox.appendChild(inputReason);
    let button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Aceptar';
    chatbox.appendChild(button);
    /*Esto de aca se va a cambiar por el getAjax()*/


    /*Escuchar la confirmacion del swal*/
    button.addEventListener('click', () => {
        if(inputReason.value === ''){
            return alert('Debe ingresar un motivo');
        }
        websocket.send(JSON.stringify({message_code: 'P00A', message_type: 'loan-accept',message: 'Aceptar Prestamo', message_extra:inputReason.value ,userid: userid}));
    })
    /*Escuchar la confirmacion del swal*/
}

function rechazar(websocket,userid){
    /*Esto de aca se va a cambiar por el getAjax()*/
    let chatbox = document.getElementById("chat-box");
    let inputReason = document.createElement('input');
    inputReason.type = 'text';
    inputReason.placeholder = 'Motivo';
    inputReason.id = 'reason';
    inputReason.required = true;
    chatbox.appendChild(inputReason);
    let button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Rechazar';
    chatbox.appendChild(button);
    /*Esto de aca se va a cambiar por el getAjax()*/

    
    /*Escuchar la confirmacion del swal*/
    button.addEventListener('click', () => {
        if(inputReason.value === ''){
            return alert('Debe ingresar un motivo');
        }
        websocket.send(JSON.stringify({message_code: 'P00R', message_type: 'loan-reject',message: 'Rechazar Prestamo', message_extra:inputReason.value ,userid: userid}));
    })
    /*Escuchar la confirmacion del swal*/
}

function connect() {
    websocket = new WebSocket(url);
    websocket.onopen = function (event) {
        attempt = 0;
        emitPersToast('Conectado al servidor interno.','info');
    };
    websocket.onmessage = function (event) {
        const data = JSON.parse(event.data);
        console.log('Received message:', data);
        
        if(data.message_code == 'P001'){
            showRow("<div class='row'><div class='col-md-12'><div class='alert alert-info' role='alert'>"+data.message+"</div></div></div>");
        }
        if(data.message_code == 'P002'){
            var chat = document.getElementById("chat-box");
            chat.innerHTML = "";
            emitPersToast("Verificando listado...","info");
            data.message.forEach(el => {
                //console.log(el);
                
                /*Esta parte de acá no será necesara para la implementacion*/
                let row = document.createElement('div');
                row.classList.add('row');
            
                let col = document.createElement('div');
                col.classList.add('col-md-4');
                
                let col2 = document.createElement('div');
                col2.classList.add('col-md-4');
            
                let col3 = document.createElement('div');
                col3.classList.add('col-md-4');
            
                let alert = document.createElement('div');
                alert.classList.add('alert');
                alert.classList.add('alert-info');
                alert.setAttribute('role', 'alert');
                alert.innerHTML = el[1];
            
                let alert2 = document.createElement('div');
                alert2.classList.add('alert');
                alert2.classList.add('alert-info');
                alert2.setAttribute('role', 'alert');
                alert2.innerHTML = el[2];
            
                let alert3 = document.createElement('div');
                alert3.classList.add('alert');
                alert3.classList.add('alert-info');
                alert3.setAttribute('role', 'alert');
                /*Esta parte de acá no será necesara para la implementacion*/
            
                /*Esta parte de acá no será necesara para la implementacion*/
                let acceptButton = document.createElement('button');
                acceptButton.type = 'button';
                acceptButton.textContent = 'V';
                acceptButton.classList.add('aceptar');
                acceptButton.addEventListener('click', () => aceptar(websocket, el[1]));
            
                let rejectButton = document.createElement('button');
                rejectButton.type = 'button';
                rejectButton.textContent = 'X';
                rejectButton.classList.add('rechazar');
                rejectButton.addEventListener('click', () => rechazar(websocket, el[1]));
            
                alert3.appendChild(acceptButton);
                alert3.appendChild(rejectButton);
            
                col.appendChild(alert);
                col2.appendChild(alert2);
                col3.appendChild(alert3);
            
                row.appendChild(col);
                row.appendChild(col2);
                row.appendChild(col3);
            
                chat.appendChild(row);
                /*Esta parte de acá no será necesara para la implementacion*/
            });
            
        }

        /*Actualizar tabla frente a recibir una solicitud... habria que buscar el ajax que genera el listado.*/
        setTimeout(() => {
            var update = document.getElementById("update");
            if(update){
                update.remove();
            }
        }, 2000);
        /*Actualizar tabla frente a recibir una solicitud... habria que buscar el ajax que genera el listado.*/
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
    //instanciar websocket
    connect();
});