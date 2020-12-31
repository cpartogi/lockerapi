<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Helpers\WebCurl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;


class SMSCaseController extends Controller{
    var $curl;

    public function __construct(){
        $headers = ['Content-Type: application/json'];
        $this->curl = new WebCurl($headers);
    }

    // function from old app
    protected $headers = ['Content-Type: application/json'];
    protected $is_post = 0;

    //condition handle in case emergency => wahyudi [20-02-2018]
    protected $emergency = true; 
    
    public function post_data($url, $post_data = [], $headers = [], $options = []){
        $result = null;
        $curl = curl_init();

        if ((is_array($options)) && count($options) > 0) {
            $this->options = $options;
        }
        if ((is_array($headers)) && count($headers) > 0) {
            $this->headers = $headers;
        }
        if ($this->is_post !== null) {
            $this->is_post = 1;
        }

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, $this->is_post);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_COOKIEJAR, "");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // required for https urls
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);

        $content = curl_exec($curl);
        $response = curl_getinfo($curl);
        $result = json_decode($content, TRUE);

        curl_close($curl);
        return $result;
    }

    public function sms_content($groupName, $param){
        $content = "Pin Code: {validateCode}\nCollect your order {expressNumber} at PopBox Locker@{boxName} before {overdueDayOfMouth}/{overdueMouth}/17. www.clientname.id";
        
        if ($param['operator_id'] == '145b2728140f11e5bdbd0242ac110001'){
            $content = "Kode PIN: {validateCode}\nOrder No: {expressNumber} sudah tiba di PopBox@{boxName}. Harap diambil sebelum {overdueDayOfMouth}/{overdueMouth}/17. www.clientname.id";
        }
        
        if (isset($groupName) && ($groupName != 'UNDEFINED' || !empty($groupName))) {
            $sqlsms = "SELECT a.content, b.name FROM tb_newlocker_smstemplate a, tb_newlocker_grouptemplate b WHERE a.smsType <> 'REJECT_EXPRESS_STORE_NOTIFY_CUSTOMER'  AND b.id = a.templateGroup_id AND b.name ='".$groupName."'";
            $rsms = DB::select($sqlsms);

            if (count($rsms) != 0 ) {
                $content = $rsms[0]->content;  
            } 
        }

        $validateCode = $param['validateCode'];
        $expressNumber = $param['expressNumber'];
        $box_name = $param['box_name'];
        $overduetimesms = $param['overduetimesms'];
        //==================================================================================================//
        $content_ = str_replace('{validateCode}', $validateCode, $content);
        $content__ = str_replace('{expressNumber}', $expressNumber, $content_);
        $content___ = str_replace('{boxName}', $box_name, $content__);
        $content____ = str_replace('{overdueDayOfMouth}/{overdueMouth}/17', $overduetimesms, $content___);

        return $content____;

    }

    public function sendFromNexmo($to, $message){        
        // $notif      = $this->_checkToken($req->json('token'));
        $response = null;
        if(!empty($to) && !empty($message)) {
            if(substr($to, 0, 1) == '+') {
                $to = substr($to, 1);
            } else if(substr($to, 0, 1) == '0') {
                $to = '62'.substr($to, 1);
            } else if(substr($to, 0, 1) == '8') {
                $to = '62'.$to;
            } else if(substr($to, 0, 2) == '01') { //Can also handle Malaysia Phone Number. [Wahyudi 09-09-17] for Malaysia Backup
                $to = '6'.$to;
            }
            $data = array();
            $data["api_key"] = '5b582569';
            $data["api_secret"] = 'f1708d28f0dfaffa';
            $data["from"] = 'POPBOX-ASIA';
            $data["to"] = $to;
            $data["text"] = $message;
            $url = 'https://rest.nexmo.com/sms/json?' . http_build_query($data);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
            $response = json_decode(curl_exec($ch), TRUE);
            $response['raw'] = $response;
            $response = json_encode($response);
//            var_dump($response);
            // $notif->setOK(json_decode($response));
        }
        return response()->json($response);
    }

    public function sendFromIsentric ($to, $message){
        $response = null;
        // $notif      = $this->_checkToken($req->json('token'));      
        if(!empty($to) && !empty($message)) {
            if(substr($to, 0, 1) == '+') {
                $to = substr($to, 1);
            } else if(substr($to, 0, 1) == '0') {
                $to = '60'.substr($to, 1);
            } else if(substr($to, 0, 1) == '1') {
                $to = '60'.$to;
            }
            $message = urlencode($message);
            $mtid = "707".time().rand(111, 999);
            $accountName = 'popboxsunway'; //This is live account, please don't change..! [Wahyudi 09-09-17]
            // $server_ip = '203.223.130.118';
            $server_ip = '203.223.130.115';
            $runfile = 'http://'.$server_ip.'/ExtMTPush/extmtpush?shortcode=39398&custid='.$accountName.'&rmsisdn='.$to.'&smsisdn=62003&mtid='.$mtid.'&mtprice=000&productCode=&productType=4&keyword=&dataEncoding=0&dataStr='.$message.'&dataUrl=&dnRep=0&groupTag=10';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $runfile);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
//            //add loop sending when no answer receive from isentric server, Wahyudi [01-12-17]
//            do {
//                $content = curl_exec ($ch);
//            } while (strpos($content, "returnCode = ") === false);
            $content = curl_exec ($ch);
            $ret = strpos($content, "returnCode = ");
            $start = $ret + 13;
            $retcode = substr($content, $start, 1);
            curl_close ($ch); 
            $response = ['response' => ['code' => 200, 'message' => 'OK'], 'raw' => $content,
                'data' => ['message-count' => '1', 'messages' => [['to' => $to, 'message-id' => $mtid,
                    'status' => $retcode, 'remaining-balance' => '0', 'message-price' => '0', 'network'=>'0']]]];

            // $resp=['message-count' => '1', 'messages' => [['to' => $to, 'message-id' => $mtid, 'status' => $retcode, 'remaining-balance' => '0', 'message-price' => '0', 'network'=>'0']]];         
    //      $notif->setOK(json_decode($resp));          
        }       
        return response()->json($response);       
    }
    
    
        /*====================NON-LOCKER API======================*/
    //For below these non-locker API(s), need to put usertoken on Header as mandatory
    
    public function resendSMS (Request $req) {
        $userToken = $req->header('userToken');
        $express_id = $req->json('id');
        $expressNumber = null;
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{            
            if (!empty($express_id)) {
                $sql = "SELECT * FROM tb_newlocker_express WHERE deleteFlag = '0' AND status = 'IN_STORE' AND expressType = 'COURIER_STORE' AND id ='".$express_id."'";
                $r = DB::select($sql);
                if (count($r) != 0 ) {
                    $box_id = $r[0]->box_id;
                    $groupName = $r[0]->groupName;
                    $validateCode = $r[0]->validateCode;
                    $expressNumber = $r[0]->expressNumber;
                    $overdueTime = $r[0]->overdueTime;
                    $takeUserPhoneNumber = $r[0]->takeUserPhoneNumber;
                    $overduetimesms = date('j-n-y', $overdueTime / 1000);
                    $sqlb = "SELECT * FROM tb_newlocker_box WHERE id ='".$box_id."'";
                    $rb = DB::select($sqlb);
                    $box_name = $rb[0]->name;
                    $operator_id = $rb[0]->operator_id;
                    $param = array('box_name' => $box_name, 'validateCode' => $validateCode, 'overduetimesms' => $overduetimesms, 'expressNumber' => $expressNumber, 'operator_id' => $operator_id);
                    $message = $this->sms_content($groupName, $param);
                    if ($groupName == 'COD' ) {
                        $message = "Order Anda " . $expressNumber . " sudah sampai di Popbox@" . $box_name . ", bayar sebelum " . $overduetimesms . " di loker/kasir untuk dapat PIN ambil barang - www.clientname.id";        
                    } 
                    if ($operator_id != '145b2728140f11e5bdbd0242ac110001'){
                        $message = "PIN Code: " . $validateCode . "\nYour order " . $expressNumber . " has arrived at PopBox@" . $box_name . ". Please collect before " . $overduetimesms . " - www.clientname.id";
                    }                    
                    if($operator_id == '145b2728140f11e5bdbd0242ac110001'){
                        $resp = $this->sendFromNexmo($takeUserPhoneNumber, $message);
                    }else{
                        $resp = $this->sendFromIsentric($takeUserPhoneNumber, $message);
                    }
                    if (!empty($resp)){
                        if($operator_id == '145b2728140f11e5bdbd0242ac110001'){
                            $statusMsg = (strpos(str_replace(' ', '', $resp->getData()), '"status":"0"') != false) ? 'SUCCESS' : 'FAILED' ;
                        } else {
                            $statusMsg = (strpos(str_replace(' ', '', json_encode($resp->getData())), '"status":"0"') != false) ? 'SUCCESS' : 'FAILED' ;
                        }
                    } else {
                        $statusMsg = 'ERROR';
                    }
                    DB::table('tb_newlocker_smslog')
                        ->insert([
                                'express_id' => $express_id,
                                'sms_content' => '[Manual] '.$message,
                                'sms_status' => $statusMsg,
                                'sent_on' => date("Y-m-d H:i:s"),
                                'original_response' => json_encode($resp)
                        ]);
                    /*{"response":{"code":200,"message":"OK"},
                    "data":{"message-count":"1",
                    "messages":[{"to":"6281287944008","message-id":"0F000000708B95E2","status":"0","remaining-balance":"100.96820000","message-price":"0.01850000","network":"51010"}]}}*/
                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $express_id, 'expressNumber' => $expressNumber, 'status' => 'SMS RESEND']];
                    DB::table('tb_newlocker_generallog')
                            ->insert([
                                'api_url' =>  'http://pr0x.clientname.id'.'/task/express/resendSMS',
                                'api_send_data' => json_encode(['express_id' => $express_id,
                                    'expressNumber' => $expressNumber]),
                                'api_response' => json_encode($res),
                                'response_date' => date("Y-m-d H:i:s")
                                ]);
                }else{
                    $res = ['statusCode' => 401, 'errorMessage' => 'Express Id Not Found or Already Taken!'];
                }
            } else {
                $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter!'];
            }               
        }
        return response()->json($res);
    } 

    public function smshistory(Request $req) {
        $userToken = $req->header('userToken');
        $express_id = $req->json('id');
        // $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        // $rtok = DB::select($sqltok);
        // $granted = $rtok[0]->token;

        $granted = Cache::remember("locker_token-$userToken",1440,function() use($userToken){
            $data = DB::table('tb_newlocker_token')->select('token')->where('token','=',$userToken)->where('deleteFlag', '=', '0')->count();
            return $data;
        });
        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => ['userToken' => $userToken]];       
        }else{            
            if (!empty($express_id)) {
                $sqlsms = "SELECT sms_content, sms_status, sent_on FROM tb_newlocker_smslog WHERE express_id ='".$express_id."' ORDER BY sent_on DESC";
                $smslog = DB::select($sqlsms);
                if (count($smslog) != 0 ) {
                    $datahistory = array();
                    foreach ($smslog as $history) {
                        array_push($datahistory, $history);
                    }
                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => $datahistory];
                }else{
                    $res =  ['response'=> ['code' => 404, 'message' => 'SMS History Not Found/Recorded!'],  'data' => ['id' => $express_id]];
                }
            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing parameter!'],  'data' => ['id' => '']]; 
            }               
        }
        return response()->json($res);
    }

}