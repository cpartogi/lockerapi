<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use Illuminate\Support\Facades\Validator;


class ParcelCaseController extends Controller{
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

    public function importedexpress(Request $req, $imported) {
        $boxToken = $req->header('BoxToken');
        $diskSerialNumber = $req->header('DiskSerialNumber');
        $orderNo = $req->header('OrderNo');
        $userToken = $req->header('UserToken');

        // cek di tabel express
        $sql = "select * from tb_newlocker_express where deleteFlag = '0' and expressNumber = '".$imported."' and expressType ='COURIER_STORE' and status = 'IMPORTED'";
        $exp = DB::select($sql);

        if (count($exp) != 0 ) {
            $res =  ['additionalPayment' => [] , 'version' => $exp[0]->version, 'id' => $exp[0]->id, 'expressType' => $exp[0]->expressType, 'expressNumber' => $exp[0]->expressNumber, 'groupName' => $exp[0]->groupName, 'takeUserPhoneNumber' => $exp[0]->takeUserPhoneNumber ];
        } else {
            $res =  ['statusCode' => 404 , 'errorMessage' => 'express not found'];
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/express/imported/' . $imported,
                    'api_send_data' => json_encode(['orderNo' => $orderNo, 'checkFor' => 'IMPORTED COURIER_STORE']),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res);
    } 

    public function rejectcheckrule(Request $req, $imported) {
        $boxToken = $req->header('BoxToken');
        $diskSerialNumber = $req->header('DiskSerialNumber');
        $orderNo = $req->header('OrderNo');
        $userToken = $req->header('UserToken');

        //parsing express & groupname
        $express = $imported;
        $groupExpress = $_GET['type'];

        // cek rule di tabel return rule
        $sqlgr = "select * from tb_newlocker_returnrules where deleteFlag = '0' and groupName = '".$groupExpress."'";
        $rules = DB::select($sqlgr);
        $electronicCommerce_id = $rules[0]->ecommerce_id;
        $logisticsCompany_id = $rules[0]->logistic_id;
        $uniqueKey = '{{p0pb0x_4514}}';
        $canContinue = false;
        
        $ruleList = array();
        
        if (count($rules) > 1) {
            foreach ($rules as $rule) {
                array_push($ruleList, $rule->regularContent);           
            }
        }

        if (count($rules) > 1) {
            foreach ($ruleList as $rule_) {
                if (strpos($rule_, $groupExpress) !== false || is_array($rule_)) {
                    //cek di db apakah nomor express itu udah diupload dan memiliki groupname yang sama
                    $sql = "select * from tb_newlocker_express where deleteFlag = '0' and customerStoreNumber = '".$express."' and expressType = 'CUSTOMER_REJECT' and status = 'IMPORTED' and groupName = '" .$groupExpress. "'";
                    $exp = DB::select($sql);

                    if (count($exp) != 0) {
                        $canContinue = true;
                    } else {
                        $canContinue = false;
                    }
                } else {
                    //cek pola regex pada nomor express dengan key unik {{p0pb0x_4514}}
                    if (preg_replace('/'.$rule_.'/', $uniqueKey, $express) == $uniqueKey){
                        $canContinue = true;
                    }else{
                        $canContinue = false;
                    }
                }
                if ($canContinue) break;
            }
        } else if (count($rules) == 1) {
            $rule_ = $rules[0]->regularContent;
                if (strpos($rule_, $groupExpress) !== false || is_array($rule_)) {
                    //cek di db apakah nomor express itu udah diupload dan memiliki groupname yang sama
                    $sql = "select * from tb_newlocker_express where deleteFlag = '0' and customerStoreNumber = '".$express."' and expressType = 'CUSTOMER_REJECT' and status = 'IMPORTED' and groupName = '" .$groupExpress. "'";
                    $exp = DB::select($sql);

                    if (count($exp) != 0) {
                        $canContinue = true;
                    } else {
                        $canContinue = false;
                    }
                } else {
                    //cek pola regex pada nomor express dengan key unik {{p0pb0x_4514}}
                    if (preg_replace('/'.$rule_.'/', $uniqueKey, $express) == $uniqueKey){
                        $canContinue = true;
                    }else{
                        $canContinue = false;
                    }
                }
        } else {
            $canContinue = false;       
        }
        
        if ($canContinue) {
            //ambil data logistic company
            $sqlog = "select * from tb_newlocker_company where company_type = 'LOGISTICS_COMPANY' and id_company = '".$logisticsCompany_id."'";
            $rlog = DB::select($sqlog);

            $logisticsCompany_ = array('name' => $rlog[0]->company_name, 'id' => $rlog[0]->id_company);

            //ambil data ecommerce company
            $sqlecom = "select * from tb_newlocker_company where company_type = 'ELECTRONIC_COMMERCE' and id_company = '".$electronicCommerce_id."'";
            $rlecom = DB::select($sqlecom);

            $electronicCommerce_ = array('name' => $rlecom[0]->company_name, 'id' => $rlecom[0]->id_company, 'address' => $rlecom[0]->company_address);

            $res =  ['logisticsCompany' => $logisticsCompany_, 'groupName' => $groupExpress, 'electronicCommerce' => $electronicCommerce_]; 

        } else {

            $res =  ['statusCode' => 404 , 'errorMessage' => 'express reject rule not found for : '.$groupExpress ];
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/express/reject/checkRule/' . $imported,
                    'api_send_data' => json_encode(['orderNo' => $orderNo, 'checkFor' => 'CUSTOMER_REJECT RULES']),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res);
    }

