#!/usr/bin/env php
<?php

require_once('chat.php');

class echoServer extends WebSocketServer
{
	//protected $maxBufferSize = 1048576; //1MB... overkill for an echo server, but potentially plausible for other applications.
	protected function process ($user, $message)
	{
		$message = array(
			'nome' => $user->nome,
			'msg' => htmlentities($message)
		);
		foreach ($this->users as $currentUser) {
			$this->send($currentUser, json_encode($message));
		}
	}
	
	protected function connected ($user)
	{
		$header = $this->parse_get_header($user->requestedResource);

		$usuario = get_usuario_by_id($header['token']);

		if ( ! $usuario )
		{
			return $this->disconnect($user->socket);
		}

		$this->users[$user->id]->nome = $usuario['nick'];
		$this->users[$user->id]->token = $header['token'];

		update_usuario_to_online($header['token']);
	}
	
	protected function closed ($user)
	{
		update_usuario_by_id($user->token);
	}
}

$echo = new echoServer("0.0.0.0","8888");

try
{
	$echo->run();
}
catch (Exception $e)
{
	$echo->stdout($e->getMessage());
}
