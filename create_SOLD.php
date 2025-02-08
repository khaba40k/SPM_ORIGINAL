<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$container = new HTEL('form !=container onSubmit=return+false');

$conn = new SQLconn();

//ДАНІ ЗАЯВКИ

session_start();

$user = ($_SESSION['logged'] ?? null) !== null ? true : false;

#region ОБРОБКА ВХІДНИХ ДАНИХ

$INPUT = new ZDATA2($_GET['ID'] ?? 0, ZType::SOLD);

#endregion

#region ПРОРИСОВКА ШАПКИ ФОРМИ
$dateNow = $INPUT->GET('date_in', date("Y-m-d\TH:i"));
$termin = strftime("%Y-%m-%d", strtotime($dateNow . " +3 day"));
$termin = $INPUT->GET('date_max', $termin);

$fsHead = new HTEL('fieldset !=headFS', new HTEL('legend/ДАНІ ЗАМОВЛЕННЯ:'));

if ($INPUT->LOADED && $INPUT->GET('service_number') != 0)
    $fsHead(new HTEL('div', [
        $INPUT->NUMBER_LABLE,
        "number",
        "Номер заявки",
        $INPUT->GET('service_number'),
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] ?=[0] #=[3] min=1 [r]')
    ]));

if ($INPUT->TYPE == ZType::DEFF || $INPUT->TYPE == ZType::SOLD)
    $fsHead(new HTEL('div', [
        "date_in",
        "datetime-local",
        "Створено",
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] ?=[0] min=[2] #=[2] [r]', [2 => $dateNow])
    ]));

if ($INPUT->TYPE == ZType::DEFF || $INPUT->TYPE == ZType::SOLD)
    $fsHead(new HTEL('div', [
        "date_max",
        "date",
        "Термін",
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] ?=[0] #=[2] [r]', [2 => $termin])
    ]));

if ($INPUT->CLOSED)
    $fsHead(new HTEL('div', [
        "date_out",
        "datetime-local",
        "Відправлено",
        $INPUT->GET('date_out'),
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] ?=[0] #=[3] [r]')
    ]));

$fsHead(new HTEL('div', [
    "phone",
    "tel",
    "Номер телефону",
    $INPUT->GET('phone'),
    new HTEL('label for=[0]/[2]'),
    new HTEL('input *=[1] !=[0] ?=[0] #=[3] [r]')
]));

$fsHead(new HTEL('div', [
    "client_name",
    "text",
    "Прізвище, ім'я",
    $INPUT->GET('client_name'),
    new HTEL('label for=[0]/[2]'),
    new HTEL('input *=[1] !=[0] ?=[0] #=[3] [r]')
]));

$fsHead(new HTEL('div', [
    "reqv",
    "text",
    "Реквізити зворотньої доставки",
    $INPUT->GET('reqv'),
    new HTEL('label for=[0]/[2]'),
    new HTEL('div', [
        new HTEL('input *=[1] !=[0] ?=[0] #=[3] $=[2] list=list_town [r]', [2=>"населений пункт, номер відділення"]),
        new HTEL('select !=NP_town'),
        new HTEL('datalist !=list_town'),
        new HTEL('select !=NP_vidd')
    ])
]));

if ($INPUT->TYPE == ZType::DEFF || $INPUT->TYPE == ZType::SOLD)
    $fsHead(new HTEL('div', [
        "TTN_IN",
        "text",
        "ТТН (вхідна)",
        $INPUT->GET('TTN_IN'),
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] ?=[0] #=[3]')
    ]));

if ($INPUT->CLOSED)
    $fsHead(new HTEL('div', [
        "TTN_OUT",
        "text",
        "ТТН (вихідна)",
        $INPUT->GET('TTN_OUT'),
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] ?=[0] #=[3]')
    ]));

