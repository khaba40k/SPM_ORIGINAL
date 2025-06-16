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
type_ids.name type_NAME, color_map.color_ID, colors.color color_NAME, IFNULL(price_list.cost, 0) price
FROM category_map JOIN categories ON categories.ID=category_map.cat
RIGHT JOIN service_ids ON service_ids.ID=category_map.service_ID
LEFT JOIN type_ids ON type_ids.service_ID=category_map.service_ID
LEFT JOIN color_map ON color_map.service_ID=category_map.service_ID AND color_map.type_ID=ifnull(type_ids.type_ID, 1)
LEFT JOIN colors ON colors.ID=color_map.color_ID
LEFT JOIN price_list ON price_list.service_id=category_map.service_ID AND price_list.type_id=ifnull(type_ids.type_ID, 1)
WHERE service_ids.atr > -1 and (service_ids.atr & {$atr}) > 0 AND price_list.cost > 0
ORDER BY category_map.cat, service_ids.NAME, type_ids.type_ID, color_map.color_ID");

$out_arr = []; $tmp_key = 0;

foreach ($all_serv as $serv){
        $tmp_key = $serv['category_ID'];
        unset($serv['category_ID']);
        $out_arr[$tmp_key][] = $serv;
}

$html = ""; $zmist = [];

foreach ($out_arr as $k=>$out){
      if (!empty($out)){
           $cat_name = $out[0]['category_NAME'] . " (" . count($out) . ")";
           $zmist[$k] = $cat_name;

           $html .= @"<section class='product-slider' id='category_{$k}'><h2>" . $cat_name . "</h2><div class='product-row'>";
           foreach ($out as $line){
                $color = $line['color_ID'] ?? "00"; 
                $html .= @"<div class='product'><img class='service_logo' src='/img/kompl/{$line['service_ID']}.{$line['type_ID']}.{$color}.png' onerror=this.src='/img/logo.png'></img><br>" . 
                $line['service_NAME'] . "<br>" . $line['type_NAME'] . "<br>" . $line['color_NAME'] . "<br>" . $line['price'] . " грн." .
                @"<br><button class='add_to_cart_but' id='button_{$line['service_ID']}.{$line['type_ID']}.{$color}'>Додати до кошика</button>" . "</div>";
           }
           $html .= "</div></section>";
      }
}

echo '<nav class="toc"><ul>';

foreach ($zmist as $k=>$z){
      echo "<li><a href='#category_{$k}'>{$z}</a></li>";
}

echo "</ul></nav>";

echo $html;

$conn->close();

#endregion

?>

<script>

$('.add_to_cart_but').click((but)=>{
    ADD_TO_CART(but.currentTarget.id);
});

function ADD_TO_CART(id){

    let spl = id.split("_")[1].split('.');

    let data = "service_ID=" + spl[0]  +",type_ID=" + spl[1] + ",color=" + spl[2];

    $.post('add_to_cart.php', data, (success) => {
             alert(success);
    });
}

</script>

<style>

.service_logo{
    max-height: 100px;
}

.product-slider {
  padding: 2rem;
}

.product-slider h2 {
  margin-bottom: 1rem;
  font-size: 1.5rem;
  color: #3b4d1e;
}

.product-row {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding-bottom: 1rem;

  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;

  scroll-snap-type: x mandatory;
  scroll-padding: 1rem;
}

.product-row::-webkit-scrollbar {
  height: 8px;
}

.product-row::-webkit-scrollbar-thumb {
  background-color: #556b2f;
  border-radius: 4px;
}

.product {
  flex: 0 0 auto;
  min-width: 160px;
  background: #e0e0d1;
  border-radius: 6px;
  padding: 1rem;
  text-align: center;
  white-space: nowrap;
  border: 1px solid #ccc;
  scroll-snap-align: start;
  font-weight: bold;
}

html {
  scroll-behavior: smooth;
}

.toc {
  padding: 1rem;
  margin-bottom: 1rem;
  border-left: 4px solid #556b2f;
  font-family: Arial, sans-serif;
}

.toc strong {
  display: block;
  margin-bottom: 0.5rem;
  color: #3b4d1e;
}

.toc ul {
  list-style: none;
  padding-left: 0;
  margin: 0;
}

.toc li {
  margin: 0.3rem 0;
}

.toc a {
  color: #3b4d1e;
  text-decoration: none;
  font-weight: bold;
}

.toc a:hover {
  text-decoration: underline;
}

</style>