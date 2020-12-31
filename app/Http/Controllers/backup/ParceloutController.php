<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;



class ParceloutController extends Controller{
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

    public function takuserreject (Request $req) {
        /* SAMPLE JSON
        { 
           "operator_id":"145b2728140f11e5bdbd0242ac110001",
           "logisticsCompany_id":"1501153739agess",
           "staffTakenUser_id":"2c9180af5115409801511a05b39a00a5",
           "status":"COURIER_TAKEN",
           "syncFlag":0,
           "mouth_id":"1501653009hpuxs",
           "box_id":"1501653337kdtrp",
           "chargeType":"NOT_CHARGE",
           "takeTime":1502352070000,
           "version":1,
           "storeTime":1502352058000,
           "storeUserPhoneNumber":"085710157057",
           "customerStoreNumber":"RN3851322562060501",
           "staffTakenUser":{ 
              "id":"2c9180af5115409801511a05b39a00a5"
           },
           "electronicCommerce_id":"f6af7b5e813811e5919e6c40088b8482",
           "expressType":"CUSTOMER_REJECT",
           "id":"47986830322411e7b143b8aeed23da92"
        }*/

        $operator_id = $req->json('operator_id');
        $logisticsCompany_id = $req->json('logisticsCompany_id');
        $staffTakenUser_id = $req->json('staffTakenUser_id');
        $status = $req->json('status');
        $syncFlag = $req->json('syncFlag');
        $mouth_id = $req->json('mouth_id');
        $box_id = $req->json('box_id');
        $chargeType = $req->json('chargeType');
        $takeTime = $req->json('takeTime');
        $version = $req->json('version');
        $storetime = $req->json('storeTime');
        $storeUserPhoneNumber = $req->json('storeUserPhoneNumber');
        $customerStoreNumber = $req->json('customerStoreNumber');
        $electronicCommerce_id = $req->json('electronicCommerce_id');
        $expressType = $req->json('expressType');
        $id = $req->json('id');

        $sqlc = "SELECT id FROM tb_newlocker_express WHERE id='".$id."'";
        $rc = DB::select($sqlc);

        if (count($rc) != 0 ) {
            //update data menjadi courier taken
            DB::table('tb_newlocker_express')
                ->where('id', $id)->where('customerStoreNumber', $customerStoreNumber)
                        ->update(array(
                                'status' =>  $status,
                                'takeTime' => $takeTime,
                                'staffTakenUser_id' => $staffTakenUser_id,
                                'storeUserPhoneNumber' => $storeUserPhoneNumber,
                                'mouth_id' => $mouth_id,
                                'operator_id' => $operator_id,
                                'electronicCommerce_id' => $electronicCommerce_id,
                                'logisticsCompany_id' => $logisticsCompany_id,
                                'lastModifiedTime' => time() * 1000
                        )); 
        } else {
            //force define groupname
            /*$prefix = substr($customerStoreNumber, 0, 1);
            switch ($prefix) {
                case 'S':
                    $$groupName = "MATAHARIMALL";
                    break;
                case 'C':
                    $$groupName = "MATAHARIMALL";
                    break;
                case '2':
                    $$groupName = "ZALORA";
                    break;
                case 'R':
                    $$groupName = "LAZADA";
                    break;
                case '3':
                    $$groupName = "LAZADA";
                    break;
                case 'O':
                    $$groupName = "ORIFLAME";
                    break;
                case 'J':
                    $$groupName = "JAVASMIFI";
                    break;
                default:
                    $$groupName = "POPBOX RETURN";
                    break;
            }*/

            //insert data
            DB::table('tb_newlocker_express')
                    ->insert([
                            'groupName' => $groupName,
                            'expressType' => 'CUSTOMER_REJECT',
                            'status' => $status,
                            'customerStoreNumber' => $customerStoreNumber,
                            'storeTime' => $storeTime,
                            'syncFlag' => $syncFlag,
                            'takeTime' => $takeTime,
                            'storeUserPhoneNumber' => $storeUserPhoneNumber,
                            'version' => $version,
                            'box_id' => $box_id,
                            'logisticsCompany_id' => $logisticsCompany_id,
                            'mouth_id' => $mouth_id,
                            'operator_id' => $operator_id,
                            'chargeType' => $chargeType,
                            'electronicCommerce_id' => $electronicCommerce_id,
                            'staffTakenUser_id' => $staffTakenUser_id,
                            'id' => $id,
                            'lastModifiedTime' => time() * 1000
             ]);
        }

        //update tabel mouth
        DB::table('tb_newlocker_mouth')            
          ->where('id_mouth', $mouth_id)
                    ->update(array(
                            'status' =>  'ENABLE',
                            'express_id' => null,
                            'lastChangingTime' => time() * 1000
                    )); 

        //ambil data box 
        $sqlb = "select * from tb_newlocker_box where id='".$box_id."'";
        $rb = DB::select($sqlb);    
        $box = array ('orderNo' => $rb[0]->orderNo, 'name' => $rb[0]->name, 'id' => $rb[0]->id );

        //ambil data mouth
        $sqlm = "select a.* , b.* from tb_newlocker_mouth a, tb_newlocker_mouthtype b where a.id_mouth='".$mouth_id."'and a.mouthType_id=b.id_mouthtype";
        $rm = DB::select($sqlm);
        $mouthtype = array('name' => $rm[0]->name, 'defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'defaultUserPrice' => $rm[0]->defaultUserPrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag);
        $mouth = array('number' => $rm[0]->number, 'status' => $rm[0]->status , 'id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box);

        //ambil data company
        $sqlc = "select * from tb_newlocker_company where id_company='".$logisticsCompany_id."'";
        $rc = DB::select($sqlc);
        $logistic = array ('companyType' => $rc[0]->company_type, 'level' => $rc[0]->level, 'deleteFlag' => $rc[0]->deleteFlag, 'name' => $rc[0]->company_name, 'contactPhoneNumber' => [], 'contactEmail' => [], 'id' => $rc[0]->id_company);

        //ambil data express
        $sqle = "select * from tb_newlocker_express where id='".$id."'";
        $re = DB::select($sqle);

        //ambil data electronic commerce
        $sqlecom = "select * from tb_newlocker_company where id_company ='".$electronicCommerce_id."'";
        $recom = DB::select($sqlecom);

        $storeuser = array('userNo' => '', 'name' => $storeUserPhoneNumber, 'userCardList' => [], 'phoneNumber' =>  $storeUserPhoneNumber, 'id' => 'S_'. $storeUserPhoneNumber , 'loginName' => $storeUserPhoneNumber);

        $electronicCommerce = array('name' => $recom[0]->company_name, 'id' => $recom[0]->id_company, 'address' => $recom[0]->company_address);

        $res =  ['mouth' => $mouth , 'logisticsCompany' => $logistic, 'groupName' => $re[0]->groupName, 'status' => $status, 'includedNumbers' => 0, 'storeUserPhoneNumber' => $re[0]->storeUserPhoneNumber, 'storeUser' => $storeuser , 'version' => 1 , 'storeTime' => $storetime, 'box' => $box, 'electronicCommerce' => $electronicCommerce, 'customerStoreNumber' => $customerStoreNumber ,'items' => [], 'chargeType' => $re[0]->chargeType, 'expressType' => $re[0]->expressType, 'id' => $id, 'additionalPayment' => [], 'takeTime' => $takeTime];

        //Post to mirror server
        $url_push = 'https://internalapi.clientname.id/synclocker/customereject';
        $push = $this->post_data($url_push, json_encode($res));
        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    ['api_url' =>  'http://pr0x.clientname.id'.'/express/staffTakeUserRejectExpress',
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

    public function customertakexpress (Request $req) {
        //OLD SAMPLE JSON
        /*{  
           "syncFlag":0,
           "staffTakenUser_id":null,
           "status":"CUSTOMER_TAKEN",
           "id":"2c9180874dff9bd8014e0ba188400032",
           "version":1,
           "takeTime":1502771076000
        }*/
        $syncFlag = $req->json('syncFlag');
        $staffTakenUser_id = $req->json('staffTakenUser_id');
        $status = $req->json('status');
        $id = $req->json('id');
        $version = $req->json('version');
        $takeTime = $req->json('takeTime');
        $expressNumber = $req->json('expressNumber');
        $takeUserPhoneNumber = $req->json('takeUserPhoneNumber');
        //$groupName = $req->json('groupName');
        $overdueTime = $req->json('overdueTime');
        $storeTime = $req->json('storeTime');
        $validateCode = $req->json('validateCode');
        $box_id = $req->json('box_id');
        $logisticsCompany_id = $req->json('logisticsCompany_id');
        $operator_id = $req->json('operator_id');
        $storeUser_id = $req->json('storeUser_id');
        $mouth_id = $req->json('mouth_id');

        if($expressNumber=="N\/A"){
            $expressNumber = "C-".$takeUserPhoneNumber;
        }

        //select box nya
        $sqle = "SELECT * FROM tb_newlocker_express WHERE id ='".$id."'";
        $re = DB::select($sqle); 

        if (count($re) != 0 ) {
            // update table express
            DB::table('tb_newlocker_express')
                ->where('id', $id)
                        ->update(array(
                            'syncFlag' => $syncFlag,
                            'status' => $status,
                            'version' => $version,
                            'takeTime' => $takeTime,
                            'lastModifiedTime' => time() * 1000
            ));      
        } else {
            DB::table('tb_newlocker_express')
                    ->insert([
                            'expressNumber' => $expressNumber,
                            'expressType' => 'COURIER_STORE',
                            'takeUserPhoneNumber' => $takeUserPhoneNumber,
                            'status' => $status,
                            'overdueTime' => $overdueTime,
                            'storeTime' => $storeTime,
                            'syncFlag' => $syncFlag, 
                            'takeTime' => $takeTime,
                            'validateCode' => $validateCode,
                            'version' => $version,
                            'box_id' => $box_id,
                            'logisticsCompany_id' => $logisticsCompany_id,
                            'mouth_id' => $mouth_id,
                            'operator_id' => $operator_id,
                            'storeUser_id' => $storeUser_id,
                            'staffTakenUser_id' => $staffTakenUser_id,
                            'id' => $id,
                            'lastModifiedTime' => time() * 1000
             ]);
        }           

        // update tabel mouth
        DB::table('tb_newlocker_mouth')
            ->where('id_mouth', $re[0]->mouth_id)
                    ->update(array(
                        'status' => 'ENABLE' , 
                        'express_id' => null,
                        'lastChangingTime' => time() * 1000
        ));

        //ambil data box 
        $locker_id = $re[0]->box_id;            
        $lockerbox = Cache::remember("locker_id-$locker_id",720,function() use($locker_id){
            $data = DB::table('tb_newlocker_box')->select('id','token','name','currencyUnit','freeDays','overdueType','validateType','freeHours','orderNo')->where('id','=',$locker_id)->first();
            return $data;
        });            

        $box = array ('orderNo' => $lockerbox->orderNo, 'name' => $lockerbox->name, 'id' => $lockerbox->id );
        $takeuser = array('id' => $re[0]->storeUser_id, 'userNo' => '', 'name' => $re[0]->takeUserPhoneNumber, 'userCardList' => [['id'=>'']],'phoneNumber' =>  $re[0]->takeUserPhoneNumber, 'loginName' => $re[0]->takeUserPhoneNumber );

        $logid = $re[0]->logisticsCompany_id;
        //ambil data company
       $lockercom = Cache::remember("locker_com-$logid",720,function() use($logid){
        $data = DB::table('tb_newlocker_company')->select('level','company_type','company_name','id_company','deleteFlag')->where('id_company','=',$logid)->first();
                                return $data;
        }); 

        $logistic = array ('companyType' => $lockercom->company_type, 'id' => $lockercom->id_company, 'deleteFlag' => '0', 'name' => $lockercom->company_name,  'contactPhoneNumber' => [], 'level' => $lockercom->level, 'contactEmail' => []);
    
        //ambil data user
        $id_user = $re[0]->storeUser_id;
        $lockeruser = Cache::remember("locker_user-$id_user",720,function() use($id_user){
                    $data = DB::table('tb_newlocker_user')->select('id_user','username','displayname','phone')->where('id_user','=',$id_user)->first();
                                return $data;
        }); 

        $storeuser = array('id' => $id_user , 'userNo' => '', 'name' => $lockeruser->displayname, 'userCardList' => [['id'=>'']], 'phoneNumber' => $lockeruser->phone, 'loginName' => $lockeruser->username);

        //ambil data mouth
        $sqlm = "select a.* , b.* from tb_newlocker_mouth a, tb_newlocker_mouthtype b where a.id_mouth='".$re[0]->mouth_id."'and a.mouthType_id=b.id_mouthtype";                 
        $rm = DB::select($sqlm);

        $mouthtype = array('defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag, 'name' => $rm[0]->name,  'defaultUserPrice' => $rm[0]->defaultUserPrice );
        $mouth = array('id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box,'status' => $rm[0]->status, 'number' => $rm[0]->number);  

        $res =  ['box' => $box , 'validateCode' => $re[0]->validateCode, 'groupName' => $re[0]->groupName, 'expressType' => $re[0]->expressType, 'overdueTime' => $re[0]->overdueTime, 'includedNumbers' => 0, 'takeUser' => $takeuser, 'logisticsCompany' => $logistic , 'version' => $version , 'additionalPayment' => [], 'id' => $id, 'storeUser' => $storeuser, 'status' => $status ,'mouth' => $mouth, 'takeUserPhoneNumber' => $re[0]->takeUserPhoneNumber, 'storeTime' => $re[0]->storeTime, 'expressNumber' => $re[0]->expressNumber, 'items' => [], 'takeTime' => $takeTime];


        //Post to mirror server
        $url_push = 'https://internalapi.clientname.id/synclocker/courierstore';
        $push = $this->post_data($url_push, json_encode($res));
        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    ['api_url' =>  'http://pr0x.clientname.id'.'/express/customerTakeExpress',
                    'api_send_data' => json_encode(['id' => $id, 'expressNumber' => $expressNumber, 'validateCode' => $validateCode]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")],
                    ['api_url' =>  $url_push,
                    'api_send_data' => json_encode($res),
                    'api_response' => json_encode($push),
                    'response_date' => date("Y-m-d H:i:s")]
                    ]);

        return response()->json($res);
    }

    public function takeuserexpress (Request $req) {
        //SAMPLE JSON
        /*{ 
           "status":"COURIER_TAKEN",
           "version":1,
           "recipientUserPhoneNumber":"08957885580",
           "operator_id":"145b2728140f11e5bdbd0242ac110001",
           "customerStoreNumber":"PLA170815GWSS",
           "staffTakenUser_id":"4028808357516592015754bc49391836",
           "box_id":"1501653337kdtrp",
           "recipientName":"Ghcgh",
           "storeTime":1502780466000,
           "weight":0,
           "expressType":"CUSTOMER_STORE",
           "logisticsCompany_id":"1501153739agess",
           "syncFlag":0,
           "id":"402880835659d52801565beba337034f",
           "chargeType":"NOT_CHARGE",
           "takeTime":1502788793000,
           "staffTakenUser":{  
              "id":"4028808357516592015754bc49391836"
           },
           "mouth_id":"1502784583zijdm"
        }        */
        $status = $req->json('status');
        $version = $req->json('version');
        $recipientUserPhoneNumber = $req->json('recipientUserPhoneNumber');
        $operator_id = $req->json('operator_id');
        $customerStoreNumber = $req->json('customerStoreNumber');
        $staffTakenUser_id = $req->json('staffTakenUser_id');
        $box_id = $req->json('box_id');
        $recipientName = $req->json('recipientName');
        $storeTime = $req->json('storeTime');
        $weight = $req->json('weight');
        $chargeType = $req->json('chargeType');
        $mouth_id = $req->json('mouth_id');
        $syncFlag = $req->json('syncFlag');
        $id = $req->json('id');
        $takeTime = $req->json('takeTime');
        $logisticsCompany_id = $req->json('logisticsCompany_id');

        $sqle = "SELECT * FROM tb_newlocker_express WHERE id ='".$id."'";
        $re = DB::select($sqle); 

        if (count($re) != 0 ) {
            //update tabel express
            DB::table('tb_newlocker_express')
                ->where('id', $id)->where('customerStoreNumber', $customerStoreNumber)
                        ->update(array(
                            'status' => $status,
                            'version' => $version,
                            'takeTime' => $takeTime,
                            'staffTakenUser_id' => $staffTakenUser_id,
                            'syncFlag' => $syncFlag,
                            'lastModifiedTime' => time() * 1000

            )); 
        } else {
            //insert data
            DB::table('tb_newlocker_express')
                    ->insert([
                            'expressType' => 'COURIER_STORE',
                            'status' => $status,
                            // 'groupName' => 'POPSEND',
                            'customerStoreNumber' => $customerStoreNumber,
                            'overdueTime' => $overdueTime,
                            'storeTime' => $storeTime,
                            'syncFlag' => $syncFlag,
                            'takeTime' => $takeTime,
                            'version' => $version,
                            'box_id' => $box_id,
                            'logisticsCompany_id' => $logisticsCompany_id,
                            'mouth_id' => $mouth_id,
                            'operator_id' => $operator_id,
                            'recipientName' => $recipientName,
                            'weight' => $weight,
                            'chargeType' => $chargeType,
                            'staffTakenUser_id' => $staffTakenUser_id,
                            'id' => $id,
                            'lastModifiedTime' => time() * 1000
             ]);

        }

        // update tabel mouth
        DB::table('tb_newlocker_mouth')
            ->where('id_mouth', $mouth_id)
                    ->update(array(
                        'status' => 'ENABLE', 
                        'express_id' => null,
                        'lastChangingTime' => time() * 1000 
                        ));

        //select box nya
        $sqle = "select * from tb_newlocker_express where id ='".$id."'";
        $re = DB::select($sqle);    

        //ambil data box 
        $sqlb = "select * from tb_newlocker_box where id='".$re[0]->box_id."'";
        $rb = DB::select($sqlb);    
        $box = array ('orderNo' => $rb[0]->orderNo, 'name' => $rb[0]->name, 'id' => $rb[0]->id );

        //ambil data mouth
        $sqlm = "select a.* , b.* from tb_newlocker_mouth a, tb_newlocker_mouthtype b where a.id_mouth='".$mouth_id."'and a.mouthType_id=b.id_mouthtype";        
        $rm = DB::select($sqlm);

        //ambil data company
        $sqlc = "select * from tb_newlocker_company where id_company='".$logisticsCompany_id."'";
        $rc = DB::select($sqlc);

        $logistic = array ('companyType' => $rc[0]->company_type, 'id' => $rc[0]->id_company, 'deleteFlag' => $rc[0]->deleteFlag, 'name' => $rc[0]->company_name,  'contactPhoneNumber' => [], 'level' => $rc[0]->level, 'contactEmail' => []);

        $mouthtype = array('defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag, 'name' => $rm[0]->name,  'defaultUserPrice' => $rm[0]->defaultUserPrice );
        $mouth = array('id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box,'status' => $rm[0]->status, 'number' => $rm[0]->number);              
            
        $res =  ['status' => $status , 'version' => $version, 'chargeType' => $re[0]->chargeType, 'customerStoreNumber' => $customerStoreNumber, 'mouth' => $mouth, 'logisticsCompany' => $logistic, 'endAddress' => $re[0]->endAddress, 'storeTime' => $re[0]->storeTime , 'takeUserPhoneNumber' => $re[0]->takeUserPhoneNumber , 'weight' => $re[0]->weight , 'expressType' => $re[0]->expressType, 'box' => $box, 'id' => $id ,'includedNumbers' => 0 , 'groupName' => $re[0]->groupName , 'additionalPayment' => [] , 'items' => [], 'takeTime' => $takeTime];

        //Post to mirror server
        $url_push = 'https://internalapi.clientname.id/synclocker/customerstore';
        $push = $this->post_data($url_push, json_encode($res));
        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    ['api_url' =>  'http://pr0x.clientname.id'.'/express/staffTakeUserSendExpress',
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

    public function takeoverduexpress (Request $req) {
        /*SAMPLE JSON
        {  
           "syncFlag":0,
           "takeUserPhoneNumber":"08126498988",
           "operator_id":"145b2728140f11e5bdbd0242ac110001",
           "storeTime":1500878954000,
           "expressNumber":"TESTSIMPAN170815",
           "status":"COURIER_TAKEN",
           "storeUserPhoneNumber":"707d3v",
           "mouth_id":"1502766155g93v9",
           "box_id":"1501653337kdtrp",
           "staffTakenUser_id":"1501144238abcd",
           "validateCode":"XACV7K",
           "logisticsCompany_id":"1501153739agess",
           "storeUser_id":"1501144238abcd",
           "overdueTime":1501171200000,
           "id":"2c9180874dff9bd8014e0ba188400032",
           "expressType":"COURIER_STORE",
           "staffTakenUser":{  
              "id":"1501144238abcd"
           },
           "version":1,
           "takeUser_id":"402880825d40639e015d5fc168ab3962",
           "takeTime":1502845206000
        }*/
        $syncFlag = $req->json('syncFlag');
        $takeUserPhoneNumber = $req->json('takeUserPhoneNumber');
        $operator_id = $req->json('operator_id');
        $storeTime = $req->json('storeTime');
        $expressNumber = $req->json('expressNumber');
        $status = $req->json('status');
        $storeUserPhoneNumber = $req->json('storeUserPhoneNumber');        
        $mouth_id = $req->json('mouth_id');
        $box_id = $req->json('box_id');
        $staffTakenUser_id = $req->json('staffTakenUser_id');
        $validateCode = $req->json('validateCode');
        $logisticsCompany_id = $req->json('logisticsCompany_id');
        $storeUser_id = $req->json('storeUser_id');
        $overdueTime = $req->json('overdueTime');
        $id = $req->json('id');
        $expressType = $req->json('expressType');
        $version = $req->json('version');
        $takeUser_id = $req->json('takeUser_id');
        $takeTime = $req->json('takeTime');

        $sqlc = "SELECT id FROM tb_newlocker_express WHERE id='".$id."'";
        $rc = DB::select($sqlc);

        if (count($rc) != 0 ) {
            //update tabel express
            DB::table('tb_newlocker_express')
                ->where('id', $id)->where('expressNumber',$expressNumber)
                        ->update(array(
                            'syncFlag' => $syncFlag,
                            'takeUserPhoneNumber' => $takeUserPhoneNumber,
                            'status' => $status,
                            'version' => $version,
                            'staffTakenUser_id' => $staffTakenUser_id,
                            'takeUser_id' => $takeUser_id,
                            'logisticsCompany_id' => $logisticsCompany_id,
                            'takeTime' => $takeTime,
                            'lastModifiedTime' => time() * 1000

            )); 
        } else {
            DB::table('tb_newlocker_express')
                ->insert([
                            'expressNumber' => $expressNumber,
                            'expressType' => $expressType,
                            'takeUserPhoneNumber' => $takeUserPhoneNumber,
                            'status' => $status,
                            'overdueTime' => $overdueTime,
                            'storeTime' => $storeTime,
                            'syncFlag' => $syncFlag,
                            'takeTime' => $takeTime,
                            'validateCode' => $validateCode,
                            'version' => $version,
                            'box_id' => $box_id,
                            'logisticsCompany_id' => $logisticsCompany_id,
                            'mouth_id' => $mouth_id, 
                            'operator_id' => $operator_id, 
                            'storeUser_id' => $storeUser_id,
                            'staffTakenUser_id' => $staffTakenUser_id,
                            'id' => $id,
                            'lastModifiedTime' => time() * 1000
             ]);

        }

        //update tabel mouth
        DB::table('tb_newlocker_mouth')
            ->where('id_mouth', $mouth_id)
                    ->update(array(
                        'status' => 'ENABLE' , 
                        'express_id' => null,
                        'lastChangingTime' => time() * 1000
                        ));

        //ambil data box 
        $sqlb = "select * from tb_newlocker_box where id='".$box_id."'";
        $rb = DB::select($sqlb);    
        $box = array ('orderNo' => $rb[0]->orderNo, 'name' => $rb[0]->name, 'id' => $rb[0]->id );

        //ambil data mouth
        $sqlm = "select a.* , b.* from tb_newlocker_mouth a, tb_newlocker_mouthtype b where a.id_mouth='".$mouth_id."'and a.mouthType_id=b.id_mouthtype";        
        $rm = DB::select($sqlm);

        $mouthtype = array('defaultOverduePrice' => $rm[0]->defaultOverduePrice, 'id' => $rm[0]->id_mouthtype, 'deleteFlag' => $rm[0]->deleteFlag, 'name' => $rm[0]->name,  'defaultUserPrice' => $rm[0]->defaultUserPrice );
        $mouth = array('id' => $rm[0]->id_mouth, 'mouthType' => $mouthtype, 'box' => $box,'status' => $rm[0]->status, 'number' => $rm[0]->number);   

        //ambil data company
        $sqlc = "select * from tb_newlocker_company where id_company='".$logisticsCompany_id."'";
        $rc = DB::select($sqlc);
        $logistic = array ('companyType' => $rc[0]->company_type, 'id' => $rc[0]->id_company, 'deleteFlag' => $rc[0]->deleteFlag, 'name' => $rc[0]->company_name,  'contactPhoneNumber' => [], 'level' => $rc[0]->level, 'contactEmail' => []);

        // ambil data user store
        $sqlu = "select * from tb_newlocker_user where id_user='".$storeUser_id."'";
        $ru = DB::select($sqlu);
        $storeUser = array('loginName' => $ru[0]->username, 'name' => $ru[0]->displayname, 'userNo' => '' , 'userCardList' => [['id' => '']], 'id' => $storeUser_id, 'phoneNumber' => $ru[0]->phone);

        // ambil data user take
        $sqlut = "select * from tb_newlocker_user where id_user='".$storeUser_id."'";
        $rut = DB::select($sqlut);

        $takeUser = array('loginName' => $rut[0]->username, 'name' => $rut[0]->displayname, 'userNo' => '' ,'userCardList' => [['id' => '']], 'id' => $takeUser_id , 'phoneNumber' => $rut[0]->phone);
            
        $res =  ['items' => [] ,  'mouth' => $mouth, 'takeUserPhoneNumber' => $takeUserPhoneNumber, 'additionalPayment' => [], 'expressNumber' => $expressNumber, 'status' => $status, 'storeTime' => $storeTime, 'validateCode' => $validateCode, 'box' => $box, 'storeUser' => $storeUser, 'logisticsCompany' => $logistic, 'overdueTime' => $overdueTime, 'id' => $id ,'expressType' => $expressType, 'version' => $version , 'takeUser' => $takeUser ,  'includedNumbers' => 0, 'takeTime' => $takeTime];

        //Post to mirror server
        $url_push = 'https://internalapi.clientname.id/synclocker/courierstore';
        $push = $this->post_data($url_push, json_encode($res));
        
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    ['api_url' =>  'http://pr0x.clientname.id'.'/express/staffTakeOverdueExpress',
                    'api_send_data' => json_encode(['id' => $id, 'expressNumber' => $expressNumber, 'validateCode' => $validateCode]),
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