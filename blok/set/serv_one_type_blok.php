<?php

//var_dump($_GET);
//echo "<br>\n----------------------------------------------------";
//exit;

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$conn = new SQLconn();

$SERV_ID = $_GET['serv_ID'];

$SERV_TYPE = $_GET['type_ID'];

$has_color = $_GET['has_color'];

$is_append = $_GET['append'];

#region Отримання списку кольорів
$_COLORS = array();

$result = $conn('SELECT * FROM colors');

$map = $conn('SELECT * FROM color_map');

foreach ($result as $row) {
    $_COLORS[$row['ID']] = new MyColor($row['ID'], $row['color'], $map, $row['css_name'], $row['is_def']);
}
#endregion

//Номер за порядком
$count_N = $_GET['counter'];
//Назва типу
$type_name = $_GET['type_name'];

$placeholder = $_GET['placeholder'];

//---------------------------------------------------------------
$usingTypes = array();

$canDelete = '';

$usingTypes['in'] = $conn('SELECT * FROM service_in WHERE service_ID = '
. $SERV_ID . ' AND type_ID = ' . $SERV_TYPE . ' LIMIT 1');

$usingTypes['out'] = $conn('SELECT * FROM service_out WHERE service_ID = '
           . $SERV_ID . ' AND type_ID = ' . $SERV_TYPE . ' LIMIT 1');

$conn->close();

$is_using = (count($usingTypes['in']) + count($usingTypes['out']) != 0);

if ($is_using || $type_name == '' || $SERV_TYPE == 1){
    $canDelete = 'hidden';
}

$rnd = rand(10000, 99999);

$del_el = new HTEL();

if ($is_append == 1){
    $del_el = new HTEL('button *=button .=del_append_type #=[0]/x', $SERV_TYPE);
}else{
    $del_el = new HTEL('label for=[0] .=del_type [1]/Видалити', [$rnd, $canDelete, $SERV_TYPE,
         new HTEL('input *=checkbox !=[0] ?=remowe_types[] #=[2]')]);
}

$input_name = new HTEL();

if ($is_append == 1){
    $input_name = new HTEL('input *=text ?=new_types[[0]] st=[0] #=[1] $=[2] pattern=[3] [r]', [$SERV_TYPE, $type_name, $placeholder, '^[^ ].+[^ ]$']);
} else{
    $input_name = new HTEL('input *=text ?=type_names[[0]] st=[0] #=[1] $=[2] pattern=[3] [r]', [$SERV_TYPE, $type_name, $placeholder, '^[^ ].+[^ ]$']);
}

$pid_type = new HTEL('div .=pid_type', [
    new HTEL('label .=type_counter/[0].', $count_N),
    $input_name,
    $del_el
]);

if ($has_color){
    $img_col = new HTEL('div .=img_col', [$SERV_ID, $SERV_TYPE]);

    foreach ($_COLORS as $c){
        $rnd = rand(100000, 999999);

        $is_apply = $c->AppleTo($SERV_ID,$SERV_TYPE) ? 'checked': '';

        $file_dir = '/img/kompl/';
        $file_name = $SERV_ID . '.' . $SERV_TYPE . '.' . $c->ID . '.png';

        $bckgColor = '#ffffff';

        if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $file_dir . $file_name)){
            $file_name = 'none.png';
            $bckgColor = 'none';
        }

        $file_png = $file_dir . $file_name;

        $img_col([
           new HTEL('label for=[0]/[1]', [$rnd, $c->NAME]),
           new HTEL('input *=checkbox !=[2] ?=type_colors[[1]][] #=[3] [4]', [2=>$rnd, $c->ID, $is_apply]),
           new HTEL('div .=img_file_cont', [
                 3=>new HTEL("label for=[0]-[1]-[2] &=background-image:url([3]);background-color:[4];",
                           [2=>$c->ID, $file_png, $bckgColor]),
                 new HTEL('input *=file !=[0]-[1]-[2] ?=[0]-[1]-[2] accept=[3] #',
                           [2=>$c->ID, 'image/png'])
           ])
        ]);
    }

    $pid_type($img_col);
}

echo $pid_type;

?>