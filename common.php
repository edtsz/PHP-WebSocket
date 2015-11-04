<?php

date_default_timezone_set('America/Sao_Paulo');
error_reporting(E_ALL);


/**
 * Resgata usuário por ID
 *
 * @author  Éderson T. Szlachta
 * @access  public
 * @param   string  $id       id do usuário
 * @return  object            dados do usuário
 */
function get_usuario_by_id( $id = '' )
{
	$usuarios = json_decode(file_get_contents(__DIR__ . '/users.json'), TRUE);

	return $usuarios[$id];
}


/**
 * Resgata usuário por par de USER/PASS corretos
 *
 * @author  Éderson T. Szlachta
 * @access  public
 * @param   string  $user     USUÁRIO
 * @param   string  $pass     SENHA
 * @return  string  json      id do usuário
 */
function get_usuario_by_pass( $user = '', $pass = '' )
{
	$usuarios = json_decode(file_get_contents(__DIR__ . '/users.json')/*, TRUE*/);

	$result = array('id' => NULL);

	foreach ($usuarios as $id => $usuario)
	{
		if ( $usuario->online == FALSE &&  $usuario->user == $user && $usuario->pass == md5($pass))
		{
			$result['id'] = $id;
			break;
		}
	}

	return $result;
}


/**
 * Atualiza o ID do usuário
 *
 * @author  Éderson T. Szlachta
 * @access  public
 * @param   string  $id       id do USUÁRIO
 * @return  void
 */
function update_usuario_by_id( $id = '' )
{
	$usuarios = json_decode(file_get_contents(__DIR__ . '/users.json'), TRUE);

	// if ( ! in_array($id, $usuarios) )
	// 	return;

	$usuario = $usuarios[$id];
	unset($usuarios[$id]);

	$usuario['online'] = FALSE;

	$usuarios[uniqid()] = $usuario;

	file_put_contents(__DIR__ . '/users.json', json_encode($usuarios));
}


/**
 * Atualiza o ID do usuário
 *
 * @author  Éderson T. Szlachta
 * @access  public
 * @param   string  $id       id do USUÁRIO
 * @return  void
 */
function update_usuario_to_online( $id = '' )
{
	$usuarios = json_decode(file_get_contents(__DIR__ . '/users.json'), TRUE);

	// if ( ! in_array($id, $usuarios) )
	// 	return;

	$usuarios[$id]['online'] = TRUE;

	file_put_contents(__DIR__ . '/users.json', json_encode($usuarios));
}


/**
 * Atualiza o ID do usuário
 *
 * @author  Éderson T. Szlachta
 * @access  public
 * @param   string  $id       id do USUÁRIO
 * @return  void
 */
function update_usuario_to_offline( $id = '' )
{
	$usuarios = json_decode(file_get_contents(__DIR__ . '/users.json'), TRUE);

	// if ( ! in_array($id, $usuarios) )
	// 	return;

	$usuarios[$id]['online'] = FALSE;

	file_put_contents(__DIR__ . '/users.json', json_encode($usuarios));
}
