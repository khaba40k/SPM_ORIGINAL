<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$hideForWorker = $_GET['hideForWorker'] != 0;
$type_Z = isset($_GET['type']) ? $_GET['type'] : 'new';
$worker = '';

if (!isset($_GET['variant'])){
    $_GET['variant'] = 'def';
}

$cell_num = $_GET['variant'] == 'def' ? 'sholom_num' : 'sold_number';

$conn = new SQLconn();

//Отримання списку кольорів

$_COLORS = array();

$result = $conn('SELECT * FROM colors');

$map = $conn('SELECT * FROM color_map');

foreach ($result as $row) {
    $_COLORS[$row['ID']] = new MyColor($row['ID'], $row['color'], $map, $row['css_name'], $row['is_def']);
}

//Отримання масиву послуг

$_service_name = array();
$arr_types = array();

$result = $conn('SELECT * FROM `service_ids` ORDER BY `order` ASC');

foreach ($result as $row) {
    $_service_name[$row['ID']] = $row['NAME'];

    if($row['ID'] != 19){
        $arr_types[$row['ID']][1] = '';
    }

    $arr_types[19][$row['ID']] = " (" . $row['NAME'] . ")";
}

//ВИБІРКА ІСНУЮЧИХ ТИПІВ

$result = $conn('SELECT * FROM type_ids');

foreach ($result as $row) {
    $arr_types[$row["service_ID"]][$row["type_ID"]] = " (" . $row["name"] . ")";
}

//Введення даних отримувача

$result = $conn('SELECT * FROM `client_info` where `ID` = '. $_GET['ID'] . ' LIMIT 1');

$serv_for_sol = '';

$table = new HTEL('table .=printInfo');

$ttn = '';

foreach ($result as $row) {

    $ID_NUM = $row[$cell_num] != 0 ? ' №'.$row[$cell_num]:'';

    $table(new HTEL('caption/ІНФОРМАЦІЯ ПО [0][1]', [ $_GET['variant'] == 'def' ? 'ШОЛОМУ':'ЗАМОВЛЕННЮ', $ID_NUM]));

    $tbody = new HTEL('tbody');

    $tbody(setRow('Дата надходження', dateToNorm($row['date_in'], false, true), 2));
    $tbody(setRow('Термін', dateToNorm($row['date_max']), 2));
    if ($type_Z == 'archiv') $tbody(setRow('Дата відправки', dateToNorm($row['date_out'], false, true), 2));
    if (!$hideForWorker) $tbody(setRow('Номер телефону', $row['phone'], 2)); else
        $tbody(setRow('Номер телефону', "..." . substr($row['phone'], -4), 2));//Показати останні 4 цифри
    if (!$hideForWorker) $tbody(setRow('Прізвище, ім`я', $row['client_name'], 2));
    if (!$hideForWorker) $tbody(setRow('Реквізити', $row['reqv'], 2));

    $ttn = !empty($row['TTN_IN']) ? 'Вхідна: ' . $row['TTN_IN'] . '  ' : '';
    if (!empty($row['TTN_OUT'])) $ttn .= 'Вихідна: ' . $row['TTN_OUT'];
    if( $ttn != '') $tbody(setRow('ТТН', $ttn, 2));

    $ttn = !is_null($row['TTN_OUT']) ? $row['TTN_OUT'] : '';
    if ($row['comm'] != null) $tbody(setRow('Коментар', $row['comm'], 2));
    if ($row['discount'] != null)
        $tbody(setRow('Врахована знижка', $row['discount'].'%', 2));

    $worker = $row['worker'];
    if ($hideForWorker) $tbody(setRow('Відповідальний', $row['redaktor'], 2));

    $worker = !is_null($row['worker']) ? $row['worker'] : '';

    $table($tbody);
}


//Введення комплектуючих

$query = 'SELECT service_out.service_ID,service_out.type_ID,service_out.count,service_out.color,service_out.costs FROM service_out JOIN service_ids ON service_ids.ID=service_out.service_ID WHERE service_out.ID="' . $_GET['ID'] . '" ORDER BY `order` ASC';

$result = $conn($query);

$sum = 0;

if (count($result) > 0) {

    $tbody(setRow('КОМПЛЕКТУЮЧІ', '', 1, $type_Z != 'archiv'));

    foreach ($result as $row) {

        $sum += CostOut($row['costs']);

        $col = isset($_COLORS[$row['color']]) ? $_COLORS[$row['color']]->NAME : '';

        if ($col != '')
            $col = " | " . $col;

        $price = $hideForWorker ? '' : " | " . CostOut($row['costs']) . " грн.";

        $tbody(
            setRow(
                $_service_name[$row['service_ID']] . $arr_types[$row['service_ID']][$row['type_ID']],
                $row['count'] . $col . $price, 1, $type_Z != 'archiv'
            )
        );

        $col = null;
    }

    if (!$hideForWorker) $tbody(setRow('ДО СПЛАТИ', CostOut($sum)." грн.", 2));
}
else{
    $sum = null;
}

$conn->close();

$form = new HTEL('form !=infoForm onsubmit=return+rec([0],`[1]`);',[$_GET['ID'], $_GET['variant']]);

if ($hideForWorker){
    $table(setRow('Виконавець', $worker.' '));
}

$form($table);

if (!$hideForWorker && $type_Z == 'inwork' && $sum !== null){//Підтвердження виконання

    $div = new HTEL('div .=no-print+doneApply');

    $div(new HTEL('div' ,[
    new HTEL('label for=worker/Працівник:'),
    new HTEL('input !=worker *=text ?=worker #=[0] [r]', $worker)
    ]));

    $div(new HTEL('div' ,[
    new HTEL('label for=ttn_done/Вихідна ТТН:'),
    new HTEL('input !=ttn_done *=tel ?=ttn_done #=[0] [r]',$ttn)
    ]));

    $div(new HTEL('div' ,[
    new HTEL('label for=sum_fact/Сума (факт):'),
    new HTEL('input !=sum_fact *=number step=0.01 min=0 ?=sum_fact #=[0] [r]', CostOut($sum))
    ]));

    $div(new HTEL('button !=but_done *=submit/>Виконано'));
    $form($div);
}
else if($worker != '' && !$hideForWorker && $type_Z != 'new'){
    $div = new HTEL('div .=no-print+doneApply');
    $div(new HTEL('label for=worker/Працівник:'));
    $div(new HTEL('input !=worker *=text ?=worker #=[0] [ro]', $worker));
    $form($div);
}

echo $form;

function setRow($name, $val = '', $csp = 1, $toPrint = true):HTEL{
    $out = new HTEL('tr .=[0]', $toPrint ? '':'no-print');

    if (!empty($val)) {
        $out(new HTEL('td/[0]', $name));
        $out(new HTEL('td colspan=[0] &=width:50%;/[1]', [$csp, $val]));
        if ($csp == 1)
            $out(new HTEL('td &=width:10%;'));
    } else {
        $out(new HTEL('td colspan=3 &=text-align:center;/[0]', $name));
    }

    return $out;
}

?>

<script>

    function rec($id = 1, $type = 'def') {
        if (!confirm('Підтвердіть виконання заявки...')) return false;

          dataForm = $('#infoForm :input').serialize();

          $.ajax({
              url: 'blok/z_list/close_z.php',
              method: 'GET',
              dataType: 'html',
              data: dataForm + '&ID=' + $id + '&type='+ $type,
                  success: function (data) {
                      $('#workfield').html(data);
                  }
          });

        return false;
    }

</script>