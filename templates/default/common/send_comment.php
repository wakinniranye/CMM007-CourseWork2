<?php
debug_backtrace() || die ('Direct access not permitted');

if(isset($_POST['send_comment'])){

    $msg_error = '';
    $msg_success = '';
    $field_notice = array();
    
    if(PMS_CAPTCHA_PKEY != '' && PMS_CAPTCHA_SKEY != ''){
        require(SYSBASE.'includes/recaptchalib.php');
    
        $secret = PMS_CAPTCHA_SKEY;
        $response = null;
        $reCaptcha = new ReCaptcha($secret);
        if(isset($_POST['g-recaptcha-response']))
            $response = $reCaptcha->verifyResponse($_SERVER['REMOTE_ADDR'], $_POST['g-recaptcha-response']);
        if($response == null || !$response->success) $field_notice['captcha'] = $pms_texts['INVALID_CAPTCHA_CODE'];
    }
    
    $name = html_entity_decode($_POST['name'], ENT_QUOTES, 'UTF-8');
    $email = html_entity_decode($_POST['email'], ENT_QUOTES, 'UTF-8');
    $msg = html_entity_decode($_POST['msg'], ENT_QUOTES, 'UTF-8');
    $item_id = $_POST['item_id'];
    $item_type = $_POST['item_type'];
    
    $rating = (isset($_POST['rating'])) ? $_POST['rating'] : false;
    
    if($name == '') $field_notice['name'] = $pms_texts['REQUIRED_FIELD'];
    if($msg == '') $field_notice['msg'] = $pms_texts['REQUIRED_FIELD'];
    if($rating !== false && (!is_numeric($rating) || $rating < 0 || $rating > 5)) $rating = null;
    
    if($email == '' || !preg_match('/^[\w.-]+@[\w.-]+\.[a-zA-Z]{2,6}$/i', $email)) $field_notice['email'] = $pms_texts['INVALID_EMAIL'];
    
    if(!isset($_COOKIE['COMMENT_'.$item_type.'_'.$item_id])){
        $result_rating = $pms_db->query('SELECT * FROM pm_comment WHERE item_type = '.$pms_db->quote($item_type).' AND id_item = '.$pms_db->quote($item_id).' AND rating > 0 AND rating <= 5 AND (UPPER(email) = '.$pms_db->quote(mb_strtoupper($email, 'UTF-8')).' OR ip = '.$pms_db->quote($_SERVER['REMOTE_ADDR']).')');
        if($result_rating === false || $pms_db->last_row_count() > 0)
            $rating = null;
    }else
        $rating = null;

    if(is_numeric($item_id) && count($field_notice) == 0){
        
        $data = array();
        $data['id_item'] = $item_id;
        $data['item_type'] = $item_type;
        $data['name'] = $name;
        $data['email'] = $email;
        $data['msg'] = $msg;
        $data['checked'] = 0;
        $data['add_date'] = time();
        $data['ip'] = $_SERVER['REMOTE_ADDR'];
        
        if($rating !== false) $data['rating'] = $rating;
        
        $result_insert = pms_db_prepareInsert($pms_db, 'pm_comment', $data);
        
        if($result_insert->execute() !== false){
            if($rating !== false && $rating > 0 && $rating <= 5) setcookie('COMMENT_'.$item_type.'_'.$item_id, 1, time()+2592000);
    
            $msg_success .= $pms_texts['COMMENT_SUCCESS'];
    
            $mailContent = 'Name: '.$name.'<br>'."\n\n";
            $mailContent .= 'E-mail: '.$email.'<br>'."\n\n";
            if($rating > 0) $mailContent .= 'Rating: '.$rating.'/5<br>'."\n\n";
            $mailContent .= '<b>Message:</b><br>'.nl2br($msg)."\n\n";
            
            if(!pms_sendMail(PMS_EMAIL, PMS_OWNER, 'New comment', $mailContent, $email, $name))
                $msg_error .= $pms_texts['MAIL_DELIVERY_FAILURE'];
        }
    }else
        $msg_error .= $pms_texts['FORM_ERRORS'];
    
}else{
    $name = '';
    $email = '';
    $msg = '';
    $rating = 0;
}
