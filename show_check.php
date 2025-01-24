<?php

$serviceNames = $_GET["SERVICE"] ?? [];
$type_names = $_GET["TYPE_NAME"] ?? [];
$selected_types = $_GET["type"] ?? [];
$costs = $_GET["cost"] ?? [];
$faktIDS = $_GET["service_ID"] ?? [];
//color ?????

$summ_out = 0;
$service_type_name = "";
$counter = 0;

if (isset($costs[21])){
    $faktIDS[21] = 1;
    $serviceNames[21] = "Терміново";
}

echo "<span style='grid-column: 1 / 4;text-align: center;padding-bottom: 20px;'>ПОПЕРЕДНІЙ ПЕРЕГЛЯД</span>";
echo "<span></span><hr><hr>";

foreach($faktIDS as $id=>$apply){
     if ($apply > 0){
        $service_type_name = $apply == 1 ? $type_names[$id][($selected_types[$id] ?? 1)]:"установка";
        $service_type_name = $serviceNames[$id] . ($service_type_name != "" ? " (" . $service_type_name . ")" : "");
        $counter++;
        echo @"<span>{$counter}.</span><span>{$service_type_name}</span><span style='text-align:right;'>{$costs[$id]}</span>";
        echo "<span></span><hr><hr>";
        $summ_out += $costs[$id];
     }
}

if ($summ_out > 0){
    echo @"<span></span><span></span><span style='text-align:right; color: black; font-weight: bold;'>{$summ_out}</span>";
}
?>