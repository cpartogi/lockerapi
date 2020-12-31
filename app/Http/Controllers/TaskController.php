<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use Illuminate\Support\Facades\Validator;


class TaskController extends Controller{
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
    public function taskfinish (Request $req) {
        $orderNo = $req->header('orderNo');
        $id = $req->json('id');
        $result = $req->json('result');
        $statusType = $req->json('statusType');
        if (empty($statusType)) {
            $statusType = 'DONE';
        } 
        if (empty($result)) {
            $result = 'SUCCESS';
        }
        $taskType = null;

        $sql = "SELECT * FROM tb_newlocker_box where orderNo = '".$orderNo."'";
        $r = DB::select($sql);
        
        if(count($r) != 0){

            $sql_ = "SELECT * FROM tb_newlocker_tasks where id = '".$id."'";
            $r_ = DB::select($sql_);            

            if (count($r_) != 0) {
                $taskType = $r_[0]->task;
                 //update data task
            DB::table('tb_newlocker_tasks')
                ->where('id', $id)
                    ->update(array(
                            'status' =>  $statusType,
                            'result' => $result
                ));

                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id, 'taskType' => $taskType, 'status' => $result]];
            } else {
                $res = ['response'=> ['code' => 404, 'message' => 'Task Id Unknown!'],  'data' => []];
            }

        } else {
            $res = ['response'=> ['code' => 404, 'message' => 'Box Id Unknown!'],  'data' => []];
        }

        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/task/finish',
                    'api_send_data' => json_encode(['id' => $id, 'taskType' => $taskType]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res); 
    }

    public function migratelocker(Request $req) {
        $operatorId = $req->json('operator.id');
        $freeHours = $req->json('freeHours');
        $name = $req->json('name');
        $freeDays = $req->json('freeDays');
        $currencyUnit = $req->json('currencyUnit');
        $deleteFlag = $req->json('deleteFlag');
        $overdueType = $req->json('overdueType');
        $cabinets = $req->json('cabinets'); //bulk cabinet & mouth data
        $zoneId = $req->json('zoneId');
        $orderNo = $req->json('orderNo');
        $smsAccountId = $req->json('smsAccount.id');
        $validateType = $req->json('validateType');
        $syncFlag = $req->json('syncFlag');
        $id = $req->json('id');

        //update/insert data box
        $sqlc = "SELECT id FROM tb_newlocker_box WHERE id='".$id."'";
        $rc = DB::select($sqlc);

        if (count($rc) != 0 ) {
            DB::table('tb_newlocker_box')
            ->where('id', $id)
                    ->update(array(
                        'sms_account_id' => $smsAccountId,
                        'receiptNo' => 0,
                        'overdueType' => $overdueType,
                        'freeHours' => $freeHours,
                        'freeDays' => $freeDays,
                        'operator_id' => $operatorId,
                        'validateType' => $validateType,
                        'syncFlag' => $syncFlag,
                        'orderNo' => $orderNo,
                        'name' => $name,
                        'deleteFlag' => $deleteFlag,
                        'zoneId' => $zoneId,
                        'currencyUnit' => $currencyUnit
            ));
        } else {
            DB::table('tb_newlocker_box')
                    ->insert([
                        'sms_account_id' => $smsAccountId,
                        'receiptNo' => 0,
                        'overdueType' => $overdueType,
                        'freeHours' => $freeHours,
                        'freeDays' => $freeDays,
                        'operator_id' => $operatorId,
                        'validateType' => $validateType,
                        'syncFlag' => $syncFlag,
                        'orderNo' => $orderNo,
                        'name' => $name,
                        'deleteFlag' => $deleteFlag,
                        'zoneId' => $zoneId,
                        'currencyUnit' => $currencyUnit,
                        'id' => $id
             ]);
        }

        /*//update cabinet dan mouth dengan clear up data awalnya */
        try { 
            DB::table('tb_newlocker_cabinet')->where('box_id', $id)->delete();
            DB::table('tb_newlocker_mouth')->where('box_id', $id)->delete();
            DB::table('tb_newlocker_express')->where('box_id', $id)->delete();
        } catch (Exception $e) {
            echo $e ."\n";
            //break;
        }

        //print_r($cabinets); 
        $process = 0;

        foreach ($cabinets as $cabinet) {
            // $cabinet_ = json_decode($cabinet);
            if (is_array($cabinet)) {             
                $numberC = $cabinet['number'];
                $deleteFlagC = $cabinet['deleteFlag'];
                $idC = $cabinet['id'];
                $mouths = $cabinet['mouths'];

                //re-input data cabinet
                DB::table('tb_newlocker_cabinet')
                    ->insert([           
                            'box_id' => $id,
                            'deleteFlag' => $deleteFlagC,
                            'number' => $numberC,
                            'id' => $idC
                ]);
                        
                foreach ($mouths as $mouth) {
                    if (is_array($mouth)) {
                            $idM = $mouth['id'];
                            $numberM = $mouth['number'];
                            $deleteFlagM = $mouth['deleteFlag'];
                            $syncFlagM = $mouth['syncFlag'];
                            $numberInCabinetM = $mouth['numberInCabinet'];
                            $statusM = $mouth['status'];
                            $mouthType_id = $mouth['mouthType']['id'];
                            $express_idM = null;
                            $user_idM = null;


                            if ($statusM=='USED') {
                                $express = $mouth['express'];
                                $express_idM = $express['id'];

                                $sqlc_ = "SELECT * FROM tb_newlocker_express WHERE id = '".$express_idM."'";
                                $resc_ = DB::select($sqlc_);

                                $exist = (count($resc_) != 0) ? true : false;
                                if ($exist) {
                                    try{
                                        DB::table('tb_newlocker_express')->where('id', $express_idM)->delete();
                                    }catch(Exception $e) {
                                        echo $e ."\n";
                                    }
                                }

                                if ($express['expressType'] == "COURIER_STORE"){
                                    $user_idM = $express['storeUser']['id'];
                                    if(!empty($express['groupName'])){
                                        $groupName_ = $express['groupName'];
                                    } else {
                                        $groupName_ = "UNDEFINED";
                                    }

                                    DB::table('tb_newlocker_express')
                                        ->insert([
                                            'expressNumber' => $express['expressNumber'],
                                            'expressType' => $express['expressType'],
                                            'takeUserPhoneNumber' => $express['takeUserPhoneNumber'],
                                            'status' => $express['status'],
                                            'groupName' => $groupName_,
                                            'overdueTime' => $express['overdueTime'],
                                            'storeTime' => $express['storeTime'],
                                            'syncFlag' => 1,
                                            'validateCode' => $express['validateCode'],
                                            'version' => $express['version'],
                                            'box_id' => $express['box']['id'],
                                            'logisticsCompany_id' => $express['logisticsCompany']['id'],
                                            'mouth_id' => $idM,
                                            'operator_id' => $operatorId,
                                            'storeUser_id' => $user_idM,
                                            'takeUser_id' => $express['takeUser']['id'],
                                            'id' => $express['id'],
                                            'lastModifiedTime' => time() * 1000
                                    ]); 

                                } else {
                                    if ($express['expressType'] == "CUSTOMER_REJECT"){
                                        $user_idM = $express['storeUser']['id'];
                                        $electronicCommerce_id_ = $express['electronicCommerce']['id'];
                                        $endAddress_ = null;
                                        $takeUserPhoneNumber_ = null;
                                        $storeUserPhoneNumber_ = $express['storeUserPhoneNumber'];                                                                                
                                    }else if ($express['expressType'] == "CUSTOMER_STORE"){
                                        $electronicCommerce_id_ = null;
                                        $endAddress_ = $express['endAddress'];
                                        $user_idM = null;
                                        $takeUserPhoneNumber_ = $express['takeUserPhoneNumber'];
                                        $storeUserPhoneNumber_ = null;
                                    }
                                    
                                    if(!empty($express['groupName'])){
                                        $groupName_ = $express['groupName'];
                                    } else {
                                        $groupName_ = "UNDEFINED";
                                    }

                                    DB::table('tb_newlocker_express')
                                        ->insert([
                                            'customerStoreNumber' => $express['customerStoreNumber'],
                                            'expressType' => $express['expressType'],
                                            'status' => $express['status'],
                                            'groupName' => $groupName_,
                                            'storeTime' => $express['storeTime'],
                                            'syncFlag' => 1,
                                            'storeUserPhoneNumber' => $storeUserPhoneNumber_,
                                            'takeUserPhoneNumber' => $takeUserPhoneNumber_,
                                            'version' => $express['version'],
                                            'box_id' => $express['box']['id'],
                                            'logisticsCompany_id' => $express['logisticsCompany']['id'],
                                            'mouth_id' => $idM,
                                            'operator_id' => $operatorId,
                                            'storeUser_id' => $user_idM,
                                            'chargeType' => $express['chargeType'],
                                            'weight' => 0,
                                            'endAddress' => $endAddress_,
                                            'electronicCommerce_id' => $electronicCommerce_id_,
                                            'id' => $express['id'],
                                            'lastModifiedTime' => time() * 1000
                                    ]);
                                }                                   
                            } 

                        DB::table('tb_newlocker_mouth')
                            ->insert([         
                                'id_mouth'          => $idM,
                                'deleteFlag'        => $deleteFlagM,
                                'number'            => $numberM,
                                'numberinCabinet'   => $numberInCabinetM,
                                'overduePrice'      => 0,
                                'status'            => $statusM,
                                'syncFlag'          => $syncFlagM,
                                'userPrice'         => 0,
                                'box_id'            => $id,
                                'cabinet_id'        => $idC,
                                'mouthType_id'      => $mouthType_id,
                                'express_id'        => $express_idM,
                                'user_id'           => $user_idM
                        ]);

                    }

                    $process++;
                }
            }
        }

        if ($process != 0) {

            $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id, 'status' => 'MIGRATED']];            

        } else {

            $res = ['response'=> ['code' => 999, 'message' => 'ERROR'],  'data' => []];
        }
        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/start/migrate',
                'api_send_data' => json_encode(['name' => $name, 'zoneId' => $zoneId, 'timeStamp' => time() * 1000]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
        ]);

        return response()->json($res); 
    }

    public function alertCreate (Request $req){
        /*Sample Json
        param = {
                'alertType': alert_type,
                'alertStatus': 'ABNORMAL',
                'createTime': ClientTools.now(),
                'id': ClientTools.get_uuid(),
                'operator_id': box_info['operator_id'],
                'syncFlag': 0,
                'box_id': box_info['id'],
                'alertvalue': alert_value,
                'value_id': value_id
            }*/

        $orderNo = $req->header('orderNo');
        $id = $req->json('id');
        $alertType = $req->json('alertType');
        $alertStatus = $req->json('alertStatus');
        $createTime = $req->json('createTime');
        $operator_id = $req->json('operator_id');
        $syncFlag = $req->json('syncFlag');
        $box_id = $req->json('box_id');
        $alertvalue = $req->json('alertvalue');
        $value_id = $req->json('value_id');
        $box_name = null;

        if (empty($alertType)){
            $alertType = 'UNKNOWN';
        }
       
        $sql = "SELECT * FROM tb_newlocker_box where orderNo = '".$orderNo."'";
        $r = DB::select($sql);
        
        if(count($r) != 0){
            $sql_ = "SELECT * FROM tb_newlocker_alert where id = '".$id."'";
            $r_ = DB::select($sql_);            
            $box_name = (!empty($r[0]->name)) ? $r[0]->name : 'UNKNOWN' ;       

            if (count($r_) != 0) {
                DB::table('tb_newlocker_alert')
                    ->where('id', $id)
                        ->update(array(
                            'alertType' =>  $alertType,
                            'alertStatus' =>  $alertStatus,
                            'createTime' =>  $createTime,
                            'operator_id' =>  $operator_id,
                            'syncFlag' =>  $syncFlag,
                            'box_id' =>  $box_id,
                            'alertvalue' =>  $alertvalue,
                            'value_id' =>  $value_id                            
                        ));
                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id, 'alertType' => $alertType, 'alertStatus' => $alertStatus, 'status' => 'ALERT SYNCED']];
            } else {
                DB::table('tb_newlocker_alert')
                    ->insert([
                            'alertType' =>  $alertType,
                            'alertStatus' =>  $alertStatus,
                            'createTime' =>  $createTime,
                            'operator_id' =>  $operator_id,
                            'syncFlag' =>  $syncFlag,
                            'box_id' =>  $box_id,
                            'alertvalue' =>  $alertvalue,
                            'value_id' =>  $value_id,
                            'id' => $id                           
                        ]);
                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id, 'alertType' => $alertType, 'alertStatus' => $alertStatus, 'status' => 'ALERT RECORDED']];
            }

        } else {
            $res = ['response'=> ['code' => 404, 'message' => 'Box Id Unknown!'],  'data' => []];
        }

        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/alert/create',
                    'api_send_data' => json_encode(['id' => $id, 'alertType' => $alertType, 'box_name' => $box_name]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res);
    }

    /*====================NON-LOCKER API======================*/
    //For below these non-locker API(s), need to put usertoken on Header as mandatory
    
    public function remoteUpdateBox (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $name = $req->json('name');
        $box_id = null;
        $id_task = null;

        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => []];   
        }else{            
            if (!empty($id)) {
                $sql = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND id ='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $box_id = $r[0]->id;
                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);

                    if(!empty($name)){
                        DB::table('tb_newlocker_box')
                            ->where('id', $id)
                                ->update(array(
                                        'name' => $name
                                    ));                              
                    }

                    DB::table('tb_newlocker_tasks')
                        ->insert([
                            'id' => $id_task,
                            'box_id' => $id,
                            'status' => 'COMMIT',
                            'task' => 'UPDATE_BOX',
                            'messageType' => 'ASYNC_TASK',
                            'createTime' => $timestamp
                        ]);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'box_id' => $box_id,'status' => 'REMOTE UPDATE BOX TASK CREATED']];

                }else{
                    $res = ['response'=> ['code' => 401, 'message' => 'Box Id Not Found!'],  'data' => []];
                }

            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing or Invalid Parameter!'],  'data' => []];
            }               
            
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/box/remoteUpdate',
                'api_send_data' => json_encode(['id' => $id_task, 'box_id' => $box_id]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }

    public function forceInitBox (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $box_id = null;
        $id_task = null;

        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => []];   
        }else{            
            if (!empty($id)) {
                $sql = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND id ='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $box_id = $r[0]->id;
                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);

                    DB::table('tb_newlocker_tasks')
                        ->insert([
                            'id' => $id_task,
                            'box_id' => $id,
                            'status' => 'COMMIT',
                            'task' => 'INIT_CLIENT',
                            'messageType' => 'INIT_CLIENT',
                            'createTime' => $timestamp
                        ]);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'box_id' => $box_id,'status' => 'FORCE INIT BOX TASK CREATED']];

                }else{
                    $res = ['response'=> ['code' => 401, 'message' => 'Box Id Not Found!'],  'data' => []];
                }

            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing or Invalid Parameter!'],  'data' => []];
            }               
            
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/box/forceInit',
                'api_send_data' => json_encode(['id' => $id_task, 'box_id' => $box_id]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }

    public function resetExpress (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $overdueTime = $req->json('overdueTime');
        $expressNumber = null;
        $id_task = null;

        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => []];   
        }else{            
            if (!empty($id) && !empty($overdueTime)) {                
                $sql = "SELECT * FROM tb_newlocker_express WHERE deleteFlag = '0' AND status = 'IN_STORE' AND expressType = 'COURIER_STORE' AND id='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $expressNumber = $r[0]->expressNumber;
                    $box_id = $r[0]->box_id;
                    $mouth_id = $r[0]->mouth_id;
                    DB::table('tb_newlocker_express')
                        ->where('id', $id)
                            ->update(array(
                                'overdueTime' => $overdueTime,
                                'lastModifiedTime' => time() * 1000
                                ));

                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);

                    DB::table('tb_newlocker_tasks')
                        ->insert([
                            'id' => $id_task,
                            'box_id' => $box_id,
                            'expressId' => $id,
                            'status' => 'COMMIT',
                            'task' => 'RESET_EXPRESS',
                            'messageType' => 'ASYNC_TASK',
                            'createTime' => $timestamp,
                            'mouth_id' => $mouth_id
                        ]);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'expressNumber' => $expressNumber, 'status' => 'OVERDUE TIME CHANGED']];

                } else {
                    $res = ['response'=> ['code' => 401, 'message' => 'Express Id Not Found/Cannot Be Changed or Already Taken!'],  'data' => []];
                }
            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing or Invalid Parameter!'],  'data' => []];               
            }            
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/express/resetExpress',
                'api_send_data' => json_encode(['id' => $id_task, 'overdueTime' => $overdueTime, 'expressNumber' => $expressNumber]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }

    public function remoteUnlock (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $box_id = null;
        $id_task = null;

        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => []];   
        }else{            
            if (!empty($id)) {
                $sql = "SELECT * FROM tb_newlocker_mouth WHERE deleteFlag = '0' AND id_mouth ='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $box_id = $r[0]->box_id;
                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);
                    DB::table('tb_newlocker_tasks')
                        ->insert([
                            'id' => $id_task,
                            'box_id' => $box_id,
                            'status' => 'COMMIT',
                            'task' => 'REMOTE_UNLOCK',
                            'messageType' => 'ASYNC_TASK',
                            'createTime' => $timestamp,
                            'mouth_id' => $id
                        ]);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'box_id' => $box_id, 'status' => 'REMOTE OPEN TASK CREATED']];

                }else{
                    $res = ['response'=> ['code' => 404, 'message' => 'Mouth Id Not Found!'],  'data' => []];
                }

            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing or Invalid Parameter!'],  'data' => []];
            }               
            
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/mouth/remoteUnlock',
                'api_send_data' => json_encode(['id' => $id_task, 'box_id' => $box_id]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }

    public function remoteUnlockByExpress (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $box_id = null;
        $id_task = null;
        $mouth_id = null;
        $log = false;

        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => []];   
        }else{            
            if (!empty($id)) {
                $sql = "SELECT * FROM tb_newlocker_express WHERE deleteFlag = '0' AND id ='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $box_id = $r[0]->box_id;
                    $mouth_id = $r[0]->mouth_id;
                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);
                    //SEND TASK TO PROX
                    DB::table('tb_newlocker_tasks')
                        ->insert([
                            'id' => $id_task,
                            'box_id' => $box_id,
                            'status' => 'COMMIT',
                            'task' => 'REMOTE_UNLOCK',
                            'messageType' => 'ASYNC_TASK',
                            'createTime' => $timestamp,
                            'mouth_id' => $mouth_id
                        ]);

                    //SEND TASK TO EBOX
                    $url = 'http://eboxapi.clientname.id:8080/ebox/api/v1/task/mouth/remoteUnlock';
                    $param = array('id' => $mouth_id);
                    $this->headers[]="userToken:22cc0a50e199494682a9fc2ee2e88294";
                    $push = $this->post_data($url, json_encode($param), $this->headers);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'box_id' => $box_id, 'status' => 'REMOTE OPEN TASK CREATED BY EXPRESS']];

                    $log = true;

                }else{
                    $res = ['response'=> ['code' => 401, 'message' => 'Express Id Not Found!'],  'data' => []];
                }

            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing or Invalid Parameter!'],  'data' => []];
            }               
        }

        if ($log) {
            //insert ke tabel generallog
            DB::table('tb_newlocker_generallog')
                ->insert([
                    [   'api_url' =>  'http://pr0x.clientname.id'.'/task/mouth/remoteUnlock',
                        'api_send_data' => json_encode(['id' => $id_task, 'box_id' => $box_id]),
                        'api_response' => json_encode($res),
                        'response_date' => date("Y-m-d H:i:s")],
                    [   'api_url' =>  $url,
                        'api_send_data' => json_encode($param),
                        'api_response' => json_encode($push),
                        'response_date' => date("Y-m-d H:i:s")]
                    ]);
        }

        return response()->json($res);
    }

    public function remoteReboot (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $password = $req->json('password');
        $sqltok2 = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$password."'";
        $rtok2 = DB::select($sqltok2);
        $granted2 = $rtok2[0]->token;
        $box_id = null;
        $id_task = null;

        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => []];
        }else{            
            if (!empty($id) && !empty($granted2)) {
                $sql = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND id ='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $box_id = $id;
                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);
                    DB::table('tb_newlocker_tasks')
                        ->insert([
                            'id' => $id_task,
                            'box_id' => $box_id,
                            'status' => 'COMMIT',
                            'task' => 'BACKEND_REBOOT',
                            'messageType' => 'BACKEND_REBOOT',
                            'createTime' => $timestamp
                        ]);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'box_id' => $box_id, 'status' => 'REMOTE REBOOT TASK CREATED']];

                }else{
                    $res = ['response'=> ['code' => 401, 'message' => 'Box Id Not Found!'],  'data' => []];
                }

            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing parameter or Invalid Password!'],  'data' => []];
            }               
            
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/remoteReboot',
                'api_send_data' => json_encode(['id' => $id_task, 'box_id' => $box_id]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }

    public function forceresyncall (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $password = $req->json('password');
        $sqltok2 = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$password."'";
        $rtok2 = DB::select($sqltok2);
        $granted2 = $rtok2[0]->token;
        $box_id = null;
        $id_task = null;

        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => []];
        }else{            
            if (!empty($id) && !empty($granted2)) {
                $sql = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND id ='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $box_id = $id;
                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);
                    DB::table('tb_newlocker_tasks')
                        ->insert([
                            'id' => $id_task,
                            'box_id' => $box_id,
                            'status' => 'COMMIT',
                            'task' => 'FORCE_RESYNC_ALL',
                            'messageType' => 'FORCE_RESYNC_ALL',
                            'createTime' => $timestamp
                        ]);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'box_id' => $box_id, 'status' => 'REMOTE REBOOT TASK CREATED']];

                }else{
                    $res = ['response'=> ['code' => 401, 'message' => 'Box Id Not Found!'],  'data' => []];
                }

            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing parameter or Invalid Password!'],  'data' => []];
            }               
            
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/forceResyncAll',
                'api_send_data' => json_encode(['id' => $id_task, 'box_id' => $box_id]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }

    public function remoteCommand (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $command = $req->json('command');
        $password = $req->json('password');
        $sqltok2 = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$password."'";
        $rtok2 = DB::select($sqltok2);
        $granted2 = $rtok2[0]->token;
        $box_id = null;
        $id_task = null;

        if ($granted == 0 || $granted == ''){
            $res = ['response'=> ['code' => 404, 'message' => 'UserToken Not Found or Invalid!'],  'data' => []];
        }else{            
            if (!empty($id) && !empty($command) && !empty($granted2)) {
                $sql = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND id ='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $box_id = $id;
                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);
                    DB::table('tb_newlocker_tasks')
                        ->insert([
                            'id' => $id_task,
                            'box_id' => $box_id,
                            'status' => 'COMMIT',
                            'task' => $command,
                            'messageType' => 'REMOTE_COMMAND',
                            'createTime' => $timestamp
                        ]);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'box_id' => $box_id, 'status' => 'REMOTE COMMAND TASK CREATED']];

                }else{
                    $res = ['response'=> ['code' => 401, 'message' => 'Box Id Not Found!'],  'data' => []];
                }

            } else {
                $res = ['response'=> ['code' => 501, 'message' => 'Missing parameter or Invalid Password or Command!'],  'data' => []];
            }               
            
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/remoteCommand',
                'api_send_data' => json_encode(['id' => $id_task, 'box_id' => $box_id]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }

    public function pingPong ($imported){
        if(empty($imported)) echo "null";
        if($imported=='ping') echo "pong";
    }   


}