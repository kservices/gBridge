<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('db.php');

$hdl = db_init();
$data = db_getDevicesOfUser($hdl, $_GET['uid']);

header('Content-Type: application/json; charset=utf-8');

echo json_encode($data);
//echo var_dump($data)

?>