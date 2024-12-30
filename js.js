<script>
		var websocket;
		function showMessage(messageHTML) {
		    const chatBox = document.getElementById("chat-box");
		    chatBox.innerHTML += messageHTML;
		}

		document.addEventListener("DOMContentLoaded", function () {
			const userId = getRandomInt(1,1000);
			const userType = "operator";
			 websocket = new WebSocket(`ws://localhost:8090/php-socket.php?userId=${userId}&userType=${userType}`);

			function getRandomInt(min, max) {
			    min = Math.ceil(min);
			    max = Math.floor(max);
			    return Math.floor(Math.random() * (max - min + 1)) + min; // Both inclusive
			}

			websocket.onopen = function (event) {
			   const metadata = {
				userId: getRandomInt(1, 1000),
			       userType: "operator"
			   };
			   console.log("Sending metadata:", metadata); // Log the metadata
			   websocket.send(JSON.stringify(metadata));
			   showMessage("<div class='chat-connection-ack'>Connection is established!</div>");
			};

		
		    websocket.onmessage = function (event) {
		        const Data = JSON.parse(event.data);
				console.log(Data,'datos');
		        showMessage("<div class='" + Data.message_type + "'>" + Data.message + "</div>");
		        document.getElementById("chat-message").value = "";

				let chatUser = document.getElementById("chat-user");
		        chatUser.type = "hidden";

				btnacep = document.querySelectorAll('.btnAcep')
					console.log(btnacep);
					
						btnacep.forEach(b => {
							b.addEventListener('click',(event)=>{
								console.log('click');
								
								const messageJSON = {
		        				    	chat_user: chatUser.value,
		        				    	chat_response: 'aceptar',
										chat_user_id: b.dataset.id
		        				};
		        				websocket.send(JSON.stringify(messageJSON));
							});
						});

					btnrech = document.querySelectorAll('.btnRech')
					console.log(btnrech);
					
						btnrech.forEach(b => {
							b.addEventListener('click',(event)=>{
								console.log('click');
								
								const messageJSON = {
		        				    chat_user: chatUser.value,
		        				    chat_response: 'rechazar',
									chat_user_id: b.dataset.id
		        				};
		        				websocket.send(JSON.stringify(messageJSON));
							});
						});

						//leer los mensajes que van apareciendo
						replyTo();
		    };
		
		    websocket.onerror = function (event) {
		        showMessage("<div class='error'>Problem due to some Error</div>");
		    };
		
		    websocket.onclose = function (event) {
		        showMessage("<div class='chat-connection-ack'>Connection Closed</div>");
		    };
		
		    const frmChat = document.getElementById("frmChat");
		    frmChat.addEventListener("submit", function (event) {
		        event.preventDefault();
		        let chatUser = document.getElementById("chat-user");
		        chatUser.type = "hidden";
				let chat_user_id;

				if(typeof chat_user_id === 'undefined'){
					chat_user_id = 'all'; //esto es arbitrario.
				}
			
		        const messageJSON = {
		            chat_user: chatUser.value,
					chat_user_id: chat_user_id,
		            chat_message: document.getElementById("chat-message").value
		        };
			
		        websocket.send(JSON.stringify(messageJSON));
		    });
		});
				function rechazar(id){	
					 fetch('./rechazar.php', {
					     method: 'POST',
					     headers: {
					         'Content-Type': 'application/json' // Indica que los datos están en formato JSON
					     },
					     body: JSON.stringify({ id }) // Convierte los datos a JSON
					 })
					 .then(response => response.json())
					 .then(data => {
						if(data.success == true){
							const messageJSON = {
		       				     chat_user: 'superAdmin',
		       				     chat_message: data.message,
		       				 };
		       				 websocket.send(JSON.stringify(messageJSON));
						}
						
					 });
				}

				function aceptar(id){	
					console.log(id);
					fetch('./aceptar.php', {
					     method: 'POST',
					     headers: {
					         'Content-Type': 'application/json' // Indica que los datos están en formato JSON
					     },
					     body: JSON.stringify({ id }) // Convierte los datos a JSON
					 })
					 .then(response => response.json())
					 .then(data => {
						if(data.success == true){
							const messageJSON = {
		       				     chat_user: 'superAdmin',
		       				     chat_message: data.message,
		       				 };
		       				 websocket.send(JSON.stringify(messageJSON));
						}

					 });
				}

				function replyTo(){
					console.log('reply');
					let btns = document.querySelectorAll('.reply.btn.btn-outline-success');
					console.log(btns);
					
					btns.forEach(btn => {
						btn.addEventListener('click',(event)=>{
							let btnUserData = btn.dataset.id;
							chat_user_id = btnUserData;
							showMessage("<div class='chat-connection-ack'>Replying to: " + `${chat_user_id}` +"</div>");
						});
					});
				}
			</script>