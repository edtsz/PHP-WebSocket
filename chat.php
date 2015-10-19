<?php  /*  >php -q server.php  */

include 'common.php';

set_time_limit(0);
ob_implicit_flush();

$master  = WebSocket("192.168.0.4",8888);
$sockets = array(-1 => $master);
$users   = array();
$debug   = FALSE;


while( TRUE ) {
	$changed = $sockets;
	$write   = NULL;
	$except  = NULL;
	socket_select($changed, $write, $except, NULL);

	foreach( $changed as $socket )
	{
		if( $socket == $master )
		{
			$client = socket_accept($master);
			if( $client < 0 )
			{
				console("socket_accept() failed");
			 	continue;
			}
			else
			{
				connect($client);
			}
		}
		else
		{
			$bytes = @socket_recv($socket, $buffer, 2048, 0);
			if( $bytes == 0 )
			{
				disconnect($socket);
			}
			else
			{
				$user = getuserbysocket($socket);
				if( ! $user->handshake )
				{
					dohandshake($user, $buffer);
				}
				else
				{
					process($user, $buffer);
				}
			}
		}
	}
}


//---------------------------------------------------------------
function process( $user, $msg )
{
	send(get_users(), unwrap($msg), $user);
}


function send( $client, $msg, $user )
{
	if ( strlen($msg) == 0 )
	{
		disconnect( $user->socket );
		return;
	}

	$msg = '{"nome":"' . $user->name . '", "msg":"' . $msg . '"}';

	say('$: ' . $user->id . ' | ' . $msg);

	$msg = wrap($msg);
	if ( is_array($client) )
	{
		foreach ($client as $key => $value) {
			$sent = socket_write($value, $msg);
		}
		return;
	}
	$sent = socket_write($client, $msg);
}


function WebSocket( $address, $port )
{
	$master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)   or die("socket_create() failed");
	socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
	socket_bind($master, $address, $port)                    or die("socket_bind() failed");
	socket_listen($master, 20)                               or die("socket_listen() failed");
	echo "Server Started : ".date('Y-m-d H:i:s')."\n";
	echo "Master socket  : ".$master."\n";
	echo "Listening on   : ".$address." port ".$port."\n\n";
	return $master;
}


function connect( $socket )
{
	global $sockets, $users;

	$user = new User();
	$user->socket = $socket;

	array_push($users, $user);
	array_push($sockets, $socket);
	console($socket." CONNECTED!");
}


function disconnect( $socket )
{
	global $sockets, $users;

	$connection_id = array_search($socket, $sockets);

	$user = $users[$connection_id];

	unset($sockets[$connection_id]);
	unset($users[$connection_id]);

	socket_close($socket);
	console($socket . " DISCONNECTED!");

	update_usuario_by_id($user->id);
}


function dohandshake( $user, $buffer )
{
	console("\nRequesting handshake...");
	console($buffer);
	list($resource, $host, $origin, $key) = getheaders($buffer);

	$_GET = parse_get_header($resource);

	console("Start handshaking...");

	$upgrade =
		"HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
		"Upgrade: WebSocket\r\n" .
		"Connection: Upgrade\r\n" .
		"Sec-WebSocket-Accept: " . base64_encode(sha1($key."258EAFA5-E914-47DA-95CA-C5AB0DC85B11", TRUE))."\r\n".
		"\r\n";
	socket_write($user->socket, $upgrade);

	$query = get_usuario_by_id($_GET['token']);

	$user->id        = $_GET['token'];
	$user->name      = $query['nick'];
	$user->handshake = TRUE;

	update_usuario_to_online($_GET['token']);

	console($upgrade);
	console("Done handshaking...");

	return TRUE;
}


function getheaders( $req )
{
	$r = $h = $o = $key1 = NULL;
	if( preg_match("/GET (.*) HTTP/",    $req, $match) )
	{
		$r = $match[1];
	}
	if( preg_match("/Host: (.*)\r\n/",   $req, $match) )
	{
		$h = $match[1];
	}
	if( preg_match("/Origin: (.*)\r\n/", $req, $match) )
	{
		$o = $match[1];
	}
	if( preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$req,$match) )
	{
		$key1 = $match[1];
	}
  //if(preg_match("/\r\n(.*?)\$/",$req,$match)){ $data=$match[1]; }
	return array( $r, $h, $o, $key1 );
}


function getuserbysocket( $socket )
{
	global $users;

	$found = array();

	foreach( $users as $user )
	{
		if( $user->socket == $socket )
		{
			return $user;
		}
	}
	return $found;
}


function  say( $msg = "" )
{
	echo print_r( $msg , 1) , PHP_EOL;
}


function wrap( $msg = "" )
{
	$length = strlen($msg);
	$header = chr(0x81).chr($length);
	$msg    = $header.$msg;
	return $msg;
}


function unwrap( $msg = "" )
{
	$firstMask      = bindec("10000000");
	$secondMask     = bindec("01000000"); //im not doing anything with the rsvs since we arent negotiating extensions...
	$thirdMask      = bindec("00100000");
	$fourthMask     = bindec("00010000");
	$firstHalfMask  = bindec("11110000");
	$secondHalfMask = bindec("00001111");
	$payload        = "";
	$firstHeader    = ord(($msg[0]));
	$secondHeader   = ord($msg[1]);
	$key            = Array();
	$fin            = (($firstHeader & $firstMask) ? 1 : 0 );
	$rsv1           = $rsv2 = $rsv3 = 0;
	$opcode         = $firstHeader & (~$firstHalfMask);//TODO: make the opcode do something. it extracts it but the program just assumes text;
	$masked         = (($secondHeader & $firstMask) != 0);
	$length         = $secondHeader & (~$firstMask);
	$index          = 2;

	if($length==126)
	{
		$length = ord($msg[$index]) + ord($msg[$index+1]);
		$index += 2;
	}

	if($length==127)
	{
		$length = ord($msg[$index]) + ord($msg[$index+1]) +
							ord($msg[$index+2]) + ord($msg[$index+3]) +
							ord($msg[$index+4]) + ord($msg[$index+5]) +
							ord($msg[$index+6]) + ord($msg[$index+7]);
		$index += 8;
	}

	if( $masked )
	{
		for( $x = 0; $x < 4; $x++ )
		{
			$key[$x] = ord($msg[$index]);
			$index++;
		}
	}

	for( $x = 0; $x < $length; $x++ )
	{
		$msgnum         = ord($msg[$index]);
		$keynum         = $key[$x % 4];
		$unmaskedKeynum = $msgnum ^ $keynum;
		$payload       .= chr($unmaskedKeynum);
		$index++;
	}

	if($fin!=1)
	{
		return $payload.processMsg(substr($msg,$index));
	}
	return $payload;
}


function get_users()
{
	global $users;
	$return = array();

	foreach ($users as $user) {
		$return[] = $user->socket;
	}

	return $return;
}


function console( $msg = "" )
{
	global $debug;

	if ( $debug )
	{
		echo print_r($msg,1)."\n";
	}
}


function parse_get_header($header) {
	$resource = parse_url($header);

	$get = explode('&',$resource['query']);
	$_GET = array();
	foreach ($get as $each)
	{
		$tmp = explode('=', $each);
		$_GET[$tmp[0]] = $tmp[1];
	}

	return $_GET;
}


class User {
	var $id;
	var $socket;
	var $handshake;

	var $name;
}
