<?php  

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

session_start();

$redaktor = $_SESSION['logged'];

$conn = new SQLconn();

#region Отримання масиву послуг/типів/кольорів
$result = $conn->SELECT('service_ids', '*', 'ORDER BY `order` ASC');

$attr = 16;

$serviceInfo = array();

foreach ($result as $row) {
    if (inclAttr($attr, $row['atr'])) {

        $result = $conn->SELECT('type_ids', 'type_ID, name', 'where service_ID=' . $row['ID']);

        $types_out = array();

        foreach ($result as $t){
            $types_out[intval($t['type_ID'])] = $t['name'];
        }

        $colors = array();

        if ($row['color'] == 1){
            $result = $conn->SELECT('color_map', 'type_ID, color_ID', 'where service_ID = ' . $row['ID'] . ' ORDER BY type_ID, color_ID');

            foreach ($result as $c) {
                $colors[$c['type_ID']][] = $c['color_ID'];
            }
        }

        $serviceInfo[intval($row['ID'])] = new ServiceInfo($row['ID'], $row['NAME'], $row['color'], $types_out, $colors);
    }
}

$COLOR_NAMES = array();

$result = $conn->SELECT('colors', 'ID, color, css_name', 'ORDER BY ID');

foreach ($result as $r){
    $COLOR_NAMES[$r['ID']] = ['name' => strAbbr($r['color']), 'color' => $r['css_name']];
}

#endregion

$lastIndexIn = $conn->SELECT('service_in', 'ID', 'ORDER BY ID DESC LIMIT 1')[0]['ID'];

$conn->close();

$form = new HTEL("form !=form_exp onsubmit=return+false");

$search = new HTEL('input *=search !=tag $=швидкий+пошук');

$dateInput = new HTEL('input *=date !=date_in ?=date_in #=[0]', date('Y-m-d'));

$select_ids = new HTEL("select !=service_list ?=service_ID", new HTEL("option [d] [s] #/послуга..."));

foreach($serviceInfo as $id=>$service){
    $select_ids(new HTEL('option .=service_opt #=[0]/[1]', [$id, $service->NAME]));
}

$head_form = new HTEL('fieldset !=headForm',  [$dateInput, $search, $select_ids]);

$fs_types = new HTEL("fieldset !=service_types");

$div_colors = new HTEL('div !=colors');

$div_sum = new HTEL('div !=sum_info', [
    new HTEL('input *=number !=cnt ?=count min=1 step=1 [r] $=к-ть'),
    new HTEL('input *=number !=sum ?=costs min=0 step=0.01 [r] $=сума')
]);

$inpSpis = new HTEL('label for=spis_cb !=sps/Списання', new HTEL('input *=checkbox !=spis_cb ?=is_spis #=1'));

$inp_comm = new HTEL('div', new HTEL('input *=text !=comm ?=comm $=коментар'));

$table_info = new HTEL('div !=result_div', new HTEL('table !=result_table', new HTEL('tbody')));

$form([$head_form, $fs_types, $div_colors, $div_sum, $inpSpis, $inp_comm, new HTEL('button !=submBut *=submit [h]/+'), $table_info]);

echo $form;

?>

