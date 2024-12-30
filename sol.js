/**
 * Cosas guardadas.
 */

//lado cliente
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


//lado operador
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