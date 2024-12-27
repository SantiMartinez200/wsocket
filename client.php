<?php
/*Un ejemplo de un cliente WebSocket (cualquiera) que se conecta a un servidor WebSocket y envía y recibe mensajes.
*/
?>

<html>
<head>
	<style>
	body{width:600px;font-family:calibri;}
	.error {color:#FF0000;}
	.chat-connection-ack{color: #26af26;}
	.chat-message {border-bottom-left-radius: 4px;border-bottom-right-radius: 4px;
	}
	#btnSend {background: #26af26;border: #26af26 1px solid;	border-radius: 4px;color: #FFF;display: block;margin: 15px 0px;padding: 10px 50px;cursor: pointer;
	}
	#chat-box {background: #fff8f8;border: 1px solid #ffdddd;border-radius: 4px;border-bottom-left-radius:0px;border-bottom-right-radius: 0px;min-height: 300px;padding: 10px;overflow: auto;
	}
	.chat-box-html{color: #09F;margin: 10px 0px;font-size:0.8em;}
	.chat-box-message{color: #09F;padding: 5px 10px; background-color: #fff;border: 1px solid #ffdddd;border-radius:4px;display:inline-block;}
	.chat-input{border: 1px solid #ffdddd;border-top: 0px;width: 100%;box-sizing: border-box;padding: 10px 8px;color: #191919;
	}
	</style>	
	<script src="http://code.jquery.com/jquery-1.9.1.js"></script>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

	</head>
	<body>
		<form name="frmChat" id="frmChat">
			<div id="chat-box"></div>
			<input type="text" name="chat-user" id="chat-user" placeholder="Name" class="chat-input" required value="username"/>
			<input type="text" name="chat-message" id="chat-message" placeholder="Message"  class="chat-input chat-message" required />
			<input type="submit" id="btnSend" name="send-chat-message" value="Send" >
			<!-- <button type="button" id="btnAsk" name="send-chat-message">Generar peticion (en chat)</button> -->
		</form>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script>
	
		function getRandomInt(min, max) {
				    min = Math.ceil(min);
				    max = Math.floor(max);
				    return Math.floor(Math.random() * (max - min + 1)) + min; // Both inclusive
		}

		var chat_user_id = getRandomInt(1,1000);

		function showMessage(messageHTML) {
		    const chatBox = document.getElementById("chat-box");
		    chatBox.innerHTML += messageHTML;
		}

		document.addEventListener("DOMContentLoaded", function () {
				  userId = chat_user_id
			const userType = "user";
			const websocket = new WebSocket(`ws://localhost:8090/php-socket.php?userId=${userId}&userType=${userType}`);

			
			function replyTo(){
					let btns = document.querySelectorAll('.reply.btn.btn-outline-success');
					
					btns.forEach(btn => {
						btn.addEventListener('click',(event)=>{
							let btnUserData = btn.dataset.id;
							let btnOperData = btn.dataset.operator; //definitivamnete hay que reformar esto
							chat_operator_id = userId;
							chat_user_id = btnUserData;
							
							showMessage("<div class='chat-connection-ack'>Replying to: " + `${chat_user_id}` +"</div>");
						});
					});
				}

			websocket.onopen = function (event) {
			   const metadata = {
				userId: chat_user_id,
			       userType: "user"
			   };
			   console.log("Sending metadata:", metadata); // Log the metadata
			   websocket.send(JSON.stringify(metadata));
			   showMessage("<div class='chat-connection-ack'>Connection is established!</div>");
			};

		
		    websocket.onmessage = function (event) {
		        const Data = JSON.parse(event.data);
		        showMessage("<div class='" + Data.message_type + "'>" + Data.message + "</div>");
		        document.getElementById("chat-message").value = "";
				alert('msg recibido');
				console.log(Data.message);
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
		        const chatUser = document.getElementById("chat-user");
		        chatUser.type = "hidden";
			
		        const messageJSON = {
					chat_userType: "user",
		            chat_user: chatUser.value,
					chat_user_id: chat_user_id,
		            chat_message: document.getElementById("chat-message").value
		        };
				//console.log(messageJSON);
				
		        websocket.send(JSON.stringify(messageJSON));
		    });

			const btnAsk = document.getElementById("btnAsk");
			btnAsk.addEventListener("click", function (event) {
				//event.preventDefault();
				const chatUser = document.getElementById("chat-user");
				chatUser.type = "hidden";
				const messageJSON = {
					chat_userType: "user",
					chat_user: chatUser.value,
					chat_user_id: chat_user_id,
					chat_ask: "Esto es una peticion"
				};
				console.log("Sending metadata:", messageJSON); // Log the metadata
				websocket.send(JSON.stringify(messageJSON));
			});
		});




		
</script>
</html>