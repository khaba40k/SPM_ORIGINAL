<?php

require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

function monthName($id):string{
    $out = "";
    switch($id){
        case 1:
            $out = "Січень";
            break;
        case 2:
            $out = "Лютий";
            break;
        case 3:
            $out = "Березень";
            break;
        case 4:
            $out = "Квітень";
            break;
        case 5:
            $out = "Травень";
            break;
        case 6:
            $out = "Червень";
            break;
        case 7:
            $out = "Липень";
            break;
        case 8:
            $out = "Серпень";
            break;
        case 9:
            $out = "Вересень";
            break;
        case 10:
            $out = "Жовтень";
            break;
        case 11:
            $out = "Листопад";
            break;
        default:
            $out = "Грудень";
            break;
    }
    return $out;
}

$but_text = [
    0 => 'днів: ...',
    1 => "місяць: ...",
    2 => "рік: ...",
    3 => monthName(date('m') - 1),
    4 => monthName(date('m')),
    5 => date('Y') . " рік",
    6 => 'Вчора',
    7 => 'Сьогодні',
    8 => "Цей тиждень",
    9 => 'Мин.тиждень',
    10 => "ВЕСЬ ЧАС"
];

echo new HTEL(
    'div .=zvitMenu+no-print',
    new HTEL(
        'form !=zvitMenu onsubmit=return+false',
        array_merge(
            APPEND_LABEL($but_text),
            [
                new HTEL("div !=periodInputs", [
                    new HTEL('input *=date !=ot ?=ot'),
                    new HTEL('input *=date !=do ?=do')
                ])
            ]
        )
    )
);

function APPEND_LABEL(array $names, $asLabel = [4, 3, 5]):array{
    $OUT_LABEL = [];
    $OUT_SELECT = new HTEL('select !=perOther', new HTEL('option [d] [s] #=[0]/інші варіанти...', [-1]));

    $checkedId = $asLabel[0] ?? 0;

    foreach ($names as $perId => $perName) {
        if (in_array($perId, $asLabel)){
            $OUT_LABEL[] = new HTEL('div .=perDiv', [
                $perId,
                $perName,
                $checkedId == $perId ? "checked" : "",
                new HTEL('input *=radio !=per[0] ?=period[] #=[0] [2]'),
                new HTEL('label for=per[0]/[1]')
            ]);
        }else{
            $OUT_SELECT(new HTEL('option #=[0]/[1]', [$perId, $perName]));
        }
    }

    $OUT_LABEL[] = new HTEL("div .=perDiv+perOther", [$OUT_SELECT, new HTEL('input !=customPer *=number step=1 min=1 max=2050')]);

    return $OUT_LABEL;
}

echo new HTEL('div !=zvitResult');

?>

