<?php
session_destroy();
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600);
session_start();

function  HIDE()
{
    if (!isset($_SESSION['logged'])) {
        session_start();
    }
    if ($_SESSION['logged'] != 'Administrator') {
        echo 'ТИМЧАСОВО НЕ ДОСТУПНО... СТОРІНКА В РОЗРОБЦІ!';
        exit;
    }
}

class SQLconn
{

    private $CONN;
    private $host = '127.0.0.1';
    private $database = 'sholompr_data';
    public $DB = "";
    private $user = 'sholompr_admin';
    private $password = 'R[$cB{&A5n]$';
    private $QUERYANS = null;

    function __construct($query = '', $keepAlive = false)
    {
        $this->connect();

        if (!empty($query)) {
            $this->QUERYANS = $this->query($query, $keepAlive);
        }
    }

    private function connect()
    {
        $this->CONN = mysqli_connect($this->host, $this->user, $this->password, $this->database);

        mysqli_query($this->CONN, "SET collation_connection = utf8_general_ci");
        mysqli_query($this->CONN, "SET NAMES utf8");
    }

    function query($q = '', $keepAlive = true)
    {
        $this->QUERYANS = null;

        $result = mysqli_query($this->CONN, $q);

        if ($result === true) return true;

        if (!empty($this->CONN->error)) {
            return $this->CONN->error;
        }

        $out_arr = array();

        if (mysqli_num_rows($result) > 0) {
            foreach ($result as $row) {
                $out_arr[] = $row;
            }
        }

        if (!$keepAlive) $this->close();

        $this->QUERYANS = $out_arr;

        return $out_arr;
    }

    function SELECT_DB($fields = "*", $q_end = "")
    {
        if (!empty($this->DB)) {
            return $this->SELECT($this->DB, $fields, $q_end);
        } else {
            return [];
        }
    }

    function SELECT($database, $fields = '*', $q_end = ''): array
    {
        $this->DB = $database;

        $flds = '';
        $fieldArr = array();

        if (!is_array($fields)) {
            if ($fields != '*') {
                $separator = " ";

                if (strpos($fields, ",") > 0) {
                    $separator = ",";
                }

                $tmpArr = array();
                $tmpArr = explode($separator, trim($fields));

                foreach ($tmpArr as $v) {
                    if (!empty($v)) {
                        $fieldArr[] = "`" . str_replace("`", "", trim($v)) . "`";
                    }
                }
            } else {
                $fieldArr[] = '*';
            }
        } else {
            foreach ($fields as $f) {
                $fieldArr[] = "`" . str_replace("`", "", trim($f)) . "`";
            }
        }

        foreach ($fieldArr as $f) {
            $flds .= trim($f) . " ";
        }

        $flds = str_replace(' ', ', ', trim($flds));

        $q = "SELECT " . $flds . " FROM `" . $database . "` " . $q_end;

        return $this->query($q);
    }

    function INSERT_DB(array $values, $OD_UPD_KEY = 1, $q_end = '')
    {
        if (!empty($this->DB)) {
            $this->INSERT($this->DB, $values, $OD_UPD_KEY, $q_end);
            return true;
        } else {
            return false;
        }
    }

    function INSERT($database, array $values, $OD_UPD_KEY = 1, $q_end = '')
    {
        $this->DB = $database;

        $q = "INSERT INTO `" . $database . "` ";
        $keys = "";
        $vals = "";

        $this->ArrayToCorrect($values);

        foreach ($values as $k => $v) {
            $keys .= $k . ", ";
            $vals .= $v . ", ";
        }

        $keys = "(" . substr(trim($keys), 0, strlen($keys) - 2) . ")";
        $vals = " VALUES (" . substr(trim($vals), 0, strlen($vals) - 2) . ")";

        $q .= $keys . $vals;

        if ($OD_UPD_KEY == 1) {

            $dublicate_id = $this->query('show columns from `' . $database . '` where `KEY` = "PRI"');

            if (array_key_exists('0', $dublicate_id)) {
                $q .= ' ON DUPLICATE KEY UPDATE ';
                $tmp = '';

                $DK = "`" . $dublicate_id[0]['Field'] . "`";

                foreach ($values as $k => $v) {
                    if ($k != $DK) {
                        $tmp .= $k . ' = ' . $v . ", ";
                    }
                }

                $q .= substr(trim($tmp), 0, strlen($tmp) - 2);
            }
        }

        $this->query($q . " " . trim($q_end));
    }

