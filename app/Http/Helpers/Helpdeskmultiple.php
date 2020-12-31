<?php

namespace App\Http\Helpers;
use Illuminate\Support\Facades\DB;
use App\Users;

class Helpdeskmultiple {

public static function getValidate($recipients) {
    $isEmptyRecipientLockerName = true;
    $isRecipientAddress = true;
    foreach ($recipients as $key => $value) {
      if ($value["recipient_address"]!=""){
        $isRecipientAddress = false;              
      }
      if ($value["recipient_locker_name"]!=""){
        $isEmptyRecipientLockerName = false;
      }
    }   
    return array("isRecipientAddress" => $isRecipientAddress, "isEmptyRecipientLockerName" => $isEmptyRecipientLockerName);
  } 

  public static function getPickerLockerToHome($notif, &$recipients, $pickup_locker_name){
    if ($pickup_locker_name == "" ){
                $notif->setDataError();
                return response()->json($notif->build());
        }


    $sql_pickup_lock = "Select latitude, longitude from locker_locations 
              where name like '%".$pickup_locker_name."%'";

        $pickup_lock =  DB::select($sql_pickup_lock);
        $lat1 = $pickup_lock[0]->latitude;
        $lon1 = $pickup_lock[0]->longitude;
        $sum_amount = 0;
        foreach ($recipients as $key => $value) {
            $lat2 = $value["recipient_address_lat"];
            $lon2 = $value["recipient_address_long"];

                
      $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
          
        $pickdistance = $miles * 1.609344;

      $unit = "K";
        
      if ($pickdistance < 4000) {
            $total_amount = 10000;            
          } else {
            $distdiff = $pickdistance - 4000;
            $gross_amount = 10000+($distdiff*1000);
            $total_amount = ceil($gross_amount/100)*100;            
          } 
          $sum_amount = $sum_amount + $total_amount;
          $recipients[$key]["amount"] = $total_amount;
        }         
        return $sum_amount;       
  }

