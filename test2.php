<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$container = new HTEL('form !=container onSubmit=return+false');

$conn = new SQLconn();

//ДАНІ ЗАЯВКИ

#region ОБРОБКА ВХІДНИХ ДАНИХ

$INPUT = new ZDATA2($_GET['ID'] ?? 0, ZType::DEFF);

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

    $clb = $INPUT->GET('callback') == 0 ? "checked" : "";

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

$atr = ZType::DEFF;

#region ПРОРИСОВКА ТІЛА ФОРМИ

$fsBody = new HTEL('fieldset !=bodyFS', new HTEL('legend/[0]', "КОМПЛЕКТУЮЧІ / ПОСЛУГИ:"));

$priceLabel = new HTEL('input *=text !=priceLabel #=0+грн. [d]');

$fsBody($priceLabel);

//ЗЧИТУВАННЯ ПОСЛУГ/ТОВАРІВ/ТИПІВ/КОЛЬОРІВ в массив $SERVICES

$result = $conn->SELECT('price_list', 'cost', 'WHERE service_id = 19 AND type_id = 1');

$vidZamovnPrice = $result[0]['cost'];

$result = $conn->SELECT('service_ids', 'ID, NAME, color, atr', 'ORDER BY `order`');

$SERVICES = array();

$COLORS = array();

$color_read = $conn->SELECT('colors', 'ID, color, css_name', 'ORDER BY ID');

foreach ($color_read as $c) {
    $COLORS[$c['ID']] = ["name"=> "COLOR[" . $c['ID'] . "][" . $c['css_name'] . "]", "value"=> $c['color']];
}

$counter = 0;