    private function OutSqlVal($_in)
    {
        if ($_in === null)
            $_in = "NULL";

        if (is_numeric($_in) || $_in == "NULL") {
            return $_in;
        } else {
            return "\"" . $_in . "\"";
        }
    }

    function UPDATE_DB(array $values, $q_end = '')
    {
        if (!empty($this->DB)) {
            $this->UPDATE($this->DB, $values, $q_end);
            return true;
        } else {
            return false;
        }
    }

    function UPDATE($database, array $values, $q_end = '')
    {
        $this->DB = $database;

        $q = 'UPDATE `' . $database . '` SET ';
        $tmp = '';

        $this->ArrayToCorrect($values);

        foreach ($values as $k => $v) {
            $tmp .= $k . " = " . $v . ", ";
        }

        $q .= substr(trim($tmp), 0, strlen($tmp) - 2);

        $this->query($q . " " . trim($q_end));
    }

    function DELETE_FROM_DB($where)
    {
        if (!empty($this->DB)) {
            $this->DELETE($this->DB, $where);
            return true;
        } else {
            return false;
        }
    }

    function DELETE($database, $where)
    {
        $this->DB = $database;

        $q = 'DELETE FROM `' . $database . '` WHERE ' . trim(str_ireplace("WHERE", "", $where));

        $this->query($q);
    }

    function CLEAR_DB()
    {
        if (!empty($this->DB)) {
            $this->CLEAR($this->DB);
            return true;
        } else {
            return false;
        }
    }

    function CLEAR($database)
    {
        $this->DB = $database;

        $q = 'DELETE FROM `' . $database . '`';
        $this->query($q);
    }

    function ArrayToCorrect(&$inputArr)
    {

        if (!is_array($inputArr)) return false;

        $out = array();

        $tmp = '';

        foreach ($inputArr as $k => $v) {
            if ($v !== null && $v !== "NULL") {
                if (!is_numeric($v)) {
                    $tmp = str_replace("`", "'", $v);
                    $tmp = str_replace('"', '\"', $tmp);

                    $out["`" . $k . "`"] = "\"" . $tmp . "\"";
                } else {
                    $out["`" . $k . "`"] = "\"" . $v . "\"";
                }
            } else {
                $out["`" . $k . "`"] = 'NULL';
            }
        }

        $inputArr = $out;
    }

    function TABLE($query = ''): HTEL
    {
        if (!empty($query)) {
            $this->query($query);
        }

        if ($this->QUERYANS === null || !is_array($this->QUERYANS) || count($this->QUERYANS) == 0)
            return new HTEL('table .=query_ans_table', [new HTEL('tbody', new HTEL('th/empty'))]);

        $arr = $this->QUERYANS;

        $cells = array();

        foreach ($arr as $row) {
            foreach ($row as $col => $val) {
                $cells[$col][] = new HTEL('td/[0]', $val);
            }
        }

        $tbody = new HTEL('tbody');

        $rows = array();

        $tr_head = new HTEL('tr');

        $tr = array();

        $counter = 0;

        foreach ($cells as $col => $val) {
            $tr_head(new HTEL('th/[0]', $col));

            $counter = 0;

            foreach ($val as $v) {
                if (!isset($tr[$counter]))
                    $tr[$counter] = new HTEL('tr');

                $tr[$counter]($v);

                $counter++;
            }
        }

        $tbody($tr_head);

        foreach ($tr as $r) {
            $rows[] = new HTEL('tr', $r);
        }

        $tbody($rows);

        //foreach($rows as $r){
        //	$tbody($r);
        //}

        return new HTEL('table .=query_ans_table', $tbody);
    }

    function close()
    {
        $this->CONN->close();
    }

    function __invoke($query = '', $keepAlive = true)
    {
        if (empty($query)) return $this->QUERYANS;
        return  $this->query($query, $keepAlive);
    }

    function ans()
    {
        return $this->QUERYANS;
    }
}

