<?php
require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

$head_buttons = new HTEL('div .=set_buttons', [
   new HTEL('button !=set_services/СЕРВІСИ'),
   new HTEL('button !=set_colors/КОЛЬОРИ')
]);

$_id  = $_GET['id'] ?? 'none';

echo '<script>var ID =  "' . $_id . '";</script>' ;

echo $head_buttons;
echo new HTEL('div !=set_workfield');
?>

<script>

    $('#set_services').on('click', SHOW_SERVICES);
    $('#set_colors').on('click', SHOW_COLORS);
    
    SHOW_SERVICES();

    function SHOW_SERVICES() {
        $.ajax({
            url: 'blok/set/services.php',
            method: 'GET',
            data:  'id=' + ID,
            success: function (data) {
                $('#set_workfield').html(data);
            }
        });
    }
    
    function SHOW_COLORS() {
        $.get('blok/set/colors.php', '', 
        function (responce) {
                $('#set_workfield').html(responce);
        });
    }

</script>

<style>

.set_buttons{
   width: 100%;
   display: inline-flex;
   justify-content: space-between;
   align-items: center;
   padding: 5px 5%;
}

    .set_buttons button{
         width: 45%;
    }

#set_info{
margin: 10px;
padding: 20px;
border: 3px solid blue;
border-radius: 40px 0 0 0;
background-color: rgb(255, 244, 0 , 0.55);
}
    
    #set_info > select, #set_info > input[type=text]{
        background: linear-gradient(to left, olive, yellow);
        font-size: 120%;
        border-radius: 40px;
        padding: 10px;
    }

        #set_info > select{
            text-transform: uppercase;
            color: red;
            font-weight: bold;
        }

        #set_info input[type=text]{
            color: darkred;
            font-style: italic;
        }

            #set_info > select option{
                  color: darkkhaki;
                  font-size: 100%;
                  font-style: italic;
                  text-transform: none;
            }

            #set_info > select option[serv=new]{
                  color: khaki;
                  font-size: 120%;
                  font-style: normal;
                  font-weight: bold;
                  background-color: green;
                  padding: 0 5px;
                  text-transform: uppercase;
            }

                #set_info fieldset{
                     margin-top: 20px;
                }
</style>