if ($INPUT->LOADED && $INPUT->GET('discount') != ""){
    $fsHead(new HTEL('div', [
        "discount",
        "text",
        "Дисконт",
        $INPUT->GET('discount'),
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] ?=[0] #=[3] [ro]')
    ]));
}
else{
    $fsHead(new HTEL('div', [
        "discount",
        "text",
        "Дисконт",
        $INPUT->GET('discount'),
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] ?=[0] min=1 max=5 #=[3]')
    ]));
}

if ($INPUT->TYPE == ZType::DEFF || $INPUT->TYPE == ZType::SOLD) {
    $messOpt = [];

    foreach ($INPUT->massangers as $ind => $m) {
        $messOpt[] = new HTEL('option [1]/[0]', [$m, $ind === $INPUT->GET('mess', -1) ? 'selected' : '']);
    }

    $fsHead(new HTEL('div', [
        "messendger",
        "Зв'язок",
        new HTEL('label for=[0]/[1]'),
        new HTEL('select !=[0] ?=[0]', $messOpt)
    ]));
}

$fsHead(new HTEL('div', [
    "comm",
    "Коментар",
    $INPUT->GET('comm'),
    new HTEL('label for=[0]/[1]'),
    new HTEL('textarea !=[0] ?=[0]/[2]')
]));

$result = $conn->SELECT('price_list', 'cost', 'WHERE service_id = 21 AND type_id = 1');

$terminovoPrice = $result[0]['cost'];
$terminovoCheck = '';

if (!empty($INPUT->GET_SERVICE(21))) {
    $temp = $INPUT->GET_SERVICE(21);
    $terminovoPrice = $temp['costs'];
    $terminovoCheck = 'checked';
}

$fsHead(new HTEL('div', [
    "terminovo",
    "checkbox",
    "Терміново",
    $terminovoPrice,
    $terminovoCheck,
    new HTEL('label for=[0]/[2] (+[3] грн.)'),
    new HTEL('input *=[1] !=[0] #=[3] [4]')
]));

if (!$INPUT->CLOSED){

    $clb = ($INPUT->GET('callback') == 1 || !$user) ? "" : "checked";

    $fsHead(new HTEL('div', [
        "callback",
        "checkbox",
        "Не телефонуйте мені",
        $clb,
        new HTEL('label for=[0]/[2]'),
        new HTEL('input *=[1] !=[0] [3]')
    ]));
}

$container($fsHead);

#endregion

//1 замовлення (абонент)
//2 замовлення (вручну)
//4 покупка (абонент)
//8 покупка (вручну)
//16 витрати

$atr = ZType::SOLD;

#region ПРОРИСОВКА ТІЛА ФОРМИ

$fsBody = new HTEL('fieldset !=bodyFS', new HTEL('legend/[0]', "КОМПЛЕКТУЮЧІ / ПОСЛУГИ:"));

//ЗЧИТУВАННЯ ПОСЛУГ/ТОВАРІВ/ТИПІВ/КОЛЬОРІВ в массив $SERVICES

$result = $conn->SELECT('service_ids', 'ID, NAME, color, atr', 'ORDER BY `order`');

$SERVICES = array();

$COLORS = array();

$color_read = $conn->SELECT('colors', 'ID, color, css_name', 'ORDER BY ID');

foreach ($color_read as $c) {
    $COLORS[$c['ID']] = ["name"=> $c['color'], "value"=>  $c['ID']];
}

$counter = 0;

