var websocket;
var attempt = 0;
var inputMessage = document.getElementById('chat-message');
var btnSend = document.getElementById('btnSend');
var retryTime = 1000;
var chat_user_id;
var chat_operator_id;
const userType = type; //definido en un archivo padre

	if(userType == 'operator'){
        inputMessage.disabled = true;
	    btnSend.disabled = true;
    }
    function showMessage(messageHTML) {
        const chatBox = document.getElementById("chat-box");
        chatBox.innerHTML += messageHTML;
    }

	function replyTo(){
        let btns = document.querySelectorAll('.reply.btn.btn-outline-success');
        btns.forEach(btn => {
            if(userType == 'operator'){
                if(btn.dataset.reply.length != 0){
                    //console.log(btn.dataset.reply);
                    chat_user_id = btn.dataset.reply;
                }
            }
        });
        btns.forEach(btn => {
            btn.addEventListener('click',(event)=>{
                inputMessage.disabled = false;
                btnSend.disabled = false;
                showMessage("<div class='chat-connection-ack'>Replying to: " + `${chat_user_id}` +"</div>");
            });
        });
    }
		var userId = getRandomInt(1,1000);
	    const frmChat = document.getElementById("frmChat");
		
		function clearChat(){
			const chatBox = document.getElementById("chat-box");
			chatBox.innerHTML = "";
		}
		
		function connect() {
		    websocket = new WebSocket(`ws://localhost:8090/php-socket.php?userId=${userId}&userType=${userType}`);
		    websocket.onopen = function (event) {
		        const metadata = {
		            userId: userId,
		            userType: userType
		        };
		        console.log("Sending metadata:", metadata);
		        websocket.send(JSON.stringify(metadata));
		        showMessage("<div class='chat-connection-ack'>Connection is established!</div>");
				attempt = 0;
		    };
		    websocket.onmessage = function (event) {
		        const Data = JSON.parse(event.data);
		        console.log(Data, 'datos');

		        if (Data.message_type === 'operator-list') {
                    let selector = document.getElementById('operator-id');
		            console.log('Lista de operadores:', Data.message);
                    Data.message.forEach(op => {
                        let option = document.createElement("option");
                        option.text = op.user_id;
                        option.value = op.user_id;
                        selector.add(option);
                    });
                } else {
		            showMessage("<div class='" + Data.message_type + "'>" + Data.message + "</div>");
		        }

		        document.getElementById("chat-message").value = "";
		        if (userType == 'operator') {
		            btnSend.disabled = true;
		            inputMessage.disabled = true;
		        }
		        replyTo();
		    };
		    websocket.onerror = function (event) {
		        if (event.code === 1006) {
		            showMessage("<div class='error'>Imposible conectar con el servidor.</div>");
		        } else {
		            showMessage("<div class='error'>Problem due to some Error</div>");
		        }
		    };
		    websocket.onclose = function (event) {
		        if (event.code === 1006) {
		            attempt++;
		            if (attempt <= 3) {
		                console.warn("Conexión cerrada. Código:", event.code, "Motivo:", event.reason || "No especificado");
		                showMessage(`<div class='chat-connection-ack'>Reintentando conexión (${attempt})</div>`);
		                setTimeout(() => {
		                    connect();
		                }, retryTime);
		            } else {
						clearChat();
		                console.warn("No se pudo establecer la conexion con el servidor interno.");
		                showMessage("<div class='error'>No se pudo establecer la conexion con el servidor interno.</div>");
		                showMessage("<div class='error'>Puedes recargar la página para seguir intentando.</div>");
		                showMessage("<div class='error'>Si el problema persiste, ponte en contacto con el administrador del sitio.</div>");
		            }
		            console.warn("Reintentando conexión ", `(${attempt})`);
		        } else {
		            showMessage("<div class='chat-connection-ack'>Connection Closed</div>");
		        }
		    };
		}
		connect();
		
		function getRandomInt(min, max) {
		    min = Math.ceil(min);
		    max = Math.floor(max);
		    return Math.floor(Math.random() * (max - min + 1)) + min; // Both inclusive
		}
		
	
	    frmChat.addEventListener("submit", function (event) {
	        event.preventDefault();
	        let chatUser = document.getElementById("chat-user");
	        chatUser.type = "hidden";
			//let chat_user_id;
			if(typeof chat_user_id === 'undefined'){
				chat_user_id = userId; //esto es arbitrario, contesten a este usuario.
			}
		
	        const messageJSON = {
                chat_username: document.getElementById("chat-user").value,
				chat_userType: userType,
	            chat_operator_id: userType == 'user' ? document.getElementById("operator-id").value : null,
				chat_user_id: chat_user_id,
	            chat_message: document.getElementById("chat-message").value
	        };
		
			console.log(messageJSON);
			
	        websocket.send(JSON.stringify(messageJSON));
	    });			
	
