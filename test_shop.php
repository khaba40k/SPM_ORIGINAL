<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

//1 замовлення (абонент)
//2 замовлення (вручну)
//4 покупка (абонент)
//8 покупка (вручну)
//16 витрати

#region ВИТЯГНЕННЯ ВСІХ ТОВАРІВ ПО КАТЕГОРІЯМИ/ТОВАРАМ/ТИПАМ/КОЛЬОРАМ (4 x ID)

$atr = 4;

$conn = new SQLconn();

$all_cat = $conn->SELECT('categories', '*', 'ORDER BY ID');
$all_serv = $conn->SELECT('service_ids', 'ID, NAME, color', 'ORDER BY ID');
$all_types = $conn->SELECT('type_ids', '*', 'ORDER BY service_ID, type_ID');

$all_serv = $conn(@"SELECT IFNULL(category_map.cat, 0) category_ID, IFNULL(categories.category, 'Інше') category_NAME, 
service_ids.ID service_ID, service_ids.NAME service_NAME, ifnull(type_ids.type_ID, 1) type_ID, 
type_ids.name type_NAME, color_map.color_ID, colors.color color_NAME
FROM category_map JOIN categories ON categories.ID=category_map.cat
RIGHT JOIN service_ids ON service_ids.ID=category_map.service_ID
LEFT JOIN type_ids ON type_ids.service_ID=category_map.service_ID
LEFT JOIN color_map ON color_map.service_ID=category_map.service_ID AND color_map.type_ID=ifnull(type_ids.type_ID, 1)
LEFT JOIN colors ON colors.ID=color_map.color_ID
WHERE service_ids.atr > -1 and (service_ids.atr & {$atr}) > 0
ORDER BY category_map.cat, service_ids.NAME, type_ids.type_ID, color_map.color_ID");

$out_arr = []; $tmp_key = 0;

foreach ($all_serv as $serv){
        $tmp_key = $serv['category_ID'];
        unset($serv['category_ID']);
        $out_arr[$tmp_key][] = $serv;
}

foreach ($out_arr as $k=>$out){
      if (!empty($out)){
           $cat_name = $out[0]['category_NAME'];
           echo "<a href='#'>" . $cat_name . "</a>";
           foreach ($out as $line){
                 echo "<nav>" . $line['service_NAME'] . " " . $line['type_NAME'] . " " . $line['color_NAME'] .  "</nav>";
           }
      }
}

$conn->close();

#endregion

?>

<script>

</script>

<style>

</style>