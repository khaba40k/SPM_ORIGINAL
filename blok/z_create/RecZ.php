<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$conn = new SQLconn();

#region ÊÎÅÔ²Ö²ªÍÒ ÇÍÈÆÊÈ
$DISCOUNT_KOEF = 1;

if (!json_decode($_GET["DISCOUNT_IGNORE"])){
    if (strlen($_GET["discount"]) == 5) {
        $_GET["discount"] = strtoupper($_GET["discount"]);

        $result = $conn->SELECT('discount_list', 'percent', @"WHERE code = '{$_GET['discount']}' AND from_ID IS NULL");
        if (is_array($result) && count($result) == 1) {
            $_GET["discount"] = $result[0]["percent"];
        } 
    }

    if (!empty($_GET["discount"]) && is_numeric($_GET["discount"]) && $_GET["discount"] < 100) {
        $DISCOUNT_KOEF = (100 - $_GET["discount"]) / 100;
    } else if (!is_numeric($_GET["discount"]) || ($_GET["discount"] > 99 || $_GET["discount"] < 1)) {
        $_GET["discount"] = null;
    }
}

#endregion

#region ÏÐÈÑÂÎªÍÍß ID
$NEW_ID = false;

if ($_GET['ID'] == 0) {
    $result = $conn->SELECT('client_info', 'ID', "ORDER BY ID DESC LIMIT 1");
    $_GET['ID'] = count($result) == 1 ? ($result[0]['ID'] + 1) : 1;
    $NEW_ID = true;
}
#endregion

#region ÏÐÈÑÂÎªÍÍß ÍÎÌÅÐÀ

$ZNumberName = $_GET['ZTYPE'] == "DEFF" ? "sholom_num" : "sold_number";

if (!empty($_GET['TTN_IN']) && empty($_GET[$ZNumberName] ?? null)){
    $result = $conn->SELECT("client_info", $ZNumberName, "ORDER BY " . $ZNumberName . " DESC LIMIT 1");
    $_GET[$ZNumberName] = ($result[0][$ZNumberName] ?? 0) + 1;
}else if (empty($_GET['TTN_IN'])){
    $_GET[$ZNumberName] = 0;
}

#endregion

#region ÇÀÏÈÑ ÄÀÍÈÕ ÇÀßÂÊÈ (êë³ºíò, ðåêâ³çèòè...)

function formatString($inp, $def = null){
    return ($inp !== null && trim($inp) != "") ? trim($inp) : $def;  
}

$conn->INSERT('client_info', [
    "ID"=>formatString($_GET['ID'], 1),
    $ZNumberName => formatString($_GET[$ZNumberName] ?? 0, 0),
    "date_in" => formatString($_GET['date_in']),
    "date_max" => formatString($_GET['date_max']),
    "date_out" => formatString($_GET['date_out'] ?? null),
    "phone" => $_GET['phone'],
    "client_name" => formatString($_GET['client_name'], ""),
    "reqv" => formatString($_GET['reqv'], ""),
    "TTN_IN" => formatString($_GET['TTN_IN']),
    "TTN_OUT" => formatString($_GET['TTN_OUT'] ?? null),
    "comm" => formatString($_GET['messendger'] . " " . $_GET['comm']),
    "discount" => formatString($_GET['discount']),
    "callback" => formatString($_GET['callback'] ?? 1, 1),
    "worker" => formatString($_GET['worker']),
    "redaktor" => formatString($_GET['redaktor']),
]);

#endregion

#region ÇÀÏÈÑ ÊÎÌÏËÅÊÒÓÞ×ÈÕ

//âèäàëåííÿ ³ñíóþ÷èõ

if (!$NEW_ID) {
    $conn->DELETE('service_out', 'ID = ' . $_GET['ID']);
}

$SERVICES = $_GET['SERVICES'];

foreach ($SERVICES as $serv){
    $conn->INSERT('service_out', [
        "ID"=>$_GET['ID'],
        "service_ID"=>$serv['ID'],
        "type_ID"=>formatString($serv['TYPE']['ID'], 1),
        "color"=>formatString($serv['COLOR']['ID']),
        "count"=>1,
        "costs"=>round($serv['COST'] * $DISCOUNT_KOEF, 2)
    ], 0);
}

#endregion

$conn->close();

//echo json_encode($_GET);

if (!empty($_GET['TTN_IN']) && ($_GET['date_out'] ?? null) == null){
    echo "info?i=" . $_GET['ID'];
}else{
    echo "work";
}

?>
