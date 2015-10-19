<?php

include 'common.php';

$user = isset($_POST['user']) ? $_POST['user'] : FALSE;
$pass = isset($_POST['pass']) ? $_POST['pass'] : FALSE;


header("Content-Type: text/plain");
echo json_encode(get_usuario_by_pass($user, $pass));