function phpAlert($msg, $location = '')
{
    if ($location == '') {
        echo '<script type="text/javascript">alert("' . $msg . '")</script>';
    } else {
        echo '<script language="javascript">alert("' . $msg . '");</script>';
        echo "<script>document.location = '$location'</script>";
    }
}

function console($msg)
{
    echo '<script type="text/javascript">console.log("' . $msg . '")</script>';
}

function strAbbr(string $in, $letCount = 3, $absoluteChrCount = 1): string
{
    if (strlen($in) <= $letCount) return $in;

    $let1 = array(
        'а',
        'е',
        'є',
        'и',
        'і',
        'ї',
        'о',
        'у',
        'ю',
        'ь',
        'ъ',
        'я',
        'a',
        'e',
        'i',
        'o',
        'u',
        'y'
    );

    $enc = 'UTF-8';

    $out = mb_substr($in, 0, $absoluteChrCount, $enc);

    $tmp = mb_substr($in, $absoluteChrCount);

    for ($i = 0; $i < strlen($tmp); $i++) {
        $s = mb_substr($tmp, $i, 1, $enc);

        if (!in_array(mb_strtolower($s, $enc), $let1)) {
            $out .= $s;
        }
    }

    if (strlen($out) >= $letCount) {
        return mb_substr($out, 0, $letCount, $enc);
    } else {
        return strAbbr($in, $letCount, $absoluteChrCount + 1);
    }
}

class MyColor
{
    public $ID;
    public $NAME;
    public $CSS_ANALOG;
    private $MAP;
    private $IS_DEF;

    function __construct($_id, $_name, $_map, $_css = '', $_isdef = 0)
    {
        $map = array();

        foreach ($_map as $m) {
            $map[$m['service_ID']][$m['type_ID']][$m['color_ID']] = true;
        }

        $this->ID = $_id;
        $this->NAME = $_name;
        $this->MAP = $map;
        $this->CSS_ANALOG = $_css;
        $this->IS_DEF = $_isdef != 0;
    }

    function AppleTo($servId, $type = 1): bool
    {
        if (isset($this->MAP[$servId])) {
            return $this->MAP[$servId][$type][$this->ID] ?? false;
        } else {
            return $this->Universal();
        }
    }

    function ANS($si): bool
    {
        return isset($this->MAP[$si]);
    }

    function Universal(): bool
    {
        return $this->IS_DEF;
    }

    function __toString(): string
    {
        return $this->NAME;
    }
}


class ZDATA
{
    public $ID = 0;
    public $SHOLOM_NUM = null;
    public $SOLD_NUM = null;
    public $DATE_IN;
    public $DATE_MAX;
    public $DATE_OUT;
    public $PHONE_OUT = '';
    public $PIP = '';
    public $REQ_OUT = '';
    public $TTN_IN = '';
    public $TTN_OUT = '';
    public $COMM = '';
    public $WORKER = '';
    public $REDAKTOR = '';
    public $KOMPLECT = array();
    public $CALLBACK = 0;
    public $DISCOUNT = null;

    function __construct($IN = null)
    {
        if (is_array($IN))
            $this->SET($IN);
        if (is_int($IN))
            $this->SET('ID', $IN);
    }

    public function SET($arr_or_servid, $color = null)
    {
        if (is_null($color) && is_array($arr_or_servid)) {
            $this->set_arr($arr_or_servid);
        } else if (!is_null($color)) {
            $this->set_arr(array($arr_or_servid => $color));
        }
    }

