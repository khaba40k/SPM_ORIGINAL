<?php
//var_dump($_GET);
//exit;

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$conn = new SQLconn();

$names = $_GET['name'];
$color_code = $_GET['color'];
$defs = $_GET['def'] ?? array();
$dels = $_GET['del'] ?? array();

foreach ($dels as $deleteID){
    $conn('DELETE FROM colors WHERE ID=' . $deleteID);

    $names[$deleteID] = '';
}

foreach ($names as $id=>$n){
     if (!empty($n)){
         $def = in_array($id, $defs) ? 1:0;

         $conn('INSERT INTO colors (ID, color, css_name, is_def) VALUES ('
             . $id . ', "'
             . $n . '", "'
             . $color_code[$id] . '", '
             . $def
         . ') ON DUPLICATE KEY UPDATE color="' . $n . '", css_name="' . $color_code[$id] . '", is_def=' . $def);
     }
}

$conn->close();

echo 'Кольори збережено вдало!';
?>