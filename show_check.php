<?php
$SERVICES = $_GET["SERVICES"];

$COLORS = $_GET["COLOR"];

if (!is_array($SERVICES) || count($SERVICES) == 0){
    echo "<span style='grid-column: 1 / 4;text-align: center;'>НІЧОГО НЕ ОБРАНО</span>";
    exit;
}

$counter = 1;
$sum = 0;

echo "<span style='grid-column: 1 / 4;text-align: center;padding-bottom: 20px;'>ПОПЕРЕДНІЙ ПЕРЕГЛЯД</span>";
echo "<span></span><hr><hr>";

$color = "";

foreach($SERVICES as $serv){

    $color = key($COLORS[$serv["COLOR"]["ID"]]);

    echo 
    @"<span style='color:{$color}'>" . $counter++ . ".</span>" .
    @"<span style='color:{$color}'>" . $serv["NAME"] . (!empty($serv["TYPE"]["NAME"]) ? " (" . $serv["TYPE"]["NAME"] . ")" : "") . "</span>" . 
    @"<span style='text-align: right;color:{$color}'>" . $serv["COST"] . "</span>";
    echo "<span></span><hr><hr>";
    $sum += $serv["COST"];
}

echo @"<span></span><span></span><span style='color: white; text-align: right; font-weight: bold; background-color: gray'>{$sum}</span>";

?>