    private function set_arr(array $in)
    {
        if (isset($in['ID']))
            $this->ID = $in['ID'];
        if (isset($in['sholom_num']))
            $this->SHOLOM_NUM = $in['sholom_num'];
        if (isset($in['sold_number']))
            $this->SOLD_NUM = $in['sold_number'];
        if (isset($in['date_in']))
            $this->DATE_IN = $in['date_in'];
        if (isset($in['date_max']))
            $this->DATE_MAX = $in['date_max'];
        if (isset($in['date_out']))
            $this->DATE_OUT = $in['date_out'];
        if (isset($in['phone']))
            $this->PHONE_OUT = $in['phone'];
        if (isset($in['client_name']))
            $this->PIP = $in['client_name'];
        if (isset($in['reqv']))
            $this->REQ_OUT = $in['reqv'];
        if (isset($in['TTN_IN']))
            $this->TTN_IN = $in['TTN_IN'];
        if (isset($in['TTN_OUT']))
            $this->TTN_OUT = $in['TTN_OUT'];
        if (isset($in['comm']))
            $this->COMM = $in['comm'];
        if (isset($in['callback']))
            $this->CALLBACK = $in['callback'];
        if (isset($in['discount']))
            $this->DISCOUNT = $in['discount'];
        if (isset($in['worker']))
            $this->WORKER = $in['worker'];
        if (isset($in['redaktor']))
            $this->REDAKTOR = $in['redaktor'];

        if (isset($in['serv'])) {
            foreach ($in['serv'] as $id => $tp) {
                foreach ($tp as $t => $row) {
                    $this->KOMPLECT[$id][$t]['color'] = isset($row['color']) ? $row['color'] : -1;
                    $this->KOMPLECT[$id][$t]['count'] = isset($row['count']) ? $row['count'] : 1;
                    $this->KOMPLECT[$id][$t]['cost'] = isset($row['cost']) ? $row['cost'] : 0;
                }
            }
        }
    }


    public function GET_KOMPLECT($id = null, $name = 'cost', $type = 1): string
    { //ДЛЯ AJAX

        if (!is_null($id)) {
            if (isset($this->KOMPLECT[$id][$type][$name])) {
                return $this->KOMPLECT[$id][$type][$name];
            } else
                return '';
        }

        $out = '';

        if (count($this->KOMPLECT) > 0) {
            foreach ($this->KOMPLECT as $i => $iarr) {
                foreach ($iarr as $t => $row) {
                    if ($row['color'] != -1)
                        $out .= "&color_" . $i . "_" . $t . "=" . $row['color'];
                    $out .= "&count_" . $i . "_" . $t . "=" . $row['count'];
                    $out .= "&cost_" . $i . "_" . $t . "=" . $row['cost'];
                }
            }
        }

        return $out;
    }
}

class ZDATA2
{
    protected array $INFO;
    public $ID = 0;
    public $LOADED = false;
    public $DISCOUNT_IGNORE = false;
    public $CLOSED = false;
    public $TYPE = ZType::NONE;
    public $NUMBER_LABLE = "sholom_num";
    public $massangers = [
        '',
        'Телеграм',
        'Вотсап',
        'Інстаграм',
        'Вайбер',
        'Телефон',
        'Наручно',
        'Сигнал',
        'ТікТок'
    ];

    function __construct($id = 0, $type = ZType::NONE)
    {
        $this->TYPE = $type;
        $this->ID = $id;
        $this->LOAD($id);
    }

    protected function VALIDATE_LOAD($shol_num_val): bool
    {
        if ($this->TYPE == ZType::NONE)
            return true;

        $TABLE_LINE_TYPE = $shol_num_val !== null ? ZType::DEFF : ZType::SOLD;

        return $TABLE_LINE_TYPE == $this->TYPE;
    }

    function LOAD($id)
    {
        if ($id <= 0)
            return;

        $conn = new SQLconn();

        $result = $conn->SELECT('client_info', '*', 'WHERE ID = ' . $id);

        if (count($result) == 1 && $this->VALIDATE_LOAD($result[0]["sholom_num"])) {

            $this->LOADED = true;

            $this->INFO = $result[0];
            //service_number

            if ($this->INFO['sholom_num'] !== null) {
                $this->INFO['service_number'] = $this->INFO['sholom_num'];
                $this->NUMBER_LABLE = "sholom_num";
                $this->TYPE = ZType::DEFF;
            } else {
                $this->INFO['service_number'] = $this->INFO['sold_number'];
                $this->NUMBER_LABLE = "sold_number";
                $this->TYPE = ZType::SOLD;
            }

            if ($result[0]['date_out'] !== null)
                $this->CLOSED = true;

            // discount CHECK to ignore

            if (is_numeric($result[0]['discount'])) {
                $this->DISCOUNT_IGNORE = true;
            }

            //mess comm
            $temp = explode(' ', $this->INFO['comm']);

            if (count($temp) != 0) {
                foreach ($this->massangers as $ind => $m) {
                    if (strtolower($temp[0]) == strtolower($m)) {
                        $this->INFO['comm'] = substr($this->INFO['comm'], strlen($m) + 1);
                        $this->INFO['mess'] = $ind;
                        break;
                    }
                }
            }

            $result = $conn->SELECT('service_out', '*', 'WHERE ID = ' . $id);

            $this->INFO['SERVICES'] = $this->SetServ($result);
        }

        $conn->close();
    }