   // gcm hanya sekali
   // sms berkali2
  public static function getPickerLockerToHomeSubmit($controll, $req, $notif, $pickup_locker_name){   
    $phone = $req->json('phone','');
    $pickup_address = $req->json('pickup_address','');
    $pickup_address_detail = $req->json('pickup_address_detail','');
    $pickup_address_name = $req->json('pickup_address_name','');
    $pickup_address_phone = $req->json('pickup_address_phone','');
    $pickup_address_lat = $req->json('pickup_address_lat','');
    $pickup_address_long = $req->json('pickup_address_long','');
    $pickup_locker_name = $req->json('pickup_locker_name','');
    $pickup_locker_size = $req->json('pickup_locker_size','');
    $pickup_date = $req->json('pickup_date','');
    $pickup_time = $req->json('pickup_time','');
    $item_description = $req->json('item_description','');
    $recipients = $req->json('recipients');   

    $controll->headers[]="Content-Type: application/json";
       
    $sql_pickup_lock = "Select latitude, longitude from locker_locations 
                  where name like '%".$pickup_locker_name."%'";

    $pickup_lock =  DB::select($sql_pickup_lock);
    $lat1 = $pickup_lock[0]->latitude;
    $lon1 = $pickup_lock[0]->longitude;        
    $invoice_id_parent = "PLA".date('y').date('m').date('d').date('H').date('i').rand(0,999);
    $sum_amount = 0; 
    $arrayInvoice = array();
    $id_parent = DB::table('tb_member_pickup_parent')
        ->insertGetId(array(                    
            'id_invoice' => $invoice_id_parent,
            'created_date'=>date("Y-m-d H:i:s")
        ));  
        
        foreach ($recipients as $key => $value) {
            $invoice_id = "PLA".date('y').date('m').date('d').date('H').date('i').rand(0,999);
            $lat2 = $value['recipient_address_lat'];
            $lon2 = $value['recipient_address_long'];            
            // insert partner response to database
            

            $theta = $lon1 - $lon2;
            $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
            $dist = acos($dist);
            $dist = rad2deg($dist);
            $miles = $dist * 60 * 1.1515;
        
            $pickdistance = $miles * 1.609344;

            $unit = "K";
        
            if ($pickdistance < 4000) {
                $total_amount = 10000;
            } else {
                $distdiff = $pickdistance - 4000;
                $gross_amount = 10000+($distdiff*1000);
                $total_amount = ceil($gross_amount/100)*100;
            } 

            // upload parcel photos
            $output_file_1 = env('IMG_PATH')."member/".$invoice_id."_1.jpg";
            $output_file_name_1 = $invoice_id."_1.jpg";
            $ifp = fopen($output_file_1, "wb"); 
            fwrite($ifp, base64_decode($value['item_photo_1'])); 
            fclose($ifp);  

            if ($value['item_photo_2'] != "") {
                $output_file_2 = env('IMG_PATH')."member/".$invoice_id."_2.jpg";
                $output_file_name_2 = $invoice_id."_2.jpg";
                $ifp = fopen($output_file_2, "wb"); 
                fwrite($ifp, base64_decode($value['item_photo_2'])); 
                fclose($ifp);   
            } else {
                $output_file_name_2 = "";
            }

            if ($value['item_photo_3'] != "") {
                $output_file_3 = env('IMG_PATH')."member/".$invoice_id."_3.jpg";
                $output_file_name_3 = $invoice_id."_3.jpg";
                $ifp = fopen($output_file_3, "wb"); 
                fwrite($ifp, base64_decode($value['item_photo_3'])); 
                fclose($ifp);   
            } else {
                $output_file_name_3 = "";
            }

            $output_file_name = $output_file_name_1 .",".$output_file_name_2 .",".$output_file_name_3;

            $ins = array(
                'phone' => $phone,
                'id_parent' => $id_parent,
                'invoice_id' =>$invoice_id,
                'pickup_address' => $pickup_address,
                'pickup_address_detail' => $pickup_address_detail,
                'pickup_address_name' => $pickup_address_name,
                'pickup_address_phone' => $pickup_address_phone,
                'pickup_address_lat' => $pickup_address_lat,
                'pickup_address_long' => $pickup_address_long,
                'pickup_locker_name' => $pickup_locker_name,
                'pickup_locker_size' => $pickup_locker_size,
                'pickup_date' => $pickup_date,
                'pickup_time' => $pickup_time,
                'item_description' => $item_description,
                'recipient_name' => $value['recipient_name'],
                'recipient_phone' => $value['recipient_phone'],
                'recipient_address' => $value['recipient_address'],
                'recipient_address_detail' => $value['recipient_address_detail'],
                'recipient_address_lat' => $value['recipient_address_lat'],
                'recipient_address_long' => $value['recipient_address_long'],
                'recipient_locker_name'=>$value['recipient_locker_name'],
                'recipient_locker_size'=>$value['recipient_locker_size'],
                'recipient_email'=>$value['recipient_email'],
                'amount'=>$total_amount,
                'item_photo' => $output_file_name,
                'pickup_order_date' => date("Y-m-d H:i:s")
            );             
            $res = DB::table('tb_member_pickup')->insertGetId($ins);

         // deduct balance

        $sqlbal = "Select current_balance from tb_member_balance where phone='".$phone."' order by last_update desc limit 0,1";
            $bl = DB::select($sqlbal);
            $blc = count($bl);

            if ($blc != 0) {
              $prev_balance = $bl[0]->current_balance;
              $current_balance = $prev_balance-$total_amount;
              DB::table('tb_member_balance')
                      ->insert(['phone' => $phone,
                              'deduction_amount' => $total_amount,
                              'prev_balance'  => $prev_balance, 
                              'current_balance'     => $current_balance,
                              'invoice_id' => $invoice_id,
                              'last_update' => date('Y-m-d H:i:s')
                    ]);

            } else {
                DB::table('tb_member_balance')
                      ->insert(['phone' => $phone,
                              'deduction_amount' => $total_amount,
                              'prev_balance'  => 0, 
                              'current_balance'  => -$total_amount,
                              'invoice_id' => $invoice_id,
                              'last_update' => date('Y-m-d H:i:s')
                    ]);
            }

          //get member gcm
        $sqlgcm = "select member_gcm_token from tb_member where phone='".$phone."'";
        $rsgcm = DB::select($sqlgcm);
        $gcm = $rsgcm[0]->member_gcm_token; 
        
           
          $sum_amount = $sum_amount + $total_amount;
            $arrayInvoice[$key]["invoice_id"] = $invoice_id;
            $arrayInvoice[$key]["amount"] = $total_amount;

        }                      

        return array("sum_amount" => $sum_amount, "invoice_id_parent" => $invoice_id_parent, "invoices" => $arrayInvoice, "gcm" =>$gcm);
  }


   public static function sendSms($controll, $phone, $invoice_id, $pickup_locker_name){
        $message = "Pilih menu Mengirim Barang masukkan kode ".$invoice_id." di loker ".$pickup_locker_name.". Rincian info: https://popsend.clientname.id/ord/".$invoice_id;
                    //   send sms using jatis
         $message = "Pilih menu Mengirim Barang masukkan kode ".$invoice_id." di loker ".$pickup_locker_name.". Rincian info: https://popsend.clientname.id/ord/".$invoice_id;


                    //   send sms using jatis
         $sms = json_encode(['to' => $phone,
                           'message' => $message,
                           'token' => '0weWRasJL234wdf1URfwWxxXse304'
         ]);
         $urlsms = "http://smsdev.clientname.id/sms/send";
         $resp=$controll->post_data($urlsms, $sms);
         // insert partner response to database
         DB::table('companies_response')
                 ->insert([
                    'api_url' => $urlsms,
                    'api_send_data' => $sms,
                    'api_response'  => json_encode($resp),                      
                    'response_date'     => date("Y-m-d H:i:s")
         ]);         
   }


