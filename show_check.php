<?php

$serviceNames = $_GET["SERVICE"] ?? [];
$type_names = $_GET["TYPE_NAME"] ?? [];
$selected_types = $_GET["type"] ?? [];
$costs = $_GET["cost"] ?? [];
$faktIDS = $_GET["service_ID"] ?? [];
//color ?????

$summ_out = 0;

foreach($faktIDS as $id=>$apply){
     if ($apply == 1){
        echo @"<span>{$serviceNames[$id]}</span>|<span>{$type_names[$id][($selected_types[$id] ?? 1)]}</span>|<span style='text-align:right;'>{$costs[$id]}</span>";
        echo "<hr><span></span><hr><span></span><hr>";
        $summ_out += $costs[$id];
     }
}

if ($summ_out > 0){
    echo @"<span></span><span></span><span></span>|<span style='text-align:right; color: black; font-weight: bold;'>{$summ_out}</span>";
}
?>