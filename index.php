<?php
/*
Un ejemplo de un operador que acepta o rechaza solicitudes de un cliente.
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
		<p>teoricamente esta vista conecta al "operador" al websocket</p>
		<!-- <table style="text-align: center;border: 1px solid; width:500px;">
			<thead>
			   <tr>
				<th>id</th>
				<th>estado</th>
				 <th>dni</th> 
				<th>accion</th>
			   </tr>
			</thead>
			<tbody> -->
				
		<?php
			// require_once('./conn.php');
			// $stmt = $conn->prepare("SELECT * FROM solicituds");
			// $stmt->execute();
			// //print_r($stmt);


			// $result = $stmt->setFetchMode(PDO::FETCH_ASSOC);
			// foreach($stmt->fetchAll() as $k => $v) {
			// 	?>
			<!-- 	<tr>
			 		<td><?php //echo $v['id']  ?></td>
			 		<td><?php //echo $v['estado']  ?></td>
			 		<td>
			 			<a href="#"><button class="btnAcep" data-value="<?php //echo $v['id'] ?>">aceptar</button></a><a href="#"><button class="btnRech" data-value="<?php echo $v['id'] ?>">rechazar</button></a>
		
			 		</td>
			 		</tr>-->
			 	<?php
			// }



		?>
			<!-- </tbody>
		</table> -->
		<form name="frmChat" id="frmChat">
			<div id="chat-box"></div>
			<input type="text" name="chat-user" id="chat-user" placeholder="Name" class="chat-input" required value="operator"/>
			<input type="text" name="chat-message" id="chat-message" placeholder="Message"  class="chat-input chat-message"  />
			<input type="submit" id="btnSend" name="send-chat-message" value="Send" >
		</form>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
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

			 function replyTo(){
					let btns = document.querySelectorAll('.reply.btn.btn-outline-success');
					
					btns.forEach(btn => {
						btn.addEventListener('click',(event)=>{
							let btnUserData = btn.dataset.id;
							chat_operator_id = userId;
							chat_user_id = btnUserData;
							
							showMessage("<div class='chat-connection-ack'>Replying to: " + `${chat_user_id}` +"</div>");
						});
					});
				}

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
				//let chat_user_id;

				if(typeof chat_user_id === 'undefined'){
					chat_user_id = 'all'; //esto es arbitrario.
				}
			
		        const messageJSON = {
					chat_userType: 'operator',
		            chat_user: chatUser.value,
					chat_operator_id: userId,
					chat_user_id: chat_user_id,
		            chat_message: document.getElementById("chat-message").value
		        };
			
				console.log(messageJSON);
				
		        websocket.send(JSON.stringify(messageJSON));
		    });

			
		});
	</script>

</html>