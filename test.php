<!DOCTYPE HTML>

<html>

<head>
    <title>SholomProMax</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" type="text/css" href="style.css" />
    <link rel="icon" type="image/x-icon" href="/img/favicon.ico" />

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="/js/myJS.js"></script>

</head>

<body>

    <?php

    require "blok/header.php";

    require_once $_SERVER['DOCUMENT_ROOT'] . "/class/universal.php";

    //$_GET['ID'] = 1977;
    //$_GET['hideForWorker'] = 0;
    //$_GET['type'] = "archiv";//archive
    //$_GET['variant'] = "def";

    //require "test_print.php";
    require "create_SOLD.php"; //ЗАЛИШИТИ
    
    //$inp = new HTEL('input !=explain');
    //$subm = new HTEL('button *=submit !=subm/+');
    //$h = new HTEL('label !=h/FindByString getCities getWarehouses WarehouseId TypeOfWarehouse AreaDescription CityID');
    //$ans = new HTEL("textarea !=ans [ro]");

    //$form = new HTEL("form onsubmit=return+false", [
    //    $h, $inp, $subm, $ans
    //]);

    //echo $form;

    ?>

<style>

/*form > * {
display: flex;*/
/*width: 100%;*/
/*align-items: center;
justify-content: center;
}

#explain{
width: 50%;
}
#subm{
width: 100px;
}
#ans{
width: 100%;
word-wrap: break-word;
resize:vertical;
min-height: 800px;
font-size: 8px;
}*/
</style>
<script>

    //var NP = new NovaPay('4e4de3b4d068a37e30e0da387a049415');

    //NP.TEST();

    //var ans = {};

    //$("form").submit(() => {
    //    $("#ans").html('');

    //    //let a = JSON.stringify(NP.TEST($("#explain").val()));

    //    //$("#ans").html(a);

    //    console.clear();

    //    console.log(NP.TEST($("#explain").val()));
    //});

</script>
</body>

</html>