#region ЗАПОВНЕННЯ РОБОЧОГО МАСИВУ $SERVICES
foreach ($result as $row) {
    if (inclAttr($atr, $row['atr'])) {
        $SERVICES[$counter] = ["ID" => $row['ID'], "NAME" => $row['NAME'], "HAS_COLOR" => $row['color'], "HAS_TYPES" => true];

        $types_read = $conn->SELECT('type_ids', 'type_ID, name', 'WHERE service_ID = ' . $row['ID'] . ' ORDER BY type_ID');

        $color_read = $conn->SELECT('color_map', 'color_ID, type_ID', 'WHERE service_ID = ' . $row['ID'] . ' ORDER BY type_ID, color_ID');

        $cost_read = $conn->SELECT('price_list', 'type_id, cost', 'WHERE service_id = ' . $row['ID']);

        if (count($types_read) < 1) {
            $types_read[0]['type_ID'] = 1;
            $types_read[0]['name'] = '';
            $SERVICES[$counter]['HAS_TYPES'] = false;
        }

        $types = array();

        foreach ($types_read as $t) {
            $colors = array();

            foreach ($color_read as $c) {
                if ($c['type_ID'] == $t['type_ID']) {
                    $colors[] = $c['color_ID'];
                }
            }

            $cost = 0;

            foreach ($cost_read as $cst) {
                if ($cst['type_id'] == $t['type_ID']) {
                    $cost = $cst['cost'];
                }
            }

            $types[$t['type_ID']] = ["TYPE" => $t['name'], "COST" => $cost, "COLORS" => $colors];
        }

        $SERVICES[$counter]['TYPES'] = $types;

        $counter++;

    }

}
#endregion

$workFIELD = new HTEL('th !=wField colspan=6');
$selectWorkField = new HTEL('select !=listServ', new HTEL('option #=-1 [s] [d]/послуга...'));

foreach($SERVICES as $s){
       $selectWorkField(new HTEL("option #=[0]/[1]", [$s['ID'], $s['NAME']]));
}

$workFIELD($selectWorkField);

$workFIELD(new HTEL('select !=listType'));

$workFIELD(new HTEL('select !=listColor '));

$workFIELD(new HTEL('div', [
       new HTEL('label for=inpCount/К-ть'),
       new HTEL('input !=inpCount *=number min=1 #=1')
]));

$workFIELD(new HTEL('div', [
    new HTEL('label for=inpCost/Ціна'),
    new HTEL('input !=inpCost *=number step=0.01 min=0 #=0')
]));

$workFIELD(new HTEL('button *=button !=btn_add/+'));

$workTABLE = new HTEL('table !=work_TABLE');

$workTABLE(new HTEL('tr', $workFIELD));

$workTABLE(new HTEL('tr', [
    new HTEL('th/№'),
    new HTEL('th/НАЗВА'),
    new HTEL('th/КОЛІР'),
    new HTEL('th/к-ть'),
    new HTEL('th colspan=2/СУМА')
]));

$fsBody($workTABLE);

$container($fsBody);

#endregion

#region ПРОРИСОВКА НИЗА

session_start();

$bottom = new HTEL("div !=bott");

if (!isset($_SESSION['logged'])) {
    $_SESSION['logged'] = null;
} else {
    $curID = $conn->SELECT("users", "ID", @"WHERE `login` = '{$_SESSION['logged']}'")[0]['ID'];

    $redaktors = $conn->SELECT("users", "login", @"WHERE ID >= {$curID} ORDER BY ID");

    $options = [];

    $curSelect = "";

    $redaktor = !empty($INPUT->GET("redaktor")) ? $INPUT->GET("redaktor") : $_SESSION['logged'];

    foreach ($redaktors as $r) {
        $curSelect = $r["login"] == $redaktor ? "selected" : "";

        $options[] = new HTEL(@"option #=[0] {$curSelect}/[0]", $r['login']);
    }

    $selectAcc = new HTEL("select !=redaktor ?=redaktor", $options);

    $access = new HTEL("div !=acc", [
        new HTEL("label for=redaktor/ЗАКРІПИТИ:"),
        $selectAcc
    ]);

    $bottom($access);

    $bottom(new HTEL("input !=worker ?=worker $=працівник #=[0]", $INPUT->GET("worker")));
}

$butt = new HTEL("button !=submBut *=submit/ЗБЕРЕГТИ");

$bottom($butt);

$container($bottom);

#endregion

$conn->close();

echo $container;

?>

