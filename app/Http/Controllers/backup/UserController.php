<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use Illuminate\Support\Facades\Validator;


class UserController extends Controller{
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

    public function clientlogin(Request $req) {

        /*$password = "001122";
        $salt = "_POPBOX_NEWLOCKER_SALT";
        $hpwd = hash("sha256", $pwd.$salt);
        $hashpwd = hash("sha256", $password.$salt);*/
        
        $pwd = $req->json('password');
        $loginName = $req->json('loginName');

        $boxToken = $req->header('BoxToken');
        $diskSerialNumber = $req->header('DiskSerialNumber');
        $orderNo = $req->header('OrderNo');
        $userToken = $req->header('UserToken');
        $lastlogin = time() * 1000;

        //get uuid by time stamp   
        $uuid = hash("haval128,5", $lastlogin);
        $lockerName = "";
        $logisticsName = "";

        //get data locker
        $sqlb = "SELECT * FROM tb_newlocker_box WHERE deleteFlag = '0' AND orderNo='".$orderNo."'";
        $rb = DB::select($sqlb); 

        //cek data logistic dari user
        $sqluser = "SELECT * FROM tb_newlocker_user WHERE deleteFlag = '0' AND username = '".$loginName."'";
        $resuser = DB::select($sqluser);
        $id_user = "UNDEFINED";

        if (count($rb) != 0) {
            $lockerName = $rb[0]->name;
            if (count($resuser) != 0) { 
                $id_user = $resuser[0]->id_user;               
                // query ke tabel company
                $sqlc = "SELECT * FROM tb_newlocker_company WHERE id_company='".$resuser[0]->id_company."'";
                $rc = DB::select($sqlc);

                if (count($rc) != 0) {
                    $logisticsName = $rc[0]->company_name;
                } else {
                    $logisticsName = "UNDEFINED_COMPANY";                    
                }

                if (strtolower($pwd) == strtolower($resuser[0]->password)) {
                    $res = ['name' => $resuser[0]->displayname , 'lastLoginTime' => $lastlogin, 'items' => [], 'wallets' => [], 'createTime' => $lastlogin , 'company' => ['name' => $rc[0]->company_name, 'contactEmail' => [], 'level' => intval($rc[0]->level), 'deleteFlag' => $resuser[0]->deleteFlag, 'contactPhoneNumber' => [], 'companyType' => $rc[0]->company_type, 'id' => $rc[0]->id_company ],'token' => $uuid, 'phoneNumber' => $resuser[0]->phone, 'userCardList' => ['id' => ''], 'itemList' => ['id' => ''], 'role' => $resuser[0]->role, 'id' => $resuser[0]->id_user, 'loginName' => $loginName] ;
                    $status = 1;
                } else {
                    $res = ['statusCode' => 401, 'errorMessage' => 'User Not Found Or Password Error!'];
                    $status = 0;
                }
            } else {
                $res = ['statusCode' => 401, 'errorMessage' => 'User Not Found Or Password Error!'];
                $status = 0;
            }
        } else {
            $res = ['statusCode' => 404, 'errorMessage' => 'Unknown Locker System!'];
            $status = 0;
        }

        // insert informasi login ke database   
        if (count($rb) != 0) {
            DB::table('tb_newlocker_loginlog')
                        ->insert([
                            'id_login' => $uuid,
                            'username' => $loginName,
                            'lastlogin'  => date("Y-m-d H:i:s"),
                            'status'     => $status,
                            'locker_name' =>  $lockerName,
                            'logistics_name' => $logisticsName
                            ]);
        }

        if ($status == 1) {
        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/user/clientLogin',
                    'api_send_data' => json_encode(['name' => $loginName, 'password' => $pwd]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                    ]);
        }