<script>
    $( ".zvitMenu input[type=date]" ).change(showTable);

    var TIMER_CUSTOM_INPUT = null;
    var CURRENT_INFO = [null, null];

    const Period = {
        daysCount: 0,
        customMonth: 1,
        customYear: 2,
        prevMonth: 3,
        curMonth: 4,
        curYear: 5,
        yesterDay: 6,
        toDay: 7,
        thisWeek: 8,
        prevWeek: 9,
        ALLTIME: 10
    }

    SET_PERIOD_BY_CHANGE();

    function showTable() {

            $('#zvitResult').empty();

            $('#zvitResult').append(
            '<div style="display:inline-flex;justify-content:center;align-items:center;' +
            'background-color: blue;border-radius: 25px;padding:10px 0;">' +
            '<img src="../img/load.gif" style="width:64px;">' +
            '<label style="height:100%;margin:0 10px;color:yellow;font-weight: bold; font-size: 150%;">ЗАЧЕКАЙТЕ</label>' +
            '<img src="../img/load.gif" style="width:64px;">' +
            '<div>');

            let _data = $(".zvitMenu input[type=date]").serialize();

            $.ajax({
                 url: 'blok/zvit/table_sum.php',
                 method: 'GET',
                 dataType: 'html',
                 data: _data,
                 success: function(data) {
                     $('#zvitResult').html(data);
                 }
            });
    }

    $('.perDiv > input[type=radio]').change((me) => {
        $('#perOther').val(-1);
        $('#perOther').css("background", "none");
        $('#perOther').css("color", "black");

        $('#customPer').css('display', 'none');
        $('#customPer').val(0);

        SET_PERIOD_BY_CHANGE(me.currentTarget.value * 1);
    });

    $('#perOther').change((me) => {
        let _index = me.currentTarget.value * 1;

        if (_index < 3) {
            $('#customPer').css('display', 'inherit');
            $('#customPer').val('');
            $('#customPer').focus();
        } else {
            $('#customPer').css('display', 'none');
            $('#customPer').val(0);
        }

        $('.perDiv input').prop("checked", false);
        $('#perOther').css("background-color", "darkslateblue");
        $('#perOther').css("color", "white");

        SET_PERIOD_BY_CHANGE(_index, $('#customPer').val());
    });

    $('#customPer').change((me) => {
        let _ind = $('#perOther').val() * 1;
        let _val = me.currentTarget.value;

        SET_PERIOD_BY_CHANGE(_ind, _val);
    });

    $('#customPer').on('input', (me) => {
        clearTimeout(TIMER_CUSTOM_INPUT);
        TIMER_CUSTOM_INPUT = setTimeout(SET_PERIOD_BY_CHANGE, 500, $('#perOther').val() * 1, me.currentTarget.value);
    });

    function SET_PERIOD_BY_CHANGE(id = Period.curMonth, custInp = 0) {
        if (CURRENT_INFO[0] == id && CURRENT_INFO[1] == custInp) return;

        let _NOW = new Date();
        let _ot = new Date(_NOW);
        let _do = new Date(_NOW);

        custInp *= 1;

        CURRENT_INFO[0] = id;
        CURRENT_INFO[1] = custInp;

        let correctYEAR = function (_inp) {
            if (_inp < 10 || _inp > new Date().getFullYear() || (_inp > 99 && _inp < 1000)) return false;

            _inp = new Date(_inp, 0, 1).getFullYear();

            if (_inp < 2000) _inp += 100;

            if (_inp < 2023) return false;

            return _inp;
        }

        let toNormal = function (d) {
            return d.getFullYear() +
                "-" + (d.getMonth() + 1).toString().padStart(2, '0') +
                "-" + d.getDate().toString().padStart(2, '0');
        };

        let _day;

        switch (id) {
            case Period.prevMonth://Минулий місяць
                if (_NOW.getMonth() > 0) {
                    _ot = new Date(_NOW.getFullYear(), _NOW.getMonth() - 1, 1);
                } else {
                    _ot = new Date(_NOW.getFullYear() - 1, 11, 1);
                }
                _do.setDate(new Date(_NOW.getFullYear(), _NOW.getMonth(), 1).getDate() - 1);
                break;
            case Period.curMonth://Рік
                _ot = new Date(_NOW.getFullYear(), _NOW.getMonth(), 1);
                break;
            case Period.ALLTIME://Весь час
                _ot = new Date(2023, 8, 1);
                break;
            case Period.curYear://Теперішній рік
                 _ot = new Date(_NOW.getFullYear(), 0, 1);
                break;
            case Period.yesterDay:
                _ot.setDate(_NOW.getDate() - 1);
                _do.setDate(_NOW.getDate() - 1);
                break;
            case Period.daysCount:
                if (custInp < 1) return;
                _ot.setDate(_NOW.getDate() - (custInp - 1));
                break;
            case Period.customMonth:
                if (custInp < 1 || custInp > 12) return;
                let _year =  _NOW.getFullYear();

                 _year = (custInp <= (_NOW.getMonth() + 1)) ? _year : _year - 1;

                _ot = new Date(_year, custInp - 1, 1);

                _do = new Date(_year, custInp, 0);

                break;
            case Period.customYear:
                custInp = correctYEAR(custInp);

                if (!custInp) return;
                
                _ot = new Date(custInp, 0, 1);
                _do = new Date(custInp + 1, 0, 0);
                break;
            case Period.thisWeek:
                _day = _NOW.getDay() - 1;

                _ot.setDate(_NOW.getDate() - _day);
                break;
            case Period.prevWeek:
                _day = _NOW.getDay();
                _do.setDate(_NOW.getDate() - _day);
                _ot.setDate(_do.getDate() - 6);
                break;
        }

        $("#ot").val(toNormal(_ot));
        $("#do").val(toNormal(_do));

        showTable();
    }

</script>

<style>

.zvitMenu{
    display: flex;
    justify-content: center;
    align-items: center;
}

    #zvitMenu {
        display: grid;
        width: 100%;
        grid-template-columns: 25% 25% 20% 30%;
        background-color: rgb(0, 128, 0, 0.75);
        border: solid 2px grey;
        border-radius: 25px;
        margin-bottom: 30px;
        padding: 15px;
        grid-row-gap: 25px;
    }

    .perDiv {
        width: 100%;
        display: inline-flex;
        justify-content: center;
        align-items: center;
    }

        .perDiv label {
            text-align: center;
            text-overflow: ellipsis;
            padding: 2px 6px;
            width: 100%;
            height: auto;
        }

        .perDiv input[type=radio] {
            display: none;
        }

    #customPer {
        background-color: darkslateblue;
        border: none;
        border-radius: 10px;
        padding: 2px 5px;
        text-align: center;
        color: white;
        font-weight: bold;
        margin-left: 5px;
        display: none;
    }

    #zvitMenu select {
        background: none;
        border: none;
        font-size: medium;
        padding: 0 5px;
        border-radius: 30px;
        cursor: pointer;
    }

    input[name="period[]"] + label {
        padding: 0 5px;
        border-radius: 30px;
        cursor: pointer;
    }

        input[name="period[]"] + label:hover {
            color: white;
        }

    input[name="period[]"]:checked + label {
        background-color: darkslateblue;
        color: white;
    }

    #periodInputs {
        grid-column: span 4;
        padding: 0;
    }

    #periodInputs > input {
        width: 49%;
        text-align: center;
        border: none;
        font-family: cons;
        font-weight: bold;
        color: yellow;
        background: none;
    }

    @media screen and (max-width: 900px) {
        #zvitMenu {
            grid-template-columns: 35% 35% auto;
            padding: 10px 5px;
        }

        .perOther {
            grid-column: span 3;
            width: 100%;
        }

        #periodInputs {
            grid-column: span 3;
            padding: 0;
        }
    }

</style>