<script>

    var SERVICES =  <?= json_encode($serviceInfo); ?>;
    var COLOR_NAMES = <?= json_encode($COLOR_NAMES); ?>;
    var LAST_INDEX = <?= json_encode($lastIndexIn); ?>;
    var Redaktor =  <?= json_encode($redaktor); ?>;

    var CUR_SERV_ID = -1;

    var ID = 0;
    var IDS = [];

    var mySearchBar = document.getElementById('tag');

    $("#tag").focus();

    mySearchBar.addEventListener('input', (e) => {
        if (!e.currentTarget.value) {
            $('#service_list option').prop('hidden', false);
            $('#service_list').children(":first").prop('selected', true);
            AppendTypeInfo();
            $('#submBut').prop('hidden', true);
        }
    });

    $('#tag').keyup(function () {

        $('#service_list option').prop('hidden', false);

        let searchText = $(this).val().trim().toLowerCase();

        if (searchText.trim() === '') {
            return;
        }

        let finded = false;

        let findedServices = {};

        $.each(SERVICES, function (ind, val) {
            if (val.NAME.toLowerCase().indexOf(searchText) > -1) {
                finded = true;
                findedServices[val.ID] = [];
            } else if (val.HAS_TYPES) {
                let _typeFinded = [];
                
                $.each(val.TYPES, function (tind, tname) {
                    let indFind = tname.toLowerCase().indexOf(searchText);

                    if (indFind > -1) {
                        finded = true;
                        _typeFinded.push(tind);
                    }
                });

                if (_typeFinded.length > 0) {
                    findedServices[val.ID] = _typeFinded;
                }
            }
        });

        let firstSelectServ = true;

        if (finded) {
            $('#service_list option').prop('hidden', true);
            $.each(findedServices, function (ind, val) {
                $('#service_list option[value="'+ind+'"]').prop('hidden', false);

                if (firstSelectServ) {
                    $('#service_list option[value="' + ind + '"]').prop('selected', true);
                    firstSelectServ = false;

                    if (val.length == 1) {
                        AppendTypeInfo(ind, val[0]);
                    } else {
                        AppendTypeInfo(ind);
                    }
                }
            });
        }

    });

    $('#form_exp').on('change', '#service_list', function () {
        AppendTypeInfo($(this).val());
    });

    function AppendTypeInfo(ServiceID = -1, SelectedType = null) {
        let types = "";

        CUR_SERV_ID = ServiceID;

        $('#service_types').html('');
        $('#colors').html('');
        $('#cnt').val('');
        $('#sum').val('');
        $('#comm').val('');
        $('#spis_cb').prop('checked', false);
        $('#comm').prop('required', false);
        $('#sum').prop('disabled', false);
        $('#submBut').prop('hidden', false);

        if (ServiceID === -1) {
            return;
        }

        if (SERVICES[CUR_SERV_ID].HAS_COLOR) {
            $('#cnt').prop('disabled', false);
            $('#sps').prop('hidden', false);
        } else{
            $('#cnt').prop('disabled', true);
            $('#sps').prop('hidden', true);
        }

        if (SERVICES[CUR_SERV_ID].HAS_TYPES) {
            let selectOne = "";

            if (SelectedType === null && Reflect.ownKeys(SERVICES[CUR_SERV_ID].TYPES).length == 1) {
                selectOne = "checked";
            }

            $.each(SERVICES[CUR_SERV_ID].TYPES, function (ind, val) {
                if (SelectedType === ind) {
                    selectOne = "checked";
                } else if (SelectedType !== null){
                    selectOne = "";
                }

                types += "\n<div class='cb'><input class='type_opt' id='typ" + ind +
                    "' type='radio' name='type_ID' value='" + ind + "' " + selectOne + " />" +
                    "\n<label for='typ" + ind + "'>" + val + "</label></div>";
            });

            $('#service_types').html(types);

            if (SelectedType !== null) {
                ColorRadioCreate(CUR_SERV_ID, SelectedType);
            }
        } else {
            ColorRadioCreate(CUR_SERV_ID, 1);
        }
    }

    $('#form_exp').on('change', '.type_opt', function () {
        $('#colors').html('');
        ColorRadioCreate(CUR_SERV_ID, $(this).val());
    });

    function ColorRadioCreate(_id, _type) {
        var colors = "";

        var checked = "checked='checked'";

        if (SERVICES[_id].HAS_COLOR && SERVICES[_id].COLORS[_type].length > 1) checked = "";

        $.each(SERVICES[_id].COLORS[_type], function (ind, val) {
            colors += "\n<div class='cb'><input class='color_opt' id='col" + val + "' type='radio' name='color' value='" + val + "' " +
                checked + " /> " + "<label for='col" + val + "' style='background-color:" +
                COLOR_NAMES[val].color + ";'>" + COLOR_NAMES[val].name.toLowerCase() +
                "</label></div>";
        });

        $('#colors').html(colors);
    }

    $('#form_exp').on('change', '#spis_cb', function () {
        if ($(this).prop('checked') == true) {
            $('#comm').prop('required', true);
            $('#sum').val('');
            $('#sum').prop('disabled', true);
        } else {
            $('#comm').prop('required', false);
            $('#sum').prop('disabled', false);
        }
    });

    $('#form_exp').submit(function () {

        if (SERVICES[CUR_SERV_ID].HAS_TYPES) {
            if (!IsNumeric($(".type_opt:checked").val())) {
                highlight('.type_opt + label');
                return;
            }
        }

        if (SERVICES[CUR_SERV_ID].HAS_COLOR) {
            if (!IsNumeric($(".color_opt:checked").val())) {
                highlight('.color_opt + label');
                return;
            }
        }

        var arr = $(this).serializeArray();

        var ans = new SerialFormVars(arr);

        var out = "";
        var spis = ans.GET("is_spis") == 1 ? "списання" : "витрата";

        var outCell = [];

        var outCell = [
            $(".service_opt:selected").text(),
            $(".type_opt:checked + label").text(),
            $(".color_opt:checked + label").text(),
            spis,
            ans.GET("count") + " шт.",
            ans.GET("costs") + " грн.",
            ans.GET("comm", true)
        ];

        var _OutCellTCollspan = [1];
        var _counter = 0;

        $.each(outCell, function (ind, val) {
            if (val.toString().trim() === '') {
                _OutCellTCollspan[_counter - 1] += 1;
            } else {
                _OutCellTCollspan[_counter] = 1;
                _counter++;
            }
        });

        _counter = 0;

        $.each(outCell, function (ind, val) {
            if (val.toString().trim() !== '') {
                out += "<td colspan='"+_OutCellTCollspan[_counter]+"'>" + val + "</td>";
                _counter++;
            }
        });

        id = ID++;

        IDS[id] = { service_ID:ans.GET("service_ID"), type_ID:ans.GET("type_ID"), color:ans.GET("color"), count:ans.GET("count"), costs:ans.GET("costs") };

        out += "<td class='removeTD'><input class='remove_line' id='remowe" + id + "' removeID='" + id +
            "' type='button' /><label for='remowe" + id + "'>-</label></td>";

        $('#result_table tbody').prepend("<tr>" + out + "</tr>");

        $.get('blok/exp/record_in.php', ans.ToArray({redaktor: Redaktor}), function (result) {
            $('#form_exp').append(result);
        });

        $("#tag").focus();
    });

    function highlight(selector) {
        var element = $(selector);
        var defaultBG = [];
        var defBorder = element[0].style["boxShadow"];
        var defaultTransition = element[0].style.transition;

        $.each(element, function (ind, val) {
            defaultBG[ind] = val.style.backgroundColor;

            val.style.transition = "background 1s";
            val.style.backgroundColor = "#DC143C";
            val.style["boxShadow"] = "0 0 10px yellow";

            setTimeout(function()
            {
                val.style.backgroundColor = defaultBG[ind];
                val.style["boxShadow"] = defBorder;

                setTimeout(function() {
                    val.style.transition = defaultTransition;
                }, 700);
            }, 700);

        });
    }

    $('#form_exp').on("click", ".remove_line", function () {
        var row = $(this);

        IDS[row.attr("removeID")]['LInd'] = LAST_INDEX;
        $.get('blok/exp/remove_in.php', IDS[row.attr("removeID")], function () {
               row.parent().parent().remove();
        });
    });

