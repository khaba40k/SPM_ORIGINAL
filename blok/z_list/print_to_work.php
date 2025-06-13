<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$hideForWorker = $_GET['hideForWorker'] != 0;
$ID = $_GET['ID'];
$TYPE_Z = $_GET['type'] ?? 'new';
$VARIANT = $_GET['variant'] ?? "def";

$conn = new SQLconn();

$result = $conn->SELECT("client_info", "*", @"WHERE ID = {$ID} LIMIT 1");

if (count($result) == 1){

    $cl_info = $result[0];

    $number = $cl_info['sholom_num'] ?? $cl_info['sold_number'];

    if (!empty($number)){
        $number = " №" . $number;
    }else{
        $number = "";
    }

    $txt = ($VARIANT == "def" ? "ШОЛОМУ" : "ЗАЯВЦІ") . $number;

    $tBody = new HTEL('tbody');

    //ШАПКА

    $tBody(new HTEL('tr',[
          new HTEL('td/Дата надходження'),
          new HTEL('td colspan=3/[0]', dateToNorm($cl_info['date_in'], false, true))
    ]));

    $tBody(new HTEL('tr', [
        new HTEL('td/Термін'),
        new HTEL('td colspan=3/[0]', dateToNorm($cl_info['date_max'], false, true))
    ]));

    if ($cl_info['date_out'] !== null)
        $tBody(new HTEL('tr', [
            new HTEL('td/Дата відправки'),
            new HTEL('td colspan=3/[0]', dateToNorm($cl_info['date_out'], false, true))
        ]));

    $phone = $hideForWorker ? "..." . substr($cl_info['phone'], -4) : $cl_info['phone'];

    $tBody(new HTEL('tr', [
        new HTEL('td/Номер телефону'),
        new HTEL('td colspan=3/[0]', $phone)
    ]));

    if (!$hideForWorker)
    $tBody(new HTEL('tr', [
        new HTEL('td/Прізвище, ім`я'),
        new HTEL('td colspan=3/[0]', $cl_info['client_name'])
    ]));

    $tBody(new HTEL('tr', [
        new HTEL('td/Реквізити'),
        new HTEL('td colspan=3/[0]', $cl_info['reqv'])
    ]));

    $ttn_info = $cl_info['TTN_IN'] !== null ? "Вх.: ". $cl_info['TTN_IN']: "";
    $ttn_info .= $cl_info['TTN_OUT'] !== null ? " Вих.: " . $cl_info['TTN_OUT'] : "";

    $tBody(new HTEL('tr', [
        new HTEL('td/ТТН'),
        new HTEL('td colspan=3/[0]', $ttn_info)
    ]));

    if ($hideForWorker)
    $tBody(new HTEL('tr', [
        new HTEL('td/Відповідальний'),
        new HTEL('td colspan=3/[0]', $cl_info['redaktor'] ?? "")
    ]));

    //КОМПЛЕКТУЮЧІ

    $print_status = $TYPE_Z == "archiv" ? "class=no-print" : "";

    $tBody(new HTEL(@"tr {$print_status}", [
        new HTEL('td &=text-align:center colspan=4/КОМПЛЕКТУЮЧІ')
    ]));

    if (!empty($cl_info['comm']) && $TYPE_Z != "archiv")
    $tBody(new HTEL('tr', [
        new HTEL('td &=text-align:center;font-weight:bold colspan=4/[0]', $cl_info['comm'])
    ]));

    $services = $conn("SELECT service_ids.NAME AS name, type_ids.name AS type, colors.color, ".
    "service_out.count, service_out.costs ".
    "FROM service_out JOIN service_ids ON service_ids.ID=service_out.service_ID ".
    "LEFT JOIN type_ids ON service_out.service_ID=type_ids.service_ID AND service_out.type_ID=type_ids.type_ID ".
    "LEFT JOIN colors ON service_out.color=colors.ID ".
    @"WHERE service_out.ID={$ID} ORDER BY `order`");

    $sum_out = 0;

    if (count($services) > 0){
          $name = "";
          $hide_style = $hideForWorker ? "display:none;" : "";

          foreach($services as $serv){
                 $name = $serv["name"] . ($serv['type'] != null ? @" ({$serv['type']})":"");

                 $tBody(new HTEL(@"tr {$print_status}", [
                     new HTEL('td/[0]', $name),
                     new HTEL('td &=text-align:center/[0]', $serv["count"]),
                     new HTEL('td &=border-left-style:hidden/[0]', $serv["color"] ?? ""),
                     new HTEL(@"td &={$hide_style}text-align:right;border-left-style:hidden/[0]", CostOut($serv["costs"]))
                 ]));

                 $sum_out += $serv["costs"];
          }

          if (!$hideForWorker)
          $tBody(new HTEL("tr &=font-weight:bold", [
              new HTEL('td colspan=2/ДО СПЛАТИ'),
              new HTEL("td colspan=2 &=text-align:right;border-left-style:hidden/[0]", CostOut($sum_out))
          ]));
    }

    if ($hideForWorker)
        $tBody(new HTEL('tr', [
            new HTEL('td/ПРАЦІВНИК'),
            new HTEL('td colspan=3/[0]', $cl_info['worker'] ?? "")
        ]));

    $table = new HTEL('table .=printInfo', [new HTEL(@"caption/ІНФОРМАЦІЯ ПО {$txt}"), $tBody]);

    $form = new HTEL('form !=infoForm onsubmit=return+false', $table);

    if ($TYPE_Z == "inwork") {
        $bottMenu = new HTEL('div .=no-print+doneApply');

        $div = new HTEL('div');

        $div(new HTEL('label for=worker/Працівник:'));
        $div(new HTEL('input !=worker ?=worker #=[0] [r]', $cl_info['worker'] ?? ""));

        $bottMenu($div);

        $div = new HTEL('div');

        $div(new HTEL('label for=ttn_done/Вихідна ТТН:'));
        $div(new HTEL('input !=ttn_done ?=ttn_done #=[0] [r]', $cl_info['TTN_OUT'] ?? ""));

        $bottMenu($div);

        $div = new HTEL('div');

        $div(new HTEL('label for=sum_fact/Сума (факт):'));
        $div(new HTEL('input !=sum_fact ?=sum_fact #=[0] [r]', $sum_out));

        $bottMenu($div);

        $bottMenu(new HTEL('button !=but_done *=submit/>ВИКОНАНО'));

        $form($bottMenu);
    }
}

$conn->close();

echo $form;
?>

<script>
    var ID = <?= json_encode($ID) ?>;
    var TYPE = <?= json_encode($VARIANT) ?>;
</script>

<script>

    $('#infoForm').submit(rec);

    function rec() {
        if (!confirm('Підтвердіть виконання заявки...')) return false;

          dataForm = $('#infoForm :input').serialize();

          $.ajax({
              url: 'blok/z_list/close_z.php',
              method: 'GET',
              dataType: 'html',
              data: dataForm + '&ID=' + ID + '&type='+ TYPE,
                  success: function (data) {
                      $('#workfield').html(data);
                  }
          });

        return false;
    }

</script>

<style>
td:first-child:not(:last-child){
    width: 40%;
}
</style>