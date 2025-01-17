<?php

if (count($_GET) > 0) {
    //var_dump(json_encode($_GET));
    var_dump($_GET);
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

?>