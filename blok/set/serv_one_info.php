<script>
    var LastTypeArr = [];

    function TypeAppend(_Types = [], _Append = false) {

        var ID = $('#serv').val();
        var inpData = $('#attr_info :input').serialize();

        inpData += '&serv_ID=' + ID;

        var counter = 0;

        if (_Append == false) {
            $('#types_info').empty();
            LastTypeArr = _Types;
        } else {
            counter = LastTypeArr.length;
        }

        var ap = 0;

        if (_Append) ap = 1;

        $.each(_Types, function (index, value) {
              counter++;
              if(_Append) LastTypeArr.push(value);

              $.ajax({
                  url: "blok/set/serv_one_type_blok.php",
                  type: "GET",
                  async: false,
                  dataType: 'html',
                  data: inpData + '&type_ID=' +
                      value[0] + '&type_name=' +
                      value[1] + '&counter=' + counter +
                      '&placeholder=' + value[2] +
                      '&append=' + ap,
                  success: function (data) {
                       $('#types_info').append(data);
                  }
              });
        });

        CreateTypeCheck();
    }

    $('#set_info').on('change keyup paste', '.pid_type > input[type=text]', function () {
        var _type = $(this).attr('st');
        var _text = $(this).val();

        $.each(LastTypeArr, function (index, value) {
            if (value[0] == _type) {
                LastTypeArr[index][1] = _text;
                return true;
            }
        });

        CreateTypeCheck();
    });

    $('#set_info').on('click', '.del_append_type', function () {
        var _type = $(this).val();

        $(this).parent().empty();

        var removeInd = -1;

        $.each(LastTypeArr, function (i, v) {
            if (v[0] == _type) {
                removeInd = i;
                return true;
            }
        });

        LastTypeArr.splice(removeInd, 1);

        var lbls = $('.type_counter');

        $.each(lbls, function (index) {
                $(this).text((index + 1) + '.');
        });
    });

    function _sort_types(a, b) {
        return a[0] - b[0];
    }

    function CreateTypeCheck() {
        var _apply = true;

        $.each(LastTypeArr, function (i, v) {
            if (v[1].trim() == '') {
                _apply = false;
                return false;
            }
        });

        if (LastTypeArr.length == 1) {
            $('.pid_type > input[type=text]').removeAttr('required');
        } else {
            $('.pid_type > input[type=text]').attr('required', '');
        }

        if (_apply) {
            $('#add_type').removeAttr('hidden');
        } else {
            $('#add_type').attr('hidden', '');
        }
    }

    //function Refr() {
    //    TypeAppend(LastTypeArr);
    //}

</script>