<script>

    var SERVICES = <?= json_encode($SERVICES) ?>

    //console.log(SERVICES);

    var COLORS = <?= json_encode($COLORS) ?>

    var INPUTSERVICES = <?= json_encode($INPUT->GET_SERVICES()) ?>

    var DISCOUNT_IGNORE = <?= json_encode($INPUT->DISCOUNT_IGNORE) ?>

        //NOVA POSHTA

    var NP_INP = document.getElementById('reqv');
    var NP_DList = document.getElementById('list_town');
    var NP_CSelect = document.getElementById('NP_town');
    var NP_WSelect = document.getElementById('NP_vidd');
    var Loaded = <?= json_encode($INPUT->LOADED) ?>;
    var LoadedID = <?= $INPUT->ID ?>;

    var NP = new NovaPay(
        '4e4de3b4d068a37e30e0da387a049415',
        NP_INP,
        NP_DList,
        NP_CSelect,
        NP_WSelect,
        Loaded
    );

</script>

<script>

    var DISCOUNT_KOEF = 1;

    var AUTOSET_TIME = false;

    var CURRENT_ROW_ID = -1;

    var CURRENT_SERVICE_ID = -1;

    var CURR_SERV_ARR_INDEX = -1;

    //INPUT_SET();

    $('#listServ').change((slct) => {
        CURRENT_SERVICE_ID = slct.currentTarget.value;

        $.each(SERVICES, (ind, SRV) => { if (SRV.ID == CURRENT_SERVICE_ID) { CURR_SERV_ARR_INDEX = ind; return; } });

        $('#listType').html('');

        if (SERVICES[CURR_SERV_ARR_INDEX]['HAS_TYPES']) {
            $.each(SERVICES[CURR_SERV_ARR_INDEX]['TYPES'], (i, t) => {
                $('#listType').append("<option value='" + i + "'>"+t.TYPE+"</option>");
            });
        }
    });

    $('#listType').change((slct) => {
        SET_COLORS(slct.currentTarget.value);
    });

    function SET_COLORS(type_id = 1) {
        $('#listColor').html('');

        if (SERVICES[CURR_SERV_ARR_INDEX]['HAS_COLOR']) {
            let colorName = function (cid) {
                for (let i = 0; i < COLORS.length; i++) {
                    if (COLORS[i].value == cid) return COLORS[i].name;
                }
            };

            $.each(SERVICES[CURR_SERV_ARR_INDEX]['TYPES'][type_id]['COLORS'], (i, cID) => {
                $('#listColor').append("<option value='" + i + "'>"+colorName(cID)+"</option>");
            });
        }
    }

    function INPUT_SET() {
        if (INPUTSERVICES.length == 0) {
            ADD_ROW();
            return;
        }

        AUTOSET_TIME = true;

        $.each(INPUTSERVICES, (ind, serv) => {
            ADD_ROW(ind, serv.type_ID, serv.color, serv.count, serv.costs);
        });

        ADD_ROW();

        AUTOSET_TIME = false;
    }

    function ADD_ROW(id = null, type = null, color = null, count = 1, cost = 0) {
        CURRENT_ROW_ID++;

        let ROW = "<tr class='oneservice' id='row["+CURRENT_ROW_ID+"]'><td class='row_index' align='center'></td><td><select class='service_ID'><option value=''>...</option>";
        let selected = function (_id) {
            return (id === null || _id !== id) ? "":  " selected=''";
        };

        $.each(SERVICES, (ind, serv) => {
            ROW += "<option value='" + serv.ID + "'" + selected(serv.ID) +">" + serv.NAME + "</option>";
        });

        ROW += "</select></td>";

        if (type !== null) {
            ROW += "<td>"+type+"</td>";
        } else {
            ROW += "<td></td>";
        }

        if (color !== null) {
            ROW += "<td>"+color+"</td>";
        } else {
            ROW += "<td></td>";
        }

        ROW += "<td><input type='number' min='1' step='1' style='text-align:center' value='" + count + "' /></td>";

        ROW += "<td><input type='number' min='0' step='0.01' style='text-align:right' value='" + cost + "' /></td>";

        ROW += "<td class='del_row' id='del["+CURRENT_ROW_ID+"]'>X</td></tr>";

        $('#work_TABLE').append(HTEL.FORMAT(ROW));

        REINDEX();
    }

    function ROW_COUNT() {
        let counter = 0;

        $('.oneservice').each(() => {
            counter++;
        });

        return counter;
    }

    function REINDEX() {
        $('.row_index').each((ind, td) => {
            td.innerText = (ind + 1) + ".";
        });
    }

    $('.del_row').on('click', (row) => {
        if (row.currentTarget.id.match(/\d+/) == CURRENT_ROW_ID) return;

        if (ROW_COUNT() > 0) {
            row.currentTarget.parentElement.remove();
            REINDEX();
        } 
    });

    //$('.oneservice').on('change', '> input[type=radio]', function () {
    //     ADD_SERVICE(this);
    //});

    //$('.price_input, #terminovo').change(PRE_SUM_SHOW);

    function SERIALIZE(onlyService = false) {

        let OUT = [];

        let type_arr = $('#bodyFS [name*="type"]:checked').serializeArray();

        let vidZamovn_arr = $('#bodyFS input[VZ]:checked').serializeArray();

        $.each(vidZamovn_arr, (ind, line) => { line.name = "type[19]" });

        type_arr = type_arr.concat(vidZamovn_arr);
        
        let counter = 0;

        let GET_SERVICE_NAME = function (index, type = -1) {
            let out = "";

            if (type == -1 && index != 19) {
                $.each(SERVICES, (ind, srv) => {
                    if (srv.ID == index) {
                        out = srv.NAME;
                        return;
                    }
                });
            } else if (index == 19 && type != -1) {
                $.each(SERVICES, (ind, srv) => {
                    if (srv.ID == type) {
                        out = srv.NAME;
                        return;
                    }
                });
            } else if (index == 19 && type == -1) {
                $.each(SERVICES, (ind, srv) => {
                    if (srv.ID == index) {
                        out = srv.NAME;
                        return;
                    }
                });

            } else {
                $.each(SERVICES, (ind, srv) => {
                    if (srv.ID == index) {
                        out = srv.TYPES[type].TYPE;
                        return;
                    }
                });
            }

            return out;
        }

        if ($('#terminovo').prop('checked')) {
            OUT.push({ name: "SERVICES[" + counter + "][ID]", value: 21 });
            OUT.push({ name: "SERVICES[" + counter + "][NAME]", value: GET_SERVICE_NAME(21) });
            OUT.push({ name: "SERVICES[" + counter + "][TYPE][ID]", value: 1 });
            OUT.push({ name: "SERVICES[" + counter + "][TYPE][NAME]", value: "" });
            OUT.push({ name: "SERVICES[" + counter + "][COLOR][ID]", value: null });
            OUT.push({ name: "SERVICES[" + counter + "][COLOR][NAME]", value: null });
            OUT.push({ name: "SERVICES[" + counter + "][COST]", value: $('#terminovo').val() });

            counter++;
        }

        let serv_ind = 0; let serv_ind_19 = 0; let checkedColorInp = null;

        $.each(type_arr, (ind, type) => {

            serv_ind = type.name.match(/\d+/);
            serv_ind_19 = serv_ind;

            if (serv_ind == 19) serv_ind_19 = type.value;

            OUT.push({ name: "SERVICES[" + counter + "][ID]", value: serv_ind });
            OUT.push({ name: "SERVICES[" + counter + "][NAME]", value: GET_SERVICE_NAME(serv_ind) });
            OUT.push({ name: "SERVICES[" + counter + "][TYPE][ID]", value: type.value });
            OUT.push({ name: "SERVICES[" + counter + "][TYPE][NAME]", value: GET_SERVICE_NAME(serv_ind, type.value) });
            checkedColorInp = $("input[name='color[" + serv_ind + "]']:checked");
            OUT.push({ name: "SERVICES[" + counter + "][COLOR][ID]", value: checkedColorInp.val() });
            OUT.push({ name: "SERVICES[" + counter + "][COLOR][NAME]", value: $("label[for='" + checkedColorInp.attr("id") + "']").text().trim() });
            OUT.push({ name: "SERVICES[" + counter + "][COST]", value: $("input[name='cost[" + serv_ind_19 + "]']").val() });

            counter++;
        });

        if (onlyService) {
            return [{ name: "dKoef", value: DISCOUNT_KOEF }].concat(COLORS.concat(OUT));
        } else {
            let TEMP_ARR = [{ name: "ID", value: LoadedID }];

            if ($('#callback').prop('checked') != undefined && $('#callback').prop('checked')) {
                TEMP_ARR.push({ name: "callback", value: 0 });
            } else {
                TEMP_ARR.push({ name: "callback", value: 1 });
            }

            TEMP_ARR.push({ name: "redaktor", value: $('#redaktor').val() });
            TEMP_ARR.push({ name: "worker", value: $('#worker').val() });
            TEMP_ARR.push({ name: "ZTYPE", value: "DEFF" });
            TEMP_ARR.push({ name: "DISCOUNT_IGNORE", value: DISCOUNT_IGNORE });

            return TEMP_ARR.concat($('#headFS').serializeArray().concat(OUT));
        }
    }

    $('#container').submit(this.SUBM);

    function SUBM() {
    //    if (!confirm("ЗБЕРЕГТИ ЗАЯВКУ?")) return;

    //    $.get('blok/z_create/RecZ.php', SERIALIZE(), (succes) => {
    //        document.location = succes;
    //    });
    }

    $('input#discount').on('input', (inp) => {
        //console.log(inp.currentTarget.value);
        if (inp.currentTarget.value > 0 && inp.currentTarget.value < 100) {
            DISCOUNT_KOEF = (100 - inp.currentTarget.value) / 100;
        } else {
            DISCOUNT_KOEF = 1;
        }

        //PRE_SUM_SHOW();
    });