    protected function SetServ($res): array
    {
        $out = [];

        foreach ($res as $srv) {
            if ($srv['service_ID'] != 19) {
                $out[$srv['service_ID']] = [
                    'type_ID' => $srv['type_ID'] * 1,
                    'color' => $srv['color'] * 1,
                    'count' => $srv['count'] * 1,
                    'costs' => $srv['costs'] * 1
                ];
            } else {
                $out[19][$srv['type_ID'] * 1] = [
                    'count' => $srv['count'] * 1,
                    'costs' => $srv['costs'] * 1
                ];
            }
        }

        return $out;
    }

    function GET($name, $deffault = '')
    {
        return $this->INFO[$name] ?? $deffault;
    }

    function GET_SERVICE($id)
    {
        return $this->INFO['SERVICES'][$id] ?? null;
    }

    function GET_SERVICES(): array
    {
        return $this->INFO['SERVICES'] ?? [];
    }

    function ToArray(): array
    {
        return $this->INFO;
    }

    function JSONArray()
    {

        if (is_array($this->INFO)) {
            return json_encode($this->INFO);
        } else {
            return "{}";
        }
    }
}

abstract class ZType
{
    const NONE = 0;
    const DEFF = 2;
    const DEFF_A = 1;
    const SOLD = 8;
    const SOLD_A = 4;
}

abstract class ZStatus
{
    const NONE = -1;
    const NEWZ = 0;
    const INWORK = 1;
    const CLOSED = 2;
}

function dateToNorm($in, $short = false, $time = false, $sec = false): string
{
    if (is_null($in)) {
        return '';
    }

    $myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $in);
    if ($myDateTime === false) {
        $time = false;
        $myDateTime = DateTime::createFromFormat('Y-m-d', $in);
        if ($myDateTime === false)
            return $in;
    }

    $out = '';

    if ($short) {
        $out = $myDateTime->format('d.m.y');
    } else {
        $out = $myDateTime->format('d.m.Y');
    }

    if ($time) {
        if ($sec) {
            $out .= $myDateTime->format(' [H:i:s]');
        } else {
            $out .= $myDateTime->format(' [H:i]');
        }
    }

    return $out;
}

function sumArray($in): float
{
    $out = 0;

    foreach ($in as $i) {
        $out += !is_array($i) ? $i : sumArray($i);
    }

    return $out;
}

function countArraysKey($in, array $ignoreKeys = []): int
{

    $out = 0;

    foreach ($in as $k => $v) {
        if (!is_array($v)) {
            if (!in_array($k, $ignoreKeys)) {
                $out += 1;
            }
        } else {
            $out += countArraysKey($v, $ignoreKeys);
        }
    }

    return $out;
}

function CostOut($in, $_nul_val = '0.00'): string
{
    //Валідація сум
    $out = str_replace(',', '.', $in);
    $out = str_replace(' ', '', $out);

    if (is_numeric($out)) {

        $com = strpos($out, '.');

        if ($com > -1) {
            switch (strlen($out) - $com) {
                case 1:
                    return str_pad($out, strlen($out) + 2, '0', STR_PAD_RIGHT) ?? $_nul_val;
                case 2:
                    return str_pad($out, strlen($out) + 1, '0', STR_PAD_RIGHT) ?? $_nul_val;
                case 3:
                    return $out ?? $_nul_val;
                default:
                    return substr($out, 0, $com + 3) ?? $_nul_val;
            }
        } else {
            return $out != 0 ? $out . ".00" : $_nul_val;
        }
    }

    return $_nul_val;
}