<?php
    $SERV_ID = $_GET['ID'] ?? null;
    $SERV_NAME = $_GET['NAME'] ?? '';

    if ($SERV_ID === null || $SERV_ID == "") exit;

    require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

    $service_types = [
        1=>'Замовлення',
        2=>'Замовлення (з адмінки)',
        4=>'Продаж',
        8=>'Продаж (з адмінки)',
        16=>'Витрати'
    ];

    $conn = new SQLconn();

    $divOut = new HTEL('div');
    //Список категорій

    $fsCat = new HTEL('fieldset !=cat_info .=cat_info', new HTEL('legend/Категорії'));

    $result = $conn('SELECT cat FROM category_map WHERE service_ID = '. $SERV_ID);

    $categories_applyed = array();

    foreach ($result as $r){
         $categories_applyed[] = $r["cat"];
    }

    $result = $conn->SELECT("categories");

    $has_cat_val = "";
    $not_cat_val = "checked";

    foreach ($result as $r){
        
        if (count($categories_applyed) > 0){
            if (in_array($r["ID"], $categories_applyed)) {
                $has_cat_val = "checked";
                $not_cat_val = "";
            } else {
                $not_cat_val = "checked";
                $has_cat_val = "";
            }
        }

        $fsCat(
        new HTEL('div .=radio_div',[
             1=>$r["ID"],
             $r["category"],
             new HTEL('label .=opt_checker for=cat_[1]/[2]'),
             new HTEL('label .=radio_chkr for=cat_[1]/так', new HTEL('input *=radio !=cat_[1] ?=cat[[1]] .=colorradio #=1 [0]', $has_cat_val)),
             new HTEL('label .=radio_chkr for=cat_0_[1]/ні', new HTEL('input *=radio !=cat_0_[1] ?=cat[[1]] .=colorradio #=0 [0]', $not_cat_val))
        ]));

    }

    $divOut($fsCat);

    //Список атрибутів сервісу

    $fsAttr = new HTEL('fieldset !=attr_info .=attr_info', new HTEL('legend/Атрибути'));

    $result = $conn('SELECT atr, color FROM service_ids WHERE ID = '. $SERV_ID);

    $atr = $result[0]['atr'] ?? -1;

    $colHas = $result[0]['color'] ?? 0;

    $has_color = $colHas == 1 ? 'checked' : '';
    $not_has_color = $colHas != 1 ? 'checked' : '';

    $usingColor = array();
    $usingColor['in'] = $conn('SELECT * FROM service_in WHERE service_ID = '
            . $SERV_ID . ' LIMIT 1');
    $usingColor['out'] = $conn('SELECT * FROM service_out WHERE service_ID = '
            . $SERV_ID . ' LIMIT 1');
    $usingColor['ans'] = (count($usingColor['in']) + count($usingColor['out'])) > 0 ? 'hidden':'';

    echo '<script>SET_ATTR_TO_RMW_LABLE("'.$usingColor['ans'].'");</script>';

    $counter = 0;

    foreach ($service_types as $a=>$an){
        $stat_yes = inclAttr($a, $atr) ? 'checked':'';
        $stat_no = inclAttr($a, $atr) ? '':'checked';

        $rnd_id_yes = rand(100000, 999999);
        $rnd_id_no = rand(100000, 999999);

        $using = '';

        switch($a){
            case 16://Чи були витрати по ІД
                $ans = count($conn('SELECT * FROM service_in WHERE service_ID='.$SERV_ID.' LIMIT 1')) == 1;

                if ($ans){
                    $using = 'hidden';
                    $stat_yes = 'checked';
                    $stat_no = '';
                }
                break;
        }

        $fsAttr(new HTEL('div .=radio_div',[
              1=>$rnd_id_yes,
              $rnd_id_no,
              $a,
              $counter++,
              new HTEL('label .=opt_checker for=[1]/[0]', $an),
              new HTEL('label .=radio_chkr for=[1]/так', new HTEL('input *=radio !=[1] ?=atr[[4]] atrid=[3] .=attrradio #=[3] [0]', $stat_yes)),
              new HTEL('label .=radio_chkr for=[2] [0]/ні',[$using, new HTEL('input *=radio !=[2] ?=atr[[4]] atrid=[3] .=attrradio #=0 [0]', $stat_no)])
        ]));
    }

    $fsAttr(new HTEL('div .=radio_div',[
        $usingColor['ans'],
        new HTEL('label .=opt_checker for=color_1/Колір'),
        new HTEL('label .=radio_chkr for=color_1/так', new HTEL('input *=radio !=color_1 ?=has_color .=colorradio #=1 [0]', $has_color)),
        new HTEL('label .=radio_chkr for=color_0 [0]/ні', [1=>new HTEL('input *=radio !=color_0 ?=has_color .=colorradio #=0 [0]', $not_has_color)])
    ]));

    $only_exp = $atr == 16;

    $divOut($fsAttr);

    //if (!$only_exp){
    //    //Список типів сервісу/кольорів/зображення

    $fsTypes = new HTEL('fieldset .=types_info', [
          new HTEL('legend/Підтипи'),
          new HTEL('div !=types_info'),
          new HTEL('button *=button !=add_type/[0]', '+')
    ]);

    $type_arr = $conn('SELECT type_ID, name FROM type_ids WHERE service_ID = '. $SERV_ID . ' ORDER BY type_ID ASC');
    $temp = array();

    foreach($type_arr as $arr){
        $temp[] =[$arr['type_ID'], $arr['name'], ''];
    }

    if (count($temp) == 0){
        $temp[] =[1, '', 'основний'];
    }

    $script = new HTEL('script/TypeAppend([0]);' , json_encode($temp));

    $conn->close();

    $divOut([
            $fsTypes,
            new HTEL('button *=submit !=save_but/ЗБЕРЕГТИ'),
            new HTEL('div !=temp_ans'),
            $script
    ]);

    echo $divOut;

?>

<script>
    $('#attr_info .colorradio').on('change', function () {
        //Refr();
        TypeAppend(LastTypeArr);
    });

    $('#add_type').on('click', function () {
        var _newType = 2;

        $.each(LastTypeArr, function (ind, val) {
            if (val[0] >= _newType) _newType = (parseInt(val[0]) + 1);
        });

        TypeAppend([[_newType, '', 'назва типу']], true);
    });
</script>

<style>

.hide_color{
      display: none;
}

.types_info{
   padding: 20px;
}

.img_col{
   margin-left: 50px;
   padding: 10px 0;
   display: grid;
   justify-items: start;
   align-items: center;
   grid-template-columns: 100px 20px 100px;
   grid-gap: 10px;
}

    .img_col img{
       border: 5px double blue;
       padding: 4px;
       border-radius: 4px;
       height: 64px;
       width: 64px;
    }

.pid_type button{
     height: 100%;
     width: 40px;
     margin: 0;
     padding: 0;
     background: none;
     border: 2px double green;
}

#set_info{
   margin-top: 20px;
   padding-top: 20px;
   border-top: 5px dotted red; 
}

.del_type{
    color: red;
    font-weight: bold;
    border: 2px dotted red;
}

.radio_div{
   display: inline-flex;
   justify-content: center;
   align-items:center;
   width: auto;
   margin: 3px 7px;
   border: 2px solid green;
   border-radius: 15px 0 0 15px;
   background-color: white;
   color: blue;
   padding: 4px 8px;
}

    .radio_div label.opt_checker{
        padding: 2px 5px;
        border-radius: 10px;
        color: green;
        margin-right: 20px;
    }

    .radio_div label.radio_chkr{
        padding: 3px;
        color: darkgrey;
        border-top: 5px solid green;
        padding: 2px;
        min-width: 35px;
        text-align: center;
    }

    .radio_div label:last-child{
        border-top: 5px solid red;
    }

    .radio_div label:has(>input:checked){
        background-color: green;
        color: white;
    }

    .radio_div label:last-child:has(>input:checked){
        background-color: red;
    }

    .radio_div input[type=radio]{
        display: none;
    }

    #add_type{
      height: 40px;
      text-align: center;
      padding: 0 35px;
    }

            .img_file_cont{
                position: relative;
                width:64px;
                height:64px;
            }
            
            .img_file_cont label{
                position: absolute;
                width: 100%;
                height: 100%;
                background-size: 64px 64px;
                cursor: pointer;
            }
            
            .img_file_cont input{
                display: none;
            }
</style>