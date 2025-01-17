<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

//var_dump($_GET);

$conn = new SQLconn();

$conn->DELETE('service_in', 
    'service_ID=' . $_GET['service_ID']  
    . " AND type_ID=" . $_GET['type_ID']
    . " AND color" . IsNull($_GET['color'])
    . " AND count=" . $_GET['count']
    . " AND costs=" . $_GET['costs']
    . " AND ID>" . $_GET['LInd']
    . " LIMIT 1");

$conn->close();

function IsNull($in){
    if ($in == 'NULL')
        return " IS NULL";
    else
        return '=' . $in;
}
?>