function inclAttr($atr, $in): bool
{

    if ($in == 0 || $atr == $in)
        return true;
    else if ($in < 0)
        return false;

    $arr = array();
    $ost = $in % 2;
    $step = 1;

    for ($i = $in; $step <= $in; $i = ($i - $ost) / 2) {
        $ost = $i % 2;

        $arr[$step] = $ost;

        $step *= 2;
    }

    return isset($arr[$atr]) ? $arr[$atr] == 1 : false;
}

class HTEL
{

    private $include_arr = array();

    public $LEVEL = 0;

    public $TEXT = '';

    private $element_type = '';

    private $element_args = array();

    public $VARS = array();

    private $IS_EMPTY = false;

    function __construct(string $input = '', $variables_incl = null)
    {

        if (trim($input) == '') {
            $this->IS_EMPTY = true;
            return;
        }

        if (!is_null($variables_incl)) {
            if (is_array($variables_incl)) {
                foreach ($variables_incl as $k => $vars) {
                    if (is_array($vars)) {
                        foreach ($vars as $kk => $in_arr) {
                            $this->VARS[$kk] = $in_arr;
                        }
                    } else if ($vars instanceof HTEL) {
                        $this->_include($vars);
                    } else {
                        $this->VARS[$k] = $vars;
                    }
                }
            } else {
                if (!is_null($variables_incl) && $variables_incl instanceof HTEL) {
                    $this->_include($variables_incl);
                } else {
                    $this->VARS[0] = $variables_incl;
                }
            }
        }
        //else {
        //    $this->VARS[0] = null;
        //}

        $text_split = explode('/', $input);

        if (count($text_split) > 1) {
            $input = $text_split[0];
            $this->TEXT = $text_split[1];
            for ($i = 2; $i < count($text_split); $i++)
                $this->TEXT .= '/' . $text_split[$i];
        }

        $abbr = array();
        $abbr['.'] = 'class';
        $abbr['!'] = 'id';
        $abbr['@'] = 'href';
        $abbr['~'] = 'url';
        $abbr['?'] = 'name';
        $abbr['#'] = 'value';
        $abbr['##'] = 'data-value';
        $abbr['*'] = 'type';
        $abbr['&'] = 'style';
        $abbr['$'] = 'placeholder';

        while (strpos($input, '  ') != false) {
            $input = str_replace('  ', ' ', $input);
        }

        $input = trim($input);

        $input = str_replace(array(' = ', '= ', ' ='), '=', $input);
        $input = str_replace(array(' + ', '+ ', ' +'), '+', $input);
        $input = str_replace('[ ', '[', $input);
        $input = str_replace(' ]', ']', $input);

        $input = str_replace('[[', '%1[', $input);
        $input = str_replace(']]', ']%2', $input);

        $input = str_replace('[r]', 'required', $input);
        $input = str_replace('[s]', 'selected', $input);
        $input = str_replace('[c]', 'checked', $input);
        $input = str_replace('[ro]', 'readonly', $input);
        $input = str_replace('[d]', 'disabled', $input);
        $input = str_replace('[h]', 'hidden', $input);
        //placeholder

        //---------------------------------------

        $input = str_replace('==', '~', $input);

        $out = explode(' ', $input);

        $this->element_type = $out[0];

        for ($i = 1; $i < count($out); $i++) { //

            $val = explode('=', $out[$i]);

            $changed = false;

            $val[1] = $val[1] ?? '';
            $val[1] = str_replace('+', ' ', $val[1]);
            $val[1] = str_replace('~', '=', $val[1]);

            foreach ($abbr as $k => $v) {
                if ($val[0] == $k) {

                    $this->element_args[$v] = $val[1];

                    $changed = true;
                    break;
                }
            }

            if (!$changed && !empty($val[0])) {
                $this->element_args[$val[0]] = $val[1];
            }
        }

        $this->IS_EMPTY = false;
    }

    function setAtr($atr_name, $val, $append = false)
    {

        if ($append) {
            if (isset($this->element_args[$atr_name])) {
                $this->element_args[$atr_name] .= $val;
            } else {
                $this->element_args[$atr_name] = $val;
            }
        } else {
            $this->element_args[$atr_name] = $val;
        }
    }

