<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$dat_now = DateTime::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s'));

$dat = $_GET['date_in'] . $dat_now->format(' H:i:s');

$_GET['date_in'] = $dat;

//var_dump($_GET);

$conn = new SQLconn();

$conn->INSERT('service_in', $_GET, 0);

$conn->close();

?>