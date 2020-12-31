<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Helpers\WebCurl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;


class ParcelinController extends Controller{
    var $curl;
    var $env_popsend_url;
    var $env_popsend_token;

    public function __construct(){
        $headers = ['Content-Type: application/json'];
        $this->curl = new WebCurl($headers);
        $this->env_popsend_url = env('POPSEND2_URL');
        $this->env_popsend_token = env('POPSEND2_TOKEN');
    }

    // function from old app
    protected $headers = ['Content-Type: application/json'];
    protected $is_post = 0; 
    // protected $env_popsend_url = 'http://popsendv2dev.clientname.id/popsafe/updateStatus';
    // protected $env_popsend_token = 'iyajJrvejTqHaucPZrtqKXrwDPGcdPoCbMVV56m6AwKOVXb3u75VdFOKGQd9U3FG';

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
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($curl, CURLOPT_TIMEOUT, 5);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        $content = curl_exec($curl);
//        $response = curl_getinfo($curl);
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
            $sqlsms = "SELECT a.content, b.name FROM tb_newlocker_smstemplate a, tb_newlocker_grouptemplate b WHERE a.smsType <> 'REJECT_EXPRESS_STORE_NOTIFY_CUSTOMER' AND b.id = a.templateGroup_id AND b.name ='".$groupName."'";
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
        $response = ['response' => ['code' => 500, 'message' => 'ERROR'], 'data' => []];
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
            DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' => $url,
                    'api_send_data' => json_encode($data),
                    'api_response' => $response,
                    'response_date' => date("Y-m-d H:i:s")
                ]);
        }
        return response()->json($response);      
    }

    public function sendFromIsentric ($to, $message){
        $response = ['response' => ['code' => 500, 'message' => 'ERROR'], 'data' => []];
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
            //add loop sending when no answer receive from isentric server, Wahyudi [01-12-17]
//            $i = 0;
//            do {
//                $content = curl_exec ($ch);
//                sleep(1);
//                $i ++;
//            } while (strpos($content, "returnCode = ") === false && $i >= 3);
            $content = curl_exec ($ch);
            $ret = strpos($content, "returnCode = ");
            $start = $ret + 13;
            $retcode = substr($content, $start, 1);
            curl_close ($ch); 
            $response = ['response' => ['code' => 200, 'message' => 'OK'], 'raw' => $content,
                'data' => ['message-count' => '1', 'messages' => [['to' => $to, 'message-id' => $mtid,
                    'status' => $retcode, 'remaining-balance' => '0', 'message-price' => '0', 'network'=>'0']]]];
            DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' => 'http://'.$server_ip.'/ExtMTPush/extmtpush?shortcode=39398',
                    'api_send_data' => $runfile,
                    'api_response' => $content,
                    'response_date' => date("Y-m-d H:i:s")
                ]);
        }       
        return response()->json($response);       
    }

    //=======================================================================================================================================

    public function rejectexpress (Request $req) {
        $mouth_number = $req->json('mouth.number');
        $mouth_cabinetid = $req->json('mouth.cabinet_id');
        $box_id = $req->json('box_id');
        $syncFlag = $req->json('mouth.syncFlag');
        $numberInCabinet = $req->json('mouth.numberInCabinet');
        $mouthType_id = $req->json('mouth.mouthType_id');
        $mouth_id = $req->json('mouth.id');
        $logisticsCompany_id = $req->json('logisticsCompany_id');
        $groupName = $req->json('groupName');
        $expressType = $req->json('expressType');
        $electronicCommerce_name = $req->json('electronicCommerce.name');
        $electronicCommerce_id = $req->json('electronicCommerce_id');
        $electronicCommerce_address = $req->json('electronicCommerce.address');
        $storeTime = $req->json('storeTime');
        $storeUserPhoneNumber = $req->json('storeUserPhoneNumber');
        $customerStoreNumber = $req->json('customerStoreNumber');
        $operator_id = $req->json('operator_id');
        $id = $req->json('id');
        $startAddress = $req->json('startAddress');
        $endAddress = $req->json('endAddress');
        $storeUser_id = $req->json('storeUser_id');
        $recipientName = $req->json('recipientName');
        $chargeType = $req->json('chargeType');

        $storeUserPhoneNumber = preg_replace('/[^0-9]/', '', $storeUserPhoneNumber);
        $customerStoreNumber = preg_replace("/^\w+.-_$/", '', $customerStoreNumber);

        $sqlc = "select id from tb_newlocker_express where id='".$id."'";
        $rc = DB::select($sqlc);

        if (count($rc) == 0) {
            DB::table('tb_newlocker_express')
                            ->insert([
                                'id' => $id,
                                'customerStoreNumber' => $customerStoreNumber,
                                'expressType' => $expressType,
                                'status'     => 'IN_STORE',
                                'storeTime' =>  $storeTime,
                                'syncFlag' => $syncFlag,
                                'storeUserPhoneNumber' => $storeUserPhoneNumber,
                                'box_id' => $box_id,
                                'mouth_id' => $mouth_id,
                                'operator_id' => $operator_id,
                                'groupName' => $groupName,
                                'electronicCommerce_id' => $electronicCommerce_id,
                                'logisticsCompany_id' => $logisticsCompany_id,
                                'startAddress' => $startAddress,
                                'endAddress' => $endAddress,
                                'storeUser_id' => $storeUser_id,
                                'recipientName' => $recipientName,
                                'chargeType' => $chargeType,
                                'brandId' => $groupName,
                                'lastModifiedTime' => time() * 1000

            ]);

        }else{
            DB::table('tb_newlocker_express')
                ->where('id', $id)->where('customerStoreNumber', $customerStoreNumber)
                        ->update(array(
                                'expressType' => $expressType,
                                'status'=> 'IN_STORE',
                                'storeTime' =>  $storeTime,
                                'syncFlag' => $syncFlag,
                                'storeUserPhoneNumber' => $storeUserPhoneNumber,
                                'box_id' => $box_id,
                                'mouth_id' => $mouth_id,
                                'operator_id' => $operator_id,
                                'groupName' => $groupName,
                                'electronicCommerce_id' => $electronicCommerce_id,
                                'logisticsCompany_id' => $logisticsCompany_id,
                                'startAddress' => $startAddress,
                                'endAddress' => $endAddress,
                                'storeUser_id' => $storeUser_id,
                                'recipientName' => $recipientName,
                                'chargeType' => $chargeType,
                                'brandId' => $groupName,
                                'lastModifiedTime' => time() * 1000
            ));
        }

        DB::table('tb_newlocker_mouth')
            ->where('id_mouth', $mouth_id)
                    ->update(array(
                            'status' => 'USED',
                            'express_id' => $id,
                            'lastChangingTime' => time() * 1000
         ));                

        $sqlb = "select * from tb_newlocker_box where id='".$box_id."'";
        $rb = DB::select($sqlb);    
        $box = array ('orderNo' => $rb[0]->orderNo, 'name' => $rb[0]->name, 'id' => $rb[0]->id );

        $sqlm = "select a.* , b.* from tb_newlocker_mouth a, tb_newlocker_mouthtype b where a.id_mouth='".$mouth_id."'and a.mouthType_id=b.id_mouthtype";
        $rm = DB::select($sqlm);

        $mouthtype = array('name' => $rm[0]->name, 'defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'defaultUserPrice' => $rm[0]->defaultUserPrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag);
        $mouth = array('number' => $rm[0]->number, 'status' => $rm[0]->status , 'id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box);    

        $sqlc = "select * from tb_newlocker_company where id_company='".$logisticsCompany_id."'";
        $rc = DB::select($sqlc);
        $logistic = array ('companyType' => $rc[0]->company_type, 'level' => $rc[0]->level, 'deleteFlag' => $rc[0]->deleteFlag, 'name' => $rc[0]->company_name, 'contactPhoneNumber' => [], 'contactEmail' => [], 'id' => $rc[0]->id_company);

        $sqle = "select * from tb_newlocker_express where id='".$id."'";
        $re = DB::select($sqle);

        $sqlecom = "select * from tb_newlocker_company where id_company ='".$electronicCommerce_id."'";
        $recom = DB::select($sqlecom);

        $storeuser = array('userNo' => '', 'name' => $storeUserPhoneNumber, 'userCardList' => [['id'=>'']], 'phoneNumber' =>  $storeUserPhoneNumber, 'id' => $storeUser_id , 'loginName' => $storeUserPhoneNumber);
        $addrtrn = $re[0]->groupName." Warehouse";
        $electronicCommerce = array('name' => $recom[0]->company_name, 'id' => $recom[0]->id_company, 'address' => $addrtrn);

        $sqlgro = "SELECT * FROM tb_newlocker_grouptemplate WHERE name = '".$groupName."'";
        $resgro = DB::select($sqlgro);

        if (count($resgro) != 0) {
            $webSMS = $resgro[0]->website;
        } else {
            $webSMS = 'www.clientname.id';
        }
        
        switch ($groupName) {
            case "ZALORA":
                $shadow = ' Zalora ';
                break;
            case "LAZADA":
                $shadow = ' Lazada ';
                break;
            case "MATAHARIMALL":
                $shadow = ' MM ';
                break;
            case "BLIBLI":
                $shadow = ' Blibli.com ';
                break; 
            case "JAVAMIFI":
                $shadow = ' Javamifi ';
                break;  
            case "LAZADA_MLY":
                $shadow = ' Lazada ';
                break;   
            case "LOGON":
                $shadow = ' Logon.my ';
                break;   
            case "ORIFLAME":
                $shadow = ' Oriflame ';
                break;                                           
            default:
                $shadow = ' ';
                break;
        }            

        if ($operator_id=='145b2728140f11e5bdbd0242ac110001') {
            $message = "Pelanggan YTH, Kami telah terima pengembalian barang" .$shadow.$customerStoreNumber." di Popbox@".$rb[0]->name.", nomor loker : ".$rm[0]->number." - " . $webSMS;      
        } else {
            $message = "Dear Customer, We've received a parcel return for" .$shadow.$customerStoreNumber." at Popbox@".$rb[0]->name.", locker number : ".$rm[0]->number." - " . $webSMS;        
        }

        $resp = null;
        if($operator_id == '145b2728140f11e5bdbd0242ac110001'){
            $resp = $this->sendFromNexmo($storeUserPhoneNumber, $message);
        }else{
            $resp = $this->sendFromIsentric($storeUserPhoneNumber, $message);
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
                'express_id' => $id,
                'sms_content' => '[Auto] '.$message,
                'sms_status' => $statusMsg,
                'sent_on' => date("Y-m-d H:i:s"),
                'original_response' => json_encode($resp)
            ]);

        $res =  ['mouth' => $mouth , 'logisticsCompany' => $logistic, 'groupName' => $re[0]->groupName, 'status' => $re[0]->status, 'createTime' => $storeTime, 'storeUserPhoneNumber' => $re[0]->storeUserPhoneNumber, 'storeUser' => $storeuser , 'version' => 0 , 'storeTime' => $storeTime, 'box' => $box, 'electronicCommerce' => $electronicCommerce, 'customerStoreNumber' => $customerStoreNumber ,'items' => [], 'chargeType' => $re[0]->chargeType, 'expressType' => $re[0]->expressType, 'id' => $id, 'includedNumbers' => 0];

        if (strpos($storeUser_id, '@') !== false){ 
            $res['courier_no'] = $endAddress;
            $res['cust_email'] = $storeUser_id;
            $res['cust_name'] = $recipientName;            
            $res['cust_phone'] = $storeUserPhoneNumber;
        }

        //Post to mirror server
        $url_push = 'https://internalapi.clientname.id/synclocker/customereject';
        $push = $this->post_data($url_push, json_encode($res));
        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    ['api_url' =>  'http://pr0x.clientname.id'.'/express/rejectExpressNotImported',
                    'api_send_data' => json_encode(['id' => $id, 'customerStoreNumber' => $customerStoreNumber, 'expressType' => 'CUSTOMER_REJECT']),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")],
                    ['api_url' =>  $url_push,
                    'api_send_data' => json_encode($res),
                    'api_response' => json_encode($push),
                    'response_date' => date("Y-m-d H:i:s")]
                    ]);
        return response()->json($res);
    }

    public function staffstorexpress (Request $req) {
        $syncFlag = $req->json('syncFlag');
        $box_id = $req->json('box.id');
        $overduetime = $req->json('overdueTime');
        $mouth_id = $req->json('mouth.id');
        $expressType = $req->json('expressType');
        $logisticsCompany_id = $req->json('logisticsCompany.id');
        $version = $req->json('version');
        $id = $req->json('id');
        $storeUser_id = $req->json('storeUser.id');
        $status = $req->json('status');
        $groupName = $req->json('groupName');
        $takeUserPhoneNumber = $req->json('takeUserPhoneNumber');
        $storeTime = $req->json('storeTime');
        $validateCode = $req->json('validateCode');
        $operator_id = $req->json('operator.id');
        $expressNumber = $req->json('expressNumber');
        $smsSend = true;
        $prefix = substr($expressNumber,0,3);
        $rpr = DB::select("SELECT groupName, sms FROM tb_newlocker_groupname WHERE prefix='".$prefix."'");

        if (count($rpr) != 0) {
            $groupName = $rpr[0]->groupName;
            $smsSend = $rpr[0]->sms;
        }else{
            $groupName = 'UNDEFINED';
        }
        if($expressNumber=="N\/A"){
            $expressNumber = "C-".$takeUserPhoneNumber;
        }
        // Add Phone Number Handling condition
        $takeUserPhoneNumber = preg_replace('/[^0-9]/', '', $takeUserPhoneNumber);
        $expressNumber = preg_replace("/^\w+.-_$/", '', $expressNumber);

        $sqlc = "SELECT * FROM tb_newlocker_express WHERE id='".$id."'";
        $rc = DB::select($sqlc);

        if (count($rc) == 0) {
            DB::table('tb_newlocker_express')
                        ->insert([
                            'id' => $id,
                            'expressNumber' => $expressNumber,
                            'expressType' =>  'COURIER_STORE' ,
                            'syncFlag' => $syncFlag,
                            'box_id' => $box_id,
                            'overdueTime'  => $overduetime,
                            'mouth_id'     => $mouth_id,
                            'logisticsCompany_id' => $logisticsCompany_id,
                            'version' => $version,
                            'storeUser_id' => $storeUser_id,
                            'status' => $status,
                            'groupName' => $groupName,
                            'takeUserPhoneNumber' => $takeUserPhoneNumber,
                            'storeTime' => $storeTime,
                            'validateCode' => $validateCode,
                            'operator_id' => $operator_id,
                            'lastModifiedTime' => time() * 1000
            ]);
        } else {                
            DB::table('tb_newlocker_express')
            ->where('id', $id)->where('expressNumber', $expressNumber)
                    ->update(array(
                            'syncFlag' => $syncFlag,
                            'box_id' => $box_id,
                            'expressType' =>  'COURIER_STORE' ,
                            'overdueTime'  => $overduetime,
                            'mouth_id'     => $mouth_id,
                            'logisticsCompany_id' => $logisticsCompany_id,
                            'version' => $version,
                            'storeUser_id' => $storeUser_id,
                            'status' => $status,
                            'storeTime' => $storeTime,
                            'validateCode' => $validateCode,
                            'operator_id' => $operator_id,
                            'lastModifiedTime' => time() * 1000
            ));             
        }               

            DB::table('tb_newlocker_mouth')
            ->where('id_mouth', $mouth_id)
                    ->update(array(
                            'status' => 'USED',
                            'express_id' => $id,
                            'lastChangingTime' => time() * 1000
            ));

        $rb = Cache::remember("locker_id-$box_id",720,function() use($box_id){
            $data = DB::table('tb_newlocker_box')->select('id','token','name','currencyUnit','freeDays','overdueType','validateType','freeHours','orderNo')->where('id','=',$box_id)->first();
            return $data;
        });

        $box = array ('orderNo' => $rb->orderNo, 'name' => $rb->name, 'id' => $rb->id );
        $box_name = $rb->name;
        $takeuser = array('id' => $storeUser_id, 'userNo' => '', 'name' => $takeUserPhoneNumber, 'userCardList' => [['id'=>'']],'phoneNumber' =>  $takeUserPhoneNumber, 'loginName' => $takeUserPhoneNumber );

        $rc = Cache::remember("locker_com-$logisticsCompany_id",720,function() use($logisticsCompany_id){
                    $data = DB::table('tb_newlocker_company')->select('level','company_type','company_name','id_company','deleteFlag')->where('id_company','=',$logisticsCompany_id)->first();
                    return $data;
        }); 
        $logistic = array ('companyType' => $rc->company_type, 'id' => $rc->id_company, 'deleteFlag' => $rc->deleteFlag, 'name' => $rc->company_name,  'contactPhoneNumber' => [], 'level' => $rc->level, 'contactEmail' => []);

        $ru = Cache::remember("locker_user-$storeUser_id",720,function() use($storeUser_id){
                    $data = DB::table('tb_newlocker_user')->select('id_user','username','displayname','phone')->where('id_user','=',$storeUser_id)->first();
                    return $data;
        }); 
        $storeuser = array('id' => $storeUser_id , 'userNo' => '', 'name' => $ru->displayname, 'userCardList' => [['id'=>'']], 'phoneNumber' => $ru->phone, 'loginName' => $ru->username);

        $sqlm = "SELECT a.* , b.* FROM tb_newlocker_mouth a, tb_newlocker_mouthtype b WHERE a.id_mouth='".$mouth_id."'and a.mouthType_id=b.id_mouthtype";
        $rm = DB::select($sqlm);
        $mouthtype = array('name' => $rm[0]->name, 'defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'defaultUserPrice' => $rm[0]->defaultUserPrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag);
        $mouth = array('number' => $rm[0]->number, 'status' => $rm[0]->status , 'id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box); 

        $overduetimesms = date('j/n/y', $overduetime / 1000);

        //GET SMS CONTENT TEMPLATE
        $param = array('box_name' => $box_name, 'validateCode' => $validateCode, 'overduetimesms' => $overduetimesms, 'expressNumber' => $expressNumber, 'operator_id' => $operator_id);
        $message = $this->sms_content($groupName, $param); 

        if ($groupName == 'COD' ) {
            $message = "Order Anda " . $expressNumber . " sudah sampai di Popbox@" . $box_name . ", bayar sebelum " . $overduetimesms . " di loker/kasir untuk dapat PIN ambil barang - www.clientname.id";        
        } 

        if ($operator_id != '145b2728140f11e5bdbd0242ac110001'){
            $message = "PIN Code: " . $validateCode . "\nYour order " . $expressNumber . " has arrived at PopBox@" . $box_name . ". Please collect before " . $overduetimesms . " - www.clientname.id";
        }
        
        $resp = null;
        if($smsSend==true||$smsSend==1){
            if($operator_id == '145b2728140f11e5bdbd0242ac110001'){
                $resp = $this->sendFromNexmo($takeUserPhoneNumber, $message);
            }else{
                $resp = $this->sendFromIsentric($takeUserPhoneNumber, $message);
            }
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
                'express_id' => $id,
                'sms_content' => '[Auto] '.$message,
                'sms_status' => $statusMsg,
                'sent_on' => date("Y-m-d H:i:s"),
                'original_response' => json_encode($resp)
            ]);        

        $res =  ['box' => $box , 'validateCode' => $validateCode, 'groupName' => $groupName, 'expressType' => $expressType, 'overdueTime' => $overduetime, 'includedNumbers' => 0, 'takeUser' => $takeuser, 'logisticsCompany' => $logistic , 'version' => $version , 'additionalPayment' => [], 'id' => $id, 'storeUser' => $storeuser, 'status' => $status ,'mouth' => $mouth , 'takeUserPhoneNumber' => $takeUserPhoneNumber, 'storeTime' => $storeTime, 'expressNumber' => $expressNumber, 'items' => []];
        $url_push = 'https://internalapi.clientname.id/synclocker/courierstore';
        $push = $this->post_data($url_push, json_encode($res));  

        //Post to popsend2 server
        if($groupName=="POPDEPOSIT"){
            try {
                DB::table('tb_newlocker_express')->where('customerStoreNumber', $expressNumber)->where('status', 'IMPORTED')->where('groupName', 'POPDEPOSIT')->where('expressType', 'CUSTOMER_STORE')->update(['status' => '[POPDEPOSIT]', 'deleteFlag' => 1]);
            } catch (Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }            
            $url__ = $this->env_popsend_url;
            $param__ = ["token" => $this->env_popsend_token, "invoice_id" => $expressNumber, "status" => $status,
                "locker_number" => $rm[0]->number, "code_pin" => $validateCode, "parcel_id" => $id,
                "remarks" => "Updated from pr0x : ". date("Y-m-d H:i:s")];
            $resp__ = $this->post_data($url__, json_encode($param__));   

            DB::table('tb_newlocker_generallog')->insert(
                ['api_url' =>  $url__, 
                'api_send_data' => json_encode($param__),
                'api_response' => json_encode($resp__),
                'response_date' => date("Y-m-d H:i:s")]); 
        }

        DB::table('tb_newlocker_generallog')
                ->insert([
                    //log transaksi data parcel
                    ['api_url' =>  'http://pr0x.clientname.id'.'/express/staffStoreExpress',
                    'api_send_data' => json_encode(['id' => $id, 'expressNumber' => $expressNumber, 'validateCode' => $validateCode]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")],
                    //log push to popbox server
                    ['api_url' =>  $url_push,
                    'api_send_data' => json_encode($res),
                    'api_response' => json_encode($push),
                    'response_date' => date("Y-m-d H:i:s")]
                    ]);

        return response()->json($res);
    } 

    public function customerstorexpress (Request $req) {
        $groupName = $req->json('groupName');
        $storeTime = $req->json('storeTime');
        $endAddress = $req->json('endAddress');
        $recipientName = $req->json('recipientName');
        $weight = $req->json('weight');
        $expressType = $req->json('expressType');
        $box_id = $req->json('box_id');
        $mouth_id = $req->json('mouth_id');
        $barcode_id = $req->json('barcode.id');
        $version = $req->json('version');
        $recipientUserPhoneNumber = $req->json('recipientUserPhoneNumber');
        $chargeType = $req->json('chargeType');
        $customerStoreNumber = $req->json('customerStoreNumber');
        $logisticsCompany_id = $req->json('logisticsCompany_id');
        $operator_id = $req->json('operator_id');
        $status = $req->json('status');
        $takeUserPhoneNumber = $req->json('takeUserPhoneNumber');
        $storeUser_id = $req->json('storeUser_id');
        $id = $req->json('id');
        $createTime = $req->json('createTime');
        $customerStoreNumber = preg_replace("/^\w+.-_$/", '', $customerStoreNumber);

        DB::table('tb_newlocker_express')
            ->where('id', $id)->where('customerStoreNumber', $customerStoreNumber)
                    ->update(array(
                            // 'groupName' => $groupName, -> will replace the value to null
                            'storeTime' => $storeTime,
                            'weight' =>  $weight,
                            'box_id' => $box_id,
                            'mouth_id' => $mouth_id,
                            'operator_id' => $operator_id,
                            'version' => 1,
                            'status' => 'IN_STORE', 
                            'storeUser_id' => $storeUser_id,
                            'lastModifiedTime' => time() * 1000

        ));

        DB::table('tb_newlocker_mouth')
            ->where('id_mouth', $mouth_id)
                    ->update(array(
                        'status' => 'USED', 
                        'express_id' => $id,
                        'lastChangingTime' => time() * 1000
                        ));

        $sqlb = "select * from tb_newlocker_box where id='".$box_id."'";
        $rb = DB::select($sqlb);    
        $box = array ('orderNo' => $rb[0]->orderNo, 'name' => $rb[0]->name, 'id' => $rb[0]->id );

        $sqlm = "select a.* , b.* from tb_newlocker_mouth a, tb_newlocker_mouthtype b where a.id_mouth='".$mouth_id."'and a.mouthType_id=b.id_mouthtype";
        $rm = DB::select($sqlm);

        $mouthtype = array('defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag, 'name' => $rm[0]->name,  'defaultUserPrice' => $rm[0]->defaultUserPrice );
        $mouth = array('id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box,'status' => $rm[0]->status, 'number' => $rm[0]->number);              

        $sqlc = "select * from tb_newlocker_company where id_company='".$logisticsCompany_id."'";
        $rc = DB::select($sqlc);
        $logistic = array ('companyType' => $rc[0]->company_type, 'id' => $rc[0]->id_company, 'deleteFlag' => $rc[0]->deleteFlag, 'name' => $rc[0]->company_name,  'contactPhoneNumber' => [], 'level' => $rc[0]->level, 'contactEmail' => []);
        $res =  ['createTime' => $createTime , 'barcode' => ['id' => $barcode_id ], 'version' => $version, 'recipientUserPhoneNumber' => $recipientUserPhoneNumber, 'chargeType' => $chargeType, 'customerStoreNumber' => $customerStoreNumber, 'mouth' => $mouth, 'logisticsCompany' => $logistic , 'endAddress' => $endAddress , 'recipientName' => $recipientName , 'storeTime' => $storeTime, 'takeUserPhoneNumber' => $takeUserPhoneNumber, 'weight' => $weight ,'expressType' => $expressType , 'box' => $box , 'id' => $id, 'includedNumbers' => 0, 'groupName' => $groupName, 'additionalPayment' => [] , 'items' => [] , 'status' => 'IN_STORE'];

        $url_push = 'https://internalapi.clientname.id/synclocker/customerstore';
        $push = $this->post_data($url_push, json_encode($res));
        
        DB::table('tb_newlocker_generallog')
                ->insert([
                    ['api_url' =>  'http://pr0x.clientname.id'.'/express/customerStoreExpress',
                    'api_send_data' => json_encode(['id' => $id, 'customerStoreNumber' => $customerStoreNumber, 'expressType' => 'CUSTOMER_STORE']),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")],
                    ['api_url' =>  $url_push,
                    'api_send_data' => json_encode($res),
                    'api_response' => json_encode($push),
                    'response_date' => date("Y-m-d H:i:s")]
                    ]);

        return response()->json($res);
    }

}