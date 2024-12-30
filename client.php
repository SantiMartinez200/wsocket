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
			<select type="text" name="operator-id" id="operator-id" placeholder="Message"  class="chat-input chat-message" required>
				<option value="0">Select</option>
			</select>
			<input type="text" name="chat-message" id="chat-message" placeholder="Message"  class="chat-input chat-message" required />
			
			<input type="submit" id="btnSend" name="send-chat-message" value="Send" >
			<!-- <button type="button" id="btnAsk" name="send-chat-message">Generar peticion (en chat)</button> -->
		</form>
</body>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
<script>
	var type = 'user';	
	<?php
		require_once("./chatHandler.js")
	?>	
	 const messageJSON = {
        chat_message: 'operator-list',
	};
	websocket.send(JSON.stringify(messageJSON));
	websocket.onmessage(
		function (event) {
			const Data = JSON.parse(event.data);
			if(Data.message == 'operator-list'){
				const operList = Data.operators;
				operList.forEach(element => {
					const option = document.createElement('option');
					option.value = element.id;
					option.text = element.name;
					document.getElementById('operator-id').appendChild(option);
				});
			}
		}
	)
</script>
</html>