#region ЗАПОВНЕННЯ РОБОЧОГО МАСИВУ $SERVICES
foreach ($result as $row) {
        $SERVICES[$counter] = ["ID" => $row['ID'], "NAME" => $row['NAME'], "HAS_COLOR" => $row['color'], "SHOW" => inclAttr($atr, $row['atr'])];

        $types_read = $conn->SELECT('type_ids', 'type_ID, name', 'WHERE service_ID = ' . $row['ID'] . ' ORDER BY type_ID');

        $color_read = $conn->SELECT('color_map', 'color_ID, type_ID', 'WHERE service_ID = ' . $row['ID'] . ' ORDER BY type_ID, color_ID');

        $cost_read = $conn->SELECT('price_list', 'type_id, cost', 'WHERE service_id = ' . $row['ID']);

        if (count($types_read) < 1) {
            $types_read[0]['type_ID'] = 1;
            $types_read[0]['name'] = '';
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
#endregion

$workDIV = new HTEL('div !=work_body');

$readOnlyPriceAtr = $INPUT->TYPE == ZType::DEFF_A ? 'readonly' : 'required';

foreach ($SERVICES as $s) {
    if ($s["SHOW"] == true){
        $POSL = $s['HAS_COLOR'] == 0 ? 'disabled' : '';
        $VARIANT = $s['HAS_COLOR'] == 1 ? 'checked' : '';
        $workDIV(new HTEL('div .=oneservice servID=[0]', [
            $s['ID'],
            $s['NAME'],
            $s['TYPES'][1]['COST'],
            $readOnlyPriceAtr,
            new HTEL('label for=[0]_yes/[1]'),
            new HTEL('input .=price_input ?=cost[[0]] *=number step=0.01 min=0 [3] #=[2]'),
            new HTEL('input *=radio !=[0]_yes1 ?=service_ID[[0]] servID=[0] VZ #=[0] [1] price=[2]', [1 => $POSL, $vidZamovnPrice]),
            new HTEL('label for=[0]_yes1/своє'),
            new HTEL('input *=radio !=[0]_yes ?=service_ID[[0]] servID=[0] #=yes price=[2]'),
            new HTEL('label for=[0]_yes/так'),
            new HTEL('input *=radio !=[0]_no ?=service_ID[[0]] servID=[0] #=no [r] [1] price=[2]', [1 => $VARIANT]),
            new HTEL('label for=[0]_no/ні')
        ]));
    }
}

$fsBody($workDIV);

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
echo new HTEL('div !=ans');
echo new HTEL("div !=SELECT_INFO");

?>

<script>

    var SERVICES = <?= json_encode($SERVICES) ?>

    var COLORS = <?= json_encode($COLORS) ?>

    var INPUTSERVICES = <?= json_encode($INPUT->GET_SERVICES()) ?>

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

    var LAST_SELECTED_COLOR = -1;

    var DISCOUNT_KOEF = 1;

    INPUT_SET();

    var AUTOSET_TIME = false;

    function INPUT_SET() {
        if (INPUTSERVICES.length == 0) return;

        AUTOSET_TIME = true;

        $('.oneservice > input[value="no"]:not(:checked)').each((ind, inputNo) => {
            inputNo.setAttribute('checked', true);
        });

        if (INPUTSERVICES[19] !== undefined) {
             $('.oneservice > input[VZ]').each((ind, inputYes) => {
                 $.each(INPUTSERVICES[19], (servId, inf) => {
                     if (inputYes.getAttribute('servID') == servId)
                         inputYes.setAttribute('checked', true);
                         $('input[name="cost[' + servId + ']"]').val(inf.costs);
                     return;
                 });
             });
        }

        $('.oneservice > input[value="yes"]').each((ind, inputYes) => {
            if (INPUTSERVICES[inputYes.getAttribute('servID')] !== undefined) {
                inputYes.setAttribute('checked', true);
                ADD_SERVICE(inputYes);
            }
        });

        AUTOSET_TIME = false;
    }

    $('.oneservice').on('change', '> input[type=radio]', function () {
         ADD_SERVICE(this);
    });

    function ADD_SERVICE(inputYes) {
        let servID = $(inputYes).attr('servID');
        let servCheck = $(inputYes).val() == "yes" ? true : false;

        let nameLabel = $(inputYes).parent().find(">:first-child");

        let priceInput = $(inputYes).parent().find('.price_input');

        if (INPUTSERVICES[servID] === undefined || !AUTOSET_TIME) {
            priceInput.val($(inputYes).attr('price'));
        }
        else {
            priceInput.val(INPUTSERVICES[servID].costs);
        }

        if (!servCheck) {
            $('#menu_' + servID).remove();
            nameLabel.css('font-weight', 'normal');
            nameLabel.css('box-shadow', 'none');
            nameLabel.css('text-align', 'left');
            nameLabel.css('text-transform', 'none');
            nameLabel.css('width', '100%');
            nameLabel.css('padding', '0');
            nameLabel.css('border-radius', '0');

            $(inputYes).parent().css('background-color', 'transparent');
            $(inputYes).parent().css('padding', '0 0 5px 0');
            //padding-bottom: 5px;
            $(inputYes).parent().css('border-radius', '0');
            $(inputYes).parent().css('border-left', 'none');
            $(inputYes).parent().css('border-bottom', 'solid 2px black');
        } else {
            nameLabel.css('font-weight', 'bold');
            nameLabel.css('box-shadow', '0 5px 25px red');
            nameLabel.css('text-align', 'center');
            nameLabel.css('text-transform', 'uppercase');
            nameLabel.css('width', '90%');
            nameLabel.css('padding', '2px 5px');
            nameLabel.css('border-radius', '10px 0 0 0');

            $(inputYes).parent().css('background-color', '#FFF8DC');
            $(inputYes).parent().css('padding', '10px 10px');
            $(inputYes).parent().css('border-radius', '10px');
            $(inputYes).parent().css('border-left', 'solid 20px blue');
            $(inputYes).parent().css('border-bottom', 'none');

            let innerOUT = "";
            let hasTypes = true;
            let stylePodmenu = "style='grid-template-columns: 75% 25%;'";

            $.each(SERVICES, function (ind, val) {
                if (val.ID === servID) {

                    innerOUT = "<div class='type_list'>";

                    let atr = val.TYPES.length == 1 ? "checked" : "required";
                    let autoShowColor = false;

                    $.each(val.TYPES, function (i, t) {
                        if ((val.HAS_COLOR == 1 && t.COLORS.length > 0) || val.HAS_COLOR == 0) {
                            if (INPUTSERVICES[servID] !== undefined && INPUTSERVICES[servID].type_ID == i) {
                                atr = "checked";
                                autoShowColor = true;
                            }

                            if (t.TYPE !== '') {
                                innerOUT += "<input id='type_" + servID +
                                     "_" + i + "' type='radio' value='" + i + "' name='type[" +
                                     servID + "]' "+atr+"='' /><label for='type_" + servID + "_" + i + "'>" +
                                     t.TYPE + "</label>";
                            } else {
                                hasTypes = false;

                                innerOUT += "<input id='type_" + servID +
                                    "_" + i + "' type='radio' value='" + i + "' name='type[" +
                                    servID + "]' checked='' /><label for='type_" + servID + "_" + i + "'>" +
                                    nameLabel[0].innerText + "</label>";
                            }

                            atr = "required";
                        }
                    });

                    innerOUT += "</div>";

                    if (val.HAS_COLOR == 1) {
                        if (hasTypes && !autoShowColor) {
                            innerOUT += "<div class='color_list' />";
                        }
                        else {
                            innerOUT += "<div class='color_list'>" +
                                ColorAppend(servID, INPUTSERVICES[servID] !== undefined ? INPUTSERVICES[servID].type_ID : 1,
                                INPUTSERVICES[servID] !== undefined ? INPUTSERVICES[servID].color : -1) + "</div>";
                        }
                    } else {
                        stylePodmenu = "style='grid-template-columns: 100%;'";
                    }

                    return;
                }
            });

            $(inputYes).parent().append(HTEL.FORMAT('<div class="podmenu" '
                + stylePodmenu + ' servID="' + servID + '" id="menu_' + servID + '">' + innerOUT + '</div>', 5));
        }

        PRE_SUM_SHOW();
    }

    $('#bodyFS').on('change', '.type_list input[type=radio]', function () {
        ADD_COLOR_LIST(this);
    });

    function ADD_COLOR_LIST(typeradio) {
        let servID = $(typeradio).parent().parent().attr('servID');
        let servType = $(typeradio).val();
        let colorList = $(typeradio).parent().parent().find('.color_list');
        let priceInput = $(typeradio).parent().parent().parent().find('.price_input');

        let innerOUT = ColorAppend(servID, servType);

        colorList.html(HTEL.FORMAT(innerOUT, 7));

        $.each(SERVICES, (ind, srv) => {
            if (srv['ID'] == servID) {
                priceInput.val(srv['TYPES'][servType]['COST']);
                //console.log(priceInput);
                return;
            };
        });

        PRE_SUM_SHOW();
    }

    $('#bodyFS').on('change', '.color_list input[type=radio]', function () {
        LAST_SELECTED_COLOR = $(this).val();
    });

    function ColorAppend(_servID, _servType = 1, _colorID = -1) {
        let _out = "";

        let get_color_name = function (id, css = false) {
            let out = "";

            $.each(COLORS, (ind, col) => { if (ind == id) { out = (css ? col.name.match(/#[\w\d]+/) : col.value); return; } });

            return out;
        };

        $.each(SERVICES, function (ind, val) {
            if (val.ID === _servID) {

                let atr = val.TYPES[_servType].COLORS.length == 1 ? "checked" : "required";
                let tempAtr = "";

                $.each(val.TYPES[_servType].COLORS, function (i, v) {
                    if (_colorID == v || (LAST_SELECTED_COLOR == v && _colorID == -1)) {
                        tempAtr = "checked";
                    } else {
                        tempAtr = atr;
                    }

                    _out += '<input type="radio" id="color_' + _servID + '_' + i +
                        '" name="color[' + _servID + ']" value="' + v +
                        '" ' + tempAtr + '="" /><label for="color_' + _servID +
                        '_' + i + '" style="border-left: solid 10px;border-color:'+get_color_name(v, true)+';">' + get_color_name(v) + '</label>';
                });

                return;
            }
        });

        return _out;
    }

    $('.price_input, #terminovo').change(PRE_SUM_SHOW);

    function PRE_SUM_SHOW() {
        let out = $('#terminovo').prop('checked') ? $('#terminovo').val() * DISCOUNT_KOEF : 0;

        let temp = $('label[for="terminovo"]')[0].innerHTML;

        $('label[for="terminovo"]')[0].innerHTML = temp.replace(/\+[\S]+/, "+" + Number(($('#terminovo').val() * DISCOUNT_KOEF).toFixed(2)));

        let IsYes;
        let Cost = 0;

        $('.oneservice').each((ind, srv) => {

            IsYes = $.find(':input[type=radio]:checked', srv);

            if (IsYes.length > 0) {

                Cost = $.find(':input[type=number]', srv)[0].value * DISCOUNT_KOEF;

                if (IsYes[0].value != "no") {
                      out += Cost;
                }
            }
        });
        $('#priceLabel').val(Number(out.toFixed(2)) + " грн.");
    }

    //var ID_NAME_ARR = [];

    //SET_ID_NAME_ARR();

    //function SERIALIZE(onlyServices = false) {

    //    let OUT = ID_NAME_ARR.concat($('#bodyFS').serializeArray());

    //    if ($('#terminovo').prop('checked')) {
    //        OUT.push({ name: "service_ID[21]", value: 1 });
    //        OUT.push({ name: "cost[21]", value: $('#terminovo').val() });
    //    }

    //    if (!onlyServices) {
    //        OUT = OUT.concat($('#headFS').serializeArray());
    //        OUT = OUT.concat({ name: "ID", value: LoadedID }, { name: "worker", value: $('#worker').val() }, { name: "redaktor", value: $('#redaktor').val() });
    //    }

    //    return OUT;
    //}

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

            return TEMP_ARR.concat($('#headFS').serializeArray().concat(OUT));
        }
    }

    $('#container').submit(this.SUBM);

    function SUBM() {
        if (!confirm("ЗБЕРЕГТИ ЗАЯВКУ?")) return;

        $.get('blok/z_create/RecZ.php', SERIALIZE(), (succes) => {
            document.location = succes;
        });
    }

    $('input#discount').on('input', (inp) => {
        //console.log(inp.currentTarget.value);
        if (inp.currentTarget.value > 0 && inp.currentTarget.value < 100) {
            DISCOUNT_KOEF = (100 - inp.currentTarget.value) / 100;
        } else {
            DISCOUNT_KOEF = 1;
        }

        PRE_SUM_SHOW();
    });

    $('#priceLabel, #submBut').mouseenter(() => {
        SHOW_CHECK();
    });

    //mouseleave

    $('#priceLabel, #submBut').mouseleave(() => {
        $("#SELECT_INFO").css("visibility", "hidden");
    });

    function SHOW_CHECK() {

        $.get("show_check.php", this.SERIALIZE(true), (ans) => {
            $("#SELECT_INFO").html(ans);
        });

        $("#SELECT_INFO").css("visibility", "visible");
    }

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

    #priceLabel {
        position: sticky;
        width: 140px;
        font-size: 26px;
        background-color: yellow;
        border: solid 5px blue;
        border-radius: 20px 0 0 0;
        right: 10px;
        top: 30px;
        font-weight: bold;
        font-style: italic;
        color: #353535;
        text-align: center;
        z-index: 998;
        margin-bottom: 5px;
    }

    #SELECT_INFO {
        position: fixed;
        display: inline-grid;
        z-index: 999;
        grid-column-gap: 10px;
        width: fit-content;
        max-width: 85%;
        right: 30px;
        bottom: 100px;
        background-color: white;
        color: #141414;
        grid-template-columns: auto auto auto;
        padding: 2% 1%;
        font-family: cons;
        visibility: hidden;
        box-shadow: -10px -10px 5px rgb(52, 52, 52, 0.79);
    }

    #SELECT_INFO > span {
         padding: 0 3px;
    }

        @media screen and (max-width: 750px) {
            #bodyFS > div * {
                font-size: 10px;
            }
        }

        #bodyFS input[type=radio] {
        width: 1px;
        height: 1px;
        color: transparent;
        background: none;
    }

    .oneservice {
        width: 100%;
        position: relative;
        display: inline-grid;
        grid-template-columns: auto 10% 0 10% 0 10% 0 10%;
        border-bottom: solid 2px black;
        margin-bottom: 5px;
        padding-bottom: 5px;
    }

        .oneservice:first-child {
            word-wrap: break-word;
        }

        .oneservice input[type=radio][id$="_no"]:checked + label {
            background-color: red;
            border-color: red;
            color: white;
        }

        .oneservice input[type=radio]:not([id$="_no"]):checked + label {
            background-color: green;
            color: white;
        }

        .oneservice input[type=radio] + label {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: white;
            border: solid 3px green;
            border-radius: 5px;
            margin: 0 2px;
            cursor: pointer;
        }

        .oneservice input[type=radio]:disabled + label {
            background: none;
            border: none;
            color: transparent;
            cursor: default;
        }

        .oneservice input[type=number] {
            background: none;
            text-align: right;
            border: none;
            color: blue;
        }

    .podmenu {
        margin-top: 20px;
        width: 100%;
        display: grid;
        grid-column: 1 / 9;
    }

        .podmenu >:first-child {
            display: grid;
            grid-template-columns: 0 100%;
            row-gap: 2px;
        }

    .color_list {
        display: grid;
        grid-template-columns: 0 100%;
        row-gap: 2px;
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