   public static function sendGcm($controll, $gcm, $invoice_id, $total_amount){
         $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
         $controll->headers[]="Authorization:key=".env('GCM_KEY');

         $varupload = [
              'data' => ['service'=>'pickup request', 'detail'=> ['invoice_id'=> $invoice_id, 'balance_deduct' => $total_amount], 'title'=>'Anda telah melakukan pemesanan pick up request'],
              'to' => $gcm
         ];
         $varupload = json_encode($varupload);  

               // post notification to google               
         $upres=$controll->post_data($urlupload, $varupload, $controll->headers);
         DB::table('companies_response')
                  ->insert([
                    'api_url' => $urlupload,
                    'api_send_data' => $varupload,
                    'api_response'  => json_encode($upres), 
                    'response_date'     => date("Y-m-d H:i:s")
         ]);            
   }


   public static function submitTOLocker($controll, $value, $invoice_id){	   
      $username = 'OPERATOR4API';
      $password = "p0pb0x4p10p3r"; // production pwd : popbox4514 development pwd : popbox123

                    //$urlogin = env('API_URL')."/ebox/api/v1/user/login";
      $urlogin = "http://eboxapi.clientname.id:8080/ebox/api/v1/user/login";
      $varlogin = [
                    'loginName' => $username,
                    'password' => $password
                        ];     
		
      //$logres=$controll->post_data($urlogin,json_encode ( $varlogin ), null);
		
      $api_token=$logres['token'];

                    //$urlupload = env('API_URL')."/ebox/api/v1/express/staffImportCustomerStoreExpress";
		
      $urlupload =  "http://eboxapi.clientname.id:8080/ebox/api/v1/express/staffImportCustomerStoreExpress";
	  
      $controll->headers[]="userToken:".$api_token;            

      $data                               = array();
      //  $data['orderNo']                    = $invoice_id;
      $data['logisticsCompany']           = ['id' => '161e5ed1140f11e5bdbd0242ac110001'];
      $data['takeUserPhoneNumber']        = $value['recipient_phone'];
      $data['customerStoreNumber']        = $invoice_id;
      //  $data['storeUserPhoneNumber']       = $phone;
      $data['chargeType']                 = 'NOT_CHARGE';
      //  $data['designationSize']            = $pickup_locker_size;
      //  $data['expressNumber']              = $invoice_id;
      $data['recipientName']              = $value['recipient_name'];
      $data['recipientUserPhoneNumber']   = $value['recipient_phone'];
      //  $data['takeUserEmail']              = $recipient_email;
      $data['endAddress']                 = substr($value['recipient_address'],0,255);

	  print_r($data);
	  die();
      $varupload = json_encode($data);        
            
      // post api to pakpobox                
      //$upres=$controll->post_data($urlupload, $varupload, $controll->headers);
      DB::table('companies_response')
        ->insert([
             'api_url' => $urlupload,
             'api_send_data' => $varupload,
             'api_response'  => json_encode($upres), 
             'response_date'     => date("Y-m-d H:i:s")
      ]);      
   }

   public static function getUserPickupHistory($parents, $detail){
    $data = array();        
    foreach ($parents as $key=>$value) {
      // print_r($value->id_invoice);
      // die();
      $data[$key]['parent_invoice_id'] = $value->id_invoice;
      $data[$key]['status'] = $value->status;
      $data[$key]['status_history'] = $value->status_history;
      $data[$key]['total_details'] = 0;
      $data[$key]['id_parent'] = $value->id_parent;
      $data[$key]["details"] = array();
    }    

    foreach ($data as $key => $value) {      
      $i=0;
      foreach ($detail as $key1 => $value1) {          
          if ($value["id_parent"] == $value1->id_parent){
            $data[$key]["details"][$i] = $value1;
            $data[$key]['total_details'] = ($i+1);
            $i++;
          }        
      }     
    }    
    return $data;
   }

