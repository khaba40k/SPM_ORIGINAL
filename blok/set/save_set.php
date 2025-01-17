<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$conn = new SQLconn();

#region UPLOAD IMG
$save_img_path = '/img/kompl/';
//$save_img_path = '/test/';

var_dump($_FILES);

foreach ($_FILES as $name=>$png){
    if ($png['error'] == 0 && $png['type'] == 'image/png'){
        move_uploaded_file($png["tmp_name"], $_SERVER['DOCUMENT_ROOT'] . $save_img_path . str_replace('-', '.', $name) . '.png');
    }
}
#endregion

//var_dump($_POST ?? array('empty'));
//exit;

$service_ID = $_POST['service_id'];

$remowe_serv = $_POST['remove_service'] ?? 0;

if ($remowe_serv == 1){
    //��������� ������
    $conn('DELETE FROM service_ids WHERE ID=' . $service_ID);
    $conn(_RemoweTypesQuery($service_ID));
    $conn('DELETE FROM color_map WHERE service_ID=' . $service_ID);
    exit;
}

$remowe_types = $_POST['remowe_types'] ?? array();

//��������� ����
if (count($remowe_types) > 0) $conn(_RemoweTypesQuery($service_ID, $remowe_types));

//Запис категорій

$categories = $_POST['cat'];

$conn->DELETE("category_map", "service_ID = " . $service_ID);

foreach ($categories as $cat=>$v){
     if ($v == 1){
        $conn->INSERT("category_map", ["cat" => $cat, "service_ID" => $service_ID]);
     }
}

$atr = sumArray($_POST['atr']);

$typeNames = $_POST['type_names'] ?? array();
$nTypes = $_POST['new_types'] ?? array();
$has_color = $_POST['has_color'] ?? 0;
$colors = $_POST['type_colors'] ?? array();

$name_serv = $_POST['rename'] ?? '';

if (!empty($name_serv)){
    //��������������/��������� ������

    $conn('INSERT INTO service_ids (ID, NAME, `order`, color, atr) VALUES ('
    . $service_ID . ', "'
    . $name_serv . '", '
    . 30 . ', '
    . $has_color . ', '
    . $atr .
    ') ON DUPLICATE KEY UPDATE '.
    'NAME="' . $name_serv . '", '.
    'color=' . $has_color . 
    ', atr=' . $atr);
}else{
    $conn('UPDATE service_ids SET ' 
    . 'color=' . $has_color 
    . ', atr=' . $atr . ' WHERE ID=' . $service_ID);
}

foreach ($typeNames as $t=>$n){
    //��������� �������� ����
    
    if (!empty($n)){
            $tmp = $conn('SELECT name FROM type_ids WHERE service_ID=' . $service_ID . ' AND type_ID=' . $t);

    if (count($tmp) > 0 && $n != $tmp[0]['name']){
        $conn('UPDATE type_ids SET name = "' . $n . '" WHERE service_ID=' . $service_ID . ' AND type_ID=' . $t);
    }else if(count($tmp) == 0){
        $conn('INSERT INTO type_ids (service_ID, type_ID, name) VALUES ('
        . $service_ID . ','
        . $t . ', "'
        . $n .
        '")');
    }
    }  

}

//��������� ����
$max = $conn('SELECT max(type_ID) as lasttype FROM type_ids WHERE service_ID=' . $service_ID);
$nextType = $max[0]['lasttype'];
$newTypes = array();

foreach ($nTypes as $t=>$tname){

    $newTypes[$t] = ++$nextType;

    $conn('INSERT INTO type_ids (service_ID, type_ID, name) VALUES ('
            . $service_ID . ','
            . $nextType . ', "'
            . $tname .
            '")');
}

//��������� ���������� ������� �� ��

$conn('DELETE FROM color_map WHERE service_ID=' . $service_ID);

//��������� ������� �������� ����

foreach($typeNames as $t=>$n){

    if (isset($colors[$t])){
         foreach($colors[$t] as $c){
             $conn('INSERT INTO color_map (color_ID, service_ID, type_ID) VALUES ('
                 . $c . ','
                 . $service_ID . ','
                 . $t .
                 ')');
         }
    }
}

//��������� ������� ������� ����

foreach($newTypes as $from=>$to){

    if (isset($colors[$from])){
        foreach($colors[$from] as $c){
            $conn('INSERT INTO color_map (color_ID, service_ID, type_ID) VALUES ('
                . $c . ','
                . $service_ID . ', '
                . $to .
                ')');
        }
    }
}

$conn->close();

function _RemoweTypesQuery($servID, array $types = array()):string{
    $out = 'DELETE FROM type_ids WHERE service_ID=' . $servID;

    if (count($types) > 0){

        $tmp = '';

        foreach ($types as $t){
            $tmp .= $t . ' ';
        }

        $out .= ' AND type_ID IN (' . str_replace(' ', ',', trim($tmp)) . ')';
    }

    return $out;
}

?>