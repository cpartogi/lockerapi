<?php
/**
 * Created by PhpStorm.
 * User: arief
 * Date: 18/08/2017
 * Time: 08.52
 */

namespace App\Http\Helpers;

use Illuminate\Support\Facades\DB;

class PaymentAPI
{
    private $token;
    private $sessionId;
    private $clientId;
    private $uniqueId;

    public function __construct()
    {
        $sessionId = $this->sessionId;
        $this->uniqueId = uniqid();
        if (empty($sessionId)){
            // request session
            $createSession =  $this->createSession();
            if (empty($createSession)){
                //throw new \Exception($this->errorResponse('Create Session'));
                return;
            }
            if ($createSession->response->code!=200){
                $errorMessage = $createSession->response->message;
                //throw new \Exception($this->errorResponse($errorMessage));
                return;
            }
            $data = $createSession->data[0];
            $sessionId = $data->session_id;
            $this->sessionId = $sessionId;
        }
    }

    /**
     * @param $request
     * @param array $param
     * @return mixed
     */
    private function cUrl($request, $param = array()){
        $host = env('PAYMENT_API_URL');
        $token = env('PAYMENT_API_TOKEN');
        $sessionId = $this->sessionId;
        $uniqueId = $this->uniqueId;

        $url = $host.'/'.$request;
        $param['token'] = $token;
        $param['session_id'] = $sessionId;
        $json = json_encode($param);

        $date = date('Y.m.d');
        $time = date('H:i:s');
        $msg = "$uniqueId > $time Request : $url : $json\n";
        $f = fopen(storage_path().'/logs/api/payment.'.$date.'.log','a');
        fwrite($f,$msg);
        fclose($f);

        $ch = curl_init();
        // 2. set the options, including the url
        curl_setopt($ch, CURLOPT_URL,           $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt($ch, CURLOPT_POST,           count($param));
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $json );
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type:application/json'));
        $output = curl_exec($ch);
        curl_close($ch);

        $time = date('H:i:s');
        $msg = "$uniqueId > $time Response : $output\n";
        $f = fopen(storage_path().'/logs/api/payment.'.$date.'.log','a');
        fwrite($f,$msg);
        fclose($f);

        DB::table('companies_response')
            ->insert([
                'api_url' => $url,
                'api_send_data' => $json,
                'api_response'  => $output,
                'response_date'     => date("Y-m-d H:i:s")
            ]);

        return $output;
    }

    /**
     * Create Session
     * @return mixed
     */
    private function createSession(){
        $url = 'createSession';
        $clientId = env('PAYMENT_API_CLIENT_ID');

        // create parameter
        $parameter = [];
        $parameter['client_id'] =$clientId;
        $result = $this->cUrl($url,$parameter);
        $result = json_decode($result);
        return $result;
    }

    /**
     * Get Available Method
     * @param array $parameter
     * @return mixed
     */
    public function getAvailableMethod($parameter=[]){
        $url = 'payment/getPaymentMethod';
        $result = $this->cUrl($url,$parameter);
        $result = json_decode($result);
        return $result;
    }

    /**
     * Create Payment
     * @param array $parameter
     * @return mixed
     */
    public function createPayment($parameter=[]){
        $url = 'payment/createPayment';
        $result = $this->cUrl($url,$parameter);
        $result = json_decode($result);
        return $result;
    }

    /**
     * Create Error Response
     * @param $module
     * @return \Illuminate\Http\JsonResponse
     */
    private function errorResponse($module){
        $response = new \stdClass();
        $response->code = 400;
        $response->message = "Failed on $module";

        $result = new \stdClass();
        $result->response = $response;
        $result->data = [];
        return response()->json($result);
    }
}