    private function _sendGlobVars($vars)
    {
        if (is_array($vars)) {
            foreach ($vars as $k => $v) {
                if (!isset($this->VARS[$k]) || is_null($this->VARS[$k])) {
                    //
                    $this->VARS[$k] = $v;
                }
            }
        } else {
            $this->VARS[0] = $vars;
        }
    }

    private function _include($input)
    {
        if (!is_array($input)) {
            $this->include_arr[] = $input;
        } else {
            foreach ($input as $in) {
                $this->include_arr[] = $in;
            }
        }
    }

    function __invoke($include)
    {
        if (!$this->IS_EMPTY) {
            $this->_include($include);
        } else if (is_string($include)) {
            $this->__construct($include);
        }
    }

    function _tab($val = 0): string
    {
        $tab = "\t\t";

        for ($i = 0; $i < ($this->LEVEL + $val); $i++) {
            $tab .= "\t\t";
        }

        return $tab;
    }

    function GetChildren(): string
    {
        $out = '';

        foreach ($this->include_arr as $in) {
            $out .= $in;
        }

        return $out;
    }

    function childCount(): int
    {
        return count($this->include_arr);
    }

    function __toString()
    {

        if ($this->IS_EMPTY)
            return '';

        $closer = array('<', '>', '</', ' />');

        $this->_setVars();

        $out = $this->_tab() . $closer[0] . $this->element_type;

        $TEXT = $this->clearEmptyVars($this->TEXT);

        foreach ($this->element_args as $arg => $val) {
            $val = str_replace('%1', '[', $val);
            $val = str_replace('%2', ']', $val);

            $out .= ' ' . $arg . '="' . $val . '"';
        }

        switch ($this->element_type) {
            case 'input':
                $out .= $closer[3] . $TEXT;
                break;
            case 'textarea':
                $out .= $closer[3] . $TEXT . $closer[2] . $this->element_type . $closer[1] . PHP_EOL;
                break;
            default:
                $out .= $closer[1];
                if ($TEXT != '') {
                    $out .= PHP_EOL . $this->_tab(1) . $TEXT;
                }

                foreach ($this->include_arr as $el) {
                    $el->LEVEL = $this->LEVEL + 1;
                    $out .= PHP_EOL . $el;
                }
                $out .=  PHP_EOL . $this->_tab() . $closer[2] . $this->element_type . $closer[1];
                break;
        }

        return PHP_EOL . $out;
    }

    private function _setVars()
    {
        if (!is_null($this->VARS))
            foreach ($this->VARS as $id => $chn) {

                if (isset($this->element_args['[' . $id . ']'])) {
                    if ($chn != '') {
                        $this->element_args[$chn] = $this->element_args['[' . $id . ']'];
                    }
                    unset($this->element_args['[' . $id . ']']);
                }

                $this->TEXT = $this->TEXT != '' ? str_replace('[' . $id . ']', $chn, $this->TEXT) : '';

                foreach ($this->element_args as $a => $arg) {
                    $this->element_args[$a] = str_replace('[' . $id . ']', $chn, $arg);
                }

                for ($i = 0; $i < count($this->include_arr); $i++) {
                    $this->include_arr[$i]->_sendGlobVars($this->VARS);
                }
            }
    }

    function clearEmptyVars($in): string
    { //[0] ~ [..]
        $out = $in;

        for ($i = 0; $i < 10; $i++) {
            $out = str_replace('[' . $i . ']', '', $out);
        }

        return $out;
    }
}

function _requestSend(array $arr): string
{
    $out = '';

    foreach ($arr as $k => $v) {
        $out .= '&' . $k . '=' . $v;
    }

    return $out;
}

class ServiceInfo
{
    public $ID = -1;
    public $NAME = "";
    public $HAS_TYPES = false;
    public $TYPES = array();
    public $HAS_COLOR = false;
    public $COLORS = array();
    function __construct($id, $name, $has_color = 0, array $types = [], array $colors = [])
    {
        $this->ID = intval($id);
        $this->NAME = $name;
        $this->TYPES = $types;
        $this->HAS_COLOR = $has_color == 1 ? true : false;
        if (count($types) > 0)
            $this->HAS_TYPES = true;
        $this->COLORS = $colors;
    }
}

?>

<script>
    setInterval(function() {
        fetch('/ping.php');
    }, 55 * 60 * 1000);
</script>