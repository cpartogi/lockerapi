<?php

namespace App\Console\Commands;

use App\Http\Requests;
use App\Http\Helpers\WebCurl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Validator, Input, Redirect ; 


class SMSReminder extends Command
{

    var $curl;
    var $emergency;
    var $url_get;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smsreminder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule task to resend SMS for all un-collected parcel within its overdue time';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(){
        parent::__construct();
        $headers = ['Content-Type: application/json'];
        $this->curl = new WebCurl($headers);
        $this->emergency = env('EMERGENCY_SMS');
        echo '[EMERGENCY_SMS : ' . $this->emergency.']';
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */


    // function from old app
    protected $headers = array (
        'Content-Type: application/json'
    );
    protected $is_post = 0;

    private $response_= null;
    private $originURL_= null;


    //condition handle in case emergency => wahyudi [20-02-2018]

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
        $response = '';
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
            $this->originURL_ = $url;
            $this->url_get = $url;           
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  
            $get = curl_exec($ch);
            $this->response_ = json_encode($get);
            $status = json_decode($get, true);            
            $response = '"status":"'.$status['messages'][0]['status'].'"';
            DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' => $url,
                    'api_send_data' => json_encode($get),
                    'api_response' => $this->response_,
                    'response_date' => date("Y-m-d H:i:s")
                ]);
        }
        return $response;      
    }

    public function sendFromIsentric ($to, $message){
        $response = '';
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
            $this->url_get = $runfile;
            $this->originURL_ = $runfile;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $runfile);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
            //add loop sending when no answer receive from isentric server, Wahyudi [01-12-17]
//            $i = 0;
//            do {
//                $content = curl_exec ($ch);
//                sleep(1);
//                $i ++;
//            } while (strpos($content, "returnCode = ") === false && $i <= 3);
            $content = curl_exec ($ch);
            $ret = strpos($content, "returnCode = ");
            $start = $ret + 13;
            $retcode = substr($content, $start, 1);
            curl_close ($ch); 
            $this->response_ = $content;
            $response = '"status":"'.$retcode.'"';
            DB::table('tb_newlocker_generallog')
                ->insert([
                    'api_url' => 'http://'.$server_ip.'/ExtMTPush/extmtpush?shortcode=39398',
                    'api_send_data' => $runfile,
                    'api_response' => $content,
                    'response_date' => date("Y-m-d H:i:s")
                ]);
        }       
        return $response;        
    }
    
    public function handle(){
        echo "\nBEGIN CHECK AT ".date('Y-m-d H:i:s')."\n";
        $epochtime = time() * 1000;
        $timestamp = date("H:i", $epochtime/1000);
        $storedExpress = DB::SELECT("SELECT a.id, a.overdueTime, a.storeTime, a.expressNumber, a.status, a.takeUserPhoneNumber, a.operator_id, a.groupName, a.validateCode, b.name as box_name, c.url, c.token FROM tb_newlocker_express a, tb_newlocker_box b, tb_newlocker_smsaccount c WHERE a.expressNumber not LIKE '%TEST%' AND a.expressNumber not like '%DEMO%' AND a.expressType = 'COURIER_STORE' AND a.status = 'IN_STORE' AND a.overdueTime > ".$epochtime." AND a.box_id=b.id AND b.sms_account_id=c.id");
        if (count($storedExpress) != 0) {
            for($i = 0; $i < count($storedExpress); ++$i) {
                $value = $storedExpress[$i];
                $id = $value->id;
                $overduetime = $value->overdueTime;
                $storeTime = $value->storeTime;
                $expressNumber = $value->expressNumber;
                $validateCode = $value->validateCode;
                $box_name = $value->box_name;
                $groupName = $value->groupName;
                $operator_id = $value->operator_id;
                $takeUserPhoneNumber = $value->takeUserPhoneNumber;
                $overduetimesms = date('j/n/y', $overduetime/1000);
                $checkTime = date('H:i', $storeTime/1000);              
                $urlsms = $value->url;
                $tokenSMS = $value->token;
                $param = array('box_name' => $box_name, 'validateCode' => $validateCode, 'overduetimesms' => $overduetimesms, 'expressNumber' => $expressNumber, 'operator_id' => $operator_id);
                $message = $this->sms_content($groupName, $param);
                /*$message = "Kode PIN: " . $validateCode . "\nOrder No: " . $expressNumber . " sudah tiba di PopBox@" . $box_name . ". Harap diambil sebelum " . $overduetimesms . " - www.clientname.id";*/
                if ($groupName == 'COD' ) {
                    $message = "Order Anda " . $expressNumber . " sudah sampai di Popbox@" . $box_name . ", bayar sebelum " . $overduetimesms . " di loker/kasir untuk dapat PIN ambil barang - www.clientname.id";        
                }
                if ($operator_id != '145b2728140f11e5bdbd0242ac110001'){
                    $message = "PIN Code: " . $validateCode . "\nYour order " . $expressNumber . " has arrived at PopBox@" . $box_name . ". Please collect before " . $overduetimesms . " - www.clientname.id";
                }
                if ($timestamp==$checkTime) {
                    $sms = json_encode([
                            'to' => $takeUserPhoneNumber,
                            'message' => '[Reminder] '.$message,
                            'token' => $tokenSMS
                        ]);
                    if($operator_id == '145b2728140f11e5bdbd0242ac110001' || substr($takeUserPhoneNumber, 0, 2) == '08'){
                        $resp = $this->sendFromNexmo($takeUserPhoneNumber, $message);
                    }else{
                        $resp = $this->sendFromIsentric($takeUserPhoneNumber, $message);
                    }

                    if (!empty($resp)){
                        $statusMsg = (strpos($resp, "0") != false) ? 'SUCCESS' : 'FAILED' ;
                    } else {
                        $statusMsg = 'ERROR';
                    }

                    DB::table('tb_newlocker_smslog')
                        ->insert([
                            'express_id' => $id,
                            'sms_content' => '[Reminder] '.$message,
                            'sms_status' => $statusMsg,
                            'sent_on' => date("Y-m-d H:i:s"),
                            'original_response' => $this->response_
                        ]);
                    echo $id." || ".$this->originURL_. " || ".$sms." || ".json_encode($resp). " => ".$statusMsg. " => ". $this->response_ ."\n";
                } else {
                    echo "$id || [$timestamp VS $checkTime] || NO SENDING SMS THIS TIME \n";
                }
            }
        } 
        echo "=> ".count($storedExpress). " RECORDS FOUND & CHECKED============\n";
    }
}
