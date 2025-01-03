var websocket;
function showRow(messageHTML) {
    const chatBox = document.getElementById("chat-box");
    chatBox.innerHTML += messageHTML;
}


function aceptar(websocket,userid){
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
    button.addEventListener('click', () => {
        if(inputReason.value === ''){
            return alert('Debe ingresar un motivo');
        }
        websocket.send(JSON.stringify({message_code: 'P00A', message_type: 'loan-accept',message: 'Aceptar Prestamo', message_extra:inputReason.value ,userid: userid}));
    })
}

function rechazar(websocket,userid){
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
    button.addEventListener('click', () => {
        if(inputReason.value === ''){
            return alert('Debe ingresar un motivo');
        }
        websocket.send(JSON.stringify({message_code: 'P00R', message_type: 'loan-reject',message: 'Rechazar Prestamo', message_extra:inputReason.value ,userid: userid}));
    })
}


function identify() {
    var user_type = "oper";
    var message = "watch";
    
    const url = `ws://localhost:8090/php-socket.php?usertype=${encodeURIComponent(user_type)}&message=${encodeURIComponent(message)}`;

    const websocket = new WebSocket(url);
            websocket.onopen = function (event) {
                Swal.close();
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

                    showRow("<div class='row' style='position: absolute; z-index: 999; top: 0; right: 0;' id='update'><div class='col-md-12'><div class='alert alert-warning' role='alert'>"+"Actualizando listado..."+"</div></div></div>");



                    showRow("<div class='row'><div class='col-md-12'><div class='alert alert-info' role='alert'>"+"listado de prestamos ("+`${data.message.length}`+")</div></div></div>");
                    data.message.forEach(el => {
                        //console.log(el);
                        
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
                    
                        // Crea los botones y asigna los event listeners
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
                    });
                    
                }


                setTimeout(() => {
                    var update = document.getElementById("update");
                    if(update){
                        update.remove();
                    }
                }, 2000);
            };

            websocket.onerror = function (event) {
                console.error('WebSocket error:', event);
            };

            websocket.onclose = function (event) {
                console.log('WebSocket closed:', event);
            };
}

document.addEventListener("DOMContentLoaded", function () {
    identify();
});