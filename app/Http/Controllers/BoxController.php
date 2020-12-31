<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;


class BoxController extends Controller{
    var $curl;

    public function __construct(){
        $headers = ['Content-Type: application/json'];
        $this->curl = new WebCurl($headers);
    }

    // function from old app
    protected $headers = ['Content-Type: application/json'];
    protected $is_post = 0;
 
    
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
        $response = curl_getinfo($curl);
        $result = json_decode($content, TRUE);

        //debug


        curl_close($curl);
        return $result;
    }
    
    /*====================BOX-LOCKER API======================*/

    public function boxpull (Request $req) {
        $boxToken = $req->header('BoxToken');
        $diskSerialNumber = $req->header('DiskSerialNumber');
        $orderNo = $req->header('OrderNo');
        $userToken = $req->header('UserToken');
        
        //define request apakah mengandung json untuk parsing param
        if (!empty($req->json('cpu')) || !empty($req->json('disk')) || !empty($req->json('memory'))) {
            $cpu = $req->json('cpu');
            $disk = $req->json('disk');
            $memory = $req->json('memory');
        } else {
            $cpu = null;
            $disk = null;
            $memory = null;           
        }

        //ambil data dari tabel box cache
        $lockerbox = Cache::remember("locker_box-$orderNo",30,function() use($orderNo){
            $data = DB::table('tb_newlocker_box')->select('id','token','name','currencyUnit','freeDays','overdueType','validateType','freeHours')->where('orderNo','=',$orderNo)->where('deleteFlag', '=', '0')->first();
            return $data;
        });
        $box_id = $lockerbox->id;
        $box_token = $lockerbox->token;
        $box_name = $lockerbox->name;
        $currencyUnit = $lockerbox->currencyUnit;
        $freeDays =  $lockerbox->freeDays;
        $overdueType =  $lockerbox->overdueType ;
        $validateType = $lockerbox->validateType;
        $freeHours = $lockerbox->freeHours;

        $rms = Cache::remember("locker_mstat-$box_id",720,function() use($box_id){
            $data = DB::table('tb_newlocker_machinestat')->select('locker_id')->where('locker_id','=',$box_id)->count();
            return $data;
        });

        if ($rms != 0)  {
            //update status box
            DB::table('tb_newlocker_machinestat')
                ->where('locker_id', $box_id)
                        ->update(array(
                            'locker_name' => $box_name,
                            'cpu' => $cpu,
                            'disk' => $disk,
                            'memory' => $memory,
                            'disk_serial_no' => $diskSerialNumber,
                            'conn_status' => 1,
                            'update_time' => date("Y-m-d H:i:s")
            ));
        } else {
            //insert status box
            DB::table('tb_newlocker_machinestat')
                ->insert([
                        'locker_id' => $box_id,
                        'locker_name' => $box_name,
                        'cpu' => $cpu,
                        'disk' => $disk,
                        'memory' => $memory,
                        'disk_serial_no' => $diskSerialNumber,
                        'conn_status' => 1,
                        'update_time' => date("Y-m-d H:i:s")
            ]);    
        }  

        $counting = $req->json('people_count');
        
        if(!empty($counting)){
            $recordTime = date("Y-m-d H");
            $check_count = DB::table('tb_newlocker_people_count')->where('locker_id', $box_id)->where('date', $recordTime)->get();
            if (count($check_count) != 0){
                DB::table('tb_newlocker_people_count')->where('locker_id', $box_id)->where('date', $recordTime)->update(['count' => $check_count[0]->count + $counting]);
            } else {
                DB::table('tb_newlocker_people_count')->
                insert(['count' => $counting,
                    'locker_id' => $box_id,
                    'locker_name' => $box_name,
                    'date' => $recordTime
                ]);
            }
        }
        

        if (!empty($box_id)) {
            //collecting data box token
            if ($box_token == "" || empty($box_token)) {
                DB::table('tb_newlocker_box')
                    ->where('orderNo', $orderNo)
                        ->update(array('token' => $boxToken ));
            }

            // query ke tabel company
            $sqlt = "SELECT * FROM tb_newlocker_tasks WHERE status = 'COMMIT' AND box_id='" .$box_id. "' ORDER BY `tb_newlocker_tasks`.`createTime` DESC";
            $rt = DB::select($sqlt);
            
            if (count($rt) != 0){
                $res = array();

                foreach ($rt as $task) {
                    $pushMessageType = $task->messageType;
                    $id = $task->id;
                    $timestamp = $task->createTime;
                    $taskType = $task->task;
                    $statusType = $task->status;
                    $box_id = $task->box_id;
                    $mouth_id = $task->mouth_id;
                    $express_id = $task->expressId;
                    if (empty($timestamp)) {
                        $timestamp = time() * 1000;
                    }
                    $timeout = $timestamp + 120000; //Timeout 2 minutes to do the job

                    /*TODO will define how to assign task for ASYNC_TASK:
                    - BOX_START_TIME_CHANGE
                    - INIT_CLIENT*/

                  $box_ = array('orderNo' => $orderNo, 'id' => $box_id, 'name' => $box_name, 'startTime' => 3, 'currencyUnit' => $currencyUnit, 'freeDays' => $freeDays, 'overdueType' => $overdueType, 'validateType' => $validateType, 'freeHours' => $freeHours);

                    if (!empty($mouth_id) || strlen($mouth_id) != 0) {
                        //ambil data mouth
                            $sqlm = "SELECT a.* , b.* FROM tb_newlocker_mouth a, tb_newlocker_mouthtype b WHERE a.id_mouth='".$mouth_id."' AND a.mouthType_id=b.id_mouthtype";      
                            $rm = DB::select($sqlm);
                            $mouthtype = array('defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag, 'name' => $rm[0]->name,  'defaultUsePrice' => $rm[0]->defaultUserPrice );
                            $mouth_ = array('id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box_,'status' => $rm[0]->status, 'number' => $rm[0]->number, 'syncFlag' =>  $rm[0]->syncFlag, 'deleteFlag' =>  $rm[0]->deleteFlag,  'usePrice' =>  $rm[0]->userPrice, 'overduePrice' =>  $rm[0]->overduePrice, 'numberInCabinet' =>  $rm[0]->numberinCabinet, 'openOrder' => null );
                    }

                    if (!empty($express_id) || strlen($express_id) != 0) {
                            $sqle = "SELECT * FROM tb_newlocker_express WHERE id ='".$express_id."'";        
                            $re = DB::select($sqle);
                           
                            $logid = $re[0]->logisticsCompany_id;

                            // cache newlocker company
                            $lockercom = Cache::remember("locker_com-$logid",720,function() use($logid){
                                $data = DB::table('tb_newlocker_company')->select('level','company_type','company_name','id_company','deleteFlag')->where('id_company','=',$logid)->first();
                                return $data;
                            }); 

                         /*   $sqllog = "SELECT * FROM tb_newlocker_company WHERE id_company ='".$re[0]->logisticsCompany_id."'";        
                            $relog = DB::select($sqllog);*/


                            $logistic_ = array('level' => $lockercom->level, 'companyType' => $lockercom->company_type, 'name' => $lockercom->company_name, 'id' => $lockercom->id_company, 'contactEmail' => [], 'contactPhoneNumber' => [], 'deleteFlag' => $lockercom->deleteFlag);
                            $takeUser_ = array('userNo' => '' , 'id' => (!empty($re[0]->takeUser_id)) ? $re[0]->takeUser_id : hash("haval128,5", $re[0]->takeUserPhoneNumber), 'userCardList' => [], 'name' => $re[0]->takeUserPhoneNumber, 'phoneNumber' => $re[0]->takeUserPhoneNumber);
                            
                            //cache user 
                            $id_user = $re[0]->storeUser_id;
                            $lockeruser = Cache::remember("locker_user-$id_user",720,function() use($id_user){
                                $data = DB::table('tb_newlocker_user')->select('id_user','username','displayname','phone')->where('id_user','=',$id_user)->first();
                                return $data;
                            }); 

                            /*
                            $sqlusr = "SELECT * FROM tb_newlocker_user WHERE id_user ='".$re[0]->storeUser_id."'";        
                            $reusr = DB::select($sqlusr);*/


                            if(!empty($lockeruser)){
                                $storeUser_ = array('userNo' => '', 'id' => $lockeruser->id_user, 'loginName' => $lockeruser->username, 'userCardList' => [], 'name' => $lockeruser->displayname, 'phoneNumber' => $lockeruser->phone);
                            } else {
                                $storeUser_ = array('userNo' => '', 'id' => 'UNDEFINED', 'loginName' => 'UNDEFINED', 'userCardList' => [], 'name' => 'UNDEFINED', 'phoneNumber' => 'UNDEFINED');
                            }
                            
                            $express_ = array('id' => $express_id, 'logisticsCompany' => $logistic_, 'mouth' => $mouth_, 'takeUser' => $takeUser_, 'additionalPayment' => [], 'overdueTime' => $re[0]->overdueTime, 'box' => $box_, 'validateCode' => $re[0]->validateCode, 'status' => $re[0]->status, 'storeUser' => $storeUser_, 'expressType' => $re[0]->expressType, 'expressNumber' => $re[0]->expressNumber, 'takeUserPhoneNumber' => $re[0]->takeUserPhoneNumber, 'storeTime' => $re[0]->storeTime,
                                'version' => $re[0]->version);
                    }                

                    switch ($taskType) {               
                        case 'REMOTE_UNLOCK':                    
                            $task = array('mouth' => $mouth_, 'createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                        case 'STORE_EXPRESS':                  
                            $task = array('express' => $express_, 'createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                        case 'RESET_EXPRESS':                  
                            $task = array('express' => $express_, 'createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                        case 'MOUTH_STATUS_CHANGE':                    
                            $task = array('mouth' => $mouth_, 'createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                        default :                    
                            $task = array('createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                    }

                    $res_ =  ['pushMessageType' => $pushMessageType, 'id' => $id, 'timestamp' => $timestamp, 'value' => $task];

                    array_push($res, $res_);

                    //Change the status after push to locker
                    DB::table('tb_newlocker_tasks')
                        ->where('id', $id)
                            ->update(array(
                                    'status' => 'SENT : '.date("Y-m-d H:i:s"),
                                    'result' => 'WAITING'
                                ));
                }

            } else {

                $res =  [];

            }

        } else {
            
            $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];
        
        } 
        
        if (!empty($res)) {
            //insert ke tabel generallog
            DB::table('tb_newlocker_generallog')
                    ->insert([
                        'api_url' =>  'http://pr0x.clientname.id'.'/box/pull',
                        'api_send_data' => json_encode(['Box Pull' => $orderNo]),
                        'api_response' => json_encode($res),
                        'response_date' => date("Y-m-d H:i:s")
                    ]);
        } 

       return response()->json($res);
    }
    

    public function boxpullwocache (Request $req) {
        $boxToken = $req->header('BoxToken');
        $diskSerialNumber = $req->header('DiskSerialNumber');
        $orderNo = $req->header('OrderNo');
        $userToken = $req->header('UserToken');
        
        //define request apakah mengandung json untuk parsing param
        if (!empty($req->json('cpu')) || !empty($req->json('disk')) || !empty($req->json('memory'))) {
            $cpu = $req->json('cpu');
            $disk = $req->json('disk');
            $memory = $req->json('memory');
        } else {
            $cpu = null;
            $disk = null;
            $memory = null;           
        }

        //ambil data dari tabel box need cache
        $sqlb = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND orderNo = '" .$orderNo. "'";
        $rb = DB::select($sqlb);
        $box_id = $rb[0]->id;
        $box_token = $rb[0]->token;
        $box_name = $rb[0]->name;

        //update data status mesin loker
        $sqlms = "SELECT * FROM tb_newlocker_machinestat WHERE locker_id = '" .$box_id. "'";
        $rms = DB::select($sqlms);

        if (count($rms) != 0) {
            //update status box
            DB::table('tb_newlocker_machinestat')
                ->where('locker_id', $box_id)
                        ->update(array(
                            'locker_name' => $rb[0]->name,
                            'cpu' => $cpu,
                            'disk' => $disk,
                            'memory' => $memory,
                            'disk_serial_no' => $diskSerialNumber,
                            'conn_status' => 1,
                            'update_time' => date("Y-m-d H:i:s")
            ));
        } else {
            //insert status box
            DB::table('tb_newlocker_machinestat')
                ->insert([
                        'locker_id' => $box_id,
                        'locker_name' => $rb[0]->name,
                        'cpu' => $cpu,
                        'disk' => $disk,
                        'memory' => $memory,
                        'disk_serial_no' => $diskSerialNumber,
                        'conn_status' => 1,
                        'update_time' => date("Y-m-d H:i:s")
            ]);
        }   

        if (count($rb) != 0) {
            //collecting data box token
            if ($box_token == "" || empty($box_token)) {
                DB::table('tb_newlocker_box')
                    ->where('orderNo', $orderNo)
                        ->update(array('token' => $boxToken ));
            }

            // query ke tabel company
            $sqlt = "SELECT * FROM tb_newlocker_tasks WHERE status = 'COMMIT' AND box_id='" .$box_id. "' ORDER BY `tb_newlocker_tasks`.`createTime` DESC";
            $rt = DB::select($sqlt);

            if (count($rt) != 0){
                $res = array();

                foreach ($rt as $task) {
                    $pushMessageType = $task->messageType;
                    $id = $task->id;
                    $timestamp = $task->createTime;
                    $taskType = $task->task;
                    $statusType = $task->status;
                    $box_id = $task->box_id;
                    $mouth_id = $task->mouth_id;
                    $express_id = $task->expressId;
                    if (empty($timestamp)) {
                        $timestamp = time() * 1000;
                    }
                    $timeout = $timestamp + 120000; //Timeout 2 minutes to do the job

                    /*TODO will define how to assign task for ASYNC_TASK:
                    - BOX_START_TIME_CHANGE
                    - INIT_CLIENT*/

                    $box_ = array('orderNo' => $orderNo, 'id' => $box_id, 'name' => $box_name, 'startTime' => 3, 'currencyUnit' => $rb[0]->currencyUnit, 'freeDays' => $rb[0]->freeDays, 'overdueType' => $rb[0]->overdueType, 'validateType' => $rb[0]->validateType, 'freeHours' => $rb[0]->freeHours);

                    if (!empty($mouth_id) || strlen($mouth_id) != 0) {
                        //ambil data mouth
                            $sqlm = "SELECT a.* , b.* FROM tb_newlocker_mouth a, tb_newlocker_mouthtype b WHERE a.id_mouth='".$mouth_id."' AND a.mouthType_id=b.id_mouthtype";      
                            $rm = DB::select($sqlm);
                            $mouthtype = array('defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag, 'name' => $rm[0]->name,  'defaultUsePrice' => $rm[0]->defaultUserPrice );
                            $mouth_ = array('id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box_,'status' => $rm[0]->status, 'number' => $rm[0]->number, 'syncFlag' =>  $rm[0]->syncFlag, 'deleteFlag' =>  $rm[0]->deleteFlag,  'usePrice' =>  $rm[0]->userPrice, 'overduePrice' =>  $rm[0]->overduePrice, 'numberInCabinet' =>  $rm[0]->numberinCabinet, 'openOrder' => null );
                    }

                    if (!empty($express_id) || strlen($express_id) != 0) {
                            $sqle = "SELECT * FROM tb_newlocker_express WHERE id ='".$express_id."'";        
                            $re = DB::select($sqle);
                            $sqllog = "SELECT * FROM tb_newlocker_company WHERE id_company ='".$re[0]->logisticsCompany_id."'";        
                            $relog = DB::select($sqllog);
                            $logistic_ = array('level' => $relog[0]->level, 'companyType' => $relog[0]->company_type, 'name' => $relog[0]->company_name, 'id' => $relog[0]->id_company, 'contactEmail' => [], 'contactPhoneNumber' => [], 'deleteFlag' => $relog[0]->deleteFlag);
                            $takeUser_ = array('userNo' => '' , 'id' => (!empty($re[0]->takeUser_id)) ? $re[0]->takeUser_id : hash("haval128,5", $re[0]->takeUserPhoneNumber), 'userCardList' => [], 'name' => $re[0]->takeUserPhoneNumber, 'phoneNumber' => $re[0]->takeUserPhoneNumber);
                            $sqlusr = "SELECT * FROM tb_newlocker_user WHERE id_user ='".$re[0]->storeUser_id."'";        
                            $reusr = DB::select($sqlusr);
                            if(count($reusr) != 0){
                                $storeUser_ = array('userNo' => '', 'id' => $reusr[0]->id_user, 'loginName' => $reusr[0]->username, 'userCardList' => [], 'name' => $reusr[0]->displayname, 'phoneNumber' => $reusr[0]->phone);
                            } else {
                                $storeUser_ = array('userNo' => '', 'id' => 'UNDEFINED', 'loginName' => 'UNDEFINED', 'userCardList' => [], 'name' => 'UNDEFINED', 'phoneNumber' => 'UNDEFINED');
                            }
                            
                            $express_ = array('id' => $express_id, 'logisticsCompany' => $logistic_, 'mouth' => $mouth_, 'takeUser' => $takeUser_, 'additionalPayment' => [], 'overdueTime' => $re[0]->overdueTime, 'box' => $box_, 'validateCode' => $re[0]->validateCode, 'status' => $re[0]->status, 'storeUser' => $storeUser_, 'expressType' => $re[0]->expressType, 'expressNumber' => $re[0]->expressNumber, 'takeUserPhoneNumber' => $re[0]->takeUserPhoneNumber, 'storeTime' => $re[0]->storeTime,
                                'version' => $re[0]->version);
                    }                

                    switch ($taskType) {               
                        case 'REMOTE_UNLOCK':                    
                            $task = array('mouth' => $mouth_, 'createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                        case 'STORE_EXPRESS':                  
                            $task = array('express' => $express_, 'createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                        case 'RESET_EXPRESS':                  
                            $task = array('express' => $express_, 'createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                        case 'MOUTH_STATUS_CHANGE':                    
                            $task = array('mouth' => $mouth_, 'createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                        default :                    
                            $task = array('createTime' => $timestamp, 'taskType' => $taskType, 'statusType' => $statusType, 'box' => $box_, 'id' => $id, 'timeout' => $timeout);
                            break;
                    }

                    $res_ =  ['pushMessageType' => $pushMessageType, 'id' => $id, 'timestamp' => $timestamp, 'value' => $task];

                    array_push($res, $res_);

                    //Change the status after push to locker
                    DB::table('tb_newlocker_tasks')
                        ->where('id', $id)
                            ->update(array(
                                    'status' => 'SENT : '.date("Y-m-d H:i:s"),
                                    'result' => 'WAITING'
                                ));
                }

            } else {

                $res =  [];

            }

        } else {
            
            $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];
        
        }
        
        if (!empty($res)) {
            //insert ke tabel generallog
            DB::table('tb_newlocker_generallog')
                    ->insert([
                        'api_url' =>  'http://pr0x.clientname.id'.'/box/pull',
                        'api_send_data' => json_encode(['Box Pull' => $orderNo]),
                        'api_response' => json_encode($res),
                        'response_date' => date("Y-m-d H:i:s")
                    ]);
        }

        return response()->json($res);
    }

    public function boxinit (Request $req) {
        $boxToken = $req->header('BoxToken');
        $diskSerialNumber = $req->header('DiskSerialNumber');
        $orderNo = $req->header('OrderNo');
        $userToken = $req->header('UserToken');

        //ambil data dari tabel box
        $sqlb = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND orderNo = '".$orderNo."'";
        $rb = DB::select($sqlb);

        if (count($rb) != 0) {

            $operator = array('name' => 'PopBox Asia', 'contactEmail' => [], 'level' => 1, 'deleteFlag' => $rb[0]->deleteFlag, 'contactPhoneNumber' => [], 'companyType' => 'OPERATOR', 'id' => $rb[0]->operator_id);
            
            $box_id = $rb[0]->id;
            $box_ = array( 'orderNo'=> $rb[0]->orderNo, 'name' => $rb[0]->name, 'id'=> $rb[0]->id);

            //ambil data sms account
            $sqls = "SELECT b.* FROM tb_newlocker_box a, tb_newlocker_smsaccount b WHERE a.sms_account_id=b.id AND a.id ='".$box_id."'";
            $rs = DB::select($sqls);
            $smsaccount = array('name' => $rs[0]->name, 'platformType' => $rs[0]->platformType, 'id' => $rs[0]->id);

            // ambil data cabinet berdasarkan locker
            $sqlc = "SELECT * FROM tb_newlocker_cabinet WHERE box_id = '".$box_id."'";
            $cabinets_ = DB::select($sqlc);
            //$cabinets_ = json_decode(json_encode($cabinets_), true);   
            //dd($cabinets_);     
            $dataresp = array();

            foreach ($cabinets_ as $cabinet_ ) {      

                $sqlm = "SELECT * FROM tb_newlocker_mouth WHERE cabinet_id = '".$cabinet_->id."'";
                $mouths_ = DB::select($sqlm);
                //$mouths_ = json_decode(json_encode($mouths_), true);   
                //dd($mouths_);

                $datasn = array();

                foreach ($mouths_ as $mouth_) {
                    $id_mouth = $mouth_->id_mouth;
                    $syncFlag = $mouth_->syncFlag;
                    $deleteFlag_ = $mouth_->deleteFlag;
                    $number = $mouth_->number;
                    $userPrice = $mouth_->userPrice;
                    $overduePrice = $mouth_->overduePrice;
                    $numberinCabinet = $mouth_->numberinCabinet;
                    $status = $mouth_->status;
             
                    // ambil data mouth type
                    $sqlmtype = "SELECT a.* FROM tb_newlocker_mouthtype a, tb_newlocker_mouth b WHERE a.id_mouthtype = b.mouthType_id AND b.id_mouth = '" .$id_mouth. "'";
                    $mouthtypes = DB::select($sqlmtype);
                    //$mouthtypes = json_decode(json_encode($mouthtypes), true);   

                    foreach ($mouthtypes as $mouthtype_) {
                        $defaultOverduePrice = $mouthtype_->defaultOverduePrice;
                        $id_mouthtype = $mouthtype_->id_mouthtype;
                        $deleteFlag = $mouthtype_->deleteFlag;
                        $defaultUserPrice = $mouthtype_->defaultUserPrice;
                        $name = $mouthtype_->name;
                        continue;
                    }

                    $mouthtype=array('defaultOverduePrice' => $defaultOverduePrice, 'id' => $id_mouthtype, 'deleteFlag' => $deleteFlag, 'name' => $name,  'defaultUsePrice' => $defaultUserPrice );

                    $datasn_ = ['syncFlag' => $syncFlag, 'deleteFlag' => $deleteFlag_, 'mouthType' => $mouthtype, 'number' => $number, 'usePrice' => $userPrice, 'overduePrice' => $overduePrice, 'numberInCabinet' => $numberinCabinet, 'status' => $status, 'id' => $id_mouth, 'openOrder' => null, 'box' => $box_ ];

                    if ($status=='USED') {
                        $express_id = $mouth_->express_id;
                        $sqle = "SELECT * FROM tb_newlocker_express WHERE id = '".$express_id."'";
                        $re = DB::select($sqle);

                        $sqllog = "SELECT * FROM tb_newlocker_company WHERE id_company = '".$re[0]->logisticsCompany_id."'";
                        $relog = DB::select($sqllog);
                        $logistic_ = array('level' => $relog[0]->level, 'companyType' => $relog[0]->company_type, 'name' => $relog[0]->company_name, 'id' => $relog[0]->id_company, 'contactEmail' => [], 'contactPhoneNumber' => [], 'deleteFlag' => $relog[0]->deleteFlag);

                        if (!empty($re[0]->electronicCommerce_id)){
                            $sqlecom = "SELECT * FROM tb_newlocker_company WHERE id_company = '".$re[0]->electronicCommerce_id."'";
                            $relecom = DB::select($sqlecom);
                            $electronicCommerce_ = array('address'=> $relecom[0]->company_address, 'name' => $relecom[0]->company_name, 'id' => $relecom[0]->id_company);
                        }

                        
                        if (!empty($re[0]->storeUser_id)) {
                            $sqlusr = "SELECT * FROM tb_newlocker_user WHERE id_user ='".$re[0]->storeUser_id."'";        
                            $reusr = DB::select($sqlusr);

                            if(count($reusr) != 0){
                                $storeUser_ = array('userNo' => '', 'id' => $reusr[0]->id_user, 'loginName' => $reusr[0]->username, 'userCardList' => [], 'name' => $reusr[0]->displayname, 'phoneNumber' => $reusr[0]->phone);                      
                            } else {
                                $storeUser_ = array('userNo' => '', 'id' => $re[0]->storeUser_id, 'loginName' => $re[0]->storeUserPhoneNumber, 'userCardList' => [], 'name' => $re[0]->storeUserPhoneNumber, 'phoneNumber' => $re[0]->storeUserPhoneNumber );                     
                            }
                        } else {
                            $storeUser_ = array('userNo' => '', 'id' => (!empty($re[0]->storeUser_id)) ? $re[0]->storeUser_id : hash("haval128,5", $express_id), 'loginName' => $re[0]->takeUserPhoneNumber, 'userCardList' => [], 'name' => $re[0]->takeUserPhoneNumber, 'phoneNumber' => $re[0]->takeUserPhoneNumber);
                        }

                        if ($re[0]->expressType=="COURIER_STORE") {
                            $takeUser_ = array('userNo' => '', 'id' => hash("haval128,5", $re[0]->takeUserPhoneNumber) , 'loginName' => $re[0]->takeUserPhoneNumber, 'userCardList' => [], 'name' => $re[0]->takeUserPhoneNumber, 'phoneNumber' => $re[0]->takeUserPhoneNumber);

                            $express_ = array('mouth' => $datasn_, 'storeUser' => $storeUser_, 'additionalPayment' => [], 'logisticsCompany' => $logistic_, 'version' => $re[0]->version, 'includedNumbers' => 0, 'items' => [], 'status' => $re[0]->status, 'expressType' => $re[0]->expressType, 'overdueTime' => $re[0]->overdueTime, 'storeTime' => $re[0]->storeTime, 'box' => $box_, 'groupName' => $re[0]->groupName, 'takeUserPhoneNumber' => $re[0]->takeUserPhoneNumber, 'expressNumber' => $re[0]->expressNumber, 'id' => $re[0]->id, 'validateCode' => $re[0]->validateCode, 'takeUser' => $takeUser_ );  

                        } elseif ($re[0]->expressType=="CUSTOMER_REJECT") {
                            $express_ = array('mouth' => $datasn_, 'storeUser' => $storeUser_, 'additionalPayment' => [], 'logisticsCompany' => $logistic_, 'version' => $re[0]->version, 'includedNumbers' => 0, 'items' => [], 'status' => $re[0]->status, 'expressType' => $re[0]->expressType, 'weight' => $re[0]->weight , 'storeTime' => $re[0]->storeTime, 'box' => $box_ , 'groupName' => $re[0]->groupName, 'chargeType' => $re[0]->chargeType, 'electronicCommerce' => $electronicCommerce_, 'storeUserPhoneNumber' => $re[0]->storeUserPhoneNumber, 'customerStoreNumber' => $re[0]->customerStoreNumber, 'id' => $re[0]->id );

                        } elseif ($re[0]->expressType=="CUSTOMER_STORE") {
                            $express_ = array('mouth' => $datasn_, 'storeUser' => $storeUser_, 'additionalPayment' => [], 'logisticsCompany' => $logistic_, 'version' => $re[0]->version, 'includedNumbers' => 0, 'items' => [], 'status' => $re[0]->status, 'expressType' => $re[0]->expressType, 'weight' => $re[0]->weight , 'storeTime' => $re[0]->storeTime, 'box' => $box_, 'groupName' => $re[0]->groupName , 'chargeType' => $re[0]->chargeType, 'endAddress' => $re[0]->endAddress, 'takeUserPhoneNumber' => $re[0]->takeUserPhoneNumber, 'customerStoreNumber' => $re[0]->customerStoreNumber, 'id' => $re[0]->id, 'recipientName' =>  $re[0]->recipientName, 'recipientUserPhoneNumber' => $re[0]->recipientUserPhoneNumber);
                        }

                        $datasn_['express'] = $express_;
                    }

                    array_push($datasn, $datasn_);             
                }
                
                $data = array();
                $data["number"] = $cabinet_->number;
                $data["deleteFlag"] =  $cabinet_->deleteFlag;
                $data["id"] = $cabinet_->id;
                $data["mouths"] = $datasn;
                $dataresp[] = $data;
            }

            $res =  ['operator' => $operator , 'freeHours' => $rb[0]->freeHours, 'items' => [] , 'name' => $rb[0]->name, 'freeDays' => $rb[0]->freeDays, 'currencyUnit' => $rb[0]->currencyUnit, 'deleteFlag' => $rb[0]->deleteFlag, 'overdueType' => $rb[0]->overdueType, 'cabinets' => $dataresp,  'bookingList' => [], 'zoneId' => $rb[0]->zoneId, 'orderNo' => $rb[0]->orderNo, 'smsAccount' => $smsaccount, 'validateType' => $rb[0]->validateType , 'startTime' => 3, 'activityList' => [], 'id' =>$box_id, 'syncFlag' => $rb[0]->syncFlag ];

        } else {

            $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];

        }

        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/box/init',
                    'api_send_data' => json_encode(['Box Initiation' => $orderNo]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res);
    }

    public function boxfinish (Request $req) {
        $orderNo = $req->header('orderNo');
        $id = $req->json('id');
        $pushMessageType = $req->json('pushMessageType');
        $value = $req->json('value');
        if(!empty($pushMessageType) && !empty($value)){
            $status = 'INITIATED';
            $result = 'COMPLETED';
        } else {
            $pushMessageType = 'Unknown Task Type';
            $value = 'Unknown Value';
            $status = 'Unknown Status';
            $result = 'Unknown Result';
        }

        $sql = "SELECT * FROM tb_newlocker_box where orderNo = '".$orderNo."'";
        $r = DB::select($sql);

        if (count($r) != 0) {
            $sql_ = "SELECT * FROM tb_newlocker_tasks where id = '".$id."'";
            $r_ = DB::select($sql_);

            if (count($r_) != 0){
                //update data task
                DB::table('tb_newlocker_tasks')
                    ->where('id', $id)
                            ->update(array(
                                    'status' =>  $status,
                                    'result' => $result
                ));

                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id, 'Task Type' => $pushMessageType,'status' => $result]];

            } else {
                $res = ['statusCode' => 404, 'errorMessage' => 'Task Id Not Found!'];
            }

        } else {
            $res = ['statusCode' => 404, 'errorMessage' => 'Box Id Unknown!'];
        }
        //insert ke tabel log
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/box/finish',
                    'api_send_data' => json_encode(['id' => $id, 'pushMessageType' => $pushMessageType]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res); 
    }

      /*====================NON-LOCKER API======================*/
    //For below these non-locker API(s), need to put usertoken on Header as mandatory
    public function boxinfo (Request $req, $imported) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{
            $box_id = $imported;
            //ambil data dari tabel box
            $sqlb = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND id = '".$box_id."'";
            $rb = DB::select($sqlb);


            if (isset($imported) && count($rb) != 0) {
                $operator = array('name' => 'PopBox Asia', 'contactEmail' => [], 'level' => 1, 'deleteFlag' => $rb[0]->deleteFlag, 'contactPhoneNumber' => [], 'companyType' => 'OPERATOR', 'id' => $rb[0]->operator_id);
                $box_id = $rb[0]->id;
                $box_ = array( 'orderNo'=> $rb[0]->orderNo, 'name' => $rb[0]->name, 'id'=> $rb[0]->id);

                //ambil data sms account
                $sqls = "SELECT b.* FROM tb_newlocker_box a, tb_newlocker_smsaccount b WHERE a.sms_account_id=b.id AND a.id ='".$box_id."'";
                $rs = DB::select($sqls);
                $smsaccount = array('name' => $rs[0]->name, 'platformType' => $rs[0]->platformType, 'id' => $rs[0]->id);

                // ambil data status locker
                $sqlstat = "SELECT * FROM tb_newlocker_machinestat WHERE locker_id = '".$box_id."'";
                $statLock = DB::select($sqlstat);

                $current_ = array();

                if (count($statLock) != 0) {
                    $current_ = ['cpu_temperature' => $statLock[0]->cpu, 
                    'disk_free' => $statLock[0]->disk, 
                    'memory_free' => $statLock[0]->memory, 
                    'disk_serial_no' => $statLock[0]->disk_serial_no, 
                    'gui_version' => $statLock[0]->gui_version, 
                    'online' => ($statLock[0]->conn_status == 0) ? False : True]; 
                } 

                // ambil data cabinet berdasarkan locker
                $sqlc = "SELECT * FROM tb_newlocker_cabinet WHERE box_id = '".$box_id."'";
                $cabinets_ = DB::select($sqlc);
                //$cabinets_ = json_decode(json_encode($cabinets_), true);   
                //dd($cabinets_);     
                $dataresp = array();

                foreach ($cabinets_ as $cabinet_ ) {      

                    $sqlm = "SELECT * FROM tb_newlocker_mouth WHERE cabinet_id = '".$cabinet_->id."'";
                    $mouths_ = DB::select($sqlm);
                    //$mouths_ = json_decode(json_encode($mouths_), true);   
                    //dd($mouths_);

                    $datasn = array();
                    
                    foreach ($mouths_ as $mouth_) {
                        $id_mouth = $mouth_->id_mouth;
                        $syncFlag = $mouth_->syncFlag;
                        $deleteFlag_ = $mouth_->deleteFlag;
                        $number = $mouth_->number;
                        $userPrice = $mouth_->userPrice;
                        $overduePrice = $mouth_->overduePrice;
                        $numberinCabinet = $mouth_->numberinCabinet;
                        $status = $mouth_->status;
             
                    // ambil data mouth type
                    $sqlmtype = "SELECT a.* FROM tb_newlocker_mouthtype a, tb_newlocker_mouth b WHERE a.id_mouthtype = b.mouthType_id AND b.id_mouth = '" .$id_mouth. "'";
                    $mouthtypes = DB::select($sqlmtype);
                    //$mouthtypes = json_decode(json_encode($mouthtypes), true);   

                        foreach ($mouthtypes as $mouthtype_) {
                            $defaultOverduePrice = $mouthtype_->defaultOverduePrice;
                            $id_mouthtype = $mouthtype_->id_mouthtype;
                            $deleteFlag = $mouthtype_->deleteFlag;
                            $defaultUserPrice = $mouthtype_->defaultUserPrice;
                            $name = $mouthtype_->name;
                            continue;
                    }

                    $mouthtype=array('defaultOverduePrice' => $defaultOverduePrice, 'id' => $id_mouthtype, 'deleteFlag' => $deleteFlag, 'name' => $name,  'defaultUsePrice' => $defaultUserPrice );

                    $datasn_ = ['syncFlag' => $syncFlag, 'deleteFlag' => $deleteFlag_, 'mouthType' => $mouthtype, 'number' => $number, 'usePrice' => $userPrice, 'overduePrice' => $overduePrice, 'numberInCabinet' => $numberinCabinet, 'status' => $status, 'id' => $id_mouth, 'openOrder' => null, 'box' => $box_ ];

                    if ($status=='USED') {
                        $express_id = $mouth_->express_id;
                        $sqle = "SELECT * FROM tb_newlocker_express WHERE id = '".$express_id."'";
                        $re = DB::select($sqle);

                        $sqllog = "SELECT * FROM tb_newlocker_company WHERE id_company = '".$re[0]->logisticsCompany_id."'";
                        $relog = DB::select($sqllog);
                        $logistic_ = array('level' => $relog[0]->level, 'companyType' => $relog[0]->company_type, 'name' => $relog[0]->company_name, 'id' => $relog[0]->id_company, 'contactEmail' => [], 'contactPhoneNumber' => [], 'deleteFlag' => $relog[0]->deleteFlag);

                        if (!empty($re[0]->electronicCommerce_id)){
                            $sqlecom = "SELECT * FROM tb_newlocker_company WHERE id_company = '".$re[0]->electronicCommerce_id."'";
                            $relecom = DB::select($sqlecom);
                            $electronicCommerce_ = array('address'=> $relecom[0]->company_address, 'name' => $relecom[0]->company_name, 'id' => $relecom[0]->id_company);
                        }

                        
                        if (!empty($re[0]->storeUser_id)) {
                            $sqlusr = "SELECT * FROM tb_newlocker_user WHERE id_user ='".$re[0]->storeUser_id."'";        
                            $reusr = DB::select($sqlusr);

                            if(count($reusr) != 0){
                                $storeUser_ = array('userNo' => '', 'id' => $reusr[0]->id_user, 'loginName' => $reusr[0]->username, 'userCardList' => [], 'name' => $reusr[0]->displayname, 'phoneNumber' => $reusr[0]->phone);                      
                            } else {
                                $storeUser_ = array('userNo' => '', 'id' => $re[0]->storeUser_id, 'loginName' => $re[0]->storeUserPhoneNumber, 'userCardList' => [], 'name' => $re[0]->storeUserPhoneNumber, 'phoneNumber' => $re[0]->storeUserPhoneNumber );                     
                            }
                        } else {
                            $storeUser_ = array('userNo' => '', 'id' => (!empty($re[0]->storeUser_id)) ? $re[0]->storeUser_id : hash("haval128,5", $express_id), 'loginName' => $re[0]->takeUserPhoneNumber, 'userCardList' => [], 'name' => $re[0]->takeUserPhoneNumber, 'phoneNumber' => $re[0]->takeUserPhoneNumber);
                        }

                        if ($re[0]->expressType=="COURIER_STORE") {
                            $takeUser_ = array('userNo' => '', 'id' => hash("haval128,5", $re[0]->takeUserPhoneNumber) , 'loginName' => $re[0]->takeUserPhoneNumber, 'userCardList' => [], 'name' => $re[0]->takeUserPhoneNumber, 'phoneNumber' => $re[0]->takeUserPhoneNumber);

                            $express_ = array('mouth' => $datasn_, 'storeUser' => $storeUser_, 'additionalPayment' => [], 'logisticsCompany' => $logistic_, 'version' => $re[0]->version, 'includedNumbers' => 0, 'items' => [], 'status' => $re[0]->status, 'expressType' => $re[0]->expressType, 'overdueTime' => $re[0]->overdueTime, 'storeTime' => $re[0]->storeTime, 'box' => $box_, 'groupName' => $re[0]->groupName, 'takeUserPhoneNumber' => $re[0]->takeUserPhoneNumber, 'expressNumber' => $re[0]->expressNumber, 'id' => $re[0]->id, 'validateCode' => $re[0]->validateCode, 'takeUser' => $takeUser_ );  

                        } elseif ($re[0]->expressType=="CUSTOMER_REJECT") {
                            $express_ = array('mouth' => $datasn_, 'storeUser' => $storeUser_, 'additionalPayment' => [], 'logisticsCompany' => $logistic_, 'version' => $re[0]->version, 'includedNumbers' => 0, 'items' => [], 'status' => $re[0]->status, 'expressType' => $re[0]->expressType, 'weight' => $re[0]->weight , 'storeTime' => $re[0]->storeTime, 'box' => $box_ , 'groupName' => $re[0]->groupName, 'chargeType' => $re[0]->chargeType, 'electronicCommerce' => $electronicCommerce_, 'storeUserPhoneNumber' => $re[0]->storeUserPhoneNumber, 'customerStoreNumber' => $re[0]->customerStoreNumber, 'id' => $re[0]->id );

                        } elseif ($re[0]->expressType=="CUSTOMER_STORE") {
                            $express_ = array('mouth' => $datasn_, 'storeUser' => $storeUser_, 'additionalPayment' => [], 'logisticsCompany' => $logistic_, 'version' => $re[0]->version, 'includedNumbers' => 0, 'items' => [], 'status' => $re[0]->status, 'expressType' => $re[0]->expressType, 'weight' => $re[0]->weight , 'storeTime' => $re[0]->storeTime, 'box' => $box_, 'groupName' => $re[0]->groupName , 'chargeType' => $re[0]->chargeType, 'endAddress' => $re[0]->endAddress, 'takeUserPhoneNumber' => $re[0]->takeUserPhoneNumber, 'customerStoreNumber' => $re[0]->customerStoreNumber, 'id' => $re[0]->id, 'recipientName' =>  $re[0]->recipientName, 'recipientUserPhoneNumber' => $re[0]->recipientUserPhoneNumber);
                        }

                        $datasn_['express'] = $express_;
                    }

                    array_push($datasn, $datasn_);             
                }
                    
                    $data = array();
                    $data["number"] = $cabinet_->number;
                    $data["deleteFlag"] =  $cabinet_->deleteFlag;
                    $data["id"] = $cabinet_->id;
                    $data["mouths"] = $datasn;
                    $dataresp[] = $data;
                }

                $res =  ['operator' => $operator , 'freeHours' => $rb[0]->freeHours, 'items' => [] , 'name' => $rb[0]->name, 'freeDays' => $rb[0]->freeDays, 'currencyUnit' => $rb[0]->currencyUnit, 'deleteFlag' => $rb[0]->deleteFlag, 'overdueType' => $rb[0]->overdueType, 'cabinets' => $dataresp,  'bookingList' => [], 'zoneId' => $rb[0]->zoneId, 'orderNo' => $rb[0]->orderNo, 'smsAccount' => $smsaccount, 'validateType' => $rb[0]->validateType , 'startTime' => 3, 'activityList' => [], 'id' =>$box_id, 'syncFlag' => $rb[0]->syncFlag, 'currentStatus' => $current_ ];
            } else {
                $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];
            }
            
            //insert ke tabel generallog
            // DB::table('tb_newlocker_generallog')
            //         ->insert([
            //             'api_url' =>  'http://pr0x.clientname.id'.'/box/info/'.$imported,
            //             'api_send_data' => json_encode(['Box Information' => $box_id]),
            //             'api_response' => json_encode($res),
            //             'response_date' => date("Y-m-d H:i:s")
            //         ]);
        }

        return response()->json($res);
    }

    public function boxdetails (Request $req){
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{
            $res = array();
            $sqlbox = "SELECT a.locker_id AS id, b.name, b.orderNo FROM tb_newlocker_machinestat a, tb_newlocker_box b WHERE a.locker_name not LIKE '%Test%' AND a.locker_id=b.id";
            $boxes = DB::select($sqlbox);

            if (count($boxes) != 0){
                foreach ($boxes as $box_) {
                    array_push($res, $box_);
                }                
            }
        }

        return response()->json($res);
    }

    public function guiInfo (Request $req){
        $orderNo = $req->header('OrderNo');
        $gui_version = $req->json('gui_version');

        //ambil data dari tabel box
        $sqlb = "SELECT id FROM tb_newlocker_box WHERE deleteFlag = '0' AND orderNo = '".$orderNo."'";
        $rb = DB::select($sqlb);

        if (count($rb) != 0) {
            $box_id = $rb[0]->id;

            if(!empty($gui_version)){
                DB::table('tb_newlocker_machinestat')
                    ->where('locker_id', $box_id)
                        ->update(array('gui_version' => $gui_version));  
                                
                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['box_id' => $box_id,  'status' => 'Gui Version Info Received']];
            } else {
                $res = ['statusCode' => 501, 'errorMessage' => 'Missing GUI Version Information!'];
            }
        } else {
            $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];
        }
        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/box/guiInfo',
                    'api_send_data' => json_encode(['Box Gui Info ' => $orderNo]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res);
    }

    public function tvcLog (Request $req){
        $orderNo = $req->header('OrderNo');
        $filename = $req->json('filename');
        $playtime = $req->json('playtime');
        $country = $req->json('country');
        $url_ = "https://internalapi.clientname.id/synclocker/tvclog"; //replication url
        $param_ = null; 
        $resp_ = null;

        //ambil data dari tabel box
        $sqlb = "SELECT id, name FROM tb_newlocker_box WHERE deleteFlag = '0' AND orderNo = '".$orderNo."'";
        $rb = DB::select($sqlb);

        if (count($rb) != 0) {
            $box_id = $rb[0]->id;
            $box_name = $rb[0]->name;

	    	
            if(!empty($filename) && !empty($playtime) && !empty($country)){
                $check_ = DB::table('tb_newlocker_tvcmedia_log')->select('count')
                ->where('filename', $filename)->where('locker_id', $box_id)
                ->where('playtime', $playtime)->get();

	        
                if (count($check_) != 0){                    
                    DB::table('tb_newlocker_tvcmedia_log')->where('filename', $filename)->where('locker_id', $box_id)
                        ->where('playtime', $playtime)->update(['count' => $check_[0]->count + 1]);
                } else {
                    DB::table('tb_newlocker_tvcmedia_log')
                        ->insert(['filename' => $filename, 
                            'locker_id' => $box_id, 
                            'locker_name' => $box_name,
                            'playtime' => $playtime,
                            'country' => $country,
                            'count' => 1]);                                  
                }

		
                $param_ = [ "country" => $country, "filename" => $filename, "locker_id" => $box_id, "locker_name" => $box_name, "playtime" => $playtime];

                $resp_ = $this->post_data($url_, json_encode($param_));

                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['box_id' => $box_id,  'status' => 'TVC Log Recorded']];
		
            } else {
                $res = ['statusCode' => 501, 'errorMessage' => 'Missing TVC Log Information!'];
            }
        } else {
            $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];
        }
        
        //insert ke tabel generallog - DISABLED on 2018-05-03 11:54
        // DB::table('tb_newlocker_generallog')
        //         ->insert([
        //             ['api_url' =>  'http://pr0x.clientname.id'.'/box/tvcLog',
        //             'api_send_data' => json_encode(['Box TVC Log Info' => $orderNo]),
        //             'api_response' => json_encode($res),
        //             'response_date' => date("Y-m-d H:i:s")],
        //             ['api_url' =>  $url_,
        //             'api_send_data' => json_encode($param_),
        //             'api_response' => json_encode($resp_),
        //             'response_date' => date("Y-m-d H:i:s")]
        //             ]);

        return response()->json($res);
    }

    public function tvcList (Request $req){
        $orderNo = $req->header('OrderNo');
        $list = $req->json('tvclist');

        //ambil data dari tabel box
        $sqlb = "SELECT id FROM tb_newlocker_box WHERE deleteFlag = '0' AND orderNo = '".$orderNo."'";
        $rb = DB::select($sqlb);

        if (count($rb) != 0) {
            $box_id = $rb[0]->id;

            if(!empty($list)){
                DB::table('tb_newlocker_machinestat')
                    ->where('locker_id', $box_id)
                        ->update(['tvclist' => $list]);                                  
                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['box_id' => $box_id,  'status' => 'TVC Log Recorded']];
            } else {
                $res = ['statusCode' => 501, 'errorMessage' => 'Missing TVC List Information!'];
            }
        } else {
            $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];
        }
        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/box/tvcList',
                    'api_send_data' => json_encode(['Box TVC List Info ' => $orderNo]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res);
    }

    public function activityLog (Request $req){
        $orderNo = $req->header('OrderNo');
        $activity = $req->json('activity');
        $recordtime = $req->json('recordtime');
        $country = $req->json('country');
        $url_ = "https://internalapi.clientname.id/synclocker/activitylog"; //replication url
        $param_ = null; 
        $resp_ = null;

        //ambil data dari tabel box
        $sqlb = "SELECT id, name FROM tb_newlocker_box WHERE deleteFlag = '0' AND orderNo = '".$orderNo."'";
        $rb = DB::select($sqlb);

        if (count($rb) != 0) {
            $box_id = $rb[0]->id;
            $box_name = $rb[0]->name;

            if(!empty($activity) && !empty($recordtime) && !empty($country)){
                $check_ = DB::table('tb_newlocker_activity_log')->select('count')
                ->where('activity', $activity)->where('locker_id', $box_id)
                ->where('recordtime', $recordtime)->get();
                if (count($check_) != 0){                    
                    DB::table('tb_newlocker_activity_log')->where('activity', $activity)->where('locker_id', $box_id)
                        ->where('recordtime', $recordtime)->update(['count' => $check_[0]->count + 1]);
                } else {
                    DB::table('tb_newlocker_activity_log')
                        ->insert(['activity' => $activity, 
                            'locker_id' => $box_id, 
                            'locker_name' => $box_name,
                            'recordtime' => $recordtime,
                            'country' => $country,
                            'count' => 1]);                                  
                }

                $param_ = [ "country" => $country, "activity" => $activity, "locker_id" => $box_id, "locker_name" => $box_name, "recordtime" => $recordtime];

                $resp_ = $this->post_data($url_, json_encode($param_));

                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['box_id' => $box_id,  'status' => 'Activity Log Recorded']];
            } else {
                $res = ['statusCode' => 501, 'errorMessage' => 'Missing Activity Log Information!'];
            }
        } else {
            $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];
        }
        
        //insert ke tabel generallog - DISABLED on 2018-05-03 11:54
        // DB::table('tb_newlocker_generallog')
        //         ->insert([
        //             ['api_url' =>  'http://pr0x.clientname.id'.'/box/tvcLog',
        //             'api_send_data' => json_encode(['Box TVC Log Info' => $orderNo]),
        //             'api_response' => json_encode($res),
        //             'response_date' => date("Y-m-d H:i:s")],
        //             ['api_url' =>  $url_,
        //             'api_send_data' => json_encode($param_),
        //             'api_response' => json_encode($resp_),
        //             'response_date' => date("Y-m-d H:i:s")]
        //             ]);

        return response()->json($res);
    }
 


}
