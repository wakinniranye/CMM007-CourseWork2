<?php
require_once('../common/lib.php');
require_once('../common/define.php');
    
if(isset($pms_db) && $pms_db !== false){
    
    if(isset($_GET['curr']) && is_numeric($_GET['curr']) > 0){

        $curr_id = $_GET['curr'];
        $rate = '';

        $result_currency = $pms_db->query('SELECT * FROM pm_currency WHERE id = '.$curr_id);
        if($result_currency !== false && $pms_db->last_row_count() > 0){
            $row = $result_currency->fetch();
            $code = $row['code'];
            $sign = $row['sign'];

            if($code == PMS_DEFAULT_CURRENCY_CODE)
                $rate = 1;
            else{
                
                $query = PMS_DEFAULT_CURRENCY_CODE.'_'.$code;
                
                if(!isset($_SESSION[$query])){

                    $json = file_get_contents('https://free.currencyconverterapi.com/api/v6/convert?q='.$query.'&compact=ultra&apiKey='.PMS_CURRENCY_CONVERTER_KEY);
                    $obj = json_decode($json, true);
                    
                    $rate = floatval($obj[$query]);
                    
                    $_SESSION[$query] = $rate;
                }else
                    $rate = $_SESSION[$query];
            }
            if(is_numeric($rate)){
                $_SESSION['currency']['rate'] = $rate;
                $_SESSION['currency']['code'] = $code;
                $_SESSION['currency']['sign'] = $sign;
            }
        }
    }
}
