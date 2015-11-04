var socket;

function init(token) {
	var host = "ws://localhost:8888/?token=" + token;
	try
	{
		socket = new WebSocket(host);
		show({nome : 'Status', msg : 'Conectando'});
		socket.onmessage = function(msg){ show(JSON.parse(msg.data)); };
		socket.onopen    = function(msg){ show({nome : "Status", msg : 'Conectado'}); };
		socket.onclose   = function(msg){ show({nome : "Status", msg : 'Desconectado'}); };
	}
	catch (ex)
	{
		show({nome : "Error", msg : ex});
	}
}

function send(){
	var txt = $("#msg")[0],
			msg = txt.value;

	if( !msg ){
		alert("Message can not be empty");
		return;
	}

	txt.value = "";
	txt.focus();

	try
	{
		socket.send(/*JSON.stringify*/(msg));
	}
	catch(ex)
	{
		log(ex);
	}
}
function quit(){
	console.log("Goodbye!");

	if ( socket )
		socket.close();

	socket = null;
}

var contador_mensagens = 0;

function show(msg){
	var html  = '<div class="bloco user-nome">'+msg.nome+'</div>';
			html += '<div class="bloco user-message">'+msg.msg+'</div>';

	$("#show")[0].innerHTML += '<div class=" ln-' + (contador_mensagens%2) + '">'+html+'</div>';

	atualiza_contador();
	atualiza_campo_de_texto();
}

$("#msg")[0].onkeypress = function( event ) {
	if ( event.keyCode == 13 )
	{
		send();
	}
}
function atualiza_contador() {
	contador_mensagens++;
}
function atualiza_campo_de_texto() {
	$("#show-wrapp")[0].scrollTop = $('#show')[0].scrollHeight;
}

window.onbeforeunload = function( event ) {
	quit();
};

$("#recarregar").click(function() {
	quit();
	$("#show").html('');

	$('#chat').hide();
	$('#login').show(500);

	$('#user').focus();
});


document.authForm.onsubmit = function ( event ) {
	event.preventDefault();
	$.ajax({
		url: document.authForm.action,
		type: document.authForm.method,
		dataType: 'JSON',
		data:$(document.authForm).serialize(),
		// beforeSend: function() {
		// },
		// complete: function() {
		// },
		success: function(json) {

			if ( ! json.id ) {
				alert("Usuário ou senha inválidos.")
				return;
			}

			init(json.id);

			$('#login').hide();
			$('#chat').show(500);

			$('#msg').focus();
		}
	});
}