    public function customerexpress(Request $req, $imported) {
        $boxToken = $req->header('BoxToken');
        $diskSerialNumber = $req->header('DiskSerialNumber');
        $orderNo = $req->header('OrderNo');
        $userToken = $req->header('UserToken');

        // cek di tabel express
        $sql = "select * from tb_newlocker_express where deleteFlag = '0' and customerStoreNumber = '".$imported."' and expressType = 'CUSTOMER_STORE' and status = 'IMPORTED'";
        $exp = DB::select($sql);
 
    
        if (count($exp) != 0 ) {
            // ambil data logistic company

            $logisticsCompany_id = $exp[0]->logisticsCompany_id;

            if(empty($logisticsCompany_id)){
                $logisticsCompany_id  = '161e5ed1140f11e5bdbd0242ac110001';
            }

            $sqlc = "select * from tb_newlocker_company where id_company='".$logisticsCompany_id."'";
            $rc = DB::select($sqlc);  

            if (!empty($rc)) {
                // ambil data parent company
                $sqlp = "select * from tb_newlocker_company where id_company='".$rc[0]->id_parent."'";
                $rp = DB::select($sqlp);
            } else {
                $rp = "";
            }
            
            if (!empty($rp) ) {
                // ambil data parent company level grand
                $sqlk = "select * from tb_newlocker_company where id_company='".$rp[0]->id_parent."'";
                $rk = DB::select($sqlk);
            } else {    
                $rk = "";
            }

            if (!empty($rk)) {
                $grandcompany =  array('contactPhoneNumber' => [], 'deleteFlag' => 0, 'name' => $rk[0]->company_name, 'contactEmail' => [], 'id' => $rk[0]->id_company, 'level' => $rk[0]->level, 'companyType' => $rk[0]->company_type );
            } else {
                $grandcompany = "";
            }

            if (!empty($rp)  ) {
                $parentcompany = array('contactPhoneNumber' => [], 'deleteFlag' => 0, 'name' => $rp[0]->company_name, 'parentCompany' => $grandcompany, 'contactEmail' => [], 'id' => $rp[0]->id_company, 'level' => $rp[0]->level, 'companyType' => $rp[0]->company_type );
            } else {
                $parentcompany = "";

            }
            $logistic = array('contactPhoneNumber' => [], 'deleteFlag' => 0, 'name' => $rc[0]->company_name, 'parentCompany' => $parentcompany , 'contactEmail' => [], 'id' => $rc[0]->id_company, 'level'=> $rc[0]->level, 'companyType' => $rc[0]->company_type );

            $res =  ['endAddress' => $exp[0]->endAddress , 'createTime' => $exp[0]->importTime, 'additionalPayment' => [] , 'expressType' => $exp[0]->expressType, 'status' => $exp[0]->status,  'recipientUserPhoneNumber' => $exp[0]->recipientUserPhoneNumber, 'logisticsCompany' => $logistic, 'customerStoreNumber' => $exp[0]->customerStoreNumber, 'id' => $exp[0]->id, 'barcode' => ['id' => $exp[0]->barcode_id] , 'items' => [], 'includedNumbers' => 0, 'chargeType' => 'NOT_CHARGE', 'recipientName' => $exp[0]->recipientName , 'takeUserPhoneNumber' => $exp[0]->takeUserPhoneNumber];

            if(!empty($exp[0]->designationSize)){
                $res['designationSize'] = $exp[0]->designationSize;
            }

        } else {
            $res =  ['statusCode' => 404 , 'errorMessage' => 'express not found'];
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/express/customerExpress/' . $imported,
                    'api_send_data' => json_encode(['orderNo' => $orderNo, 'checkFor' => 'IMPORTED CUSTOMER_STORE']),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res);
    }   

