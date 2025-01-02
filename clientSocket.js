var websocket;
function showRow(messageHTML) {
    const chatBox = document.getElementById("chat-box");
    chatBox.innerHTML += messageHTML;
}

function identify() {
    var name = 'santiago';
    var user_type = "user";
    var message = "peticion";
    
    const url = `ws://localhost:8090/php-socket.php?username=${encodeURIComponent(name)}&usertype=${encodeURIComponent(user_type)}&message=${encodeURIComponent(message)}`;

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
                if(data.message_code == 'P00A'){
                    showRow("<div class='row'><div class='col-md-12'><div class='alert alert-info' role='alert'>"+data.message+"</div></div></div>");
                }
                if(data.message_code == 'P00R'){
                    showRow("<div class='row'><div class='col-md-12'><div class='alert alert-info' role='alert'>"+data.message+"</div></div></div>");
                }
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