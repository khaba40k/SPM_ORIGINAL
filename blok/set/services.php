
<script>

    $('#set_info').on('change', '#serv', function () {
        SET_BY_ID($(this).val());
    });

    function SET_ATTR_TO_RMW_LABLE(_val = '', _attr = 'hidden') {
        var _input_plh = $('#serv').find('option:selected').attr('serv');

        if (_input_plh == 'new') {
            $('#remove_id_cont').attr(_attr, _val);
            return;
        }

        if (_val == '') {
            $('#remove_id_cont').removeAttr(_attr);
        } else {
            $('#remove_id_cont').attr(_attr, _val);
        }
    }

    function  SET_BY_ID(_id){
        var _serv_name = $('#serv').find('option:selected').text();
        var _input_plh = $('#serv').find('option:selected').attr('serv');

        if (_input_plh == 'old') {
            $('#new_name').attr('placeholder', 'нова назва');
            $('#new_name').removeAttr('required');
            $('#del_serv').removeAttr('hidden');
        } else {
            $('#new_name').attr('placeholder', 'назва');
            $('#new_name').attr('required', '');
            $('#del_serv').attr('hidden', '');
        }

        $.ajax({
            url: 'blok/set/serv_one_info.php',
            method: 'GET',
            dataType: 'html',
            data: 'ID=' + _id + '&NAME=' + _serv_name,
            success: function (data) {
                $('#workset').html(data);
            }
        });
    }

    $('#set_info').submit(function (evt) {
        if (!confirm('Зберегти зміни?')) return false;

        evt.preventDefault();

        var form = $(this).closest("form");;
        var formData = new FormData(form[0]);
        var href  =  window.location.href;
        href = href.split('?')[0] + '?page=set&id=' + $('#serv').val();

        var _input_plh = $('#serv').find('option:selected');


        if (_input_plh.attr('serv') == 'new') {
            var _curVal = _input_plh.val();
            _input_plh.val(_curVal + 1);
        }

        $.ajax({
            url: "blok/set/save_set.php",
            type: "POST",
            processData: false,
            contentType: false,
            dataType: 'html',
            data:  formData,
            success: function(data) {
                $('#temp_ans').html(data);
                window.location.href = href;
            }
        });

    });

</script>

<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

    $selected_id  = $_GET['id'] ?? 'none';

    $conn = new SQLconn();

    $service_list = $conn('select ID, NAME from service_ids WHERE atr >= 0 ORDER BY `order`');

    $container = new HTEL('form !=set_info onsubmit=return+false');

    $status = $selected_id == 'none' ? 'selected':'';

    $select = new HTEL('select !=serv ?=service_id', new HTEL('option # [0] [d]/послуга/компл...', $status));

    $arr_ind = array();

    $arr_ind =  $conn('select max(ID) from service_ids');

    $last_ind = $arr_ind[0]['max(ID)'] + 1;

    $select(new HTEL('option #=[0] serv=new/[1]', [$last_ind, '+ Додати']));

    foreach($service_list as $serv){
        if ($selected_id != 'none' && $serv['ID'] == $selected_id){
             $status = 'selected';
        }else  {
             $status = '';
        }
        $select(new HTEL('option #=[0] serv=old [2]/[1]', [$serv['ID'], $serv['NAME'], $status]));
    }

    $container([
       $select,
       new HTEL('input *=text min=3 !=new_name ?=rename $=[0] pattern=[1]', ['нова назва', '^[^ ].+[^ ]$']),
       new HTEL('label for=remove_serv !=remove_id_cont .=del_type [h]/Видалити', new HTEL('input *=checkbox !=remove_serv ?=remove_service #=1')),
       new HTEL('div !=workset')
    ]);

    $conn->close();

    echo $container;

    if ($selected_id != 'none' && is_numeric($selected_id)){
        echo '<script>SET_BY_ID('.$selected_id.');</script>';
    }
?>