        return response()->json($res);
    }

    public function createStaff(Request $req){
        $userToken = $req->header('UserToken');
        $phoneNumber = $req->json('phoneNumber');
        $loginName = $req->json('loginName');
        $name = $req->json('name');
        $role = $req->json('role');
        $logistic_id = $req->json('company.id');
        $salt = "_POPBOX_NEWLOCKER_SALT";
        $password = $req->json('password');
        $id = $req->json('id'); //to handle id user which generated before from ebox
        $idcard_no = $req->json('idcard_no');

        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;
        $timestamp = time() * 1000;
        $id_user = hash("haval128,5", $timestamp);

        if ($granted == 0 || $granted == ''){
            // if (isset($userToken) && strlen($userToken) == 32){
            //     DB::table('tb_newlocker_token')
            //                 ->insert([
            //                     'token' => $userToken,
            //                     'remark' => 'GENERATED FROM EBOX',
            //                     'deleteFlag' => 0 
            //                     ]);
            //     if (!empty($loginName) && !empty($name) && !empty($password) && !empty($logistic_id) && !empty($role) && !empty($phoneNumber)) {
            //     $sqlcusr = "SELECT * FROM tb_newlocker_user WHERE username = '".$loginName."'";
            //     $recusr = DB::select($sqlcusr);
                
            //     if (count($recusr) != 0) {
            //         $res = ['statusCode' => 503, 'errorMessage' => 'User already Exist!'];
            //     } else {
            //         //use ebox user id after checking and inserting the dynamic usertoken
            //         DB::table('tb_newlocker_user')
            //             ->insert([
            //                 'id_user' => $id,
            //                 'username' => strtoupper($loginName),
            //                 'displayname' => $name,
            //                 'phone' => $phoneNumber,
            //                 'role' => strtoupper($role),
            //                 'id_company' => $logistic_id,
            //                 'deleteFlag' => 0,
            //                 'idcard_no' => $idcard_no,
            //                 'password' => hash("sha256", strtoupper($password).$salt)
            //                 ]);

            //         $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_user, 'username' => strtoupper($loginName), 'displayname' => strtoupper($name), 'status' => 'USER CREATED']];
            //     }
            // } else {
            //     $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter!'];
            // }
     
            // } else {
            //     $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];                
            // }

            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];                

        }else{

            if (!empty($loginName) && !empty($name) && !empty($password) && !empty($logistic_id) && !empty($role) && !empty($phoneNumber)) {
                $sqlcusr = "SELECT * FROM tb_newlocker_user WHERE username = '".$loginName."'";
                $recusr = DB::select($sqlcusr);
                
                if (count($recusr) != 0) {
                    $res = ['statusCode' => 503, 'errorMessage' => 'User already Exist!'];
                } else {
                    DB::table('tb_newlocker_user')
                        ->insert([
                            'id_user' => $id,
                            'username' => strtoupper($loginName),
                            'displayname' => $name,
                            'phone' => $phoneNumber,
                            'role' => strtoupper($role),
                            'id_company' => $logistic_id,
                            'deleteFlag' => 0,
                            'idcard_no' => $idcard_no,
                            'password' => hash("sha256", strtoupper($password).$salt)
                            ]);

                    $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_user, 'username' => strtoupper($loginName), 'displayname' => strtoupper($name), 'status' => 'USER CREATED']];
                }
            } else {
                $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter!'];
            }

            DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/user/createStaff',
                    'api_send_data' => json_encode(['username' => strtoupper($loginName), 'role' => strtoupper($role), 'displayname' => strtoupper($name)]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                    ]);
        }

        return response()->json($res);
    }   
    
    public function updateStaff(Request $req){
        $userToken = $req->header('UserToken');
        $name = $req->json('name');
        $salt = "_POPBOX_NEWLOCKER_SALT";
        $password = $req->json('password');
        $id_user = $req->json('id');
        $deleteFlag = $req->json('deleteFlag');
        $loginName = $req->json('loginName');
        $idcard_no = $req->json('idcard_no');      

        // {"id":"cad083486955341f87f132e388a7794e",
        // "loginName":"TESTYUDI_171202","name":"TESTYUDI_171202_xxx",
        // "password":"TESTYUDI_171202_xxx",
        // "idcard_no":"3123789686981231298679_xxx"}

        $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        $rtok = DB::select($sqltok);
        $granted = $rtok[0]->token;

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{
            $timestamp = time() * 1000;
            $newPassword = hash("sha256", strtoupper($password).$salt);
            if (!empty($id_user) && !empty($loginName) && !empty($password) ) {
                DB::table('tb_newlocker_user')
                    ->where('id_user', $id_user)
                        ->update(array(
                            'displayname' => strtoupper($name),
                            'deleteFlag' => (!empty($deleteFlag)) ? $deleteFlag : 0,
                            'idcard_no' => $idcard_no,
                            'phone' => $password,
                            'password' => $newPassword
                            ));

                $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_user, 'username' => strtoupper($loginName), 'displayname' => strtoupper($name), 'status' => 'USER DETAILS CHANGED']];

            } else {

                $res = ['statusCode' => 501, 'errorMessage' => 'Missing parameter!'];

            }

            DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/user/updateStaff',
                    'api_send_data' => json_encode(['username' => strtoupper($loginName), 'displayname' => strtoupper($name)]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                    ]);
        }

        return response()->json($res);
    }

}