</script>

<style>
    #form_exp{
         position:relative;
         background-color: darkseagreen;
         border: solid 5px green;
         border-radius: 10px;
         padding: 15px;
    }

    #form_exp > * {
         margin-left: 10px;
         padding: 0;
    }

        #form_exp input[type=date] {
            background-color: green;
            border-radius: 3px;
            border: dotted 3px yellow;
            color: yellow;
        }

    #headForm {
        position: relative;
        padding: 5px 15px;
        width: 100%;
    }

        #headForm > *{
            margin: 0 10px 10px 0;
        }

        #headForm > input[type=search]{
            position: absolute;
            right: 5px;
            top: 5px;
            background-color: white;
            border-radius: 5px;
            padding: 5px 5px 5px 15px;
        }

        #headForm > select {
            width: 100%;
        }

        #service_list {
            border: solid 4px green;
            border-radius: 5px;
            background-color: olive;
            font-weight: bold;
        }

    #service_types, #colors {
        display: inline-grid;
        row-gap: 5px;
        padding-top: 10px;
        margin: 5px 5px 5px 5px;
        border: none;
        background: none;
    }

    #colors {
        grid-template-columns: repeat(8, 1fr);
    }

    input[type="radio"] + label {
        background-color: lightgrey;
    }

    input[type="radio"]:checked + label {
        box-shadow: 0 0 12px black;
        border: solid 4px white;
    }

    input[type=number], input[type=text]{
         border: solid 2px green;
         border-radius: 5px;
         padding: 3px 0;
         text-align: center;
    }

        #service_types label{
            padding: 0 2px;
        }

        #colors label{
            padding: 0 3px;
            color: white;
            font-weight: bold;
        }

        #service_types label, #colors label {
            border-radius: 5px;
        }

        input[type=radio] {
            visibility: hidden;
        }

        #sum_info{
            margin-top: 10px;
        }

        #cnt{
        max-width: 100px;
        }

        #sum{
        max-width: 145px;
        }

    #comm{
         margin-top: 10px;
         min-width: 250px;
    }

    #form_exp button {
        min-width: 70px;
        min-height: 50px;
        font-weight: bold;
        margin-top: 30px;
    }

    #result_div {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 20px;
    }

    #result_table {
        border-collapse: separate;
        border-spacing: 2px;
        border: none;
    }

    #result_table td{
        padding: 2px 3px;
    }

    .removeTD{
         position: relative;
         background-color: red;
         min-width: 30px;
         padding: 0;
    }

        .removeTD > input + label {
            background-color: transparent;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        .removeTD label {
            position: absolute;
            width: 80%;
            height: 80%;
            text-align: center;
            margin: 0;
            padding: 0;
        }

</style>