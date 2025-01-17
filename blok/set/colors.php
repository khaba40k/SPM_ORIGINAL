<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$conn = new SQLconn();

#region Отримання списку кольорів
$_COLORS = array();

$result = $conn('SELECT * FROM colors');

$map = $conn('SELECT * FROM color_map');

foreach ($result as $row) {
    $_COLORS[$row['ID']] = new MyColor($row['ID'], $row['color'], $map, $row['css_name'], $row['is_def']);
}
#endregion

$container = new HTEL('form !=set_info onsubmit=return+false');

$next_id = 0;

$usingColor = array();

foreach($_COLORS as $i=>$c){
    if ($i >= $next_id) $next_id = $i + 1;

    $is_def = $c->Universal() ? 'checked':'';

    $usingColor['in'] = $conn('SELECT * FROM service_in WHERE color = '
            . $i . ' LIMIT 1');
    $usingColor['out'] = $conn('SELECT * FROM service_out WHERE color = '
            . $i . ' LIMIT 1');
    $is_del = (count($usingColor['in']) + count($usingColor['out'])) > 0 ? 'disabled':'';

    $one_color = new HTEL('div .=one_color', [
        $c->ID,
        $c->NAME,
        $c->CSS_ANALOG,
        new HTEL('input *=text !=[0] ?=name[[0]] #=[1] $=[1] pattern=[2] [r]', [2=>'^[^ ].+[^ ]$']),
        new HTEL('input *=color ?=color[[0]] #=[2] [r]'),
        new HTEL('label for=def_[0]/за замовч.', new HTEL('input *=checkbox !=def_[0] ?=def[] #=[0] [1]', [1=>$is_def])),
        new HTEL('label for=del_[0]/Х', new HTEL('input *=checkbox !=del_[0] ?=del[] #=[0] [1]', [1=>$is_del]))
    ]);

    $container($one_color);
}

$conn->close();

$one_color = new HTEL('div .=one_color', [
    $next_id,
    new HTEL('input *=text !=[0] ?=name[[0]] pattern=[1] $=додати+колір', [1=>'^[^ ].+[^ ]$']),
    new HTEL('input *=color ?=color[[0]]'),
    new HTEL('label for=def_[0]/за замовч.', new HTEL('input *=checkbox !=def_[0] ?=def[] #=[0]'))
]);

$container($one_color);

$container(new HTEL('button *=submit/ЗБЕРЕГТИ'));

echo $container;
echo new HTEL('div !=tempAns');

?>

<script>

    $('#set_info').submit(function () {
        var colorData = $(this).serialize();

        $.get('blok/set/save_colors.php', colorData, function (a) {
            SHOW_COLORS();
            alert(a);
        });

    });

</script>

<style>

.one_color{
    display: flex;
    justify-content: safe center;
    align-items: center;
    width: 100%;
}

    .one_color > *{
       margin: 5px 3px;
    }

    .one_color > input[type=text]{
         font-size: 90%;
    }

.one_color input[type=color]{
    height: 32px;
    width: 32px;
}

.one_color label{
    position: relative;
    height: 100%;
    padding-right: 16px;
    border: 3px dotted red;
}

.one_color label input{
    position: absolute;
    height: 100%;
    right: 0;
}

</style>

<!--
4 => 
    array (size=4)
      'ID' => string '4' (length=1)
      'color' => string 'Мультикам' (length=18)
      'css_name' => string '#CD853F' (length=7)
      'is_def' => string '0'
-->