</script>

<style>

    fieldset, #bott {
        margin: 20px 1% 0 1%;
        width: 98%;
        max-width: 98%;
        padding: 20px;
    }

    #headFS input[type=datetime-local], #headFS input[type=date] {
        width: auto;
    }

    #headFS > div {
        position: relative;
        width: 100%;
        max-width: 100%;
        margin: 3px 0;
        padding: 3px 0;
        display: grid;
        grid-template-columns: 30% 70%;
    }

    #headFS input[type=checkbox] {
        width: 25px;
        height: 25px;
    }

    #headFS > div > div {
        position: relative;
        border: solid 2px black;
        border-radius: 5px;
        padding: 10px;
        max-width: 100%;
    }

        #headFS > div > div > * {
            position: static;
            min-height: 25px;
            width: 100%;
        }

    #headFS > div >:last-child {
        display: grid;
        grid-template-columns: 100%;
        grid-row-gap: 6px;
    }

    #headFS textarea {
        min-height: 80px;
        resize: vertical;
    }

    #bodyFS {
        position: relative;
    }

        @media screen and (max-width: 750px) {
            #bodyFS * {
                font-size: 10px;
            }
        }

    #work_TABLE{
         width: 100%;
    }

    #wField {
        padding: 10px 2px;
    }

        #wField > button, #wField > :first-child, #wField > div {
            width: 90%;
            margin: 5px 5%;
        }

        #work_TABLE tr {
            width: 100%;
            padding: 0;
        }

        #work_TABLE td {
            padding: 0;
        }

        #work_TABLE td > *{
            width: 100%;
            height: 100%;
            margin: 0;
            font-size:inherit;
            border: none;
            background: none;
        }

        .oneservice {

        }

    .del_row{
        text-align: center;
        color: white;
        background-color: red;
        font-weight: bold;
        cursor: pointer;
        padding: 0 7px;
    }

    #bott {
        border: solid 5px green;
        background-color: darksalmon;
        border-radius: 15px;
        display: inline-flex;
        justify-content: space-between;
        align-items: center;
    }

        #bott input, #bott select {
            border: dotted 3px #b37474;
            border-radius: 5px;
            color: green;
            font-weight: bold;
            background-color: darkgray;
        }

        #bott input, #bott > div, #bott button {
            max-width: 30%;
        }

</style>