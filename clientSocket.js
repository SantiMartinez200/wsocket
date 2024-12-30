var websocket;
function showRow(messageHTML) {
    const chatBox = document.getElementById("chat-box");
    chatBox.innerHTML += messageHTML;
}

function identify() {
    Swal.fire({
        title: 'Simulando pedir un prestamo de alguna forma con websockets',
        html: `
            <form id="alertForm">
                <input type="text" id="alertName" class="swal2-input" placeholder="Name">
            </form>
        `,
        confirmButtonText: 'Enviar',
        preConfirm: () => {
            const name = Swal.getPopup().querySelector('#alertName').value;
            if (!name) {
                Swal.showValidationMessage(`Pone un nombre por favor te lo pido`);
            }
            return { name: name };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const name = result.value.name;
            websocket = new WebSocket(`ws://localhost:8090/php-socket.php?username=${name}`);

            Swal.fire({
                title: "Conectando al websocket",
                didOpen: () => {
                    Swal.showLoading();
                    websocket.onopen = function (event) {
                        Swal.close();
                        websocket.send(JSON.stringify({ message: "peticion", name: name }));
                    };
                    websocket.onerror = function (event) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'No se pudo conectar al WebSocket',
                        });
                    };
                }
            });
        }
    });
}

document.addEventListener("DOMContentLoaded", function () {
    identify();
});