   public static function topup($ctrl, $phone, $top_up_amount, $prev_balance, $current_balance, $transid){       
        DB::table('tb_member_balance')
            ->insert(['phone' => $phone,
                'top_up_amount' => $top_up_amount,
                'prev_balance'  => $prev_balance, 
                'current_balance'  => $current_balance,
                'id_transaction' => $transid,
                'last_update' => date('Y-m-d H:i:s')
        ]);

                    //get member gcm
        $sqlgcm = "select member_gcm_token from tb_member where phone='".$phone."'";
        $rsgcm = DB::select($sqlgcm);
        $gcm = $rsgcm[0]->member_gcm_token; 
          

           //get member gcm
        $urlupload = "https://gcm-http.googleapis.com/gcm/send";
                    
        $ctrl->headers[]="Authorization:key=".env('GCM_KEY');

        $varupload = [
            'data' => ['service'=> 'balance top up', 'detail' => ['balance_amount'=> $current_balance], 'title'=> 'Saldo anda telah bertambah'],
            'to' => $gcm
        ];
        
        $varupload = json_encode($varupload); 

          // post notification to google              
        $upres=$ctrl->post_data($urlupload, $varupload, $ctrl->headers);

              // insert partner response to database
        DB::table('companies_response')
            ->insert([
                'api_url' => $urlupload,
                'api_send_data' => $varupload,
                'api_response'  => json_encode($upres), 
                'response_date'     => date("Y-m-d H:i:s")
            ]); 

        $resp=['response' => ['code' => 200,'message' =>"top up balance success"], 'data' => [['current_balance'=> $current_balance]]]; 
        return $resp;
   }

   public static function setTopup($ctrl, $phone, $top_up_amount, $transid = ""){
        $sqlbal = "Select current_balance from tb_member_balance where phone='".$phone."' order by last_update desc limit 0,1";
        $bl = DB::select($sqlbal);
        $blc = count($bl); 
        if ($blc != 0) {
            $prev_balance = $bl[0]->current_balance;
            $current_balance = $prev_balance+$top_up_amount;
            $resp = self::topup($ctrl, $phone, $top_up_amount, $prev_balance, $current_balance, $transid);
        } else {
            $resp = self::topup($ctrl, $phone, $top_up_amount, 0, $top_up_amount, $transid);      
        }      
        return $resp;         
   }

   public static function  _get_client_ip(){
          $ipaddress = '';
          if (getenv('HTTP_CLIENT_IP'))
              $ipaddress = getenv('HTTP_CLIENT_IP');
          else if(getenv('HTTP_X_FORWARDED_FOR'))
              $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
          else if(getenv('HTTP_X_FORWARDED'))
              $ipaddress = getenv('HTTP_X_FORWARDED');
          else if(getenv('HTTP_FORWARDED_FOR'))
              $ipaddress = getenv('HTTP_FORWARDED_FOR');
          else if(getenv('HTTP_FORWARDED'))
              $ipaddress = getenv('HTTP_FORWARDED');
          else if(getenv('REMOTE_ADDR'))
              $ipaddress = getenv('REMOTE_ADDR');
          else
              $ipaddress = 'UNKNOWN';

          return $ipaddress;
    }

    public function post_data($url, $post_data = array(), $headers = array(), $options = array()) {
        $result = null;
        $curl = curl_init ();

        if ((is_array ( $options )) && count ( $options ) > 0) {
            $this->options = $options;
        }
        if ((is_array ( $headers )) && count ( $headers ) > 0) {
            $this->headers = $headers;
        }
        if ($this->is_post !== null) {
            $this->is_post = 1;
        }

        curl_setopt ( $curl, CURLOPT_URL, $url );
        curl_setopt ( $curl, CURLOPT_POST, $this->is_post );
        curl_setopt ( $curl, CURLOPT_POSTFIELDS, $post_data );
        curl_setopt ( $curl, CURLOPT_COOKIEJAR, "" );
        curl_setopt ( $curl, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt ( $curl, CURLOPT_ENCODING, "" );
        curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt ( $curl, CURLOPT_AUTOREFERER, true );
        curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, false ); // required for https urls
        curl_setopt ( $curl, CURLOPT_CONNECTTIMEOUT, 5 );
        curl_setopt ( $curl, CURLOPT_TIMEOUT, 5 );
        curl_setopt ( $curl, CURLOPT_MAXREDIRS, 10 );
        curl_setopt ( $curl, CURLOPT_HTTPHEADER, $this->headers );

        $content = curl_exec ( $curl );
        $response = curl_getinfo ( $curl );
        $result = json_decode ( $content, TRUE );        

        curl_close ( $curl );
        return $result;
    }


    public static function _getTypeDevice(){
      $ipRemoteFrom = self::_get_client_ip();          
      $type = "mobile";
      if (in_array(trim($ipRemoteFrom), config('config.ip_access'))){           
          $type="web";
      } else  if (trim($ipRemoteFrom)==='127.0.0.1'){           
          $type="anonymous";
      }      
      return $type;
    }

    public static function updateActivityToken($req){
        $uid_token = $req->json('uid_token','');
        $device_id = self::_getTypeDevice();
        Users::updateMemberTokenLastActivity($uid_token, $device_id);        
    }
}  