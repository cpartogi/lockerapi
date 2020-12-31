<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class LogAccessV2
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    private $startTime;
    private $endTime;
    private $id;
    public function handle($request, Closure $next)
    {
        // begin log request
        $this->startTime = microtime(true);
        $url = $request->url();
        $input = $request->input();
        if (isset($input['password'])) unset($input['password']);
        $input = json_encode($input);
        $msg = "$url > $input";
        $this->LogData($msg);

        //process response
        $response = $next($request);
        //end process response

        //begin log response
        $this->endTime = microtime(true);
        $duration = number_format($this->endTime - $this->startTime,3);
        $data = $response->content();
        $msg = "$duration > $data";
        $this->LogData($msg);

        /*    $logDb =  new \App\Models\LogAccess();
            $logDb->session_id = $this->id;
            $logDb->url = $url;
            $logDb->request = $input;
            $logDb->response = $data;
            $logDb->created_date = date('Y-m-d');
            $logDb->request_time = $startTime;
            $logDb->response_time = $endTime;
            $logDb->save();*/

        return $response;
    }

    private function LogData($msg){
        if (Session::has('id')) $id = Session::get('id');
        else {
            if (empty($this->id)) $id = uniqid();
            else $id = $this->id;
            Session::set('id',$id);
        }
        $this->id = $id;
        $msg = " $id $msg\n";
        $f = fopen(storage_path().'/logs/access/ver2.'.date('Y.m.d.').'log','a');
        fwrite($f,date('H:i:s')." $msg");
        fclose($f);
    }
}
