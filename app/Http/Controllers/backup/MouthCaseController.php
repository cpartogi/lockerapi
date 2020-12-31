<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use App\Http\Helpers\WebCurl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;


class MouthCaseController extends Controller{
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

        curl_close($curl);
        return $result;
    }
    
    /*====================BOX-LOCKER API======================*/
   
    public function mouthsync(Request $req) {
        $id = $req->json('id');
        $status = $req->json('status');
        $express_id = (!empty($req->json('express_id'))) ? $req->json('express_id') : null;

        // {
        //    "status":"USED",
        //    "express_id":"abc123",
        //    "mouthType_id":"fa8211cb082f11e5a29a0242ac110001",
        //    "usePrice":0,
        //    "numberInCabinet":1,
        //    "id":"402880825c2ddbf5015c38ae04eb14be",
        //    "cabinet_id":"402880825c2ddbf5015c38ae04db14bd",
        //    "deleteFlag":0,
        //    "box_id":"402880825c2ddbf5015c38ae04da14bc",
        //    "overduePrice":0,
        //    "syncFlag":0,
        //    "number":1
        // }

        // update tabel 
        DB::table('tb_newlocker_mouth')
            ->where('id_mouth', $id)
                ->update(array(
                            'status' =>  $status,
                            'syncFlag' => 1,
                            'express_id' => $express_id,
                            'lastChangingTime' => time() * 1000
                    ));   

        // ambil data
        $sql = "SELECT tb_newlocker_box.* , tb_newlocker_mouthtype.name AS typename, tb_newlocker_mouthtype.id_mouthtype, tb_newlocker_mouth.number
                FROM tb_newlocker_box , tb_newlocker_mouthtype , tb_newlocker_mouth 
                WHERE tb_newlocker_box.id=tb_newlocker_mouth.box_id 
                AND tb_newlocker_mouth.mouthType_id=tb_newlocker_mouthtype.id_mouthtype AND tb_newlocker_mouth.id_mouth = '".$id."' ";
        //echo $sql;        
        $rs = DB::select($sql);    

        $res = ['status' => $status , 'box' =>['name'=> $rs[0]->name, 'id'=> $rs[0]->id , 'orderNo' => $rs[0]->orderNo ], 'number' => $rs[0]->number, 'id' => $id , 'mouthType' =>[ 'name' => $rs[0]->typename, 'defaultOverduePrice' => 0 , 'id'=> $rs[0]->id_mouthtype, 'defaultUsePrice' => 0, 'deleteFlag' => $rs[0]->deleteFlag ]];

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' =>  'http://pr0x.clientname.id'.'/box/mouth/sync',
                    'api_send_data' => json_encode(['Mouth Sync' => $id]),
                    'api_response' => json_encode($res),
                    'response_date' => date("Y-m-d H:i:s")
                ]);

        return response()->json($res);
    }   

    /*====================NON-LOCKER API======================*/
    //For below these non-locker API(s), need to put usertoken on Header as mandatory
    public function mouthstatus (Request $req) {
        $userToken = $req->header('UserToken');
        // $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        // $rtok = DB::select($sqltok);
        // $granted = $rtok[0]->token;

        //cache
        $granted = Cache::remember("locker_token-$userToken",1440,function() use($userToken){
            $data = DB::table('tb_newlocker_token')->select('token')->where('token','=',$userToken)->where('deleteFlag', '=', '0')->count();
            return $data;
        });

  
        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{
            $orderNo = $_GET['boxOrderNo'];
            if (isset($orderNo)) {

                //ambil data dari tabel box cache
                $lockerbox = Cache::remember("locker_box-$orderNo",720,function() use($orderNo){
                        $data = DB::table('tb_newlocker_box')->select('id','token','name','currencyUnit','freeDays','overdueType','validateType','freeHours')->where('orderNo','=',$orderNo)->where('deleteFlag', '=', '0')->first();
                return $data;
                });
        
                $xl_ = array('ENABLE' => 0);
                $l_ = array('ENABLE' => 0);
                $m_ = array('ENABLE' => 0);
                $s_ = array('ENABLE' => 0);
                $mini_ = array('ENABLE' => 0);

                if (!empty($lockerbox)) {
                    $box_id = $lockerbox->id;

                    if ( date('H') < 21) {
                        $jcache = 30;
                    } else {
                        $jcache = 60;
                    }

                    //ambil data dari tabel mouth cache
                    $mouths = Cache::remember("locker_mouth-$box_id", $jcache,function() use($box_id){
                        $data = DB::table('tb_newlocker_mouth')->join('tb_newlocker_mouthtype','tb_newlocker_mouth.mouthType_id','=','tb_newlocker_mouthtype.id_mouthtype')->select('tb_newlocker_mouth.id_mouth','tb_newlocker_mouthtype.name')->where('tb_newlocker_mouth.status','=','ENABLE')->where('tb_newlocker_mouth.deleteFlag', '=', '0')->where('tb_newlocker_mouth.box_id','=',$box_id)->get();
                    return $data;
                    });

                    //dd($mouths);

                    foreach($mouths as $mouth ) {                
                        $size = $mouth->name;

                        if ($size == 'XL') {
                            $xl_['ENABLE']++;    
                            continue;
                        }else if ($size == 'L'){
                            $l_['ENABLE']++;    
                            continue;
                        }else if ($size == 'M'){
                            $m_['ENABLE']++;    
                            continue;
                        }else if ($size == 'S'){
                            $s_['ENABLE']++;  
                            continue;
                        }else if ($size == 'MINI'){
                            $mini_['ENABLE']++;    
                            continue;
                        }                    
                    }
                }    

                $res = [ 'XL' => $xl_, 'L' => $l_ , 'M' => $m_, 'S' => $s_, 'MINI' => $mini_];
            } else {
                $res = ['statusCode' => 401, 'errorMessage' => 'Locker Id Not Found!'];
            }
        }
        return response()->json($res);
    }

    public function boxmouthupdate (Request $req) {
        $userToken = $req->header('UserToken');
        // $sqltok = "SELECT count(*) AS token FROM tb_newlocker_token WHERE deleteFlag = '0' AND token ='".$userToken."'";
        // $rtok = DB::select($sqltok);
        // $granted = $rtok[0]->token;
        //cache
        $granted = Cache::remember("locker_token-$userToken",1440,function() use($userToken){
            $data = DB::table('tb_newlocker_token')->select('token')->where('token','=',$userToken)->where('deleteFlag', '=', '0')->count();
            return $data;
        });

        $id = $req->json('id');
        $status = $req->json('status');
        $changeSize = $req->json('changeSize');
        $box_id = null;
        $id_task = null;
        $door_sizes = array('MINI' => 'fa8211cb082f11e5a29a0242ac110001' ,  'S' => 'fa820f16082f11e5a29a0242ac110001' ,  'M' => 'fa820ca9082f11e5a29a0242ac110001' ,  'L' => 'fa8212d6082f11e5a29a0242ac110001' ,  'XL' => 'fa821384082f11e5a29a0242ac110001');
        $passed = false;

        if (empty($status) && !empty($changeSize)){
            if (array_key_exists($changeSize, $door_sizes)){
                $passed = true;                
            }
        }
        if (!empty($status) && empty($changeSize)) {
            if ($status=='ENABLE' || $status=='LOCKED'){
                $passed = true;
            }
        } 

        if ($granted == 0 || $granted == ''){
            $res = ['statusCode' => 404, 'errorMessage' => 'UserToken Not Found or Invalid!'];        
        }else{            
            if (!empty($id) && $passed==true) {
                    $sql = "SELECT * FROM tb_newlocker_mouth WHERE deleteFlag = '0' AND status <> 'USED' AND id_mouth ='".$id."'";             
                    $r = DB::select($sql);

                if (count($r) != 0 ) {
                    $box_id = $r[0]->box_id;
                    $timestamp = time() * 1000;
                    $id_task = hash("haval128,5", $timestamp);
                        //send task to prox
                        DB::table('tb_newlocker_tasks')
                            ->insert([
                                'id' => $id_task,
                                'box_id' => $box_id,
                                'status' => 'COMMIT',
                                'task' => 'MOUTH_STATUS_CHANGE',
                                'messageType' => 'ASYNC_TASK',
                                'createTime' => $timestamp,
                                'mouth_id' => $id
                            ]);

                        //update db mouth
                        if (!empty($status)) {

                            DB::table('tb_newlocker_mouth')
                                ->where('id_mouth', $id)
                                    ->update(array(
                                        'status' => $status,
                                        'lastChangingTime' => time() * 1000
                                        ));

                        } else if (!empty($changeSize)) {

                            DB::table('tb_newlocker_mouth')
                                ->where('id_mouth', $id)
                                    ->update(array(
                                        'mouthType_id' => $door_sizes[$changeSize],
                                        'lastChangingTime' => time() * 1000
                                        ));

                        }

                        $res = ['response'=> ['code' => 200, 'message' => 'OK'],  'data' => ['id' => $id_task, 'box_id' => $box_id, 'status' => 'CHANGE DOOR STATUS TASK CREATED']];

                } else {
                        $res = ['statusCode' => 403, 'errorMessage' => 'Door Not Available for Change!'];
                }

            } else {

                $res = ['statusCode' => 501, 'errorMessage' => 'Missing or Invalid Parameter!'];
            }               
            
        }

        //insert ke tabel generallog
        DB::table('tb_newlocker_generallog')
            ->insert([
                'api_url' =>  'http://pr0x.clientname.id'.'/task/box/mouth/update',
                'api_send_data' => json_encode(['id' => $id_task, 'box_id' => $box_id]),
                'api_response' => json_encode($res),
                'response_date' => date("Y-m-d H:i:s")
            ]);

        return response()->json($res);
    }
}