    public function syncexpress(Request $req) {
        $id = $req->json('id');
        $expressNumber = $req->json('expressNumber');
        $expressType = $req->json('expressType');
        $takeUserPhoneNumber = $req->json('takeUserPhoneNumber');
        $status = $req->json('status');
        $groupName = $req->json('groupName');
        $customerStoreNumber = $req->json('customerStoreNumber');
        $overdueTime = $req->json('overdueTime');
        $storeTime = $req->json('storeTime');
        $syncFlag = $req->json('syncFlag');
        $takeTime = $req->json('takeTime');
        $storeUserPhoneNumber = $req->json('storeUserPhoneNumber');
        $validateCode = $req->json('validateCode');
        $version = $req->json('version');
        $box_id = $req->json('box_id');
        $logisticsCompany_id = $req->json('logisticsCompany_id');
        $mouth_id = $req->json('mouth_id');
        $operator_id = $req->json('operator_id');
        $storeUser_id = $req->json('storeUser_id');
        $takeUser_id = $req->json('takeUser_id');
        $recipientName = $req->json('recipientName');
        $weight = $req->json('weight');
        $chargeType = $req->json('chargeType');
        $electronicCommerce_id = $req->json('electronicCommerce_id');
        $staffTakenUser_id = $req->json('staffTakenUser_id');
        $endAddress = $req->json('endAddress');

        $url_ = null;
        $param_ = null;
        $resp = null;

        //cek apakah ada di database :
        $sqlc = "SELECT id FROM tb_newlocker_express WHERE id='".$id."'";
        $rc = DB::select($sqlc);

        if (count($rc) != 0 ) {
            //update dataupdateT
            DB::table('tb_newlocker_express')
            ->where('id', $id)
                    ->update(array(
                        'expressNumber' => $expressNumber,
                        'expressType' => $expressType,
                        'takeUserPhoneNumber' => $takeUserPhoneNumber,
                        'status' => $status,
                        'groupName' => $groupName,
                        'customerStoreNumber' => $customerStoreNumber,
                        'overdueTime' => $overdueTime,
                        'storeTime' => $storeTime,
                        'syncFlag' => $syncFlag,
                        'takeTime' => $takeTime,
                        'storeUserPhoneNumber' => $storeUserPhoneNumber,
                        'validateCode' => $validateCode,
                        'version' => $version,
                        'box_id' => $box_id,
                        'logisticsCompany_id' => $logisticsCompany_id,
                        'mouth_id' => $mouth_id,
                        'operator_id' => $operator_id,
                        'storeUser_id' => $storeUser_id,
                        'takeUser_id' => $takeUser_id,
                        'recipientName' => $recipientName,
                        'weight' => $weight,
                        'chargeType' => $chargeType,
                        'electronicCommerce_id' => $electronicCommerce_id,
                        'staffTakenUser_id' => $staffTakenUser_id,
                        'lastModifiedTime' => time() * 1000

            ));
        } else {
            //insert data
            DB::table('tb_newlocker_express')
                    ->insert([
                            'expressNumber' => $expressNumber,
                            'expressType' => $expressType,
                            'takeUserPhoneNumber' => $takeUserPhoneNumber,
                            'status' => $status,
                            'groupName' => $groupName,
                            'customerStoreNumber' => $customerStoreNumber,
                            'overdueTime' => $overdueTime,
                            'storeTime' => $storeTime,
                            'syncFlag' => $syncFlag,
                            'takeTime' => $takeTime,
                            'storeUserPhoneNumber' => $storeUserPhoneNumber,
                            'validateCode' => $validateCode,
                            'version' => $version,
                            'box_id' => $box_id,
                            'logisticsCompany_id' => $logisticsCompany_id,
                            'mouth_id' => $mouth_id,
                            'operator_id' => $operator_id,
                            'storeUser_id' => $storeUser_id,
                            'takeUser_id' => $takeUser_id,
                            'recipientName' => $recipientName,
                            'weight' => $weight,
                            'chargeType' => $chargeType,
                            'electronicCommerce_id' => $electronicCommerce_id,
                            'staffTakenUser_id' => $staffTakenUser_id,
                            'id' => $id,
                            'lastModifiedTime' => time() * 1000

             ]);
        }

        //update tabel mouth
        if($status=='IN_STORE'){
            DB::table('tb_newlocker_mouth')
                ->where('id_mouth', $mouth_id)
                    ->update(array(
                            'status' => 'USED',
                            'express_id' => $id,
                            'syncFlag' => 1,
                            'lastChangingTime' => time() * 1000
                        ));
        } else {
            DB::table('tb_newlocker_mouth')
                ->where('id_mouth', $mouth_id)
                    ->update(array(
                            'status' => 'ENABLE',
                            'express_id' => null,
                            'syncFlag' => 1,
                            'lastChangingTime' => time() * 1000
                        ));            
        }

        //ambil data store user
        $sqlu = "SELECT * FROM tb_newlocker_user WHERE id_user='".$storeUser_id."'";
        $ru = DB::select($sqlu);

        if (count($ru) != 0) {
            $storeUser = array('id'=> $storeUser_id, 'userCardList' => ['id'=>''], 'name'=> $ru[0]->displayname, 'loginName' => $ru[0]->username, 'phoneNumber' => $ru[0]->phone, 'userNo' => '');
        } else {
            $storeUser = array('id'=> $storeUser_id, 'userCardList' => ['id'=>''], 'name'=> $storeUser_id, 'loginName' => $storeUser_id, 'phoneNumber' => $storeUser_id, 'userNo' => '');            
        }

        //ambil data mouth
        $sqlm = "SELECT * FROM tb_newlocker_mouth WHERE id_mouth='".$mouth_id."'";
        $rm = DB::select($sqlm);

        //ambil data mouth type
        $sqlmt = "SELECT * FROM tb_newlocker_mouthtype WHERE id_mouthtype='".$rm[0]->mouthType_id."'";
        $mt = DB::select($sqlmt);

        //ambil data box
        $sqlb  = "SELECT * FROM tb_newlocker_box WHERE id = '".$box_id."'";
        $rb = DB::select($sqlb);

        $box = array('id' => $box_id, 'orderNo' => $rb[0]->orderNo, 'name' => $rb[0]->name ); 

        $mouthType = array('defaultOverduePrice' => $mt[0]->defaultOverduePrice, 'id' => $mt[0]->id_mouthtype, 'defaultUserPrice' => $mt[0]->defaultUserPrice, 'deleteFlag' => $mt[0]->deleteFlag, 'name' => $mt[0]->name);

        $mouth = array('mouthType' => $mouthType, 'id' => $mouth_id, 'box' => $box , 'status' => $rm[0]->status , 'number' => $rm[0]->number);

        //ambil data take user
        $loginName = 'C_'.$takeUserPhoneNumber;
        $takeUser = array('id'=> $staffTakenUser_id, 'userCardList' => ['id'=>''], 'name'=> $takeUserPhoneNumber, 'loginName' => $loginName, 'phoneNumber' => $takeUserPhoneNumber, 'userNo' => '');

        //ambil data logistic company
        $sqlog = "SELECT * FROM tb_newlocker_company WHERE id_company = '".$logisticsCompany_id."'";
        $rl = DB::select($sqlog);

        $logistic = array('id' => $logisticsCompany_id, 'contactPhoneNumber' => [], 'contactEmail' => [], 'name' => $rl[0]->company_name, 'companyType' => $rl[0]->company_type, 'deleteFlag' => $rl[0]->deleteFlag, 'level' => $rl[0]->level);

        //get data operator
        $sqlop = "SELECT * FROM tb_newlocker_company WHERE id_company = '".$operator_id."'";
        $rop = DB::select($sqlop);

        $operator = array('id' => $operator_id, 'contactPhoneNumber' => [], 'contactEmail' => [], 'name' => $rop[0]->company_name, 'companyType' => $rop[0]->company_type, 'deleteFlag' => $rop[0]->deleteFlag, 'level' => $rop[0]->level);

        if($expressType == 'COURIER_STORE') {
            $res =  ['validateCode' => $validateCode , 'storeUser' => $storeUser, 'mouth' => $mouth, 'takeUser' => $takeUser, 'storeTime' => $storeTime, 'takeTime' => $takeTime, 'expressType' => $expressType, 'status' => $status , 'items' => [] , 'version' => $version, 'expressNumber' => $expressNumber, 'logisticsCompany' => $logistic, 'id' => $id ,'box' => $box, 'additionalPayment' => [], 'includedNumbers' => 0, 'takeUserPhoneNumber' => $takeUserPhoneNumber, 'overdueTime' =>  $overdueTime, 'operator' => $operator]; 
            
            $url_ = 'https://internalapi.clientname.id/synclocker/courierstore';
            $param_ = json_encode($res);
            $resp = $this->post_data($url_, $param_);        
        }
        
        if($expressType == 'CUSTOMER_REJECT') {
            //ambil data ecommerce company
            $sqecom = "SELECT * FROM tb_newlocker_company WHERE id_company = '".$electronicCommerce_id."'";
            $recom = DB::select($sqecom);

            $electronicCommerce = array('id' => $electronicCommerce_id, 'contactPhoneNumber' => [], 'contactEmail' => [], 'name' => $recom[0]->company_name, 'companyType' => $recom[0]->company_type, 'deleteFlag' => $recom[0]->deleteFlag, 'level' => $recom[0]->level);

            $res =  ['storeUser' => $storeUser, 'mouth' => $mouth, 'takeUser' => $takeUser, 'storeTime' => $storeTime, 'takeTime' => $takeTime, 'status' => $status , 'version' => $version, 'customerStoreNumber' => $customerStoreNumber, 'logisticsCompany' => $logistic, 'id' => $id ,'box' => $box, 'electronicCommerce' => $electronicCommerce, 'operator' => $operator]; 

            $url_ = 'https://internalapi.clientname.id/synclocker/customereject';
            $param_ = json_encode($res);
            $resp = $this->post_data($url_, $param_);
        } 

        if($expressType == 'CUSTOMER_STORE') {
            $res =  ['storeUser' => $storeUser, 'mouth' => $mouth, 'takeUser' => $takeUser, 'storeTime' => $storeTime, 'takeTime' => $takeTime, 'expressType' => $expressType, 'status' => $status , 'items' => [] , 'version' => $version, 'customerStoreNumber' => $customerStoreNumber, 'logisticsCompany' => $logistic, 'id' => $id ,'box' => $box, 'additionalPayment' => [], 'includedNumbers' => 0, 'takeUserPhoneNumber' => $takeUserPhoneNumber, 'operator' => $operator, 'endAddress' => $endAddress, 'recipientName' => $recipientName]; 
            
            $url_ = 'https://internalapi.clientname.id/synclocker/customerstore';
            $param_ = json_encode($res);
            $resp = $this->post_data($url_, $param_);
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                [   'api_url' =>  'http://pr0x.clientname.id'.'/express/syncExpress',
                    'api_send_data' => json_encode(['id' => $id, 'expressType' => $expressType, 'expressNumber' => $expressNumber, 'customerStoreNumber' => $customerStoreNumber]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")],
                [   'api_url' =>  $url_,
                    'api_send_data' => $param_,
                    'api_response' => json_encode($resp),
                    'response_date' => date("Y-m-d H:i:s")]
                ]);

        return response()->json($res);
    }

    //upload data popsend order ke locker 
    public function importcustomerstore (Request $req) {
        $id = $req->json('id');
        $logistic_id = $req->json('logistic_id');
        $takeUserPhoneNumber = $req->json('takeUserPhoneNumber');
        $customerStoreNumber = $req->json('customerStoreNumber');
        $chargeType = $req->json('chargeType');
        $recipientName = $req->json('recipientName');
        $recipientUserPhoneNumber = $req->json('recipientUserPhoneNumber');
        $endAddress = $req->json('endAddress');
        $designationSize = $req->json('designationSize');
        $groupName = $req->json('groupName');
        
        //Fill up GroupName
        $prefix = substr($customerStoreNumber, 0, 3);
        
        if ($prefix == "PLA" || $prefix == "PLL" || $prefix == "PAL" ) {
            $groupName = "POPSEND";
        } 

        if (empty($groupName)) {
            $sqlpr = "SELECT * FROM tb_newlocker_groupname WHERE prefix='".$prefix."'";
            $rpr = DB::select($sqlpr);

            //kasih handling saat get data groupname dan ditandain, mungkin belum diinput di DB
            if (count($rpr) != 0) {
                $groupName = $rpr[0]->groupName;            
            }else{
                $groupName = 'UNDEFINED';
            }
        }


        if(empty($logistic_id)){
            switch ($groupName) {
                case 'POPSEND':
                     $logistic_id == "161e5ed1140f11e5bdbd0242ac110001";
                    break;
                case 'TAYAKA':
                      $logistic_id == "402880825895e53e01589a50f9763b72";
                     break;     
                case 'OMAISU':
                      $logistic_id == "402880825895e53e01589a5030e63b5c";
                     break; 
                case 'TAPTOPICK':
                      $logistic_id == "40288083566d7b7f01568bcf5e1b703f";
                     break;  
                case 'VCS':
                      $logistic_id == "40288087598c3f230159ed4b9dbc67ce";
                     break;   
                case 'LABALABA':
                      $logistic_id == "402990835a20e7f0015a2127b4060630";
                     break; 
                case 'LOTG':
                      $logistic_id == "4028808258a8efaa0158adb7de3102d9";
                     break;         
                default:
                    $logistic_id == "161e5ed1140f11e5bdbd0242ac110001";
                    break;
             } 
        }

        //insert ke tabel express untuk popsend
        DB::table('tb_newlocker_express')
                ->insert([
                    'id' =>  $id,
                    'expressType' => 'CUSTOMER_STORE',
                    'customerStoreNumber' => $customerStoreNumber,
                    'takeUserPhoneNumber' => $takeUserPhoneNumber,
                    'status' => 'IMPORTED',
                    'groupName' => $groupName,
                    'importTime' => time() * 1000,
                    'lastModifiedTime' => time() * 1000,
                    'syncFlag' => 0,
                    'deleteFlag' => 0,
                    'version' => 0,
                    'logisticsCompany_id' => $logistic_id,
                    'endAddress' => $endAddress,
                    'recipientName' => $recipientName,
                    'recipientUserPhoneNumber' => $recipientUserPhoneNumber,
                    'designationSize' => $designationSize,
                    'chargeType' => $chargeType
        ]);

        $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id, 'status' => 'IMPORTED']];

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/express/staffImportCustomerStoreExpress',
                    'api_send_data' => json_encode(['id' => $id, 'customerStoreNumber' => $customerStoreNumber, 'expressType' => 'CUSTOMER_STORE']),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);
                
        return response()->json($res); 
    }

    //insert ke tabel express untuk lastmile
    public function importcourierstore  (Request $req) {
        $id = $req->json('id');
        $expressNumber = $req->json('expressNumber');
        $takeUserPhoneNumber = $req->json('takeUserPhoneNumber');
        $groupName = $req->json('groupName');

        //set groupname dari prefix
        if (empty($groupName) || $groupName == "") {
            $prefix = substr($expressNumber,0,3);
            $sqlpr = "select id, groupName from tb_newlocker_groupname where prefix='".$prefix."'";
            $rpr = DB::select($sqlpr);

            //kasih handling saat get data groupname dan ditandain, mungkin belum diinput di DB
            if (count($rpr) == 0) {
                $groupName = 'UNDEFINED';
            }else{
                $groupName = $rpr[0]->groupName;            
            }
        }

        //insert ke tabel express after check
        $sql = "SELECT * FROM tb_newlocker_express WHERE status = 'IMPORTED' and expressNumber = '".$expressNumber."'";
        $r = DB::select($sql);
        $generated_id = hash('haval128,5', (time()*1000));

        if (count($r) != 0){

            if ($groupName=="COD"){
                DB::table('tb_newlocker_express')
                ->insert([
                    'id' =>  $generated_id,
                    'expressType' => 'COURIER_STORE',
                    'status' => 'IMPORTED',
                    'expressNumber' => $expressNumber,
                    'takeUserPhoneNumber' => $takeUserPhoneNumber,
                    'groupName' => $groupName,
                    'importTime' => time() * 1000,
                    'lastModifiedTime' => time() * 1000,
                    'syncFlag' => 1,
                    'deleteFlag' => 0
                ]);
            $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $generated_id, 'status' => 'IMPORTED']];  
            } else{
                $res = ['response'=> ['code' => 401, 'message' => 'DUPLICATE ENTRY'],  'data' => []];
            }

        }else{

            DB::table('tb_newlocker_express')
                ->insert([
                    'id' =>  $generated_id,
                    'expressType' => 'COURIER_STORE',
                    'status' => 'IMPORTED',
                    'expressNumber' => $expressNumber,
                    'takeUserPhoneNumber' => $takeUserPhoneNumber,
                    'groupName' => $groupName,
                    'importTime' => time() * 1000,
                    'lastModifiedTime' => time() * 1000,
                    'syncFlag' => 1,
                    'deleteFlag' => 0
                ]);
            $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $generated_id, 'status' => 'IMPORTED']];            

        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/express/import',
                    'api_send_data' => json_encode(['id' => $generated_id, 'expressNumber' => $expressNumber, 'expressType' => 'COURIER_STORE']),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res); 
    }  

        /*====================NON-LOCKER API======================*/
    //For below these non-locker API(s), need to put usertoken on Header as mandatory
    public function modifyPhoneNumber (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $id = $req->json('id');
        $takeUserPhoneNumber = $req->json('takeUserPhoneNumber');
        $expressNumber = null;

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{            
            if (!empty($id) && !empty($takeUserPhoneNumber)) {
                $sql = "SELECT * FROM tb_newlocker_express WHERE deleteFlag = '0' AND id='".$id."'";
                $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $expressNumber = $r[0]->expressNumber;
                    DB::table('tb_newlocker_express')
                        ->where('id', $id)
                            ->update(array(
                                'takeUserPhoneNumber' => $takeUserPhoneNumber,
                                'lastModifiedTime' => time() * 1000
                                ));

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id, 'expressNumber' => $expressNumber, 'status' => 'PHONE NUMBER CHANGED']];

                } else {

                    $res = ['statusCode' => 401, 'errorMessage' => 'Express Id Not Found!'];
                
                }

            } else {

                $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter!'];
                
            }

        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/express/modifyPhoneNumber',
                'api_send_data' => json_encode(['id' => $id, 'takeUserPhoneNumber' => $takeUserPhoneNumber, 'expressNumber' => $expressNumber]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }

    public function deleteImportedExpress (Request $req, $imported) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $deleted = 0;

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{
            $express_id = $imported;
            $sqle = "SELECT * FROM tb_newlocker_express WHERE deleteFlag = '0' AND status = 'IMPORTED' AND id = '".$express_id."'";
            $re = DB::select($sqle);

            if (isset($imported) && count($re) != 0) {                
                $expressNumber = $re[0]->expressNumber;
                DB::table('tb_newlocker_express')
                    ->where('id', $express_id)
                        ->update(array(
                            'deleteFlag' => 1,
                            'lastModifiedTime' => time() * 1000
                ));
                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $express_id, 'expressNumber' => $expressNumber, 'status' => 'EXPRESS NUMBER DELETED!']];
                $deleted = 1;
            } else {
                $res = ['statusCode' => 401, 'errorMessage' => 'Express Id Not Found!'];
            }

            if ($deleted==1){
                //insert ke tabel generallog
                DB::table('tb_newlocker_generallog')
                        ->insert([
                            'api_url' =>  'http://pr0x.clientname.id'.'/express/deleteImportedExpress/'.$imported,
                            'api_send_data' => json_encode(['expressId' => $imported]),
                            'api_response' => json_encode($res),
                            'response_date' => date("Y-m-d H:i:s")
                ]);
            }
        }

        return response()->json($res);
    }
    
    public function resycnExpressByTime(Request $req){
        $userToken = $req->header('userToken');
        $endTime = $req->json('endTime');
        $startTime = $req->json('startTime');
        //$urlsms = 'http://smsdev.clientname.id/sms/send/nexmo';
        $urlmirror = 'https://internalapi.clientname.id/synclocker/courierstore';

        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{            
            if (!empty($startTime) && !empty($endTime) && $endTime > $startTime) {
                $sql = "SELECT a.id, a.expressNumber, a.takeUserPhoneNumber, a.validateCode, a.storeTime, a.status, a.overdueTime, a.takeTime, b.number, c.name as door_name, d.displayname, e.name as locker_name FROM tb_newlocker_express a, tb_newlocker_mouth b, tb_newlocker_mouthtype c, tb_newlocker_user d, tb_newlocker_box e WHERE a.expressType='COURIER_STORE' and a.groupName is null and a.storeTime > ".$startTime." and a.storeTime < ".$endTime." and a.storeUser_id=d.id_user and a.mouth_id=b.id_mouth and b.mouthType_id=c.id_mouthtype and b.box_id=e.id order by a.storeTime ASC";
                $expresses = DB::select($sql);

                if (count($expresses) != 0 ) {
                    /*
                        $id = $req->json('id');
                        $barcode = $req->json('expressNumber');
                        $phone =  $req->json('takeUserPhoneNumber');
                        $name = $req->json('storeUser.name');
                        $locker_name = $req->json('mouth.box.name');
                        $locker_number = $req->json('mouth.number');
                        $locker_size = $req->json('mouth.mouthType.name');
                        $storetime = date('Y-m-d H:i:s', $req->json('storeTime') / 1000);
                        $overduetime = date('Y-m-d H:i:s', $req->json('overdueTime') / 1000);
                        $overduetimesms = date('j/n/y', $req->json('overdueTime')/ 1000);
                        $status = $req->json('status');
                        $validatecode =  $req->json('validateCode');
                        $descstatus = "new record";
                    */
                    //$statusses = array();
                    $param_express = array();
                    foreach ($expresses as $express => $value) {
                        $param = array();
                        $param['id'] = $value->id; 
                        $param['expressNumber'] = $value->expressNumber; 
                        $param['takeUserPhoneNumber'] = $value->takeUserPhoneNumber; 
                        $param['storeUser']['name'] = $value->displayname; 
                        $param['mouth']['box']['name'] = $value->locker_name; 
                        $param['mouth']['number'] = $value->number; 
                        $param['mouth']['mouthType']['name'] = $value->door_name; 
                        $param['storeTime'] = $value->storeTime; 
                        $param['overdueTime'] = $value->overdueTime; 
                        $param['validateCode'] = $value->validateCode;
                        $param['status'] = $value->status;
                        if (strpos($param['status'], 'TAKEN') != false){
                            $param['takeTime'] = $value->takeTime;                            
                        }

                        array_push($param_express, $param);
                        
                        //re-push to mirrow server
                        $sendParam = $this->post_data($urlmirror, json_encode($param));
                        //$sendParam['post'] = $param;

                        //array_push($statusses, $sendParam);
                        continue;
                        /*                        
                        $overduetimesms = date('j-n-y', $value->overdueTime / 1000);

                        $message = "Kode PIN: " . $value->validateCode . "\nOrder No: " . $value->expressNumber . " sudah tiba di PopBox@" . $value->locker_name . ". Harap diambil sebelum " . $overduetimesms . " - www.clientname.id";            
                        
                        $sms = json_encode(['to' => $value->takeUserPhoneNumber,
                                'message' => $message,
                                'token' => '2349oJhHJ20394j2LKJO034823423'
                        ]);

                        //re-send sms
                        $sendSMS = $this->post_data($urlsms, $sms);
                        */
                    }

                    //to mitigate last record not pushed
                    $this->post_data($urlmirror, json_encode(end($param_express)));

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['total' => count($expresses).' DATA(s) RESYNCED', 'detail' => $param_express]];

                }else{
                    $res = ['statusCode' => 401, 'errorMessage' => 'No Data Found!'];
                }

            } else {
                $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter or Not Valid Time Range!'];
            } 
        }

        return response()->json($res);
    }

    public function searchExpress (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $like = $req->json('like');
        $type = $req->json('lastmile');

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{            
            if (!empty($like) && isset($type)){

                if ($type){
                    $sql = "SELECT a.id, a.expressNumber, a.takeUserPhoneNumber, a.validateCode, a.storeTime, a.takeTime, a.expressType, a.status, a.groupName, a.overdueTime, b.number, c.name as size, d.displayname as courier, e.name as locker FROM tb_newlocker_express a, tb_newlocker_mouth b, tb_newlocker_mouthtype c, tb_newlocker_user d, tb_newlocker_box e WHERE a.expressNumber LIKE '%".$like."%' and a.status <> 'IMPORTED' and a.storeUser_id=d.id_user and a.mouth_id=b.id_mouth and b.mouthType_id=c.id_mouthtype and b.box_id=e.id order by a.storeTime ASC";
                } else {
                    $sql = "SELECT a.id, a.customerStoreNumber, a.takeUserPhoneNumber, a.storeUserPhoneNumber, a.expressType, a.storeTime, a.takeTime, a.status, a.groupName, a.endAddress, a.staffTakenUser_id as takeUser, b.number, c.name as size, d.displayname as courier, e.name as locker FROM tb_newlocker_express a, tb_newlocker_mouth b, tb_newlocker_mouthtype c, tb_newlocker_user d, tb_newlocker_box e WHERE a.customerStoreNumber LIKE '%".$like."%' and a.status <> 'IMPORTED' and a.mouth_id=b.id_mouth and b.mouthType_id=c.id_mouthtype and b.box_id=e.id and a.staffTakenUser_id=d.id_user order by a.storeTime ASC";
                }

                $expresses = DB::select($sql);

                if (count($expresses) != 0 ) {
                    $express_ = array();

                    foreach ($expresses as $express => $value) {
                        $param = array();
                        $param['id'] = $value->id; 
                        if($value->expressType=='COURIER_STORE'){
                            $param['expressNumber'] = $value->expressNumber; 
                            $param['overdueTime'] = date('Y-m-d H:i:s', $value->overdueTime / 1000);
                            $param['validateCode'] = $value->validateCode;                           
                            $param['dropCourier'] = $value->courier; 
                        } else {
                            if (!empty($value->endAddress)) {
                                $param['address'] = $value->endAddress;
                            }
                            $param['customerNumber'] = $value->customerStoreNumber; 
                            $param['storeUserNumber'] = $value->storeUserPhoneNumber; 
                            $param['takeCourier'] = $value->courier;
                        } 
                        $param['type'] = $value->expressType;
                        $param['groupName'] = $value->groupName;
                        $param['status'] = $value->status;
                        $param['phoneNumber'] = $value->takeUserPhoneNumber;
                        $param['locker'] = $value->locker; 
                        $param['number'] = $value->number; 
                        $param['size'] = $value->size; 
                        $param['storeTime'] = date('Y-m-d H:i:s', $value->storeTime / 1000);
                        $param['takeTime'] = (!empty($value->takeTime)) ?  date('Y-m-d H:i:s', $value->takeTime / 1000) : null;

                        array_push($express_, $param);
                    }

                    $res = ['total' => count($expresses), 'data' => $express_];

                } else {

                    $res = ['statusCode' => 401, 'errorMessage' => 'Express Id Not Found!'];

                }

            } else {

                $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter!'];
            }

        }

        return response()->json($res);
    }

    public function query (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $expressId=  $_GET['expressId'];
        //echo 'id => : '.$id;

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid'];        
        }else{            
            if (!empty($expressId)){
                /* COURIER_STORE
                $id = $locker_activity["id"];
                $name = $locker_activity["storeUser"]["name"]; 
                $barcode = $locker_activity["expressNumber"];
                $phone = $locker_activity["takeUserPhoneNumber"];
                $locker_name = $locker_activity ["mouth"] ["box"] ["name"];
                $locker_number = $locker_activity ["mouth"] ["number"];
                $locker_size = $locker_activity ["mouth"] ["mouthType"] ["name"];
                $storetime =  date('Y-m-d H:i:s', $locker_activity["storeTime"]/1000);
                $overduetime = date('Y-m-d H:i:s', $locker_activity["overdueTime"]/1000);
                $status = $locker_activity["status"];
                $validatecode =  $locker_activity["validateCode"];*/

                /*CUSTOMER_STORE
                $id = $locker_activity["id"];
                $tracking_no = $locker_activity["customerStoreNumber"];
                $phone_number = $locker_activity ["takeUserPhoneNumber"];
                $locker_name = $locker_activity ["mouth"] ["box"] ["name"];
                $locker_number = $locker_activity ["mouth"] ["number"];
                $locker_size = $locker_activity ["mouth"] ["mouthType"] ["name"];
                $storetime =  date('Y-m-d H:i:s', $locker_activity["storeTime"]/1000);
                $status = $locker_activity["status"];
                $taketime = date('Y-m-d H:i:s', $locker_activity["takeTime"]/1000);
                */

                /* CUSTOMER_REJECT
                $id = $locker_activity["id"];
                $tracking_no = $locker_activity["customerStoreNumber"];
                $merchant_name = $locker_activity["electronicCommerce"]["name"];
                $phone_number = $locker_activity ["storeUser"]["name"];
                $locker_name = $locker_activity ["mouth"] ["box"] ["name"];
                $locker_number = $locker_activity ["mouth"] ["number"];
                $locker_size = $locker_activity ["mouth"] ["mouthType"] ["name"];
                $storetime =  date('Y-m-d H:i:s', $locker_activity["storeTime"]/1000);
                $status = $locker_activity["status"];
                $taketime =  date('Y-m-d H:i:s', $locker_activity["takeTime"]/1000);
                */

                $checkTypeId = DB::select("SELECT expressType FROM tb_newlocker_express WHERE id = '".$expressId."'");
                $typeExpress = $checkTypeId[0]->expressType;

                if (count($checkTypeId) != 0){

                    if ($typeExpress=='CUSTOMER_REJECT') {
                        $sql = "SELECT a.id, a.customerStoreNumber, a.storeTime, a.status, a.takeTime, a.storeUserPhoneNumber, a.groupName, a.electronicCommerce_id, b.number, c.name as door_name, e.name as locker_name FROM tb_newlocker_express a, tb_newlocker_mouth b, tb_newlocker_mouthtype c, tb_newlocker_box e WHERE a.id = '".$expressId."' AND a.mouth_id=b.id_mouth AND b.mouthType_id=c.id_mouthtype AND b.box_id=e.id;";                        
                    } else if ($typeExpress=='CUSTOMER_STORE') {
                        $sql = "SELECT a.id, a.customerStoreNumber, a.storeTime, a.status, a.takeTime, a.takeUserPhoneNumber, a.groupName, b.number, c.name as door_name, e.name as locker_name FROM tb_newlocker_express a, tb_newlocker_mouth b, tb_newlocker_mouthtype c, tb_newlocker_box e WHERE a.id = '".$expressId."' AND a.mouth_id=b.id_mouth AND b.mouthType_id=c.id_mouthtype AND b.box_id=e.id;";                       
                    } else {
                        $sql = "SELECT a.id, a.expressNumber, a.takeUserPhoneNumber, a.validateCode, a.storeTime, a.status, a.overdueTime, a.takeTime, a.groupName, b.number, c.name as door_name, d.displayname, e.name as locker_name FROM tb_newlocker_express a, tb_newlocker_mouth b, tb_newlocker_mouthtype c, tb_newlocker_user d, tb_newlocker_box e WHERE a.id = '".$expressId."' AND a.storeUser_id=d.id_user AND a.mouth_id=b.id_mouth AND b.mouthType_id=c.id_mouthtype AND b.box_id=e.id;";                         
                    }

                    $expresses = DB::select($sql);

                    $result = array();
                    foreach ($expresses as $express) {
                        $param = array();
                        $param['id'] = $express->id; 
                        $param['expressType'] = $typeExpress; 
                        $param['storeTime'] = $express->storeTime; 
                        $param['status'] = $express->status;
                        if (strpos($param['status'], 'TAKEN') != false){
                            $param['takeTime'] = $express->takeTime;                            
                        }

                        $param['groupName'] = $express->groupName;                        
                        if ($typeExpress=='CUSTOMER_REJECT') {
                            $param['customerStoreNumber'] = $express->customerStoreNumber; 
                            $param['storeUser']['name'] = $express->storeUserPhoneNumber;
                            if (empty($express->groupName)) {
                                $checkEcommerce = DB::select("SELECT company_name FROM tb_newlocker_company WHERE id_company = '".$express->electronicCommerce_id."'");
                                $param['electronicCommerce']['name'] = $checkEcommerce[0]->company_name;
                            } else {
                                $param['electronicCommerce']['name'] = $express->groupName;
                            }
                        } else if ($typeExpress=='CUSTOMER_STORE') {
                            $param['customerStoreNumber'] = $express->customerStoreNumber;
                            $param['takeUserPhoneNumber'] = $express->takeUserPhoneNumber; 
                        } else {
                            $param['overdueTime'] = $express->overdueTime; 
                            $param['validateCode'] = $express->validateCode;
                            $param['expressNumber'] = $express->expressNumber; 
                            $param['takeUserPhoneNumber'] = $express->takeUserPhoneNumber; 
                            $param['storeUser']['name'] = $express->displayname; 
                        }
                        $param['mouth']['box']['name'] = $express->locker_name; 
                        $param['mouth']['number'] = $express->number; 
                        $param['mouth']['mouthType']['name'] = $express->door_name; 

                        array_push($result, $param);
                    }
                    $res = ['page' => 0, 'maxCount' => 10, 'totalPage' => 0, 'totalCount' => count($result), 'resultList'=> $result];

                } else {

                        $res = ['page' => 0, 'maxCount' => 10, 'totalPage' => 0, 'totalCount' => 0, 'resultList' => []];
                    }

            } else {

                $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter!'];
            }

        }

        return response()->json($res);
    }

    public function queryImported (Request $req) {
        $userToken = $req->header('UserToken');
        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $expressType=  $_GET['expressType'];
        $expressStatus=  $_GET['expressStatus']; // Force ONLY IMPORTED
        $maxCount=  $_GET['maxCount'];
        //echo 'id => : '.$id;

        if(empty($maxCount)){
            $maxCount = 100;
        }

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid'];        
        }else{            
            if (!empty($expressType) && $expressStatus == 'IMPORTED'){ 
                $sql = "SELECT * FROM tb_newlocker_express WHERE expressType = '".$expressType."' AND status = 'IMPORTED' ORDER BY importTime DESC LIMIT 0,".$maxCount;

                $expresses = DB::select($sql);

                    $result = array();
                    foreach ($expresses as $express) {
                        $param = array();
                        $param['id'] = $express->id; 
                        $param['expressType'] = $express->expressType;
                        $param['createTime'] = $express->importTime; 
                        $param['status'] = $express->status;
                        $param['groupName'] = $express->groupName;                        
                        $param['takeUserPhoneNumber'] = $express->takeUserPhoneNumber; 
                        $param['item'] = []; 
                        if ($expressType=='COURIER_STORE') {
                            $param['expressNumber'] = $express->expressNumber; 
                        } else {
                            $param['customerStoreNumber'] = $express->customerStoreNumber;
                            $param['storeUserPhoneNumber'] = $express->storeUserPhoneNumber;
                        } 
                        array_push($result, $param);
                    }

                    $res = ['page' => 0, 'maxCount' => (int)$maxCount, 'totalPage' => 0, 'totalCount' => count($result), 'resultList'=> $result];

            } else {
                $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter!'];
            }

        }

        return response()->json($res);
    }



}